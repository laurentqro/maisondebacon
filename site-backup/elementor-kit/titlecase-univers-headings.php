<?php
/**
 * Title-case des 3 titres de cartes « Nos Univers » (home 106136).
 * Les libellés sont stockés EN CAPITALES dans _elementor_data, ce qui force
 * un rendu tout-capitales malgré le CSS. On passe au casse-titre éditorial
 * (Cormorant), cohérent avec le H2 « Nos Univers » et le footer.
 *   265c21d5  RESTAURANT DE BACON      -> Restaurant de Bacon
 *   728a3929  L'APPARTEMENT DE VICTOR  -> L'Appartement de Victor
 *   5ba5eec4  ROOF TOP CLUB BACON      -> Roof Top Club Bacon
 * DRY RUN par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/. IDs à réadapter en prod.
 */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106136;

$TITLES = array(
    '265c21d5' => 'Restaurant de Bacon',
    '728a3929' => "L'Appartement de Victor",
    '5ba5eec4' => 'Roof Top Club Bacon',
);

$data = get_post_meta($POST, '_elementor_data', true);
$raw  = is_string($data) ? $data : wp_json_encode($data);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/home-106136-titlecase-' . date('Ymd-His') . '.json';
@file_put_contents($bak, $raw);
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;
$changed = 0;

$walk = function (&$els) use (&$walk, $TITLES, &$changed) {
    foreach ($els as &$el) {
        $id = $el['id'] ?? '';
        if (isset($TITLES[$id]) && ($el['widgetType'] ?? '') === 'heading') {
            echo "  set heading {$id}: " . json_encode($el['settings']['title'] ?? null, JSON_UNESCAPED_UNICODE)
                . " -> " . $TITLES[$id] . "\n";
            $el['settings']['title'] = $TITLES[$id];
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
