<?php
/**
 * READ-ONLY: find any TRP rows that translate a "Maison de Bacon"-ish brand
 * string to something different in EN. We want the brand to stay identical.
 */
global $wpdb;
$table = 'mod35_trp_dictionary_fr_fr_en_us';

$rows = $wpdb->get_results(
    "SELECT id, original, translated, status FROM `{$table}`
     WHERE original LIKE '%Maison de Bacon%' OR original LIKE '%Maison De Bacon%'
        OR translated LIKE '%House of Bacon%' OR translated LIKE '%Bacon House%'
     ORDER BY CHAR_LENGTH(original) ASC");

echo "Rows mentioning the brand: " . count($rows) . "\n";
foreach ($rows as $r) {
    $diff = (trim((string)$r->translated) !== '' &&
             trim((string)$r->translated) !== trim($r->original)) ? '  <-- TRANSLATED DIFFERENTLY' : '';
    echo sprintf("id=%-6d st=%s\n  FR=%s\n  EN=%s%s\n",
        $r->id, $r->status, mb_substr($r->original,0,80), mb_substr((string)$r->translated,0,80), $diff);
}
