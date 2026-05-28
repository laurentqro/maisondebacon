<?php
/* Page 106766 / widget heading 37a9128 :
 *  - title 'LE CONCEPT' (Tenor caps eyebrow orphelin) → 'Le Concept' (titre h2).
 *  - header_size h4 → h2.
 *  Le restyle (font/size/color) est piloté côté thème dans overrides.css
 *  (sélecteur .elementor-element-37a9128) — on retire ici l'override de
 *  typography custom pour laisser le CSS thème gagner.
 *  DRY ; MDB_APPLY=1. */

$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106766;
$WID   = '37a9128';

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
        if (($el['id'] ?? '') === $WID && ($el['widgetType'] ?? '') === 'heading') {
            $s = $el['settings'] ?? array();
            echo "  before: title=" . ($s['title'] ?? '?') . " / header_size=" . ($s['header_size'] ?? '?') . "\n";
            $s['title']       = 'Le Concept';
            $s['header_size'] = 'h2';
            // Laisse le CSS thème piloter la typo : on retire les overrides Elementor.
            unset($s['typography_typography']);
            unset($s['title_color']);
            $el['settings'] = $s;
            echo "  after : title=Le Concept / header_size=h2 (typography overrides dropped)\n";
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
