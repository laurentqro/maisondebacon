<?php
/* Debug v2 — simuler la résolution sur la page 106766 puis appeler le manager.
 * Lecture seule. */

// Forcer l'environnement « on est sur la page 106766 ».
$_GET = array();
global $wp_query;
$wp_query = new WP_Query(array('page_id' => 106766));
$wp_query->is_singular = true;
$wp_query->is_page = true;
$GLOBALS['wp_query'] = $wp_query;
$GLOBALS['post'] = get_post(106766);
setup_postdata($GLOBALS['post']);

echo "is_page() = " . (is_page() ? 'yes' : 'no') . "\n";
echo "queried object id = " . (get_queried_object_id()) . "\n";

if (!class_exists('ElementorPro\\Modules\\ThemeBuilder\\Module')) {
    echo "ABORT: ThemeBuilder module non chargé\n";
    return;
}

$mod = ElementorPro\Modules\ThemeBuilder\Module::instance();
$cond_mgr = $mod->get_conditions_manager();

// Méthode publique de Elementor Pro : récupère les IDs de templates pour la location courante.
$ids = $cond_mgr->get_documents_for_location('header');
echo "conditions_manager->get_documents_for_location('header') = " . wp_json_encode(array_keys($ids ?: array())) . "\n";

// Par template, demander si la condition s'applique au contexte courant.
foreach (array(110538, 110554) as $tid) {
    $conds = get_post_meta($tid, '_elementor_conditions', true);
    if (!is_array($conds)) $conds = array();
    echo "tmpl {$tid} conditions = " . wp_json_encode($conds) . "\n";
    foreach ($conds as $c) {
        // Format: include|exclude/comparison/sub_id/sub_name
        $parts = explode('/', $c);
        echo "  parsed: " . wp_json_encode($parts) . "\n";
    }
}

// Y a-t-il un cache interne au manager ?
$reflection = new ReflectionClass($cond_mgr);
foreach ($reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED) as $p) {
    $p->setAccessible(true);
    $v = $p->getValue($cond_mgr);
    echo "  prop " . $p->getName() . " (" . gettype($v) . ") = ";
    if (is_array($v)) echo wp_json_encode($v);
    else echo (is_object($v) ? '<' . get_class($v) . '>' : (string) $v);
    echo "\n";
}
