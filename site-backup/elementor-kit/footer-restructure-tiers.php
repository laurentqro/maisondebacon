<?php
/* Footer (108363) : restructuration en tiers (demande client — le logo
 * paraissait isolé dans une colonne 1.6fr surdimensionnée, et le bandeau
 * horaires laissait la moitié droite vide).
 *
 * Cible :
 *   TIER 1 (strip 97020d4) : [colonne marque 5a1bc48] | Horaires Restaurant
 *                            8f55450 | Horaires Le Roof Top 29e34d7
 *   TIER 2 (303d9d2)       : 3 colonnes — La Maison 01a87b3 | Événements
 *                            b302f4d | Contact c936f49
 *   TIER 3 (878265c)       : barre légale (inchangée)
 *
 * On DÉPLACE le conteneur colonne-marque 5a1bc48 hors de 303d9d2 et on
 * l'insère en TÊTE des enfants du strip 97020d4. La grille de 303d9d2 passe
 * donc de 4 à 3 colonnes (géré en CSS). Aucun widget n'est recréé.
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY  = getenv('MDB_APPLY') === '1';
$POST   = 108363;
$STRIP  = '97020d4';
$TOPROW = '303d9d2';
$BRAND  = '5a1bc48';

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/footer-108363-restructure-tiers-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;

/* 1) extraire (et retirer) le conteneur marque 5a1bc48 */
$brand_node = null;
$extract = function (&$els) use (&$extract, $BRAND, &$brand_node) {
    foreach ($els as $k => &$el) {
        if (($el['id'] ?? '') === $BRAND) {
            $brand_node = $el;
            unset($els[$k]);
            $els = array_values($els);
            return true;
        }
        if (!empty($el['elements']) && $extract($el['elements'])) return true;
    }
    unset($el);
    return false;
};
$extract($tree);
echo $brand_node ? "colonne marque {$BRAND} extraite\n" : "ERREUR: {$BRAND} introuvable\n";

/* 2) vérifier qu'elle n'est pas déjà dans le strip (idempotence) */
$already = false;
$check = function ($els) use (&$check, $STRIP, $BRAND, &$already) {
    foreach ($els as $el) {
        if (($el['id'] ?? '') === $STRIP) {
            foreach (($el['elements'] ?? []) as $c) {
                if (($c['id'] ?? '') === $BRAND) $already = true;
            }
        }
        if (!empty($el['elements'])) $check($el['elements']);
    }
};

/* 3) insérer la marque en tête du strip 97020d4 */
$inserted = 0;
$insert = function (&$els) use (&$insert, $STRIP, &$brand_node, &$inserted) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $STRIP && $brand_node) {
            array_unshift($el['elements'], $brand_node);
            $inserted++;
            return true;
        }
        if (!empty($el['elements']) && $insert($el['elements'])) return true;
    }
    unset($el);
    return false;
};
if ($brand_node) $insert($tree);
echo "colonne marque insérée en tête du strip {$STRIP} : {$inserted}\n";

$changed = $brand_node ? 1 : 0;
echo "changes: {$changed}\n";

if ($APPLY && $changed) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
