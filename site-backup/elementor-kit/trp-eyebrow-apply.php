<?php
/**
 * Set the EN translation for the hero eyebrow string (TRP id 26979).
 * DRY RUN by default; MDB_APPLY=1 to write.
 */
$APPLY = getenv('MDB_APPLY') === '1';
global $wpdb;
$table = 'mod35_trp_dictionary_fr_fr_en_us';
$id = 26979;
$en = 'A gastronomic institution by the sea, since 1948';

$before = $wpdb->get_row($wpdb->prepare(
    "SELECT id, original, translated, status FROM `{$table}` WHERE id=%d", $id));
echo "BEFORE: id={$before->id} status={$before->status}\n  original={$before->original}\n  translated={$before->translated}\n";
echo "WILL SET translated='{$en}' status=2 (human reviewed)\n";

if ($APPLY) {
    $ok = $wpdb->update(
        $table,
        array('translated' => $en, 'status' => 2),
        array('id' => $id),
        array('%s', '%d'),
        array('%d')
    );
    echo "rows updated: " . var_export($ok, true) . "\n";
    $after = $wpdb->get_row($wpdb->prepare(
        "SELECT id, translated, status FROM `{$table}` WHERE id=%d", $id));
    echo "AFTER: status={$after->status} translated={$after->translated}\n";
} else {
    echo "(dry run — no write)\n";
}
