<?php
/**
 * READ-ONLY survey: how widely do per-widget typography overrides appear in
 * Elementor data, and which font families. Run with: wp eval-file survey-typo.php
 */
global $wpdb;
$rows = $wpdb->get_results(
    "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data'"
);
$total_posts = count($rows);
$posts_with_font = 0;
$total_font_keys = 0;
$fam_count = array();   // family => occurrences
$posts_by_fam = array(); // family => set of post_ids

foreach ($rows as $r) {
    $v = $r->meta_value;
    $n = substr_count($v, 'typography_font_family');
    if ($n > 0) {
        $posts_with_font++;
        $total_font_keys += $n;
    }
    // Pull every "typography_font_family":"X" value
    if (preg_match_all('/"typography_font_family":"([^"]*)"/', $v, $m)) {
        foreach ($m[1] as $fam) {
            $fam = $fam === '' ? '(empty)' : $fam;
            $fam_count[$fam] = ($fam_count[$fam] ?? 0) + 1;
            $posts_by_fam[$fam][$r->post_id] = true;
        }
    }
}

echo "TOTAL posts with _elementor_data: {$total_posts}\n";
echo "Posts containing a font override: {$posts_with_font}\n";
echo "Total typography_font_family keys: {$total_font_keys}\n";
echo "--- by family (occurrences / distinct posts) ---\n";
arsort($fam_count);
foreach ($fam_count as $fam => $cnt) {
    $posts = count($posts_by_fam[$fam]);
    echo sprintf("%-28s occ=%-5d posts=%d\n", $fam, $cnt, $posts);
}

// Also report the full set of typography_* sub-keys present, so we know what to strip
$subkeys = array();
foreach ($rows as $r) {
    if (preg_match_all('/"(typography_[a-z_]+)":/', $r->meta_value, $mm)) {
        foreach ($mm[1] as $k) { $subkeys[$k] = ($subkeys[$k] ?? 0) + 1; }
    }
}
echo "--- typography_* sub-keys present (occurrences) ---\n";
arsort($subkeys);
foreach ($subkeys as $k => $c) { echo sprintf("%-34s %d\n", $k, $c); }
