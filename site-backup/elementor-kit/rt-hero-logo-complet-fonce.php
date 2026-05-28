<?php
/* Page 106766 / widget image 34a8c93 (logo du hero RT) :
 *  swap → RT-maisondebacon-complet_orange_fonce-1.png (106794, dark orange).
 *  DRY ; MDB_APPLY=1. */

$APPLY  = getenv('MDB_APPLY') === '1';
$POST   = 106766;
$WID    = '34a8c93';
$NEW_ID = 106794;

global $wpdb;
$raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
    $POST, '_elementor_data'
));
$tree = json_decode($raw, true);
if (!is_array($tree)) { echo "ABORT bad json\n"; return; }

$new_url = wp_get_attachment_url($NEW_ID);
if (!$new_url) { echo "ABORT attach {$NEW_ID} introuvable\n"; return; }
echo "new url -> {$new_url}\n";

$patched = 0;
$walk = function (&$els) use (&$walk, $WID, $NEW_ID, $new_url, &$patched) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $WID && ($el['widgetType'] ?? '') === 'image') {
            $img = $el['settings']['image'] ?? array();
            echo "  before: id=" . ($img['id'] ?? '?') . " url=" . ($img['url'] ?? '?') . "\n";
            $img['id']  = $NEW_ID;
            $img['url'] = $new_url;
            $el['settings']['image'] = $img;
            echo "  after : id={$NEW_ID} url={$new_url}\n";
            $patched++;
            return true;
        }
        if (!empty($el['elements']) && $walk($el['elements'])) return true;
    }
    unset($el);
    return false;
};
$walk($tree);
echo "patched={$patched}\n";
if (!$APPLY || !$patched) { echo $patched ? "(dry run)\n" : "(no-op)\n"; return; }
$json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$ok = update_post_meta($POST, '_elementor_data', wp_slash($json));
echo "update_post_meta -> " . var_export($ok, true) . "\n";
delete_post_meta($POST, '_elementor_element_cache');
echo "APPLIED\n";
