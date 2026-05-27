<?php
/* Footer (108363), colonne « Nous contacter » : enrichissement (demande client).
 *
 *   e37b287 (.mdb-foot-hours)   : horaires séparés Restaurant / Le Roof Top
 *   efaf474 (.mdb-foot-contact) : réservations (2 liens Zenchef : Restaurant +
 *                                 Le Roof Top), téléphone, puis 3 contacts
 *                                 e-mail dédiés (Événementiel / Comptabilité /
 *                                 Recrutement).
 *
 * On réutilise le pattern existant <p class="mdb-foot-sub"> pour les
 * sous-titres dorés, afin d'intégrer harmonieusement le nouveau contenu sans
 * toucher au CSS. DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 108363;

$HOURS = '<p class="mdb-foot-sub">Horaires Restaurant</p>'
       . '<p>Ouvert tous les jours<br>12h &ndash; 14h<br>19h &ndash; 22h</p>'
       . '<p class="mdb-foot-sub">Horaires Le Roof Top</p>'
       . '<p>Du mercredi au dimanche<br>d&egrave;s 18h<br>Tenue &eacute;l&eacute;gante</p>';

$CONTACT = '<p class="mdb-foot-sub">R&eacute;servations</p>'
         . '<p>'
         . '<a href="https://bookings.zenchef.com/results?rid=354476&amp;pid=1001" target="_blank" rel="noopener">R&eacute;server &mdash; Restaurant</a><br>'
         . '<a href="https://bookings.zenchef.com/results?rid=367528&amp;pid=1001&amp;lang=en" target="_blank" rel="noopener">R&eacute;server &mdash; Le Roof Top</a><br>'
         . '<a href="tel:+33493615002">+33 4 93 61 50 02</a>'
         . '</p>'
         . '<p class="mdb-foot-sub">&Eacute;v&eacute;nementiel</p>'
         . '<p><a href="mailto:event@maisondebacon.fr">event@maisondebacon.fr</a></p>'
         . '<p class="mdb-foot-sub">Comptabilit&eacute;</p>'
         . '<p><a href="mailto:compta@maisondebacon.fr">compta@maisondebacon.fr</a></p>'
         . '<p class="mdb-foot-sub">Recrutement</p>'
         . '<p><a href="mailto:recrutement@maisondebacon.fr">recrutement@maisondebacon.fr</a></p>';

$MAP = [
    'e37b287' => $HOURS,
    'efaf474' => $CONTACT,
];

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/footer-108363-contact-hours-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$changed = 0;
$walk = function (&$els) use (&$walk, $MAP, &$changed) {
    foreach ($els as &$el) {
        $id = $el['id'] ?? '';
        if (isset($MAP[$id]) && ($el['widgetType'] ?? '') === 'text-editor') {
            $old = $el['settings']['editor'] ?? '';
            if ($old !== $MAP[$id]) {
                $el['settings']['editor'] = $MAP[$id];
                echo "  text-editor {$id}: mis a jour (" . strlen($old) . " -> " . strlen($MAP[$id]) . " car.)\n";
                $changed++;
            } else {
                echo "  text-editor {$id}: identique, rien a faire\n";
            }
        }
        if (!empty($el['elements'])) $walk($el['elements']);
    }
    unset($el);
};
$walk($tree);
echo "changes: {$changed}\n";

if ($APPLY && $changed) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
