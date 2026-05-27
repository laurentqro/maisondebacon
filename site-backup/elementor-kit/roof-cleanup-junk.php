<?php
/* Page « Le Roof Top » (106766) — nettoyage du contenu hérité de l'ancien thème
 * + remise en place du titre hero.
 *
 * 1) Supprime les conteneurs top-level parasites :
 *      eb7d05b  ancien conteneur du heading-tagline (vidé : on déplace le heading
 *               dans le hero, cf. point 3)
 *      79bab26  blockquote décalé hors écran (offset 530px, texte blanc invisible)
 *      2a4bc96  spacer 581px (le grand vide blanc)
 *      01bd777  spacer résiduel
 *      004e930  ElementsKit image-accordion (vieilles photos 2024, hors charte)
 *      46c5652  accordéon eltdf vide
 * 2) Heading hero bfabdc5 : marge -69px -> 0 + texte -> « Le Roof Top ».
 * 3) Déplace ce heading bfabdc5 DANS la grille hero 34da85ec (1er enfant), pour
 *    qu'il se pose sur la photo ROOFTO4. Restylé en CSS (titre Cormorant).
 *
 * Le reste (logo orange [masqué en CSS], boutons, concept+vidéo, instagram) est
 * conservé et restylé via overrides.css scellé aux IDs. DRY par défaut ;
 * MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. IDs prod à réadapter. */
$APPLY    = getenv('MDB_APPLY') === '1';
$POST     = 106766;
$DROP     = ['eb7d05b', '79bab26', '2a4bc96', '01bd777', '004e930', '46c5652'];
$HEAD     = 'bfabdc5';      // heading à recycler en titre hero
$HERO_GRID = '34da85ec';    // grille interne du hero (cible d'accueil)
$HEAD_NEW = 'Le Roof Top';

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/roof-106766-cleanup-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree    = is_string($data) ? json_decode($data, true) : $data;
$changed = 0;

/* --- (a) extraire le heading bfabdc5 (peu importe sa profondeur) ------------ */
$headNode = null;
$extract = function (&$els) use (&$extract, $HEAD, &$headNode) {
    foreach ($els as $i => &$el) {
        if (($el['id'] ?? '') === $HEAD) {
            $headNode = $el;
            unset($els[$i]);
            $els = array_values($els);
            return true;
        }
        if (!empty($el['elements']) && $extract($el['elements'])) return true;
    }
    unset($el);
    return false;
};
$extract($tree);
if ($headNode) {
    $headNode['settings']['title'] = $HEAD_NEW;
    $headNode['settings']['header_size'] = 'h1';
    foreach (['_margin', '_margin_tablet', '_margin_mobile'] as $mk) {
        if (isset($headNode['settings'][$mk])) unset($headNode['settings'][$mk]);
    }
    echo "  heading {$HEAD} extrait, titre -> '{$HEAD_NEW}', marge -69px supprimée\n";
    $changed++;
} else {
    echo "  !! heading {$HEAD} introuvable\n";
}

/* --- (b) insérer le heading en 1er enfant de la grille hero ----------------- */
if ($headNode) {
    $inserted = false;
    $insert = function (&$els) use (&$insert, $HERO_GRID, $headNode, &$inserted) {
        foreach ($els as &$el) {
            if (($el['id'] ?? '') === $HERO_GRID) {
                if (!isset($el['elements'])) $el['elements'] = [];
                array_unshift($el['elements'], $headNode);
                $inserted = true;
                return true;
            }
            if (!empty($el['elements']) && $insert($el['elements'])) return true;
        }
        unset($el);
        return false;
    };
    $insert($tree);
    echo $inserted
        ? "  heading inséré dans la grille hero {$HERO_GRID}\n"
        : "  !! grille hero {$HERO_GRID} introuvable — heading NON réinséré\n";
    if ($inserted) $changed++;
}

/* --- (c) supprimer les conteneurs top-level parasites ----------------------- */
$before = count($tree);
$tree = array_values(array_filter($tree, function ($el) use ($DROP, &$changed) {
    if (in_array($el['id'] ?? '', $DROP, true)) {
        // ne pas dropper eb7d05b s'il contient encore qqch d'inattendu
        echo "  drop container #{$el['id']}\n";
        $changed++;
        return false;
    }
    return true;
}));
echo "  top-level: {$before} -> " . count($tree) . "\n";

echo "changes: {$changed}\n";

if ($APPLY && $changed) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
