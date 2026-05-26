<?php
/**
 * Find + translate the remaining hero strings introduced by the rebuild:
 *   - "Cuisine marine du Chef Nicolas Davouze"
 *   - "Prochains événements"
 * DRY RUN by default; MDB_APPLY=1 to write.
 *
 * Note: the lede contains <em> markup in the widget, but TRP stores the
 * gettext/rendered string. We search for both the plain and the markup-ish
 * variants and translate whatever rows TRP captured.
 */
$APPLY = getenv('MDB_APPLY') === '1';
global $wpdb;
$table = 'mod35_trp_dictionary_fr_fr_en_us';

$targets = array(
    // exact short hero strings only (TRP split the lede; Nicolas Davouze is
    // an untranslated <em> proper noun that follows "...du Chef").
    'Cuisine marine du Chef'                     => 'Marine cuisine by Chef',
    'Prochains événements'                       => 'Upcoming events',
    'PROCHAINS EVENEMENTS A LA MAISON DE BACON'  => 'Upcoming events at Maison de Bacon',
);

foreach ($targets as $needle => $en) {
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, original, translated, status FROM `{$table}` WHERE original LIKE %s",
        '%' . $wpdb->esc_like($needle) . '%'
    ));
    echo "=== needle: {$needle} -> {$en} ===\n";
    if (!$rows) { echo "  (no TRP row found yet — string may not be captured)\n"; continue; }
    foreach ($rows as $r) {
        // Only translate exact-ish hero strings, skip long aggregated blocks.
        $isShort = mb_strlen($r->original) <= 60;
        echo sprintf("  id=%d short=%s status=%s\n    original=%s\n    current=%s\n",
            $r->id, $isShort?'Y':'N', $r->status, mb_substr($r->original,0,70), mb_substr((string)$r->translated,0,60));
        if ($APPLY && $isShort) {
            // For the lede, mirror the <em> on the proper-noun part if the
            // original carried markup; otherwise plain text.
            $val = $en;
            if (strpos($r->original, '<em>') !== false || strpos($r->original, 'Nicolas') !== false) {
                // keep it plain; the widget wraps Nicolas Davouze in <em>, TRP
                // typically captures the inner text node separately. Plain EN
                // is safe and renders correctly.
                $val = $en;
            }
            $ok = $wpdb->update($table, array('translated'=>$val,'status'=>2), array('id'=>$r->id), array('%s','%d'), array('%d'));
            echo "    -> updated rows: " . var_export($ok, true) . "\n";
        }
    }
}
if (!$APPLY) echo "\n(dry run — no write)\n";
