<?php
/* Page « Le Roof Top » (106766), bloc info du hero (text-editor 7488ca8) :
 * remplace l'ancien texte (« OUVERT UNIQUEMENT AUX EVENEMENTS… ») par les
 * horaires d'ouverture + tenue. Markup sémantique (classes mdb-rt-meta*) que
 * le CSS scellé à .elementor-106766 met en forme (eyebrow Tenor, filets brass).
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106766;
$W     = '7488ca8';

$HTML = '<p class="mdb-rt-days">Du mercredi au dimanche soir</p>'
      . '<p class="mdb-rt-hours">Dès 18:00</p>'
      . '<p class="mdb-rt-dress">Tenue élégante</p>';

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/roof-106766-herohours-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$changed = 0;
$walk = function (&$els) use (&$walk, $W, $HTML, &$changed) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $W && ($el['widgetType'] ?? '') === 'text-editor') {
            echo "  text-editor {$W}: ancien = " . mb_substr($el['settings']['editor'] ?? '', 0, 60) . "...\n";
            $el['settings']['editor'] = $HTML;
            // on retire l'alignement centré / tailles héritées : le CSS gère tout
            unset($el['settings']['align'], $el['settings']['typography_font_size'], $el['settings']['typography_font_size_mobile']);
            echo "  text-editor {$W}: -> nouveau bloc horaires\n";
            $changed++;
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
