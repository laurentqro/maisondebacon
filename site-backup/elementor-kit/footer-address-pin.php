<?php
/* Footer (108363), colonne « Nous Contacter », bloc adresse (text-editor
 * f8cfc0f) : retirer le sous-titre « Adresse », ajouter une épingle de
 * localisation discrète + la ligne « France ». (Demande client.)
 *
 * Avant : <p class="mdb-foot-sub">Adresse</p><p>664 Boulevard de Bacon<br>06160 Cap d'Antibes</p>
 * Après : épingle SVG + 664 Boulevard de Bacon / 06160 Cap d'Antibes / France
 *
 * L'épingle est un SVG inline (currentColor) -> teinte via CSS .mdb-foot-pin.
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 108363;
$ADDR  = 'f8cfc0f';

$pin = '<svg class="mdb-foot-pin" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>';
$html = '<p class="mdb-foot-address__line">' . $pin . '<span>664 Boulevard de Bacon<br>06160 Cap d\'Antibes<br>France</span></p>';

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/footer-108363-address-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;

$done = 0;
$set = function (&$els, $id, $html) use (&$set, &$done) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $id) { $el['settings']['editor'] = $html; $done++; return true; }
        if (!empty($el['elements']) && $set($el['elements'], $id, $html)) return true;
    }
    unset($el);
    return false;
};
$set($tree, $ADDR, $html);
echo "adresse {$ADDR} réécrite : {$done}\n";

if ($APPLY && $done) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
