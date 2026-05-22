#!/usr/bin/env bash
# optimize-webp.sh — Génère des .webp pour les images d'un répertoire WordPress uploads.
#
# Usage:
#   ./optimize-webp.sh /chemin/vers/uploads [--dry-run] [--cleanup-existing] [--max-width N]
#
# Comportement:
#   - Parcourt récursivement le répertoire passé en argument
#   - Pour chaque .jpg/.jpeg/.png, génère <source>.webp (extension ajoutée) via cwebp -q 80
#   - Skip si <source>.webp existe déjà
#   - Skip les fichiers .gif (cwebp ne gère pas l'animation correctement)
#   - GARDE ANTI-RÉGRESSION : si le .webp produit est >= au source, on le supprime
#     (servir un .webp plus gros que le JPG est contre-productif)
#   - Avec --cleanup-existing : avant le run, parcourt tous les .webp préexistants
#     (notamment ceux créés par Elementor Image Optimizer qui n'a pas cette garde)
#     et supprime ceux qui sont plus gros que leur source
#   - Avec --max-width N : redimensionne les images > N pixels de large à N px (ratio préservé).
#     Aligne notre comportement sur celui d'Elementor (qui resize à 1920 par défaut).
#     Si --cleanup-existing est aussi passé, supprime aussi les .webp existants
#     produits sans resize (dimensions > N), pour qu'ils soient régénérés à la bonne taille.
#   - Log progression à stdout, erreurs à stderr
#
# Conçu pour OVH mutualisé : pas de parallélisation par défaut (CPU partagé).

set -euo pipefail

QUALITY=80
DRY_RUN=0
CLEANUP_EXISTING=0
MAX_WIDTH=0  # 0 = pas de resize

if [ "${1:-}" = "" ]; then
  echo "Usage: $0 <repertoire> [--dry-run] [--cleanup-existing] [--max-width N]" >&2
  exit 1
fi

TARGET_DIR="$1"
shift || true

# Parse flags (ordre libre)
while [ "${1:-}" != "" ]; do
  case "$1" in
    --dry-run) DRY_RUN=1 ;;
    --cleanup-existing) CLEANUP_EXISTING=1 ;;
    --max-width) shift; MAX_WIDTH="${1:-0}" ;;
    *) echo "Flag inconnu : $1" >&2; exit 1 ;;
  esac
  shift
done

if [ ! -d "$TARGET_DIR" ]; then
  echo "ERREUR: répertoire introuvable: $TARGET_DIR" >&2
  exit 1
fi

if ! command -v cwebp >/dev/null 2>&1; then
  echo "ERREUR: cwebp non disponible dans le PATH" >&2
  exit 1
fi

# Compteurs
TOTAL=0
PROCESSED=0
SKIPPED=0
FAILED=0
SAVED_BYTES=0

# Démarre le timer
START_TS=$(date +%s)

echo "=== optimize-webp.sh — démarrage ==="
echo "Répertoire        : $TARGET_DIR"
echo "Qualité           : $QUALITY"
echo "Dry-run           : $DRY_RUN"
echo "Cleanup existing  : $CLEANUP_EXISTING"
echo "Max width         : ${MAX_WIDTH:-0} ($([ "$MAX_WIDTH" -gt 0 ] && echo "resize à ${MAX_WIDTH}px max" || echo "pas de resize"))"
echo "Démarrage         : $(date)"
echo ""

