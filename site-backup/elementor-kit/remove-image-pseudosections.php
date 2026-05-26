<?php
/**
 * Remove the two full-width image pseudo-sections on the home (106136) between
 * the events section and "Nos Univers" :
 *   535de6f4  -> bg photo terrasse (240528-Maison-de-Bacon-0699-Cedou)
 *   5e6ef985  -> bg photo aquarium (PHOTO-2024-04-30-12-17-06-1)
 * Both are empty containers existing only to display a background image.
 * DRY RUN by default; MDB_APPLY=1.
 */
$APPLY = getenv('MDB_APPLY') === '1';
$POST = 106136;
// 535de6f4 / 5e6ef985 = image pseudo-sections (déjà retirées) ;
// 36b4ff0 = section spacer vide (bande blanche) ; 3281ff98 = heading vide caché.
$TARGETS = array('535de6f4', '5e6ef985', '36b4ff0', '3281ff98');

$data = get_post_meta($POST, '_elementor_data', true);
$raw = is_string($data) ? $data : wp_json_encode($data);
$bak = '/tmp/mdb-evfix/home-106136-pseudo-' . date('Ymd-His') . '.json';
@file_put_contents($bak, $raw);
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;
$removed = 0;
function prune(&$els, $targets, &$removed) {
    foreach ($els as $k => &$el) {
        if (in_array($el['id'] ?? '', $targets, true)) {
            echo "  removing id=" . $el['id'] . " (" . ($el['elType'] ?? '?') . ")\n";
            unset($els[$k]); $removed++; continue;
        }
        if (!empty($el['elements'])) prune($el['elements'], $targets, $removed);
    }
    unset($el);
    $els = array_values($els);
}
prune($tree, $TARGETS, $removed);
echo "removed: {$removed}\n";
if ($APPLY && $removed) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else echo "(dry run)\n";
