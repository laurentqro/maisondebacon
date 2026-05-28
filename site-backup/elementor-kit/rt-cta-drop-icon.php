<?php
/* Page 106766 / bouton ad86369 : retirer l'icône (« icon-internet » globe).
 *  On vide `selected_icon` (et l'ancien champ `icon` si présent).
 *  Bonus : on retire aussi `background_color = "#EF7D00"` (orange figé qui
 *  empêchait le hover terracotta du CSS de prendre le dessus).
 *  DRY ; MDB_APPLY=1. */

$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106766;
$WID   = 'ad86369';

global $wpdb;
$raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
    $POST, '_elementor_data'
));
$tree = json_decode($raw, true);
if (!is_array($tree)) { echo "ABORT bad json\n"; return; }

$patched = 0;
$walk = function (&$els) use (&$walk, $WID, &$patched) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $WID && ($el['widgetType'] ?? '') === 'button') {
            $s = $el['settings'] ?? array();
            $changed = false;
            if (isset($s['selected_icon'])) {
                echo "  drop selected_icon = " . json_encode($s['selected_icon']) . "\n";
                $s['selected_icon'] = array('value' => '', 'library' => '');
                $changed = true;
            }
            if (isset($s['icon']) && $s['icon'] !== '') {
                echo "  drop icon = " . json_encode($s['icon']) . "\n";
                $s['icon'] = '';
                $changed = true;
            }
            if (isset($s['background_color'])) {
                echo "  drop background_color = " . $s['background_color'] . "\n";
                unset($s['background_color']);
                $changed = true;
            }
            if ($changed) { $el['settings'] = $s; $patched++; }
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
