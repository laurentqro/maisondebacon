<?php
/* Home (106136), section « Nos Univers » : nomenclature simplifiée des titres de
 * cartes (demande client) :
 *   265c21d5 : « Restaurant de Bacon »      -> « Restaurant »
 *   728a3929 : « L'Appartement de Victor »  -> « Appartement Victor »
 *   5ba5eec4 : « Le Roof Top »              -> inchangé (déjà bon)
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106136;
$MAP = [
    '265c21d5' => 'Restaurant',
    '728a3929' => 'Appartement Victor',
];

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/home-106136-univers-labels-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$changed = 0;
$walk = function (&$els) use (&$walk, $MAP, &$changed) {
    foreach ($els as &$el) {
        $id = $el['id'] ?? '';
        if (isset($MAP[$id]) && ($el['widgetType'] ?? '') === 'heading') {
            $old = $el['settings']['title'] ?? '';
            $el['settings']['title'] = $MAP[$id];
            echo "  heading {$id}: '{$old}' -> '{$MAP[$id]}'\n";
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
