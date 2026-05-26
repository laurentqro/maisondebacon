<?php
/**
 * READ-ONLY: locate which post's _elementor_data contains the hero container
 * (element id 167c86ed) so we know what to edit. Also report the front page id.
 */
global $wpdb;

$front = get_option('page_on_front');
$show  = get_option('show_on_front');
echo "show_on_front={$show} page_on_front={$front}\n";

$rows = $wpdb->get_results(
    "SELECT pm.post_id, p.post_type, p.post_status, p.post_title
       FROM {$wpdb->postmeta} pm
       JOIN {$wpdb->posts} p ON p.ID = pm.post_id
      WHERE pm.meta_key='_elementor_data'
        AND pm.meta_value LIKE '%167c86ed%'
        AND p.post_status='publish'
        AND p.post_type NOT IN ('revision')"
);
echo "Posts containing hero id 167c86ed:\n";
foreach ($rows as $r) {
    echo sprintf("  %-7d %-18s %s\n", $r->post_id, $r->post_type, $r->post_title);
}

// Dump the hero container element from the first matching post for inspection.
if (!empty($rows)) {
    $pid = $rows[0]->post_id;
    $raw = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_elementor_data' AND post_id=%d LIMIT 1", $pid));
    $data = json_decode($raw, true);

    $found = null;
    $finder = function($els) use (&$finder, &$found) {
        foreach ($els as $el) {
            if (!is_array($el)) continue;
            if (($el['id'] ?? '') === '167c86ed') { $found = $el; return true; }
            if (isset($el['elements']) && $finder($el['elements'])) return true;
        }
        return false;
    };
    $finder($data);
    if ($found) {
        echo "\n=== hero container 167c86ed (from post {$pid}) ===\n";
        // settings of the container (bg video/image, height etc.)
        echo "--- container settings keys ---\n";
        foreach (($found['settings'] ?? array()) as $k=>$v) {
            $val = is_scalar($v) ? (string)$v : json_encode($v);
            echo sprintf("  %-34s %s\n", $k, mb_substr($val,0,90));
        }
        // summarize child widgets
        echo "--- child widget tree (type :: id :: text snippet) ---\n";
        $walk = function($els, $depth) use (&$walk) {
            foreach ($els as $el) {
                if (!is_array($el)) continue;
                $type = $el['widgetType'] ?? $el['elType'] ?? '?';
                $id = $el['id'] ?? '?';
                $s = $el['settings'] ?? array();
                $txt = $s['title'] ?? $s['editor'] ?? $s['text'] ?? '';
                if (is_array($txt)) $txt='';
                $txt = trim(strip_tags((string)$txt));
                echo str_repeat('  ', $depth) . sprintf("%-16s %-10s %s\n", $type, $id, mb_substr($txt,0,46));
                if (isset($el['elements'])) $walk($el['elements'], $depth+1);
            }
        };
        $walk($found['elements'] ?? array(), 1);
    } else {
        echo "hero container not found in tree (id may be nested differently)\n";
    }
}
