<?php
/* MdB home hero → Layout A.
 *  Container 57e6219 (children of 167c86ed). Operations :
 *    - KEEP a1b2c301 (eyebrow), a1b2c302 (title)
 *    - REMOVE a1b2c303 (italic lede), a1b2c305 (Prochains événements link)
 *    - INSERT new widget mdbheroinfo (heading, hours one-liner) AFTER title
 *    - RENAME a1b2c304 CTA text "Réserver une table" → "Réserver une table en ligne"
 *    - INSERT new widget mdbherocall ("ou appelez …") AFTER CTA
 *  DRY ; MDB_APPLY=1. Backup dans /tmp/mdb-evfix/. */

$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106136;
$CONT  = '57e6219';
$PHONE = '04 93 61 50 02';
$TEL   = '+33-4-93-61-50-02';

global $wpdb;
@mkdir('/tmp/mdb-evfix', 0775, true);
$ts = date('Ymd-His');
$raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
    $POST, '_elementor_data'
));
$tree = json_decode($raw, true);
if (!is_array($tree)) { echo "ABORT bad json\n"; return; }
file_put_contents("/tmp/mdb-evfix/layoutA-home-pre-{$ts}.json", $raw);
echo "backup -> /tmp/mdb-evfix/layoutA-home-pre-{$ts}.json (" . strlen($raw) . " bytes)\n";

/* New widgets — heading info bar + html widget for "ou appelez". */
$info_widget = array(
    'id'         => 'mdbheroinfo',
    'elType'     => 'widget',
    'widgetType' => 'heading',
    'settings'   => array(
        'title'        => 'Ouvert tous les jours · 12h – 14h · 19h – 22h',
        'header_size'  => 'h2',
        '_css_classes' => 'mdb-hero__info',
    ),
    'elements'   => array(),
    'isInner'    => false,
);

$call_html = sprintf(
    '<a class="mdb-hero__call-link" href="tel:%s">ou appeler le <span class="num">%s</span></a>',
    esc_attr($TEL), esc_html($PHONE)
);
$call_widget = array(
    'id'         => 'mdbherocall',
    'elType'     => 'widget',
    'widgetType' => 'html',
    'settings'   => array(
        'html'         => $call_html,
        '_css_classes' => 'mdb-hero__call',
    ),
    'elements'   => array(),
    'isInner'    => false,
);

/* Wrap CTA + call inside a flex-row container so they sit side-by-side
   (parent .e-con-inner is column-flex). */
$cta_row = array(
    'id'         => 'mdbherorow',
    'elType'     => 'container',
    'settings'   => array(
        'content_width' => 'full',
        'flex_direction' => 'row',
        'flex_wrap' => 'wrap',
        'flex_align_items' => 'center',
        'flex_gap' => array('column' => '18', 'row' => '12', 'unit' => 'px', 'isLinked' => false),
        '_css_classes' => 'mdb-hero__ctarow',
    ),
    'elements'   => array(), // populated below
    'isInner'    => false,
);

$removed = 0; $renamed = 0; $inserted = 0;
$walk = function (&$els) use (&$walk, $CONT, $info_widget, $call_widget, $cta_row, &$removed, &$renamed, &$inserted) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $CONT) {
            $children = $el['elements'] ?? array();
            $out = array();
            $cta_handle = null; // hold the CTA widget so we can pair it with call
            foreach ($children as $c) {
                $cid = $c['id'] ?? '';
                // Remove italic lede + Prochains événements link.
                if ($cid === 'a1b2c303' || $cid === 'a1b2c305') {
                    echo "  - remove {$cid} ({$c['widgetType']} : " . ($c['settings']['title'] ?? $c['settings']['text'] ?? '?') . ")\n";
                    $removed++;
                    continue;
                }
                // Drop existing mdbherorow / mdbheroinfo / mdbherocall — we'll re-add fresh.
                if (in_array($cid, array('mdbherorow', 'mdbheroinfo', 'mdbherocall'), true)) {
                    echo "  - remove (will re-add) {$cid}\n";
                    $removed++;
                    continue;
                }
                // Rename CTA + add hero__cta class (already there but normalize).
                if ($cid === 'a1b2c304') {
                    $c['settings']['text'] = 'Réserver une table en ligne';
                    $c['settings']['_css_classes'] = trim(($c['settings']['_css_classes'] ?? '') . ' mdb-hero__cta');
                    $c['settings']['_css_classes'] = implode(' ', array_unique(preg_split('/\s+/', trim($c['settings']['_css_classes']))));
                    echo "  ~ rename a1b2c304 text → 'Réserver une table en ligne'\n";
                    $renamed++;
                    // Don't push directly — save for the row wrap.
                    $cta_handle = $c;
                    continue;
                }
                $out[] = $c;
                // After title (a1b2c302), insert info bar.
                if ($cid === 'a1b2c302') {
                    $out[] = $info_widget;
                    echo "  + insert mdbheroinfo after a1b2c302\n";
                    $inserted++;
                }
            }
            // Append CTA-row (with the CTA inside + call link) at the end.
            if ($cta_handle) {
                $row = $cta_row;
                $row['elements'] = array($cta_handle, $call_widget);
                $out[] = $row;
                echo "  + insert mdbherorow [a1b2c304 CTA + mdbherocall]\n";
                $inserted += 2;
            }
            $el['elements'] = $out;
            return true;
        }
        if (!empty($el['elements']) && $walk($el['elements'])) return true;
    }
    unset($el);
    return false;
};
$walk($tree);
echo "removed={$removed} renamed={$renamed} inserted={$inserted}\n";

if (!$APPLY) { echo "(dry run)\n"; return; }

$json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$ok = update_post_meta($POST, '_elementor_data', wp_slash($json));
echo "update_post_meta -> " . var_export($ok, true) . "\n";
delete_post_meta($POST, '_elementor_element_cache');
echo "element cache nuked\n";
echo "APPLIED\n";
