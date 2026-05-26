<?php
/**
 * READ-ONLY first: locate the TranslatePress table + the hero eyebrow string,
 * and report whether an EN translation already exists.
 */
global $wpdb;

// TRP dictionary tables look like {prefix}trp_dictionary_{from}_{to}, e.g.
// mod35_trp_dictionary_fr_fr_en_us. Find them.
$tables = $wpdb->get_col("SHOW TABLES LIKE '%trp_dictionary%'");
echo "TRP dictionary tables:\n";
foreach ($tables as $t) echo "  {$t}\n";

$needle = 'Institution gastronomique face à la mer, depuis 1948';
echo "\nSearching for source string: {$needle}\n";

foreach ($tables as $t) {
    // columns: id, original, translated, status, ...
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, original, translated, status FROM `{$t}` WHERE original LIKE %s",
        '%' . $wpdb->esc_like($needle) . '%'
    ));
    echo "--- {$t}: " . count($rows) . " row(s) ---\n";
    foreach ($rows as $r) {
        echo sprintf("  id=%d status=%s\n    original=%s\n    translated=%s\n",
            $r->id, $r->status, mb_substr($r->original,0,80), mb_substr((string)$r->translated,0,80));
    }
}

// Also: does the source string even exist (any partial)? show near matches.
echo "\n-- near matches on 'gastronomique face' across tables --\n";
foreach ($tables as $t) {
    $rows = $wpdb->get_results(
        "SELECT id, LEFT(original,70) o, LEFT(translated,70) tr, status FROM `{$t}` WHERE original LIKE '%gastronomique face%' LIMIT 10");
    foreach ($rows as $r) echo "  [{$t}] id={$r->id} st={$r->status} | {$r->o} => {$r->tr}\n";
}
