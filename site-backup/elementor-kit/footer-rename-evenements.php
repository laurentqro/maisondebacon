<?php
/* Footer (108363) : renommer l'en-tête de colonne « Événements Privés »
 *  → « Vos Événements » (demande client 2026-05-28).
 *
 * Élément cible (heading) : id `b302f4d` dans le tier nav 303d9d2.
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */

$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 108363;
$ID    = '8f4441e';
$OLD   = 'Événements Privés';
$NEW   = 'Vos Événements';

global $wpdb;
$raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
    $POST, '_elementor_data'
));
$tree = json_decode($raw, true);
if (!is_array($tree)) { echo "ABORT: _elementor_data invalide\n"; return; }

@mkdir('/tmp/mdb-evfix', 0775, true);
$ts = date('Ymd-His');
file_put_contents("/tmp/mdb-evfix/footer-108363-rename-{$ts}.json", $raw);
echo "backup -> /tmp/mdb-evfix/footer-108363-rename-{$ts}.json\n";

$hits = 0;
$walk = function (&$els) use (&$walk, $ID, $OLD, $NEW, &$hits) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $ID) {
            $current = $el['settings']['title'] ?? '';
            echo "found {$ID}: title=\"{$current}\"\n";
            // Comparaison UTF-8 propre (mb_strtolower), accepte casse + apostrophes.
            $norm_old = function_exists('mb_strtolower') ? mb_strtolower($current, 'UTF-8') : strtolower($current);
            if (strpos($norm_old, 'événements privés') !== false || strpos($norm_old, 'evenements prives') !== false) {
                $el['settings']['title'] = $NEW;
                $hits++;
                echo "  rewrote -> \"{$NEW}\"\n";
            } else {
                echo "  pattern ne matche pas exactement (normalisé=\"{$norm_old}\") ; pas de réécriture\n";
            }
            return true;
        }
        if (!empty($el['elements']) && $walk($el['elements'])) return true;
    }
    unset($el);
    return false;
};
$walk($tree);
echo "réécritures : {$hits}\n";

if (!$APPLY || !$hits) {
    echo $hits ? "(dry run)\n" : "(rien à faire)\n";
    return;
}

$json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$ok = update_post_meta($POST, '_elementor_data', wp_slash($json));
echo "update_post_meta -> " . var_export($ok, true) . "\n";
echo "APPLIED\n";
