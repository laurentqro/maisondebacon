#!/usr/bin/env bash
# optimize-webp.sh — Génère des .webp pour les images d'un répertoire WordPress uploads.
#
# Usage:
#   ./optimize-webp.sh /chemin/vers/uploads [--dry-run] [--cleanup-existing]
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
#   - Log progression à stdout, erreurs à stderr
#
# Conçu pour OVH mutualisé : pas de parallélisation par défaut (CPU partagé).

set -euo pipefail

QUALITY=80
DRY_RUN=0
CLEANUP_EXISTING=0

if [ "${1:-}" = "" ]; then
  echo "Usage: $0 <repertoire> [--dry-run] [--cleanup-existing]" >&2
  exit 1
fi

TARGET_DIR="$1"
shift || true

# Parse flags (ordre libre)
while [ "${1:-}" != "" ]; do
  case "$1" in
    --dry-run) DRY_RUN=1 ;;
    --cleanup-existing) CLEANUP_EXISTING=1 ;;
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
echo "Démarrage         : $(date)"
echo ""

# Phase 0 — Cleanup des .webp préexistants plus gros que leur source.
# Concerne notamment les fichiers créés par Elementor Image Optimizer qui ne fait
# pas cette garde et peut sauvegarder des .webp plus lourds que le JPG/PNG source.
if [ "$CLEANUP_EXISTING" = "1" ]; then
  echo "Phase 0 : nettoyage des .webp préexistants dégradants..."
  CLEANUP_REMOVED=0
  while IFS= read -r webp; do
    src="${webp%.webp}"
    if [ -f "$src" ]; then
      src_size=$(stat -c %s "$src" 2>/dev/null || echo 0)
      webp_size=$(stat -c %s "$webp" 2>/dev/null || echo 0)
      if [ "$webp_size" -gt 0 ] && [ "$src_size" -gt 0 ] && [ "$webp_size" -ge "$src_size" ]; then
        if [ "$DRY_RUN" = "1" ]; then
          echo "[dry-run] REMOVE $webp"
        else
          rm -f "$webp"
        fi
        CLEANUP_REMOVED=$((CLEANUP_REMOVED + 1))
      fi
    fi
  done < <(find "$TARGET_DIR" -type f -name '*.webp')
  echo "  -> $CLEANUP_REMOVED .webp supprimés (plus gros que leur source)"
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

  if cwebp -quiet -q "$QUALITY" "$f" -o "$webp_target" 2>/dev/null; then
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
