<?php
/* Page 106766 : retire le bandeau orphelin « Carte Cocktails / Carte Champagnes »
 *  (container top-level 34e7f20). Les 2 PDFs sont désormais accessibles via le
 *  mega-menu La Carte (items 110556 + 110557).
 *  DRY ; MDB_APPLY=1. */

$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106766;
$CID   = '34e7f20';

global $wpdb;
$raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
    $POST, '_elementor_data'
));
$tree = json_decode($raw, true);
if (!is_array($tree)) { echo "ABORT bad json\n"; return; }

$backup = '/tmp/mdb-evfix/106766-pre-drop-cartes-band-' . date('Ymd-His') . '.json';
file_put_contents($backup, $raw);
echo "backup -> {$backup}\n";

$before = count($tree);
$tree = array_values(array_filter($tree, function ($el) use ($CID) {
    return ($el['id'] ?? '') !== $CID;
}));
$after = count($tree);
echo "top-level containers: {$before} -> {$after}\n";

if ($before === $after) { echo "(no-op : container {$CID} not found)\n"; return; }
if (!$APPLY)            { echo "(dry run)\n"; return; }

$json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$ok = update_post_meta($POST, '_elementor_data', wp_slash($json));
echo "update_post_meta -> " . var_export($ok, true) . "\n";
delete_post_meta($POST, '_elementor_element_cache');
echo "APPLIED\n";
