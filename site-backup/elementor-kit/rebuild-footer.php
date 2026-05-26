<?php
/**
 * Rebuild the footer template (108363) to match the concept:
 *   col 1  Brand : white crest/wordmark + italic tagline + address
 *   col 2  La Maison : venue/about links
 *   col 3  Privé & Événements : privatisation links (incl. Recrutement)
 *   col 4  Nous contacter : phone / email / hours
 *   bottom legal bar : © + Mentions légales · Politique · Instagram
 *
 * This also fulfils the "move CSR/recruitment to footer" decision:
 * Recrutement + Engagement RSE live in col 3.
 *
 * Styling is handled in overrides.css scoped to .elementor-element-<root>.
 * DRY RUN by default; MDB_APPLY=1 to write. Full backup first.
 */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 108363;
$BASE  = 'https://staging.maisondebacon.fr/';
$LOGO  = 'https://staging.maisondebacon.fr/wp-content/uploads/2024/05/Logo_Blanc.svg';
$LOGO_ID = 106362;

$cur = get_post_meta($POST, '_elementor_data', true);
$raw = is_string($cur) ? $cur : wp_json_encode($cur);
$bak = '/tmp/mdb-evfix/footer-108363-' . date('Ymd-His') . '.json';
@file_put_contents($bak, $raw);
echo "backup -> {$bak}\n";

// id helper (Elementor uses 7-hex ids)
function id7() { return substr(md5(uniqid('', true)), 0, 7); }

function heading($text, $eyebrow = true) {
    return array(
        'id' => id7(), 'elType' => 'widget', 'widgetType' => 'heading',
        'settings' => array(
            'title' => $text,
            'header_size' => 'h3',
            '_css_classes' => $eyebrow ? 'mdb-foot-eyebrow' : '',
        ),
        'elements' => array(),
    );
}

// a links-list via a text-editor of <a> lines (simplest robust widget)
function linklist($items) {
    $html = '<ul class="mdb-foot-links">';
    foreach ($items as $label => $url) {
        $html .= '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
    }
    $html .= '</ul>';
    return array(
        'id' => id7(), 'elType' => 'widget', 'widgetType' => 'text-editor',
        'settings' => array('editor' => $html, '_css_classes' => 'mdb-foot-linklist'),
        'elements' => array(),
    );
}

function textblock($html, $class = '') {
    return array(
        'id' => id7(), 'elType' => 'widget', 'widgetType' => 'text-editor',
        'settings' => array('editor' => $html, '_css_classes' => $class),
        'elements' => array(),
    );
}

function column($children, $class = '') {
    return array(
        'id' => id7(), 'elType' => 'container',
        'settings' => array(
            'content_width' => 'full',
            '_flex_grow' => 0,
            'flex_direction' => 'column',
            '_css_classes' => $class,
        ),
        'elements' => $children,
    );
}

// --- Column 1 : brand ---
$col_brand = column(array(
    array(
        'id' => id7(), 'elType' => 'widget', 'widgetType' => 'image',
        'settings' => array(
            'image' => array('url' => $LOGO, 'id' => $LOGO_ID),
            '_css_classes' => 'mdb-foot-logo',
        ),
        'elements' => array(),
    ),
    textblock('<p>Institution gastronomique du Cap d\'Antibes depuis 1948. Cuisine marine et m&eacute;diterran&eacute;enne, face &agrave; la mer.</p>', 'mdb-foot-tagline'),
    textblock('<p>664 Boulevard de Bacon<br>06160 Cap d\'Antibes</p>', 'mdb-foot-address'),
    array(
        'id' => id7(), 'elType' => 'widget', 'widgetType' => 'social-icons',
        'settings' => array(
            '_css_classes' => 'mdb-foot-social',
            'social_icon_list' => array(
                array(
                    '_id' => id7(),
                    'social_icon' => array('value' => 'fab fa-instagram', 'library' => 'fa-brands'),
                    'link' => array('url' => 'https://www.instagram.com/maisondebacon/', 'is_external' => 'true', 'nofollow' => ''),
                ),
                array(
                    '_id' => id7(),
                    'social_icon' => array('value' => 'fab fa-facebook-f', 'library' => 'fa-brands'),
                    'link' => array('url' => 'https://www.facebook.com/maisondebacon', 'is_external' => 'true', 'nofollow' => ''),
                ),
            ),
            'shape' => 'square',
        ),
        'elements' => array(),
    ),
), 'mdb-foot-col mdb-foot-col--brand');

