<?php
/**
 * Pied de page (template 108363) :
 *  1. Remet le bloc newsletter (eyebrow mdb-foot-eyebrow--nl + shortcode
 *     mdb-foot-newsletter) dans la colonne Marque (5a1bc48), après les
 *     réseaux (4a942b2).
 *  2. Supprime la tagline « Institution gastronomique… face à la mer »
 *     (widget text-editor 43d2df8, classe mdb-foot-tagline) pour resserrer
 *     la colonne.
 *
 * Idempotent : coupe d'abord les widgets newsletter où qu'ils soient + la
 * tagline, puis réinsère la newsletter dans la colonne Marque.
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/. IDs prod à réadapter.
 */
$APPLY      = getenv('MDB_APPLY') === '1';
$POST       = 108363;
$BRAND_COL  = '5a1bc48';
$AFTER      = '4a942b2';   // social-icons (réinsertion après)
$TAGLINE    = 'mdb-foot-tagline';
$NL_EYEBROW = 'mdb-foot-eyebrow--nl';
$NL_FORM    = 'mdb-foot-newsletter';

$data = get_post_meta($POST, '_elementor_data', true);
$raw  = is_string($data) ? $data : wp_json_encode($data);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/footer-108363-brandcol-' . date('Ymd-His') . '.json';
@file_put_contents($bak, $raw);
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$grabbed = array();
$removedTagline = 0;

// 1. Couper newsletter (à réinsérer) + supprimer tagline (définitif).
$cut = function (&$els) use (&$cut, $NL_EYEBROW, $NL_FORM, $TAGLINE, &$grabbed, &$removedTagline) {
    $keep = array();
    foreach ($els as $el) {
        $cls = $el['settings']['_css_classes'] ?? '';
        if (strpos($cls, $NL_EYEBROW) !== false) { $grabbed['eyebrow'] = $el; continue; }
        if (strpos($cls, $NL_FORM) !== false)    { $grabbed['form'] = $el; continue; }
        if (strpos($cls, $TAGLINE) !== false)    { $removedTagline++; continue; }
        if (!empty($el['elements'])) $cut($el['elements']);
        $keep[] = $el;
    }
    $els = $keep;
};
$cut($tree);
echo "grabbed: " . implode(',', array_keys($grabbed)) . " | tagline removed: {$removedTagline}\n";
if (!isset($grabbed['eyebrow']) || !isset($grabbed['form'])) {
    echo "ERROR: newsletter widgets introuvables — abort\n";
    return;
}

// 2. Réinsérer la newsletter dans la colonne Marque après les réseaux.
$done = false;
$put = function (&$els) use (&$put, $BRAND_COL, $AFTER, $grabbed, &$done) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $BRAND_COL && !empty($el['elements'])) {
            $new = array();
            foreach ($el['elements'] as $child) {
                $new[] = $child;
                if (($child['id'] ?? '') === $AFTER) {
                    $new[] = $grabbed['eyebrow'];
                    $new[] = $grabbed['form'];
                    $done  = true;
                }
            }
            $el['elements'] = $new;
        }
        if (!$done && !empty($el['elements'])) $put($el['elements']);
    }
    unset($el);
};
$put($tree);
echo $done ? "newsletter back in {$BRAND_COL} after {$AFTER}\n" : "TARGET NOT FOUND\n";

if ($APPLY && $done) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
