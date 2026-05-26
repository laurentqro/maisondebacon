<?php
$POST = 106136;
$IMG = '603f6b34';
$data = get_post_meta($POST, '_elementor_data', true);
$tree = is_string($data) ? json_decode($data, true) : $data;
$walk = function (&$els) use (&$walk, $IMG) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $IMG) {
            $el['settings']['width'] = array('unit' => '%', 'size' => 100, 'sizes' => array());
            echo "set {$IMG} width 100%\n";
        }
        if (!empty($el['elements'])) $walk($el['elements']);
    }
    unset($el);
};
$walk($tree);
$json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
