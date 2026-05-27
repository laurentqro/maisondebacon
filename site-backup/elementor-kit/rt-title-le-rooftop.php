<?php
/* Titre de la carte Roof Top (heading 5ba5eec4 sur la home 106136) :
 * « Roof Top Club Bacon » -> « Le Roof Top » (demande client, section Nos Univers).
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/. IDs prod à réadapter. */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106136;
$W     = '5ba5eec4';
$NEW   = 'Le Roof Top';

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/home-106136-rttitle-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$changed = 0;
$walk = function (&$els) use (&$walk, $W, $NEW, &$changed) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $W && ($el['widgetType'] ?? '') === 'heading') {
            echo "  heading {$W}: '" . ($el['settings']['title'] ?? '') . "' -> '{$NEW}'\n";
            $el['settings']['title'] = $NEW;
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
