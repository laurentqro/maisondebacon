<?php
/* Footer (template Elementor 108363), colonne « La Maison » (text-editor 0bfda46) :
 *  - lien « Roof Top Club » (/le-rooftop-club-bacon/) -> « Le Roof Top » (/le-roof-top/).
 * Remplacement ciblé du seul <li> concerné (pas de touche aux autres liens, ni à
 * l'autre colonne qui garde « Le Roof Top Club Bacon » -> /le-rooftop-club-bacon/).
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/. IDs prod à réadapter. */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 108363;
$LINKS = '0bfda46';
$OLD_LI = '<li><a href="https://staging.maisondebacon.fr/le-rooftop-club-bacon/">Roof Top Club</a></li>';
$NEW_LI = '<li><a href="https://staging.maisondebacon.fr/le-roof-top/">Le Roof Top</a></li>';

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/footer-108363-lamaison-rt-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$changed = 0;
$walk = function (&$els) use (&$walk, $LINKS, $OLD_LI, $NEW_LI, &$changed) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $LINKS && ($el['widgetType'] ?? '') === 'text-editor') {
            $html = $el['settings']['editor'] ?? '';
            if (strpos($html, '/le-roof-top/') !== false) {
                echo "  links {$LINKS}: already updated, skip\n";
            } elseif (strpos($html, $OLD_LI) !== false) {
                $el['settings']['editor'] = str_replace($OLD_LI, $NEW_LI, $html);
                echo "  links {$LINKS}: 'Roof Top Club' -> 'Le Roof Top' (/le-roof-top/)\n";
                $changed++;
            } else {
                echo "  links {$LINKS}: target <li> not found verbatim, skip\n";
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
