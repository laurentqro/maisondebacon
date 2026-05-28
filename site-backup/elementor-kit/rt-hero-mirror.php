<?php
/* Page « Le Roof Top » (106766) — miroir du hero MdB (167c86ed sur 106136).
 *
 * Sur MdB la pile est : eyebrow → titre → italic lede → CTA gold → lien
 * secondaire souligné. Le RT a déjà : logo orange → boutons + texte horaires.
 * On ajoute l'eyebrow et le lien « voir la carte », on renomme le CTA, et on
 * pose les classes `mdb-hero__*` partagées pour bénéficier des règles globales.
 *
 * Modifications dans la grille 34da85ec :
 *  1) Insérer un widget heading EN TÊTE :
 *       id=`rthereye` widgetType=heading
 *       title="SOIRÉES & COCKTAILS FACE À LA MER"
 *       header_size=h2, _css_classes="mdb-hero__eyebrow mdb-hero__eyebrow--rt"
 *  2) Renommer le CTA `ad86369` : text → "Réserver en ligne", classe
 *     `mdb-hero__cta mdb-hero__cta--rt`.
 *  3) Le bouton téléphone `454470c` reçoit la classe `mdb-hero__link mdb-hero__link--rt`
 *     pour adopter le style « lien souligné » comme « Prochains événements ».
 *  4) Insérer un widget button « Voir la carte » APRÈS le téléphone :
 *       id=`rtherelnk` widgetType=button text="Voir la carte"
 *       link=https://staging.maisondebacon.fr/notre-carte/
 *       _css_classes="mdb-hero__link mdb-hero__link--rt"
 *  5) L'image (logo) `34a8c93` et le texte horaires `7488ca8` restent en place.
 *
 *  DRY par défaut ; MDB_APPLY=1. Backup dans /tmp/mdb-evfix/. */

$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106766;
$GRID  = '34da85ec';

global $wpdb;
@mkdir('/tmp/mdb-evfix', 0775, true);
$ts = date('Ymd-His');

$raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
    $POST, '_elementor_data'
));
$tree = json_decode($raw, true);
if (!is_array($tree)) { echo "ABORT: _elementor_data invalide\n"; return; }
file_put_contents("/tmp/mdb-evfix/rt-page-106766-mirror-pre-{$ts}.json", $raw);
echo "backup -> /tmp/mdb-evfix/rt-page-106766-mirror-pre-{$ts}.json (" . strlen($raw) . " bytes)\n";

/* Modèles de widgets à insérer. Format Elementor standard (cf. heading/button
   widgets du hero home). On garde les settings au minimum nécessaire — les
   classes CSS portent le style. */
$eyebrow = array(
    'id'         => 'rthereye',
    'elType'     => 'widget',
    'widgetType' => 'heading',
    'settings'   => array(
        'title'        => 'Soirées &amp; cocktails face à la mer',
        'header_size'  => 'h2',
        '_css_classes' => 'mdb-hero__eyebrow mdb-hero__eyebrow--rt',
    ),
    'elements'   => array(),
    'isInner'    => false,
);

$carte_link = array(
    'id'         => 'rtherelnk',
    'elType'     => 'widget',
    'widgetType' => 'button',
    'settings'   => array(
        'text'         => 'Voir la carte',
        'link'         => array(
            'url'         => 'https://staging.maisondebacon.fr/notre-carte/',
            'is_external' => '',
            'nofollow'    => '',
        ),
        '_css_classes' => 'mdb-hero__link mdb-hero__link--rt',
    ),
    'elements'   => array(),
    'isInner'    => false,
);

/* Idempotence : si rthereye / rtherelnk existent déjà, on saute leur insertion
   (mais on relance les patches de classes + texte CTA pour resynchroniser). */
$existing_ids = array();
$collect = function ($els) use (&$collect, &$existing_ids) {
    foreach ($els as $el) {
        $existing_ids[] = $el['id'] ?? '';
        if (!empty($el['elements'])) $collect($el['elements']);
    }
};
$collect($tree);
$has_eyebrow = in_array('rthereye', $existing_ids, true);
$has_link    = in_array('rtherelnk', $existing_ids, true);
echo "eyebrow rthereye présent ? " . ($has_eyebrow ? 'OUI' : 'non') . "\n";
echo "link rtherelnk présent ? " . ($has_link ? 'OUI' : 'non') . "\n";

/* Appliquer les modifications sur la grille 34da85ec. */
$patched = 0;
$walk = function (&$els) use (&$walk, $GRID, $eyebrow, $carte_link, $has_eyebrow, $has_link, &$patched) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $GRID) {
            $children = $el['elements'] ?? array();

            // 1) Eyebrow en tête.
            if (!$has_eyebrow) {
                array_unshift($children, $eyebrow);
                echo "  + eyebrow inséré en tête\n";
                $patched++;
            }

            // 2/3) Patch en place : CTA text + classes ; téléphone classes.
            foreach ($children as &$c) {
                $cid = $c['id'] ?? '';
                if ($cid === 'ad86369') {
                    $c['settings']['text'] = 'Réserver en ligne';
                    $c['settings']['_css_classes'] = trim(
                        ($c['settings']['_css_classes'] ?? '') . ' mdb-hero__cta mdb-hero__cta--rt'
                    );
                    // Dédoublonner.
                    $c['settings']['_css_classes'] = implode(' ', array_unique(preg_split('/\s+/', trim($c['settings']['_css_classes']))));
                    echo "  ~ CTA ad86369 : text='Réserver en ligne' + classes mdb-hero__cta\n";
                    $patched++;
                }
                if ($cid === '454470c') {
                    $c['settings']['_css_classes'] = trim(
                        ($c['settings']['_css_classes'] ?? '') . ' mdb-hero__link mdb-hero__link--rt mdb-hero__link--phone'
                    );
                    $c['settings']['_css_classes'] = implode(' ', array_unique(preg_split('/\s+/', trim($c['settings']['_css_classes']))));
                    echo "  ~ phone 454470c : + classes mdb-hero__link\n";
                    $patched++;
                }
            }
            unset($c);

            // 4) Lien « Voir la carte » juste après le téléphone (454470c).
            if (!$has_link) {
                $out = array(); $inserted = false;
                foreach ($children as $c) {
                    $out[] = $c;
                    if (($c['id'] ?? '') === '454470c') {
                        $out[] = $carte_link;
                        $inserted = true;
                    }
                }
                if (!$inserted) {
                    // Fallback : pousser à la fin.
                    $out[] = $carte_link;
                }
                $children = $out;
                echo "  + link rtherelnk inséré après le téléphone\n";
                $patched++;
            }

            $el['elements'] = $children;
            return true;
        }
        if (!empty($el['elements']) && $walk($el['elements'])) return true;
    }
    unset($el);
    return false;
};
$walk($tree);
echo "patches : {$patched}\n";

if (!$APPLY || !$patched) {
    echo $patched ? "(dry run)\n" : "(rien à faire)\n";
    return;
}

$json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$ok = update_post_meta($POST, '_elementor_data', wp_slash($json));
echo "update_post_meta -> " . var_export($ok, true) . "\n";
delete_post_meta($POST, '_elementor_element_cache');
echo "element cache nuked\n";
echo "APPLIED\n";
