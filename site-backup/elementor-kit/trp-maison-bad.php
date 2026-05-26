<?php
/**
 * READ-ONLY: list ONLY the rows where the brand has been actively mistranslated
 * (EN contains a translated form of "Bacon's house" / "house of Bacon", or the
 * EN differs from FR in a way that altered "Maison de Bacon").
 *
 * Strategy: a translation is "bad" if its EN, after removing the literal brand
 * "Maison de Bacon"/"Maison De Bacon", still contains Bacon-as-translated
 * tokens like "Bacon's House", "House of Bacon", "Bacon House".
 */
global $wpdb;
$table = 'mod35_trp_dictionary_fr_fr_en_us';

$rows = $wpdb->get_results(
    "SELECT id, original, translated, status FROM `{$table}`
     WHERE translated <> '' AND (
         translated LIKE '%Bacon%House%' OR
         translated LIKE '%House%Bacon%' OR
         translated LIKE '%Bacon&#039;s%' OR
         translated LIKE \"%Bacon's%\"
     )
     ORDER BY id ASC");

echo "Rows where brand looks mistranslated: " . count($rows) . "\n\n";
foreach ($rows as $r) {
    echo sprintf("id=%-6d st=%s\n  FR=%s\n  EN=%s\n\n",
        $r->id, $r->status, mb_substr($r->original,0,100), mb_substr((string)$r->translated,0,100));
}
