<?php
/* Home (106136), section « Notre histoire » (7bacf566) : l'ancien widget TOGGLE
 * 1ee0af39 (« En Savoir Plus » -> texte masqué) est converti en simple
 * text-editor toujours visible (demande client). On préserve le HTML du contenu
 * du toggle ; l'éditrice verra un bloc de texte normal dans Elementor, sans
 * accordéon. DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY  = getenv('MDB_APPLY') === '1';
$POST   = 106136;
$TOGGLE = '1ee0af39';

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/home-106136-histoire-toggle-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$changed = 0;
$walk = function (&$els) use (&$walk, $TOGGLE, &$changed) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $TOGGLE && ($el['widgetType'] ?? '') === 'toggle') {
            // récupère le HTML de tous les onglets, concaténé
            $html = '';
            foreach (($el['settings']['tabs'] ?? []) as $tab) {
                $html .= $tab['tab_content'] ?? '';
            }
            echo "  toggle {$TOGGLE}: " . count($el['settings']['tabs'] ?? []) . " onglet(s), "
                . strlen($html) . " car. -> text-editor\n";
            // remplace par un text-editor, en gardant le même id (stabilité CSS)
            $el['widgetType'] = 'text-editor';
            $el['settings']   = ['editor' => $html];
            unset($el['elements']);
            $el['elements'] = [];
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
