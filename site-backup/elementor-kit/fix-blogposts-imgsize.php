<?php
/**
 * Bump the ElementsKit Blog Posts widget (id 62c48db) featured-image size from
 * 'thumbnail' (150x150, blurry) to 'medium_large' (768px) so the promo cards
 * on the home (106136) load a crisp image. DRY RUN by default; MDB_APPLY=1.
 */
$APPLY = getenv('MDB_APPLY') === '1';
$POST = 106136;
$WIDGET = '62c48db';
$KEY = 'ekit_blog_posts_feature_img_size_size';
$NEW = 'medium_large';

$data = get_post_meta($POST, '_elementor_data', true);
$raw = is_string($data) ? $data : wp_json_encode($data);
$bak = '/tmp/mdb-evfix/elementor-data-106136-imgsize-' . date('Ymd-His') . '.json';
@file_put_contents($bak, $raw);
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;
$found = 0;
function walk(&$els, $w, $k, $new, &$found) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $w) {
            $old = $el['settings'][$k] ?? '(unset)';
            echo "  id={$w} {$k}: {$old} -> {$new}\n";
            $el['settings'][$k] = $new;
            $found++;
        }
        if (!empty($el['elements'])) walk($el['elements'], $w, $k, $new, $found);
    }
}
walk($tree, $WIDGET, $KEY, $NEW, $found);
echo "matched: {$found}\n";
if ($APPLY && $found) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else echo "(dry run)\n";
