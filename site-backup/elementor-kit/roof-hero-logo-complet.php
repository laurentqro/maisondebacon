<?php
/* Page « Le Roof Top » (106766), image hero (widget 34a8c93) :
 * remplace le logo simple « ss-blason » (att. 110396) par le logo complet
 * « RT-maisondebacon-complet_orange » (att. 106793 : blason TR + LE ROOF TOP
 * + « par la Maison de Bacon »). DRY par défaut ; MDB_APPLY=1.
 * Backup JSON dans /tmp/mdb-evfix/. IDs prod à réadapter. */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106766;
$IMG_W = '34a8c93';
$NEW_ID = 106793;

$new_url = wp_get_attachment_image_url($NEW_ID, 'full');
if (!$new_url) { echo "!! attachment {$NEW_ID} introuvable\n"; return; }

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/roof-106766-herologo-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$changed = 0;
$walk = function (&$els) use (&$walk, $IMG_W, $NEW_ID, $new_url, &$changed) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $IMG_W && ($el['widgetType'] ?? '') === 'image') {
            $old = $el['settings']['image']['id'] ?? '?';
            $el['settings']['image'] = [
                'url'    => $new_url,
                'id'     => $NEW_ID,
                'size'   => '',
                'alt'    => 'Le Roof Top par la Maison de Bacon',
                'source' => 'library',
            ];
            echo "  image {$IMG_W}: att {$old} -> {$NEW_ID}\n        {$new_url}\n";
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
