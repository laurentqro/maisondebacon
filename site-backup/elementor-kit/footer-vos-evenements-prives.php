<?php
/* Footer (template Elementor 108363), colonne « Vos Événements » :
 *  - eyebrow heading 8f4441e : « Vos Événements » -> « Vos Événements Privés »
 *  - liste de liens 54c71db (text-editor) : ajout d'un lien
 *    « Le Roof Top Club Bacon » -> /le-rooftop-club-bacon/ (en fin de liste).
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/. IDs prod à réadapter. */
$APPLY    = getenv('MDB_APPLY') === '1';
$POST     = 108363;
$EYEBROW  = '8f4441e';
$LINKS    = '54c71db';
$NEW_EYE  = 'Événements Privés';
$NEW_LI   = '<li><a href="https://staging.maisondebacon.fr/le-rooftop-club-bacon/">Le Roof Top Club Bacon</a></li>';

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/footer-108363-vosevtprives-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$changed = 0;
$walk = function (&$els) use (&$walk, $EYEBROW, $LINKS, $NEW_EYE, $NEW_LI, &$changed) {
    foreach ($els as &$el) {
        $id = $el['id'] ?? '';
        if ($id === $EYEBROW && ($el['widgetType'] ?? '') === 'heading') {
            echo "  eyebrow {$EYEBROW}: '" . ($el['settings']['title'] ?? '') . "' -> '{$NEW_EYE}'\n";
            $el['settings']['title'] = $NEW_EYE;
            $changed++;
        }
        if ($id === $LINKS && ($el['widgetType'] ?? '') === 'text-editor') {
            $html = $el['settings']['editor'] ?? '';
            if (strpos($html, '/le-rooftop-club-bacon/') !== false) {
                echo "  links {$LINKS}: Roof Top link already present, skip\n";
            } elseif (strpos($html, '</ul>') !== false) {
                $el['settings']['editor'] = preg_replace('#</ul>#', $NEW_LI . '</ul>', $html, 1);
                echo "  links {$LINKS}: appended Roof Top link\n";
                $changed++;
            } else {
                echo "  links {$LINKS}: no </ul> found, skip\n";
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