# Phase 0 — Cleanup des .webp préexistants à régénérer.
# Deux raisons de supprimer un .webp existant :
#   a) Il est >= au fichier source (régression de poids — bug Elementor sans garde)
#   b) Si --max-width est spécifié : sa largeur dépasse la cible, donc il doit être
#      régénéré plus petit pour économiser du bandwidth
if [ "$CLEANUP_EXISTING" = "1" ]; then
  echo "Phase 0 : nettoyage des .webp préexistants à régénérer..."
  CLEANUP_REMOVED_LARGER=0
  CLEANUP_REMOVED_OVERSIZED=0
  while IFS= read -r webp; do
    src="${webp%.webp}"
    if [ ! -f "$src" ]; then
      continue
    fi
    src_size=$(stat -c %s "$src" 2>/dev/null || echo 0)
    webp_size=$(stat -c %s "$webp" 2>/dev/null || echo 0)

    # Cas a : .webp >= source
    if [ "$webp_size" -gt 0 ] && [ "$src_size" -gt 0 ] && [ "$webp_size" -ge "$src_size" ]; then
      if [ "$DRY_RUN" = "1" ]; then
        echo "[dry-run] REMOVE (larger than source) $webp"
      else
        rm -f "$webp"
      fi
      CLEANUP_REMOVED_LARGER=$((CLEANUP_REMOVED_LARGER + 1))
      continue
    fi

    # Cas b : largeur > MAX_WIDTH (seulement si --max-width spécifié)
    if [ "$MAX_WIDTH" -gt 0 ]; then
      # webpinfo donne la largeur en format "Width: NNNN"
      webp_w=$(webpinfo "$webp" 2>/dev/null | awk '/Canvas size/ {print $3; exit} /Width:/ {print $2; exit}' || echo 0)
      if [ -z "$webp_w" ]; then webp_w=0; fi
      if [ "$webp_w" -gt "$MAX_WIDTH" ]; then
        if [ "$DRY_RUN" = "1" ]; then
          echo "[dry-run] REMOVE (width=${webp_w} > ${MAX_WIDTH}) $webp"
        else
          rm -f "$webp"
        fi
        CLEANUP_REMOVED_OVERSIZED=$((CLEANUP_REMOVED_OVERSIZED + 1))
      fi
    fi
  done < <(find "$TARGET_DIR" -type f -name '*.webp')
  echo "  -> $CLEANUP_REMOVED_LARGER .webp supprimés (plus gros que leur source)"
  if [ "$MAX_WIDTH" -gt 0 ]; then
    echo "  -> $CLEANUP_REMOVED_OVERSIZED .webp supprimés (largeur > ${MAX_WIDTH}px)"
  fi
  echo ""
fi

# Pré-comptage pour afficher la progression
TOTAL=$(find "$TARGET_DIR" -type f \( -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.png' \) | wc -l)
echo "Fichiers candidats trouvés : $TOTAL"
echo ""

# Boucle principale
#
# Convention de nommage : on garde l'extension source et on ajoute .webp à la fin.
# Exemple : photo.jpg → photo.jpg.webp
# C'est la même convention que celle utilisée par le plugin Elementor Image Optimizer
# (et ShortPixel, WebP Express). Permet au .htaccess rewrite de servir le .webp
# avec une seule règle, et préserve le fichier source explicitement.
find "$TARGET_DIR" -type f \( -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.png' \) | while read -r f; do
  webp_target="${f}.webp"

  if [ -f "$webp_target" ]; then
    SKIPPED=$((SKIPPED + 1))
    continue
  fi

  if [ "$DRY_RUN" = "1" ]; then
    echo "[dry-run] $f"
    PROCESSED=$((PROCESSED + 1))
    continue
  fi

  # Construire les flags cwebp. -resize W 0 redimensionne à W px de large en gardant
  # le ratio. Si la source est plus petite que W, cwebp n'agrandit PAS (no-op).
  CWEBP_RESIZE=""
  if [ "$MAX_WIDTH" -gt 0 ]; then
    CWEBP_RESIZE="-resize $MAX_WIDTH 0"
  fi

  if cwebp -quiet -q "$QUALITY" $CWEBP_RESIZE "$f" -o "$webp_target" 2>/dev/null; then
    src_size=$(stat -c %s "$f" 2>/dev/null || echo 0)
    webp_size=$(stat -c %s "$webp_target" 2>/dev/null || echo 0)

    # Garde anti-régression : si le WebP est plus gros que le source, on le supprime.
    # On veut garantir que servir le .webp est TOUJOURS un gain en bandwidth.
    if [ "$webp_size" -gt 0 ] && [ "$src_size" -gt 0 ] && [ "$webp_size" -ge "$src_size" ]; then
      rm -f "$webp_target"
      continue
    fi

    if [ "$webp_size" -gt 0 ] && [ "$src_size" -gt 0 ]; then
      SAVED_BYTES=$((SAVED_BYTES + src_size - webp_size))
    fi
    PROCESSED=$((PROCESSED + 1))
    if [ $((PROCESSED % 50)) -eq 0 ]; then
      ELAPSED=$(($(date +%s) - START_TS))
      echo "[$PROCESSED] processed, ${ELAPSED}s elapsed"
    fi
  else
    echo "ERREUR cwebp: $f" >&2
    FAILED=$((FAILED + 1))
  fi
done

# Note : à cause du subshell de `while read`, les compteurs PROCESSED/SKIPPED/FAILED/SAVED_BYTES
# affichés ici seront 0. On recompte différemment pour le résumé final.

ELAPSED=$(($(date +%s) - START_TS))
WEBP_COUNT=$(find "$TARGET_DIR" -type f -name '*.webp' | wc -l)

echo ""
echo "=== Résumé ==="
echo "Durée totale  : ${ELAPSED}s ($(printf '%dh:%dm:%ds' $((ELAPSED/3600)) $((ELAPSED%3600/60)) $((ELAPSED%60))))"
echo "Fichiers .webp présents après run : $WEBP_COUNT"
echo "Terminé       : $(date)"
