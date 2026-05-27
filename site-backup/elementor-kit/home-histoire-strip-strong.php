<?php
/* Home (106136), section « Notre histoire », 2e bloc de texte (1ee0af39, issu de
 * l'ancien toggle) : retire les <strong> qui mettaient en gras des phrases
 * entières, pour aligner le style sur le 1er paragraphe (corps de texte normal,
 * emphase uniquement via le lien doré). On conserve le texte et le <a>.
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106136;
$W     = '1ee0af39';

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/home-106136-histoire-strong-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$changed = 0;
$walk = function (&$els) use (&$walk, $W, &$changed) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $W && ($el['widgetType'] ?? '') === 'text-editor') {
            $html = $el['settings']['editor'] ?? '';
            $new  = preg_replace('#</?strong\b[^>]*>#i', '', $html);
            // nettoie d'éventuels doubles espaces hérités
            $new  = preg_replace('/[ \t]{2,}/', ' ', $new);
            if ($new !== $html) {
                $el['settings']['editor'] = $new;
                echo "  text-editor {$W}: <strong> retirés\n";
                $changed++;
            } else {
                echo "  text-editor {$W}: aucun <strong> trouvé\n";
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
