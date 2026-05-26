<?php
/**
 * Ajoute le bloc « Restez informés » (newsletter) dans le pied de page
 * (template Elementor 108363), colonne Marque (container 5a1bc48), juste
 * après les icônes réseaux (widget 4a942b2).
 *
 * Contenu : un eyebrow doré « Restez informés » + le shortcode CF7 du
 * formulaire newsletter (email seul + consentement). Le formulaire CF7
 * (id 110553) envoie à contact@maisondebacon.fr — PLACEHOLDER à confirmer
 * par le client (cf. note client). Le rendu est stylé dans overrides.css
 * via les classes mdb-foot-eyebrow / mdb-foot-newsletter.
 *
 * DRY RUN par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/.
 * IDs à réadapter en prod (template footer, container brand, widget social,
 * id du shortcode CF7).
 */
$APPLY     = getenv('MDB_APPLY') === '1';
$POST      = 108363;
$BRAND_COL = '5a1bc48';   // container colonne Marque
$AFTER     = '4a942b2';   // widget social-icons (on insère juste après)
$CF7_ID    = 110553;      // formulaire « Newsletter pied de page (MdB) »

function id7() {
    return substr(bin2hex(random_bytes(4)), 0, 7);
}

$eyebrow = array(
    'id'         => id7(),
    'elType'     => 'widget',
    'widgetType' => 'heading',
    'settings'   => array(
        'title'        => 'Restez informés',
        'header_size'  => 'h3',
        '_css_classes' => 'mdb-foot-eyebrow mdb-foot-eyebrow--nl',
    ),
    'elements'   => array(),
);

$form = array(
    'id'         => id7(),
    'elType'     => 'widget',
    'widgetType' => 'shortcode',
    'settings'   => array(
        'shortcode'    => '[contact-form-7 id="' . $CF7_ID . '" title="Newsletter pied de page (MdB)"]',
        '_css_classes' => 'mdb-foot-newsletter',
    ),
    'elements'   => array(),
);

$data = get_post_meta($POST, '_elementor_data', true);
$raw  = is_string($data) ? $data : wp_json_encode($data);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/footer-108363-newsletter-' . date('Ymd-His') . '.json';
@file_put_contents($bak, $raw);
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$done    = false;
$already = false;

// idempotence : ne pas réinsérer si un shortcode CF7 110553 existe déjà.
$scan = function ($els) use (&$scan, $CF7_ID, &$already) {
    foreach ($els as $el) {
        if (($el['widgetType'] ?? '') === 'shortcode'
            && strpos($el['settings']['shortcode'] ?? '', 'id="' . $CF7_ID . '"') !== false) {
            $already = true;
        }
        if (!empty($el['elements'])) $scan($el['elements']);
    }
};
$scan($tree);

if ($already) {
    echo "newsletter shortcode already present -> skip\n";
    echo "(no change)\n";
    return;
}

$walk = function (&$els) use (&$walk, $BRAND_COL, $AFTER, $eyebrow, $form, &$done) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $BRAND_COL && !empty($el['elements'])) {
            $new = array();
            foreach ($el['elements'] as $child) {
                $new[] = $child;
                if (($child['id'] ?? '') === $AFTER) {
                    $new[] = $eyebrow;
                    $new[] = $form;
                    $done  = true;
                }
            }
            $el['elements'] = $new;
        }
        if (!$done && !empty($el['elements'])) $walk($el['elements']);
    }
    unset($el);
};
$walk($tree);

echo $done ? "inserted newsletter block after {$AFTER} in {$BRAND_COL}\n" : "TARGET NOT FOUND\n";

if ($APPLY && $done) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
