<?php
/* Debug : Elementor résout-il bien le header RT (110554) sur la page RT (106766) ?
 * Lecture seule. */

$post = get_post(106766);
if (!$post) { echo "ABORT: page 106766 introuvable\n"; return; }
$GLOBALS['post'] = $post;
query_posts(array('page_id' => 106766));

echo "is_page('le-rooftop-club-bacon') ? " . (is_page('le-rooftop-club-bacon') ? 'yes' : 'no') . "\n";
echo "is_page(106766) ? " . (is_page(106766) ? 'yes' : 'no') . "\n";

if (!class_exists('ElementorPro\\Modules\\ThemeBuilder\\Module')) {
    echo "ABORT: ElementorPro ThemeBuilder Module non chargé\n";
    return;
}

$mod = ElementorPro\Modules\ThemeBuilder\Module::instance();
$loc_mgr = $mod->get_locations_manager();

$header_templates = $loc_mgr->get_documents_for_location('header');
echo "documents_for_location('header') = " . wp_json_encode(array_keys($header_templates ?: array())) . "\n";

$cond_mgr = $mod->get_conditions_manager();
$main = $cond_mgr->get_theme_templates_ids('header');
echo "get_theme_templates_ids('header') = " . wp_json_encode($main) . "\n";

// Comparer les conditions stockées vs ce qu'Elementor pense.
$opt = get_option('elementor_pro_theme_builder_conditions');
echo "option header.* = " . wp_json_encode($opt['header'] ?? null) . "\n";

// Conditions par template selon le manager.
foreach (array(110538, 110554) as $tid) {
    $conds_meta = get_post_meta($tid, '_elementor_conditions', true);
    echo "tmpl {$tid} _elementor_conditions postmeta = " . wp_json_encode($conds_meta) . "\n";
}
