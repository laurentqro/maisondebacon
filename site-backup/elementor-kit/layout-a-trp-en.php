<?php
/* TRP (fr_fr → en_us) : seed les nouvelles chaînes éditoriales du Layout A
 *  (heroes MdB + RT), de sorte que /en/ rende propre dès la 1re visite.
 *  Sans cela, TRP renvoie le FR jusqu'à ce qu'un humain édite le dictionnaire.
 *  status=2 (revu humain).
 *  DRY ; MDB_APPLY=1. */

$APPLY = getenv('MDB_APPLY') === '1';
global $wpdb;
$t = $wpdb->prefix . 'trp_dictionary_fr_fr_en_us';

$pairs = array(
    // CTA principal des heros (et header).
    'Réserver une table en ligne'                  => 'Book a table online',
    // CTA header.
    'Voir la carte'                                => 'See the menu',
    'Réserver'                                     => 'Book',
    // Info bars MdB + RT.
    'Ouvert tous les jours · 12h – 14h · 19h – 22h' => 'Open daily · 12pm – 2pm · 7pm – 10pm',
    'Du mercredi au dimanche · dès 18h' => 'Wed to Sun · from 6pm',
    // Lien secondaire « ou appeler le 04 93 … » (phrase complète, verbe + objet).
    'ou appeler le'                                => 'or call',
);

foreach ($pairs as $fr => $en) {
    $row = $wpdb->get_row($wpdb->prepare("SELECT id, translated FROM {$t} WHERE original=%s", $fr), ARRAY_A);
    if ($row) {
        echo "EXISTS {$fr} (current EN=\"{$row['translated']}\")\n";
        if ($APPLY) {
            $wpdb->update($t, array('translated' => $en, 'status' => 2), array('id' => $row['id']));
        }
    } else {
        echo "INSERT {$fr} => {$en}\n";
        if ($APPLY) {
            $wpdb->insert($t, array(
                'original'    => $fr,
                'translated'  => $en,
                'status'      => 2,
                'block_type'  => 0,
                'original_id' => null,
            ));
        }
    }
}
echo $APPLY ? "APPLIED\n" : "(dry run)\n";
