<?php
/**
 * Rebuild the home hero (page 106136) inner container 57e6219 to the concept
 * layout: eyebrow / big title / italic lede / gold CTA + secondary event link.
 *
 * Styling is driven by scoped CSS in laurent-mdb (classes mdb-hero__*); the
 * Elementor widgets only carry content + the css class + the link. Container
 * settings (background video, etc.) are left untouched.
 *
 * DRY RUN by default; set MDB_APPLY=1 to write.
 * Run: wp eval-file rebuild-hero.php
 */

$APPLY = getenv('MDB_APPLY') === '1';
$PID = 106136;

global $wpdb;
$raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_elementor_data' AND post_id=%d LIMIT 1", $PID));
$data = json_decode($raw, true);
if (!is_array($data)) { echo "ERROR: could not decode _elementor_data\n"; return; }

// Minimal heading widget: content + tag + our css class. Look comes from CSS.
function mdb_heading($id, $tag, $title, $cls) {
    return array(
        'id' => $id,
        'elType' => 'widget',
        'widgetType' => 'heading',
        'settings' => array(
            'title' => $title,
            'header_size' => $tag,
            'align' => 'left',
            '_css_classes' => $cls,
        ),
        'elements' => array(),
    );
}
// Minimal button widget: text + link + our css class.
function mdb_button($id, $text, $url, $cls, $external = false) {
    $link = array('url' => $url, 'is_external' => $external ? 'on' : '', 'nofollow' => '');
    return array(
        'id' => $id,
        'elType' => 'widget',
        'widgetType' => 'button',
        'settings' => array(
            'text' => $text,
            'link' => $link,
            'button_type' => '',
            '_css_classes' => $cls,
        ),
        'elements' => array(),
    );
}

// New IDs (8 hex chars, Elementor style). Keep distinct from existing.
$new_children = array(
    mdb_heading('a1b2c301', 'h2', 'Institution gastronomique face à la mer, depuis 1948', 'mdb-hero__eyebrow'),
    mdb_heading('a1b2c302', 'h1', 'Maison <em>de Bacon</em>', 'mdb-hero__title'),
    mdb_heading('a1b2c303', 'h2', 'Cuisine marine du Chef <em>Nicolas Davouze</em>', 'mdb-hero__lede'),
    mdb_button('a1b2c304', 'Réserver une table', 'https://bookings.zenchef.com/results?rid=354476&pid=1001', 'mdb-hero__cta', true),
    mdb_button('a1b2c305', 'Prochains événements', '#EVENTS', 'mdb-hero__link'),
);

// Walk to find inner container 57e6219 and the hero container 167c86ed.
$replaced = false; $tagged = false;
$walk = function(&$els) use (&$walk, &$replaced, &$tagged, $new_children) {
    foreach ($els as &$el) {
        if (!is_array($el)) continue;
        if (($el['id'] ?? '') === '167c86ed') {
            // add mdb-hero class to the hero container for CSS scoping
            $cur = $el['settings']['_css_classes'] ?? '';
            if (strpos($cur, 'mdb-hero') === false) {
                $el['settings']['_css_classes'] = trim($cur . ' mdb-hero');
            }
            $tagged = true;
        }
        if (($el['id'] ?? '') === '57e6219') {
            $el['elements'] = $new_children;
            // tag inner container too, for layout control
            $cur = $el['settings']['_css_classes'] ?? '';
            if (strpos($cur, 'mdb-hero__inner') === false) {
                $el['settings']['_css_classes'] = trim($cur . ' mdb-hero__inner');
            }
            $replaced = true;
        }
        if (isset($el['elements']) && is_array($el['elements'])) $walk($el['elements']);
    }
    unset($el);
};
$walk($data);

echo ($APPLY ? "APPLIED\n" : "DRY RUN (no writes)\n");
echo "hero container tagged mdb-hero: " . ($tagged ? 'yes' : 'NO') . "\n";
echo "inner container children replaced: " . ($replaced ? 'yes' : 'NO') . "\n";

if ($replaced && $tagged) {
    echo "--- new hero inner tree ---\n";
    foreach ($new_children as $c) {
        $label = $c['settings']['title'] ?? $c['settings']['text'] ?? '';
        echo sprintf("  %-8s %-18s %s\n", $c['widgetType'], $c['settings']['_css_classes'], $label);
    }
    if ($APPLY) {
        $encoded = wp_slash(wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        update_post_meta($PID, '_elementor_data', $encoded);
        echo "written to post {$PID}\n";
    }
} else {
    echo "ABORT: did not find both containers; no write.\n";
}
