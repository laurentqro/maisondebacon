<?php
/* TranslatePress (fr_fr -> en_us) : traduire l'en-tête de la page Avis.
 *
 * L'en-tête (heading widgets Elementor) est rendu côté serveur -> TRP peut le
 * traduire. Les cartes d'avis sont injectées par JS et NE PASSENT PAS par TRP :
 * elles sont gérées par un dictionnaire EN dans le script (voir le widget html).
 *
 * Upsert dans wp_trp_dictionary_fr_fr_en_us avec status=2 (revu humain).
 * Marque « Maison de Bacon » laissée telle quelle (cf. règle de marque).
 * DRY par défaut ; MDB_APPLY=1. */
$APPLY = getenv('MDB_APPLY') === '1';

$pairs = [
    'Témoignages'                                       => 'Testimonials',
    'Ils parlent de nous'                               => 'In their words',
    'Ce que nos hôtes retiennent de la Maison de Bacon.'=> 'What our guests remember of Maison de Bacon.',
];

global $wpdb;
$t = $wpdb->prefix . 'trp_dictionary_fr_fr_en_us';

foreach ($pairs as $fr => $en) {
    $existing = $wpdb->get_row(
        $wpdb->prepare("SELECT id, translated, status FROM {$t} WHERE original = %s", $fr),
        ARRAY_A
    );
    if ($existing) {
        echo "EXISTS  [{$existing['status']}] " . substr($fr, 0, 40) . " => " . substr($existing['translated'], 0, 40) . "\n";
        echo "  -> set: {$en}\n";
        if ($APPLY) {
            $wpdb->update($t, ['translated' => $en, 'status' => 2], ['id' => $existing['id']]);
        }
    } else {
        echo "INSERT  " . substr($fr, 0, 40) . " => {$en}\n";
        if ($APPLY) {
            $wpdb->insert($t, ['original' => $fr, 'translated' => $en, 'status' => 2, 'block_type' => 0, 'original_id' => null]);
        }
    }
}
echo $APPLY ? "APPLIED\n" : "(dry run — MDB_APPLY=1 to write)\n";
