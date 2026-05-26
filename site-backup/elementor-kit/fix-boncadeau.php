<?php
/**
 * Section « Bon Cadeau » (611a8b9a sur la home 106136) — copy + layout.
 *
 *  - text-editor 3b732905 : nouvelle accroche (vend le cadeau, plus de
 *    « sur ce lien »).
 *  - button e1e76d4 : libellé « Offrir un bon cadeau » (au lieu de
 *    « Découvrir »), URL inchangée (plateforme bonkdo.com, externe).
 *
 * Tout le layout (carte « bon cadeau » : double filet or, micro-ligne série,
 * centrage vertical de la colonne texte face à l'image) est géré en CSS dans
 * overrides.css (.elementor-element-7b368fcb / 611a8b9a / 603f6b34).
 * NE PAS toucher à l'alignement de la rangée en DB (flex_align_items) : ça
 * faisait s'effondrer la colonne image.
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/. IDs prod à réadapter.
 */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106136;

$TXT = '3b732905';
$BTN = 'e1e76d4';

$NEW_TEXT = '<p>Offrez un moment d\'exception : un déjeuner face à la Méditerranée, '
          . 'un dîner gastronomique signé Nicolas Davouze, ou un instant au Roof Top. '
          . 'Le bon cadeau Maison de Bacon se décline selon vos envies, pour toutes '
          . 'les occasions.</p>';
$NEW_BTN  = 'Offrir un bon cadeau';

$data = get_post_meta($POST, '_elementor_data', true);
$raw  = is_string($data) ? $data : wp_json_encode($data);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/home-106136-boncadeau-' . date('Ymd-His') . '.json';
@file_put_contents($bak, $raw);
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$changed = 0;

$walk = function (&$els) use (&$walk, $TXT, $BTN, $NEW_TEXT, $NEW_BTN, &$changed) {
    foreach ($els as &$el) {
        $id = $el['id'] ?? '';
        if ($id === $TXT && ($el['widgetType'] ?? '') === 'text-editor') {
            echo "  text {$TXT}: rewritten\n";
            $el['settings']['editor'] = $NEW_TEXT;
            $changed++;
        }
        if ($id === $BTN && ($el['widgetType'] ?? '') === 'button') {
            echo "  button {$BTN}: text -> {$NEW_BTN}\n";
            $el['settings']['text'] = $NEW_BTN;
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
