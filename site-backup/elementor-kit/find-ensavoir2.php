<?php
/**
 * READ-ONLY: find which widgetType + which settings key carries "savoir plus".
 * Restrict to published pages/posts (skip revisions) for a clean picture.
 */
global $wpdb;
$rows = $wpdb->get_results(
    "SELECT pm.post_id, p.post_type, p.post_status, p.post_title
       FROM {$wpdb->postmeta} pm
       JOIN {$wpdb->posts} p ON p.ID = pm.post_id
      WHERE pm.meta_key = '_elementor_data'
        AND pm.meta_value LIKE '%savoir plus%'
        AND p.post_status = 'publish'
        AND p.post_type NOT IN ('revision')"
);
echo "Published non-revision posts with 'savoir plus': " . count($rows) . "\n";

$wt_key = array(); // "widgetType :: settingKey" => count
$samples = array();

function walk($els, &$wt_key, &$samples) {
    if (!is_array($els)) return;
    foreach ($els as $el) {
        if (!is_array($el)) continue;
        $wt = $el['widgetType'] ?? ($el['elType'] ?? '?');
        $s = $el['settings'] ?? array();
        if (is_array($s)) {
            foreach ($s as $k => $v) {
                if (is_string($v) && stripos($v, 'savoir plus') !== false) {
                    $key = $wt . ' :: ' . $k;
                    $wt_key[$key] = ($wt_key[$key] ?? 0) + 1;
                    if (count($samples) < 12) $samples[] = $wt . ' / ' . $k . ' = ' . trim($v);
                }
            }
        }
        if (isset($el['elements'])) walk($el['elements'], $wt_key, $samples);
    }
}

foreach ($rows as $r) {
    $full = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_elementor_data' AND post_id=%d LIMIT 1",
        $r->post_id
    ));
    $data = json_decode($full, true);
    if (is_array($data)) walk($data, $wt_key, $samples);
}

echo "--- widgetType :: settingKey (occurrences) ---\n";
arsort($wt_key);
foreach ($wt_key as $k => $c) echo sprintf("%-5d %s\n", $c, $k);

echo "--- samples ---\n";
foreach ($samples as $s) echo $s . "\n";

echo "--- the published posts ---\n";
foreach ($rows as $r) echo sprintf("%-7d %-10s %s\n", $r->post_id, $r->post_type, $r->post_title);
