<?php
/* Page « Le Roof Top » (106766) : retirer le bouton « Carte Restaurant »
 *  (52affe4) de la rangée 34e7f20. Les 2 autres (Cocktails 4e3ba5d +
 *  Champagnes a5cd0ef) restent — la rangée est déjà en justify-content:center
 *  donc elles se re-centrent automatiquement.
 *  DRY ; MDB_APPLY=1. */

$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106766;
$ROW   = '34e7f20';
$DROP  = '52affe4';

global $wpdb;
$raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
    $POST, '_elementor_data'
));
$tree = json_decode($raw, true);
if (!is_array($tree)) { echo "ABORT bad json\n"; return; }

@mkdir('/tmp/mdb-evfix', 0775, true);
$ts = date('Ymd-His');
file_put_contents("/tmp/mdb-evfix/rt-remove-cr-pre-{$ts}.json", $raw);
echo "backup -> /tmp/mdb-evfix/rt-remove-cr-pre-{$ts}.json\n";

$removed = 0;
$walk = function (&$els) use (&$walk, $ROW, $DROP, &$removed) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $ROW) {
            $out = array();
            foreach (($el['elements'] ?? array()) as $c) {
                if (($c['id'] ?? '') === $DROP) {
                    echo "  - remove {$DROP} (" . ($c['settings']['text'] ?? '?') . ")\n";
                    $removed++;
                    continue;
                }
                $out[] = $c;
            }
            $el['elements'] = $out;
            return true;
        }
        if (!empty($el['elements']) && $walk($el['elements'])) return true;
    }
    unset($el);
    return false;
};
$walk($tree);
echo "removed={$removed}\n";
if (!$APPLY || !$removed) { echo $removed ? "(dry run)\n" : "(no-op)\n"; return; }

$json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$ok = update_post_meta($POST, '_elementor_data', wp_slash($json));
echo "update_post_meta -> " . var_export($ok, true) . "\n";
delete_post_meta($POST, '_elementor_element_cache');
echo "APPLIED\n";
