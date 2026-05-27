<?php
/* Footer (108363), strip horaires pré-footer : on accole à chaque bloc
 * horaires un bouton « Réserver en ligne » (Zenchef) vers l'établissement
 * correspondant (demande client). On retire en parallèle les 2 liens
 * « Réserver » de la colonne contact efaf474, qui ne garde alors que le
 * téléphone + les 3 e-mails de service.
 *
 *   8f55450 (.mdb-foot-hours--rest) : + bouton -> rid=354476 (Restaurant)
 *   29e34d7 (.mdb-foot-hours--roof) : + bouton -> rid=367528 (Le Roof Top)
 *   efaf474 (.mdb-foot-contact)     : Réservations -> Téléphone seul + emails
 *
 * Boutons = <a class="mdb-foot-btn"> stylés en CSS (overrides.css), pas de
 * widget button Elementor (le strip est en text-editor).
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 108363;

$BTN = function ($href, $label) {
    return '<p class="mdb-foot-btn-wrap"><a class="mdb-foot-btn" href="' . $href
         . '" target="_blank" rel="noopener">' . $label . '</a></p>';
};

$REST_HTML = '<p class="mdb-foot-sub">Horaires Restaurant</p>'
           . '<p>Ouvert tous les jours<br>12h &ndash; 14h &middot; 19h &ndash; 22h</p>'
           . $BTN('https://bookings.zenchef.com/results?rid=354476&amp;pid=1001', 'R&eacute;server en ligne');
$ROOF_HTML = '<p class="mdb-foot-sub">Horaires Le Roof Top</p>'
           . '<p>Du mercredi au dimanche &middot; d&egrave;s 18h<br>Tenue &eacute;l&eacute;gante</p>'
           . $BTN('https://bookings.zenchef.com/results?rid=367528&amp;pid=1001&amp;lang=en', 'R&eacute;server en ligne');

/* colonne contact : téléphone seul + 3 e-mails (plus de liens "Réserver") */
$CONTACT = '<p class="mdb-foot-sub">R&eacute;servations</p>'
         . '<p><a href="tel:+33493615002">+33 4 93 61 50 02</a></p>'
         . '<p class="mdb-foot-sub">&Eacute;v&eacute;nementiel</p>'
         . '<p><a href="mailto:event@maisondebacon.fr">event@maisondebacon.fr</a></p>'
         . '<p class="mdb-foot-sub">Comptabilit&eacute;</p>'
         . '<p><a href="mailto:compta@maisondebacon.fr">compta@maisondebacon.fr</a></p>'
         . '<p class="mdb-foot-sub">Recrutement</p>'
         . '<p><a href="mailto:recrutement@maisondebacon.fr">recrutement@maisondebacon.fr</a></p>';

$MAP = [
    '8f55450' => $REST_HTML,
    '29e34d7' => $ROOF_HTML,
    'efaf474' => $CONTACT,
];

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/footer-108363-booking-buttons-' . date('Ymd-His') . '.json';
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
                echo "  text-editor {$id}: identique\n";
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
