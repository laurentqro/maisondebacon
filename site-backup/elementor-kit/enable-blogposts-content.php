<?php
/**
 * Enable the post content/excerpt in the ElementsKit Blog Posts widget
 * (id 62c48db) on the home (106136). It was explicitly disabled
 * (ekit_blog_posts_content = '') leaving an empty card body. Set it to 'yes'
 * (full content, no word trim) so the article text shows under the title.
 * DRY RUN by default; MDB_APPLY=1.
 */
$APPLY = getenv('MDB_APPLY') === '1';
$POST = 106136;
$WIDGET = '62c48db';

$data = get_post_meta($POST, '_elementor_data', true);
$raw = is_string($data) ? $data : wp_json_encode($data);
$bak = '/tmp/mdb-evfix/elementor-data-106136-content-' . date('Ymd-His') . '.json';
@file_put_contents($bak, $raw);
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;
$found = 0;
function walk(&$els, $w, &$found) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $w) {
            $old = $el['settings']['ekit_blog_posts_content'] ?? '(unset)';
            echo "  id={$w} ekit_blog_posts_content: '{$old}' -> 'yes'\n";
            $el['settings']['ekit_blog_posts_content'] = 'yes';
            // leave content_trim empty = full content
            if (isset($el['settings']['ekit_blog_posts_content_trim'])) {
                echo "  (content_trim currently = '" . $el['settings']['ekit_blog_posts_content_trim'] . "')\n";
            }
            $found++;
        }
        if (!empty($el['elements'])) walk($el['elements'], $w, $found);
    }
}
walk($tree, $WIDGET, $found);
echo "matched: {$found}\n";
if ($APPLY && $found) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else echo "(dry run)\n";
