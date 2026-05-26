<?php
/**
 * Remove the image-carousel widget (id 9a5112d) from the events section on the
 * home (106136). It's a 15-photo swiper inside section 337ce1e that the client
 * wants gone. DRY RUN by default; MDB_APPLY=1 to write. Backs up data first.
 */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106136;
$TARGET = '9a5112d';

$data = get_post_meta($POST, '_elementor_data', true);
if (!$data) { echo "NO _elementor_data\n"; exit; }
$raw = is_string($data) ? $data : wp_json_encode($data);
$bak = '/tmp/mdb-evfix/elementor-data-106136-carousel-' . date('Ymd-His') . '.json';
@file_put_contents($bak, $raw);
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;
if (!is_array($tree)) { echo "decode failed\n"; exit; }

$removed = 0;
function prune(&$els, $target, &$removed) {
    foreach ($els as $k => &$el) {
        if (($el['id'] ?? '') === $target) {
            echo "  removing id={$target} widget=" . ($el['widgetType'] ?? '?') . "\n";
            unset($els[$k]);
            $removed++;
            continue;
        }
        if (!empty($el['elements']) && is_array($el['elements'])) {
            prune($el['elements'], $target, $removed);
        }
    }
    unset($el);
    $els = array_values($els); // reindex
}
prune($tree, $TARGET, $removed);
echo "removed: {$removed}\n";

if ($APPLY && $removed) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ok = update_post_meta($POST, '_elementor_data', wp_slash($json));
    echo "update_post_meta -> " . var_export($ok, true) . "\n";
} else {
    echo "(dry run)\n";
}
