<?php
/* Headers MDB (110538) + RT (110554) → drop « Carte cadeau », promouvoir « Voir la carte ».
 *  Le widget HTML mdbhdrcta porte les deux boutons (ghost + solid). On
 *  réécrit son inner HTML :
 *    - ghost  : "Voir la carte" → /notre-carte/
 *    - solid  : "Réserver"      → Zenchef (354476 sur MdB, 367528 sur RT)
 *  Idempotent : si « Voir la carte » est déjà là, on ne touche rien.
 *  DRY ; MDB_APPLY=1. */

$APPLY = getenv('MDB_APPLY') === '1';

// (header_post_id, zenchef_rid)
$TARGETS = array(
    array(110538, 354476),
    array(110554, 367528),
);
$CARTE_URL = 'https://staging.maisondebacon.fr/notre-carte/';

global $wpdb;
@mkdir('/tmp/mdb-evfix', 0775, true);
$ts = date('Ymd-His');

foreach ($TARGETS as $t) {
    list($pid, $rid) = $t;
    echo "=== header {$pid} (zenchef={$rid}) ===\n";
    $raw = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
        $pid, '_elementor_data'
    ));
    $tree = json_decode($raw, true);
    if (!is_array($tree)) { echo "  ABORT bad json\n"; continue; }
    file_put_contents("/tmp/mdb-evfix/layoutA-header-{$pid}-pre-{$ts}.json", $raw);

    $new_html = sprintf(
        '<div class="mdb-header__actions">'
        . '<a class="mdb-btn mdb-btn--ghost" href="%s">Voir la carte</a>'
        . '<a class="mdb-btn mdb-btn--solid" href="https://bookings.zenchef.com/results?rid=%d&amp;pid=1001" target="_blank" rel="noopener">Réserver</a>'
        . '</div>',
        esc_url($CARTE_URL), (int) $rid
    );

    $patched = 0;
    $walk = function (&$els) use (&$walk, $new_html, &$patched) {
        foreach ($els as &$el) {
            if (($el['id'] ?? '') === 'mdbhdrcta' && ($el['widgetType'] ?? '') === 'html') {
                $current = $el['settings']['html'] ?? '';
                if (strpos($current, 'Voir la carte') !== false && strpos($current, 'Carte cadeau') === false) {
                    echo "  already rewritten — skip\n";
                    return true;
                }
                echo "  before: " . substr($current, 0, 160) . "...\n";
                $el['settings']['html'] = $new_html;
                echo "  after : " . substr($new_html, 0, 160) . "...\n";
                $patched++;
                return true;
            }
            if (!empty($el['elements']) && $walk($el['elements'])) return true;
        }
        unset($el);
        return false;
    };
    $walk($tree);
    echo "  patched={$patched}\n";

    if (!$APPLY || !$patched) { echo "  " . ($patched ? "(dry run)" : "(no-op)") . "\n"; continue; }

    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ok = update_post_meta($pid, '_elementor_data', wp_slash($json));
    echo "  update_post_meta -> " . var_export($ok, true) . "\n";
    delete_post_meta($pid, '_elementor_element_cache');
    echo "  element cache nuked\n";
}
echo "DONE\n";
