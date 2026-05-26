<?php
/**
 * Fix EN translations that wrongly translated the brand "Maison de Bacon"
 * (-> "Bacon's House" / "House of Bacon" / "the bacon house").
 *
 * Two kinds of fix:
 *  - WHOLE-STRING brand labels: EN must equal the FR brand verbatim.
 *  - IN-SENTENCE: replace the translated brand token with "Maison de Bacon"
 *    while keeping the rest of the (correct) EN translation.
 *
 * We do explicit per-id replacements (reviewed) rather than a blind global
 * regex, so we never corrupt a legitimate sentence.
 *
 * DRY RUN by default; MDB_APPLY=1 to write.
 */
$APPLY = getenv('MDB_APPLY') === '1';
global $wpdb;
$table = 'mod35_trp_dictionary_fr_fr_en_us';

// Pure brand labels: EN set verbatim to the FR brand.
$verbatim = array(
    40 => 'MAISON DE BACON',
    64 => 'La Maison de Bacon',
);
// In-sentence: token-replace the mistranslated brand inside the EXISTING EN,
// preserving the rest of the (correct) translation. Order matters: do the
// longest/most-specific tokens first.
$sentence_ids = array(3780, 3794, 3795, 3899, 3911, 3917);

function mdb_fix_brand_tokens($en) {
    // Replace every mistranslated brand form -> "Maison de Bacon". We swallow a
    // leading article ("the"/"The"/"THE") when present so we don't leave a
    // dangling article. ALL-CAPS context keeps the brand uppercase.
    $patterns = array(
        // ---- uppercase contexts -> uppercase brand ----
        '/\b(?:THE\s+)?BACON\s+HOUSE\b/'         => 'MAISON DE BACON',
        '/\b(?:THE\s+)?HOUSE\s+OF\s+BACON\b/'    => 'MAISON DE BACON',
        // ---- mixed/title/sentence case (case-insensitive) -> brand ----
        '/\b(?:the\s+)?House\s+of\s+Bacon\b/i'   => 'Maison de Bacon',
        "/\b(?:the\s+)?Bacon(?:&#039;|')s\s+House\b/i" => 'Maison de Bacon',
        '/\b(?:the\s+)?Bacon\s+House\b/i'        => 'Maison de Bacon',
    );
    $out = preg_replace(array_keys($patterns), array_values($patterns), $en);
    // Normalise any accidental double spaces from the swallow.
    return preg_replace('/\s{2,}/', ' ', $out);
}

// --- whole-string brand labels ---
foreach ($verbatim as $id => $new) {
    $cur = $wpdb->get_row($wpdb->prepare(
        "SELECT id, original, translated, status FROM `{$table}` WHERE id=%d", $id));
    if (!$cur) { echo "id={$id}: NOT FOUND\n"; continue; }
    echo sprintf("[label] id=%-5d st=%s\n  FR =%s\n  OLD=%s\n  NEW=%s\n",
        $id, $cur->status, $cur->original, (string)$cur->translated, $new);
    if ($APPLY) {
        $ok = $wpdb->update($table, array('translated'=>$new,'status'=>2),
            array('id'=>$id), array('%s','%d'), array('%d'));
        echo "  -> updated: " . var_export($ok,true) . "\n";
    } else echo "  (dry run)\n";
    echo "\n";
}

// --- in-sentence token replacement ---
foreach ($sentence_ids as $id) {
    $cur = $wpdb->get_row($wpdb->prepare(
        "SELECT id, original, translated, status FROM `{$table}` WHERE id=%d", $id));
    if (!$cur) { echo "id={$id}: NOT FOUND\n"; continue; }
    $new = mdb_fix_brand_tokens((string)$cur->translated);
    $changed = ($new !== $cur->translated);
    echo sprintf("[sentence] id=%-5d st=%s changed=%s\n  OLD=%s\n  NEW=%s\n",
        $id, $cur->status, $changed?'Y':'N',
        mb_substr((string)$cur->translated,0,110), mb_substr($new,0,110));
    if ($APPLY && $changed) {
        $ok = $wpdb->update($table, array('translated'=>$new,'status'=>2),
            array('id'=>$id), array('%s','%d'), array('%d'));
        echo "  -> updated: " . var_export($ok,true) . "\n";
    } elseif (!$changed) {
        echo "  (no bad token — skip)\n";
    } else echo "  (dry run)\n";
    echo "\n";
}
