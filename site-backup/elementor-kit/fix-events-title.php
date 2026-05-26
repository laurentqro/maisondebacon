<?php
/**
 * Title-case + accent the events section heading on the home (106136).
 * Widget id e36233f, heading "PROCHAINS EVENEMENTS" -> "Prochains Événements".
 * DRY RUN by default; MDB_APPLY=1 to write. Backs up _elementor_data to file.
 */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106136;
$WIDGET = 'e36233f';
$NEW = 'Prochains Événements';

$data = get_post_meta($POST, '_elementor_data', true);
if (!$data) { echo "NO _elementor_data on {$POST}\n"; exit; }
$raw = is_string($data) ? $data : wp_json_encode($data);

// backup once
$bak = '/tmp/mdb-evfix/elementor-data-106136-' . date('Ymd-His') . '.json';
@file_put_contents($bak, $raw);
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;
if (!is_array($tree)) { echo "decode failed\n"; exit; }

$found = 0;
function walk(&$els, $widget, $new, &$found) {
    foreach ($els as &$el) {
        $isHeading = (($el['widgetType'] ?? '') === 'heading');
        $idMatch   = (($el['id'] ?? '') === $widget);
        if ($idMatch && $isHeading) {
            $old = $el['settings']['title'] ?? '(none)';
            echo "  match id={$widget} OLD title=[{$old}]\n";
            $el['settings']['title'] = $new;
            $found++;
        }
        if (!empty($el['elements']) && is_array($el['elements'])) {
            walk($el['elements'], $widget, $new, $found);
        }
    }
}
walk($tree, $WIDGET, $NEW, $found);
echo "matched: {$found}; NEW title=[{$NEW}]\n";

if ($APPLY && $found) {
    // Elementor stores _elementor_data as a JSON string (slashed).
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ok = update_post_meta($POST, '_elementor_data', wp_slash($json));
    echo "update_post_meta -> " . var_export($ok, true) . "\n";
} else {
    echo "(dry run)\n";
}
