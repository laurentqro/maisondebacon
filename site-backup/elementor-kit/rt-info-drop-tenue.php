<?php
/* Page 106766 / widget heading rtheroinfo :
 *  title 'Du mercredi au dimanche · dès 18h · Tenue élégante'
 *     → 'Du mercredi au dimanche · dès 18h'
 *  DRY ; MDB_APPLY=1. */

$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106766;
$WID   = 'rtheroinfo';
$NEW   = 'Du mercredi au dimanche · dès 18h';

global $wpdb;
$raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
    $POST, '_elementor_data'
));
$tree = json_decode($raw, true);
if (!is_array($tree)) { echo "ABORT bad json\n"; return; }

$patched = 0;
$walk = function (&$els) use (&$walk, $WID, $NEW, &$patched) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $WID && ($el['widgetType'] ?? '') === 'heading') {
            echo "  before: " . ($el['settings']['title'] ?? '?') . "\n";
            $el['settings']['title'] = $NEW;
            echo "  after : {$NEW}\n";
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
