<?php
/**
 * Remove the promo/blog-posts section (top-level container a8f323f) from the
 * home (106136). CSR + recruitment now live in the redesigned footer instead.
 * DRY RUN by default; MDB_APPLY=1.
 */
$APPLY = getenv('MDB_APPLY') === '1';
$POST = 106136;
$TARGET = 'a8f323f';

$data = get_post_meta($POST, '_elementor_data', true);
$raw = is_string($data) ? $data : wp_json_encode($data);
$bak = '/tmp/mdb-evfix/home-106136-promo-' . date('Ymd-His') . '.json';
@file_put_contents($bak, $raw);
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;
$removed = 0;
function prune(&$els, $target, &$removed) {
    foreach ($els as $k => &$el) {
        if (($el['id'] ?? '') === $target) {
            echo "  removing id={$target} (" . ($el['elType'] ?? '?') . ")\n";
            unset($els[$k]); $removed++; continue;
        }
        if (!empty($el['elements'])) prune($el['elements'], $target, $removed);
    }
    unset($el);
    $els = array_values($els);
}
prune($tree, $TARGET, $removed);
echo "removed: {$removed}\n";
if ($APPLY && $removed) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else echo "(dry run)\n";
