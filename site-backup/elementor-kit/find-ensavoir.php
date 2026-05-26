<?php
/**
 * READ-ONLY: locate "En Savoir Plus" button text and inspect the surrounding
 * button widget settings (text + button_type / styling) so we know what to change.
 */
global $wpdb;
$rows = $wpdb->get_results(
    "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data'
     AND meta_value LIKE '%En Savoir Plus%'"
);
echo "Posts containing 'En Savoir Plus' (exact case): " . count($rows) . "\n";

// Broader: any case variant of the phrase
$rows2 = $wpdb->get_results(
    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data'
     AND meta_value LIKE '%savoir plus%'"
);
echo "Posts containing 'savoir plus' (any case): " . count($rows2) . "\n";

$texts = array();         // distinct button texts that contain 'savoir plus'
$btn_type_counts = array();// button_type values seen on those buttons
$post_ids = array();

function walk($els, &$texts, &$btn_type_counts, $pid, &$post_ids) {
    if (!is_array($els)) return;
    foreach ($els as $el) {
        if (!is_array($el)) continue;
        $wt = $el['widgetType'] ?? '';
        $s = $el['settings'] ?? array();
        if ($wt === 'button' && isset($s['text']) && is_string($s['text'])
            && stripos($s['text'], 'savoir plus') !== false) {
            $t = trim($s['text']);
            $texts[$t] = ($texts[$t] ?? 0) + 1;
            $bt = $s['button_type'] ?? '(none)';
            $btn_type_counts[$bt] = ($btn_type_counts[$bt] ?? 0) + 1;
            $post_ids[$pid] = true;
        }
        if (isset($el['elements'])) walk($el['elements'], $texts, $btn_type_counts, $pid, $post_ids);
    }
}

foreach ($rows2 as $r) {
    $full = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_elementor_data' AND post_id=%d LIMIT 1",
        $r->post_id
    ));
    $data = json_decode($full, true);
    if (is_array($data)) walk($data, $texts, $btn_type_counts, $r->post_id, $post_ids);
}

echo "--- distinct BUTTON texts containing 'savoir plus' (text => occurrences) ---\n";
arsort($texts);
foreach ($texts as $t => $c) echo sprintf("%-4d %s\n", $c, $t);

echo "--- button_type values on those buttons ---\n";
foreach ($btn_type_counts as $bt => $c) echo sprintf("%-4d %s\n", $c, $bt);

echo "--- distinct posts with such a button: " . count($post_ids) . " ---\n";
foreach (array_keys($post_ids) as $pid) {
    $p = get_post($pid);
    echo sprintf("%-7d %-12s %s\n", $pid, $p ? $p->post_type : '?', $p ? $p->post_title : '');
}
