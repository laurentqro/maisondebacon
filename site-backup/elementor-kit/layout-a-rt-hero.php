<?php
/* RT hero → Layout A.
 *  Page 106766, grid 34da85ec. Operations :
 *    - KEEP rthereye (eyebrow), 34a8c93 (orange RT logo), ad86369 (CTA)
 *    - REMOVE bfabdc5 (hidden h1), 7488ca8 (multi-line text), 454470c (phone btn), rtherelnk (voir la carte)
 *    - INSERT rtheroinfo (heading info bar « Du mercredi au dimanche · dès 18h · Tenue élégante ») AFTER the logo
 *    - RENAME ad86369 CTA text → "Réserver une table en ligne"
 *    - INSERT rtherocall ("ou appelez …") AFTER CTA
 *  DRY ; MDB_APPLY=1. */

$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106766;
$GRID  = '34da85ec';
$PHONE = '04 93 61 50 02';
$TEL   = '+33-4-93-61-50-02';
$REMOVE = array('bfabdc5', '7488ca8', '454470c', 'rtherelnk');

global $wpdb;
@mkdir('/tmp/mdb-evfix', 0775, true);
$ts = date('Ymd-His');
$raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
    $POST, '_elementor_data'
));
$tree = json_decode($raw, true);
if (!is_array($tree)) { echo "ABORT bad json\n"; return; }
file_put_contents("/tmp/mdb-evfix/layoutA-rt-pre-{$ts}.json", $raw);
echo "backup -> /tmp/mdb-evfix/layoutA-rt-pre-{$ts}.json (" . strlen($raw) . " bytes)\n";

$info_widget = array(
    'id'         => 'rtheroinfo',
    'elType'     => 'widget',
    'widgetType' => 'heading',
    'settings'   => array(
        'title'        => 'Du mercredi au dimanche · dès 18h',
        'header_size'  => 'h2',
        '_css_classes' => 'mdb-hero__info mdb-hero__info--rt',
    ),
    'elements'   => array(),
    'isInner'    => false,
);

$call_html = sprintf(
    '<a class="mdb-hero__call-link" href="tel:%s">ou appeler le <span class="num">%s</span></a>',
    esc_attr($TEL), esc_html($PHONE)
);
$call_widget = array(
    'id'         => 'rtherocall',
    'elType'     => 'widget',
    'widgetType' => 'html',
    'settings'   => array(
        'html'         => $call_html,
        '_css_classes' => 'mdb-hero__call mdb-hero__call--rt',
    ),
    'elements'   => array(),
    'isInner'    => false,
);

$cta_row = array(
    'id'         => 'rtherorow',
    'elType'     => 'container',
    'settings'   => array(
        'content_width' => 'full',
        'flex_direction' => 'row',
        'flex_wrap' => 'wrap',
        'flex_align_items' => 'center',
        'flex_gap' => array('column' => '18', 'row' => '12', 'unit' => 'px', 'isLinked' => false),
        '_css_classes' => 'mdb-hero__ctarow mdb-hero__ctarow--rt',
    ),
    'elements'   => array(),
    'isInner'    => false,
);

$removed = 0; $renamed = 0; $inserted = 0;
$walk = function (&$els) use (&$walk, $GRID, $REMOVE, $info_widget, $call_widget, $cta_row, &$removed, &$renamed, &$inserted) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $GRID) {
            $children = $el['elements'] ?? array();
            $out = array();
            $cta_handle = null;
            foreach ($children as $c) {
                $cid = $c['id'] ?? '';
                if (in_array($cid, $REMOVE, true)) {
                    echo "  - remove {$cid} (" . ($c['widgetType'] ?? $c['elType'] ?? '?') . ")\n";
                    $removed++;
                    continue;
                }
                // Drop existing rtherorow / rtheroinfo / rtherocall — we'll re-add fresh.
                if (in_array($cid, array('rtherorow', 'rtheroinfo', 'rtherocall'), true)) {
                    echo "  - remove (will re-add) {$cid}\n";
                    $removed++;
                    continue;
                }
                // Rename CTA + normalize classes — held aside for row wrap.
                if ($cid === 'ad86369') {
                    $c['settings']['text'] = 'Réserver une table en ligne';
                    $c['settings']['_css_classes'] = trim(($c['settings']['_css_classes'] ?? '') . ' mdb-hero__cta mdb-hero__cta--rt');
                    $c['settings']['_css_classes'] = implode(' ', array_unique(preg_split('/\s+/', trim($c['settings']['_css_classes']))));
                    echo "  ~ rename ad86369 text → 'Réserver une table en ligne'\n";
                    $renamed++;
                    $cta_handle = $c;
                    continue;
                }
                $out[] = $c;
                // After logo (34a8c93) → insert info bar.
                if ($cid === '34a8c93') {
                    $out[] = $info_widget;
                    echo "  + insert rtheroinfo after 34a8c93\n";
                    $inserted++;
                }
            }
            if ($cta_handle) {
                $row = $cta_row;
                $row['elements'] = array($cta_handle, $call_widget);
                $out[] = $row;
                echo "  + insert rtherorow [ad86369 CTA + rtherocall]\n";
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
