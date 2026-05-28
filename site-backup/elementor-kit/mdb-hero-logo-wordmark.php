<?php
/* Page 106136 / hero 167c86ed > 57e6219 :
 *  Insère un widget image `mdbherologo` (wordmark SVG « Maison De Bacon »)
 *  juste après le titre heading a1b2c302. Le heading reste en DB mais sera
 *  masqué via CSS (.elementor-element-a1b2c302 { display: none }), même
 *  pattern qu'utilise la page RT (bfabdc5 masqué, logo image 34a8c93 visible).
 *  Idempotent : si mdbherologo existe déjà, no-op.
 *  DRY ; MDB_APPLY=1. */

$APPLY    = getenv('MDB_APPLY') === '1';
$POST     = 106136;
$PARENT   = '57e6219';   // container parent dans le hero
$AFTER_ID = 'a1b2c302';  // on insère après le heading « Maison de Bacon »
$NEW_ID   = 'mdbherologo';
$IMG_ID   = 110558;      // SVG wordmark white

global $wpdb;
$raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
    $POST, '_elementor_data'
));
$tree = json_decode($raw, true);
if (!is_array($tree)) { echo "ABORT bad json\n"; return; }

$backup = '/tmp/mdb-evfix/106136-pre-hero-logo-' . date('Ymd-His') . '.json';
file_put_contents($backup, $raw);
echo "backup -> {$backup}\n";

$img_url = wp_get_attachment_url($IMG_ID);
if (!$img_url) { echo "ABORT attach {$IMG_ID} introuvable\n"; return; }
echo "wordmark -> {$img_url}\n";

$new_widget = array(
    'id'          => $NEW_ID,
    'elType'      => 'widget',
    'widgetType'  => 'image',
    'settings'    => array(
        'image'         => array('id' => $IMG_ID, 'url' => $img_url, 'source' => 'library', 'alt' => 'Maison De Bacon'),
        'image_size'    => 'full',
        '_css_classes'  => 'mdb-hero__logo',
        'align'         => 'left',
    ),
    'elements'    => array(),
);

$inserted = false;
$exists   = false;

$walk = function (&$els) use (&$walk, $PARENT, $AFTER_ID, $NEW_ID, $new_widget, &$inserted, &$exists) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $NEW_ID) { $exists = true; return true; }
        if (($el['id'] ?? '') === $PARENT && !empty($el['elements'])) {
            $children = $el['elements'];
            $rebuilt  = array();
            foreach ($children as $child) {
                if (($child['id'] ?? '') === $NEW_ID) { $exists = true; return true; }
                $rebuilt[] = $child;
                if (($child['id'] ?? '') === $AFTER_ID) {
                    $rebuilt[] = $new_widget;
                    $inserted  = true;
                }
            }
            $el['elements'] = $rebuilt;
            return true;
        }
        if (!empty($el['elements']) && $walk($el['elements'])) return true;
    }
    unset($el);
    return false;
};
$walk($tree);

if ($exists)   { echo "(no-op : mdbherologo déjà présent)\n"; return; }
if (!$inserted){ echo "ABORT : parent {$PARENT} ou after {$AFTER_ID} introuvable\n"; return; }
echo "INSERT ok après {$AFTER_ID} dans {$PARENT}\n";

if (!$APPLY) { echo "(dry run)\n"; return; }

$json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$ok = update_post_meta($POST, '_elementor_data', wp_slash($json));
echo "update_post_meta -> " . var_export($ok, true) . "\n";
delete_post_meta($POST, '_elementor_element_cache');
echo "APPLIED\n";
