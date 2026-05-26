<?php
/**
 * « Nos Univers » (section 7170bb4c sur la home 106136) — mise en cohérence.
 *
 * 1. Swap de l'image Roof Top : 106101 (Design-sans-titre-16.png, watermark
 *    « 888-Reproduction interdite » incrusté dans le PNG source) -> 109077
 *    (mdb-rt-2.jpg, photo propre portrait, vue mer).
 * 2. Suppression du DOUBLON de bouton dans chaque carte (chaque univers avait
 *    DEUX widgets button identiques) — on garde le 1er, on retire le 2e.
 * 3. Réécriture du bouton restant : libellé « Découvrir » + URL de la page de
 *    l'univers (au lieu du mix Notre Carte / Devis evenement incohérent).
 *      RESTAURANT DE BACON       -> /restaurant-de-bacon/   (109260)
 *      L'APPARTEMENT DE VICTOR   -> /lappartement-de-victor/(109275)
 *      ROOF TOP CLUB BACON       -> /le-rooftop-club-bacon/ (109283)
 *
 * Les eyebrows, le title-case et le letter-spacing sont gérés en CSS
 * (overrides.css, section 7170bb4c) — rien à faire ici côté DB.
 *
 * DRY RUN par défaut ; MDB_APPLY=1 pour écrire. Backup JSON dans /tmp/.
 * IDs à réadapter en prod (section, cards, boutons, images, pages).
 */
$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 106136;
$SECTION = '7170bb4c';

$BASE = 'https://staging.maisondebacon.fr/';

// Image watermarkée -> image propre.
$IMG_OLD = 106101;
$IMG_NEW = 109077;
$IMG_NEW_URL = $BASE . 'wp-content/uploads/2025/06/mdb-rt-2.jpg';

// Par carte (container) : [ bouton à GARDER, bouton à SUPPRIMER, libellé, url ]
$CARDS = array(
    '7a5faa3b' => array('keep' => '172baca3', 'drop' => '603b8680',
        'text' => 'Découvrir', 'url' => $BASE . 'restaurant-de-bacon/'),
    '6520f472' => array('keep' => '0d393e8',  'drop' => 'ab9e2ed',
        'text' => 'Découvrir', 'url' => $BASE . 'lappartement-de-victor/'),
    '4c990681' => array('keep' => '77cac46',  'drop' => '6ab910fa',
        'text' => 'Découvrir', 'url' => $BASE . 'le-rooftop-club-bacon/'),
);

$data = get_post_meta($POST, '_elementor_data', true);
$raw  = is_string($data) ? $data : wp_json_encode($data);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/home-106136-univers-' . date('Ymd-His') . '.json';
@file_put_contents($bak, $raw);
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;

$drops = array();
foreach ($CARDS as $c) { $drops[$c['drop']] = true; }

$changed = 0;

// Closure (capture par référence) — `wp eval-file` exécute le fichier DANS une
// méthode, donc `global` ne capte pas les variables du script : on `use` tout.
$walk = function (&$els) use (&$walk, $IMG_OLD, $IMG_NEW, $IMG_NEW_URL, $CARDS, $drops, &$changed) {
    foreach ($els as $k => &$el) {
        $id = $el['id'] ?? '';

        // 1. Supprimer les boutons doublons.
        if (isset($drops[$id])) {
            echo "  drop duplicate button id={$id}\n";
            unset($els[$k]); $changed++; continue;
        }

        $wt = $el['widgetType'] ?? '';

        // 2. Swap image Roof Top.
        if ($wt === 'image' && isset($el['settings']['image']['id'])
            && (int) $el['settings']['image']['id'] === $IMG_OLD) {
            echo "  swap image {$IMG_OLD} -> {$IMG_NEW} (id={$id})\n";
            $el['settings']['image']['id']  = $IMG_NEW;
            $el['settings']['image']['url'] = $IMG_NEW_URL;
            $changed++;
        }

        // 3. Réécrire le bouton conservé de chaque carte.
        if ($wt === 'button') {
            foreach ($CARDS as $card) {
                if ($id === $card['keep']) {
                    echo "  set button id={$id} text={$card['text']} url={$card['url']}\n";
                    $el['settings']['text'] = $card['text'];
                    if (!isset($el['settings']['link']) || !is_array($el['settings']['link'])) {
                        $el['settings']['link'] = array();
                    }
                    $el['settings']['link']['url']         = $card['url'];
                    $el['settings']['link']['is_external'] = '';
                    $el['settings']['link']['nofollow']    = '';
                    $changed++;
                }
            }
        }

        if (!empty($el['elements'])) $walk($el['elements']);
    }
    unset($el);
    $els = array_values($els);
};

$walk($tree);
echo "changes: {$changed}\n";

if ($APPLY && $changed) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
