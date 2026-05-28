<?php
/* Rewording du lien secondaire « ou appelez : … » → « ou appeler le … »
 *  Cible : widgets HTML mdbherocall (page 106136) et rtherocall (106766).
 *  DRY ; MDB_APPLY=1. */

$APPLY = getenv('MDB_APPLY') === '1';
$TARGETS = array(
    array(106136, 'mdbherocall'),
    array(106766, 'rtherocall'),
);

global $wpdb;
foreach ($TARGETS as $t) {
    list($pid, $wid) = $t;
    echo "=== {$pid} / {$wid} ===\n";
    $raw = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
        $pid, '_elementor_data'
    ));
    $tree = json_decode($raw, true);
    if (!is_array($tree)) { echo "  ABORT bad json\n"; continue; }

    $patched = 0;
    $walk = function (&$els) use (&$walk, $wid, &$patched) {
        foreach ($els as &$el) {
            if (($el['id'] ?? '') === $wid && ($el['widgetType'] ?? '') === 'html') {
                $cur = $el['settings']['html'] ?? '';
                $new = str_replace('ou appelez : <span', 'ou appeler le <span', $cur);
                if ($new !== $cur) {
                    echo "  before: " . trim($cur) . "\n";
                    echo "  after : " . trim($new) . "\n";
                    $el['settings']['html'] = $new;
                    $patched++;
                } else {
                    echo "  (no change — current=" . trim($cur) . ")\n";
                }
                return true;
            }
            if (!empty($el['elements']) && $walk($el['elements'])) return true;
        }
        unset($el);
        return false;
    };
    $walk($tree);
    if (!$APPLY || !$patched) { echo "  " . ($patched ? "(dry run)" : "(no-op)") . "\n"; continue; }
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ok = update_post_meta($pid, '_elementor_data', wp_slash($json));
    echo "  update_post_meta -> " . var_export($ok, true) . "\n";
    delete_post_meta($pid, '_elementor_element_cache');
}
echo "DONE\n";
