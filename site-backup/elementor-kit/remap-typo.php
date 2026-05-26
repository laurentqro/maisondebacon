<?php
/**
 * Remap per-widget Elementor font families to the concept set, in place,
 * preserving every other typography setting (size, weight, spacing...).
 *
 * Concept: serif display (Cormorant Garamond) for headings/titles,
 * sans body (Inter Tight) for running text, Tenor Sans for eyebrows/labels.
 *
 * Role detection walks the Elementor element tree by widgetType:
 *   - heading widgets, and any element whose source font is a script face,
 *     -> Cormorant Garamond
 *   - text / text-editor / theme-post-content widgets -> Inter Tight
 *   - everything else with a font override -> mapped by font bucket
 *
 * DRY RUN by default. Set env MDB_APPLY=1 to write changes back.
 *
 * Run:  wp eval-file remap-typo.php
 */

$APPLY = getenv('MDB_APPLY') === '1';

$DISPLAY = 'Cormorant Garamond';
$BODY    = 'Inter Tight';
$EYEBROW = 'Tenor Sans';

// Fonts we consider "decorative scripts" -> always display serif.
$SCRIPTS = array(
    'Mrs Saint Delafield', 'Allura', 'Ballet', 'Monsieur La Doulaise',
    'Great Vibes', 'Dancing Script', 'Sacramento', 'Pinyon Script',
);
// Concept fonts already correct -> leave untouched.
$KEEP = array('Cormorant Garamond', 'Inter Tight', 'Tenor Sans');

// Heading-like widget types get the display serif.
$HEADING_WIDGETS = array(
    'heading', 'theme-page-title', 'theme-post-title', 'animated-headline',
);
// Body/running-text widget types get the body sans.
$BODY_WIDGETS = array(
    'text-editor', 'theme-post-content', 'text-path', 'blockquote',
);
// Label-like widgets (buttons) get the eyebrow face, matching the concept's
// uppercase letterspaced CTA labels.
$LABEL_WIDGETS = array('button');

global $wpdb;
$rows = $wpdb->get_results(
    "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data'"
);

$stats = array(
    'posts_total'    => count($rows),
    'posts_changed'  => 0,
    'families_changed' => 0,
    'map' => array(), // "Old => New" => count
);

/**
 * Recursively walk Elementor elements. $ctx is the nearest widgetType seen,
 * used to decide heading vs body when a font override appears.
 */
function mdb_walk(&$elements, $ctx, &$stats, $DISPLAY, $BODY, $EYEBROW, $SCRIPTS, $KEEP, $HEADING_WIDGETS, $BODY_WIDGETS, $LABEL_WIDGETS) {
    if (!is_array($elements)) return;
    foreach ($elements as &$el) {
        if (!is_array($el)) continue;
        $wt = isset($el['widgetType']) ? $el['widgetType'] : $ctx;

        if (isset($el['settings']) && is_array($el['settings'])) {
            foreach ($el['settings'] as $k => &$val) {
                // Only the base family key carries a font name; responsive
                // variants (font_family_tablet etc.) don't exist in Elementor.
                if (substr($k, -strlen('typography_font_family')) === 'typography_font_family' && is_string($val) && $val !== '') {
                    $old = $val;
                    if (in_array($old, $KEEP, true)) continue;

                    $isScript  = in_array($old, $SCRIPTS, true);
                    $isHeading = in_array($wt, $HEADING_WIDGETS, true);
                    $isBody    = in_array($wt, $BODY_WIDGETS, true);
                    $isLabel   = in_array($wt, $LABEL_WIDGETS, true);

                    if ($isScript || $isHeading) {
                        $new = $DISPLAY;
                    } elseif ($isLabel) {
                        $new = $EYEBROW;
                    } elseif ($isBody) {
                        $new = $BODY;
                    } else {
                        // Unknown widget context: bucket by the font itself.
                        // Sans-ish defaults -> body; otherwise display.
                        $sans = array('Josefin Sans','Roboto','Arial','Baloo 2','Helvetica','Open Sans','Lato','Montserrat','Poppins');
                        $new = in_array($old, $sans, true) ? $BODY : $DISPLAY;
                    }

                    if ($new !== $old) {
                        $val = $new;
                        $stats['families_changed']++;
                        $key = $old . ' => ' . $new . ' [' . ($wt ?: '?') . ']';
                        $stats['map'][$key] = ($stats['map'][$key] ?? 0) + 1;
                    }
                }
            }
            unset($val);
        }

        if (isset($el['elements']) && is_array($el['elements'])) {
            mdb_walk($el['elements'], $wt, $stats, $DISPLAY, $BODY, $EYEBROW, $SCRIPTS, $KEEP, $HEADING_WIDGETS, $BODY_WIDGETS, $LABEL_WIDGETS);
        }
    }
    unset($el);
}

foreach ($rows as $r) {
    $data = json_decode($r->meta_value, true);
    if (!is_array($data)) continue;

    $before = $stats['families_changed'];
    mdb_walk($data, null, $stats, $DISPLAY, $BODY, $EYEBROW, $SCRIPTS, $KEEP, $HEADING_WIDGETS, $BODY_WIDGETS, $LABEL_WIDGETS);

    if ($stats['families_changed'] > $before) {
        $stats['posts_changed']++;
        if ($APPLY) {
            $encoded = wp_slash(wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            update_post_meta($r->post_id, '_elementor_data', $encoded);
        }
    }
}

echo ($APPLY ? "APPLIED\n" : "DRY RUN (no writes)\n");
echo "Posts total: {$stats['posts_total']}\n";
echo "Posts changed: {$stats['posts_changed']}\n";
echo "Font families changed: {$stats['families_changed']}\n";
echo "--- mapping breakdown (old => new [widget]) ---\n";
arsort($stats['map']);
foreach ($stats['map'] as $k => $c) {
    echo sprintf("%-6d %s\n", $c, $k);
}