// --- Column 2 : La Maison (institution : lieux, chef, recrutement, RSE) ---
$col_maison = column(array(
    heading('La Maison'),
    linklist(array(
        'Restaurant de Bacon'   => $BASE . 'restaurant-de-bacon/',
        'Roof Top Club'         => $BASE . 'le-rooftop-club-bacon/',
        'La Carte'              => $BASE . 'notre-carte/',
        'Le Chef'               => $BASE . 'nicolas-davouze/',
        'Recrutement'           => $BASE . 'recrutement/',
        'Engagement RSE'        => $BASE . 'lengagement-rs-de-la-maison-de-bacon/',
    )),
), 'mdb-foot-col');

// --- Column 3 : Privé & Événements (espaces privatisables) ---
$col_prive = column(array(
    heading('Privé & Événements'),
    linklist(array(
        'Privatisations'        => $BASE . 'vos-evenements/',
        'Villa Les Roches de Bacon' => $BASE . 'villa-les-roches-de-bacon/',
        "L'Appartement de Victor" => $BASE . 'lappartement-de-victor/',
    )),
), 'mdb-foot-col');

// --- Column 4 : Nous contacter ---
$col_contact = column(array(
    heading('Nous contacter'),
    textblock('<p class="mdb-foot-sub">Réservations</p><p><a href="tel:+33493615002">+33 4 93 61 50 02</a><br><a href="mailto:reservation@maisondebacon.fr">reservation@maisondebacon.fr</a></p>', 'mdb-foot-contact'),
    textblock('<p class="mdb-foot-sub">Horaires</p><p>Ouvert tous les jours<br>12h &ndash; 14h<br>19h &ndash; 22h</p>', 'mdb-foot-hours'),
), 'mdb-foot-col');

// --- Top row (4 columns) ---
$top = array(
    'id' => id7(), 'elType' => 'container',
    'settings' => array(
        'content_width' => 'boxed',
        'flex_direction' => 'row',
        'flex_wrap' => 'wrap',
        '_css_classes' => 'mdb-foot-top',
    ),
    'elements' => array($col_brand, $col_maison, $col_prive, $col_contact),
);

// --- Bottom legal bar (+ mention reCAPTCHA exigée par Google quand le badge
//     est masqué). ---
$legal = textblock(
    '<div class="mdb-foot-legal"><span>&copy; 2026 Maison de Bacon</span>'
  . '<span class="mdb-foot-legal-links">'
  . '<a href="' . $BASE . 'mentions-legales/">Mentions légales</a>'
  . '<a href="' . $BASE . 'politique-de-confidentialite/">Politique de confidentialité</a>'
  . '</span></div>'
  . '<div class="mdb-recaptcha-note">Ce site est protégé par reCAPTCHA et la '
  . '<a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Politique de confidentialité</a> et les '
  . '<a href="https://policies.google.com/terms" target="_blank" rel="noopener">Conditions d\'utilisation</a> de Google s\'appliquent.</div>',
    'mdb-foot-legal-wrap'
);
$bottom = array(
    'id' => id7(), 'elType' => 'container',
    'settings' => array('content_width' => 'boxed', '_css_classes' => 'mdb-foot-bottom'),
    'elements' => array($legal),
);

// --- Root ---
$root = array(
    'id' => id7(), 'elType' => 'container',
    'settings' => array(
        'content_width' => 'full',
        '_css_classes' => 'mdb-footer',
    ),
    'elements' => array($top, $bottom),
);

$tree = array($root);
echo "new root id = {$root['id']}\n";
echo "columns: brand={$col_brand['id']} maison={$col_maison['id']} prive={$col_prive['id']} contact={$col_contact['id']}\n";

if ($APPLY) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
    echo "ROOT_ID={$root['id']}\n";
} else {
    echo "(dry run) preview JSON length=" . strlen(wp_json_encode($tree)) . "\n";
}
