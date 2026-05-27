<?php
/* Page « Le Roof Top » (106766), rangée des 3 boutons sous le hero (container
 * 34e7f20). Mise à jour libellés + liens (demande client) :
 *   52affe4 : « RESTAURANT »     -> « Carte Restaurant »  -> /notre-carte/
 *   4e3ba5d : « CARTE COCKTAILS »-> « Carte Cocktails »   -> PDF cocktails 2026
 *   a5cd0ef : « CARTE METS »     -> « Carte Champagnes »  -> PDF champagnes 2026
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. IDs prod à réadapter. */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106766;
$BASE  = 'https://staging.maisondebacon.fr';
$MAP = [
    '52affe4' => ['text' => 'Carte Restaurant',  'url' => $BASE . '/notre-carte/'],
    '4e3ba5d' => ['text' => 'Carte Cocktails',   'url' => $BASE . '/wp-content/uploads/2026/05/MENU-ROOFTOP-FRANCAIS-COCKTAILS-20-5-26.pdf'],
    'a5cd0ef' => ['text' => 'Carte Champagnes',  'url' => $BASE . '/wp-content/uploads/2026/05/VINS-CHAMPAGNES-MENU-ROOFTOP-20-05-26.pdf'],
];

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/roof-106766-buttons-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$changed = 0;
$walk = function (&$els) use (&$walk, $MAP, &$changed) {
    foreach ($els as &$el) {
        $id = $el['id'] ?? '';
        if (isset($MAP[$id]) && ($el['widgetType'] ?? '') === 'button') {
            $old = ($el['settings']['text'] ?? '') . ' -> ' . ($el['settings']['link']['url'] ?? '');
            $el['settings']['text'] = $MAP[$id]['text'];
            if (!isset($el['settings']['link']) || !is_array($el['settings']['link'])) {
                $el['settings']['link'] = ['url' => '', 'is_external' => '', 'nofollow' => '', 'custom_attributes' => ''];
            }
            $el['settings']['link']['url'] = $MAP[$id]['url'];
            echo "  btn {$id}: {$old}\n        -> {$MAP[$id]['text']} -> {$MAP[$id]['url']}\n";
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
