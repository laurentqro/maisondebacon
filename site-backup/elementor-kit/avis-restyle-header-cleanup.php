<?php
/* Page « Avis » (104781) : nettoyage + en-tête éditorial (demande client).
 *
 * État avant (vérifié par dump _elementor_data + rendu live) :
 *   - container 38cc40af (e-con-boxed) > text-editor 54c17767 : RÉSIDU WPBakery.
 *     Contient des [vc_row]/[vc_raw_html] qui ne sont plus parsés (thème Laurent
 *     désinstallé) -> le shortcode brut s'AFFICHE en clair en haut de page.
 *     L'iframe encodée dedans fait doublon avec le widget html propre.
 *   - container 89adf1b (e-con-full) > html 3f82a0d : le vrai widget Zenchef
 *     (iframe widget-reviews.zenchef.com/354476), seul élément à conserver.
 *
 * Après :
 *   1) SUPPRIMER le container 38cc40af (et son text-editor résidu).
 *   2) AJOUTER en tête un container d'en-tête (mdb-avis-head) avec 3 widgets
 *      heading : eyebrow « Témoignages », titre « Ils parlent de nous »,
 *      lede italique. Markup minimal, le style vient du CSS scoped .elementor-104781.
 *   3) Donner au container de l'iframe (89adf1b) la classe mdb-avis-frame pour
 *      pouvoir l'encadrer en CSS (89adf1b est e-con-full : cibler par ID en CSS,
 *      Elementor strip les _css_classes des CONTAINERS — cf. mémoire).
 *
 * DRY par défaut ; MDB_APPLY=1 pour écrire. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY     = getenv('MDB_APPLY') === '1';
$POST      = 104781;
$JUNK      = '38cc40af';   // container résidu WPBakery (à supprimer)
$IFRAME_C  = '89adf1b';    // container de l'iframe Zenchef (à garder + classer)
$HEAD_ID   = 'a715head';   // nouvel ID stable du container d'en-tête

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/avis-104781-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;

/* idempotence */
$hasHead = false;
(function ($els) use (&$hasHead, $HEAD_ID) {
    $scan = function ($els) use (&$scan, $HEAD_ID, &$hasHead) {
        foreach ($els as $el) {
            if (($el['id'] ?? '') === $HEAD_ID) $hasHead = true;
            if (!empty($el['elements'])) $scan($el['elements']);
        }
    };
    $scan($els);
})($tree);
if ($hasHead) { echo "en-tête {$HEAD_ID} déjà présent — rien à faire\n(dry run)\n"; return; }

/* helper pull-by-id */
$pull = function (&$els, $id) use (&$pull) {
    foreach ($els as $k => &$el) {
        if (($el['id'] ?? '') === $id) { $n = $el; unset($els[$k]); $els = array_values($els); return $n; }
        if (!empty($el['elements'])) { $n = $pull($el['elements'], $id); if ($n !== null) return $n; }
    }
    unset($el);
    return null;
};

/* 1) supprimer le container résidu WPBakery */
$junkNode = $pull($tree, $JUNK);
echo $junkNode ? "résidu WPBakery {$JUNK} supprimé\n" : "résidu {$JUNK} introuvable\n";

/* 2) classer le container de l'iframe */
$framed = 0;
$classify = function (&$els, $id, $cls) use (&$classify, &$framed) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $id) {
            $el['settings']['_css_classes'] = $cls;
            $framed++;
            return true;
        }
        if (!empty($el['elements']) && $classify($el['elements'], $id, $cls)) return true;
    }
    unset($el);
    return false;
};
$classify($tree, $IFRAME_C, 'mdb-avis-frame');
echo "container iframe {$IFRAME_C} classé mdb-avis-frame : {$framed}\n";

/* 3) construire l'en-tête */
$mk_heading = function ($id, $cls, $title, $tag) {
    return [
        'id'         => $id,
        'elType'     => 'widget',
        'widgetType' => 'heading',
        'settings'   => [
            '_css_classes' => $cls,
            'title'        => $title,
            'header_size'  => $tag,
        ],
        'elements'   => [],
    ];
};
$head = [
    'id'       => $HEAD_ID,
    'elType'   => 'container',
    'settings' => [
        '_css_classes'   => 'mdb-avis-head',
        'flex_direction' => 'column',
    ],
    'elements' => [
        $mk_heading('a715eye', 'mdb-avis-eyebrow', 'Témoignages', 'p'),
        $mk_heading('a715ttl', 'mdb-avis-title',   'Ils parlent de nous', 'h1'),
        $mk_heading('a715led', 'mdb-avis-lede',    'Ce que nos hôtes retiennent de la Maison de Bacon.', 'p'),
    ],
];

/* 4) insérer l'en-tête en tête de l'arbre racine */
array_unshift($tree, $head);
echo "en-tête {$HEAD_ID} inséré en tête de page\n";

$changed = ($junkNode ? 1 : 0) + $framed + 1;
echo "changes: {$changed}\n";

if ($APPLY && $changed) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
