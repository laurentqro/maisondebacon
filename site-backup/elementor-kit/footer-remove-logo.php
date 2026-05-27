<?php
/* Footer (108363) : nettoyage de la cellule marque (demande client).
 *
 *  1) Logo (image ea57ab5) SUPPRIMÉ — déjà présent dans le header sticky de
 *     chaque page ; le répéter alourdissait la compo.
 *  2) Adresse postale (text-editor f8cfc0f) DÉPLACÉE dans la colonne « Nous
 *     Contacter » (c936f49), en tête sous un sous-titre « Adresse ».
 *  3) Réseaux sociaux (social-icons 4a942b2) DÉPLACÉS tout en bas, dans le
 *     tier légal (878265c), à côté du © / mentions.
 *
 *  Résultat : la cellule marque 5a1bc48 devient vide -> on la SUPPRIME. Le tier
 *  haut 97020d4 ne contient plus que les 2 blocs horaires.
 *  DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY    = getenv('MDB_APPLY') === '1';
$POST     = 108363;
$LOGO     = 'ea57ab5';
$ADDR     = 'f8cfc0f';
$SOCIAL   = '4a942b2';
$BRANDCEL = '5a1bc48';   // conteneur cellule marque (à vider puis supprimer)
$CONTACT  = 'c936f49';   // colonne « Nous Contacter »
$EYE_CT   = '5c6a107';   // eyebrow « Nous contacter »
$LEGAL    = '878265c';   // tier légal (accueille les réseaux)

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/footer-108363-strip-brand-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;

/* helper : retire un noeud par id et le renvoie */
$pull = function (&$els, $id) use (&$pull) {
    foreach ($els as $k => &$el) {
        if (($el['id'] ?? '') === $id) {
            $node = $el;
            unset($els[$k]);
            $els = array_values($els);
            return $node;
        }
        if (!empty($el['elements'])) {
            $node = $pull($el['elements'], $id);
            if ($node !== null) return $node;
        }
    }
    unset($el);
    return null;
};

/* 1) supprimer le logo */
$logoNode = $pull($tree, $LOGO);
echo $logoNode ? "logo {$LOGO} supprimé\n" : "logo {$LOGO} introuvable\n";

/* 2) extraire l'adresse, la reformater */
$addrNode = $pull($tree, $ADDR);
if ($addrNode) {
    $addrNode['settings']['_css_classes'] = 'mdb-foot-contact mdb-foot-address';
    $html = $addrNode['settings']['editor'] ?? '';
    if (strpos($html, 'mdb-foot-sub') === false) {
        $addrNode['settings']['editor'] = '<p class="mdb-foot-sub">Adresse</p>' . $html;
    }
    echo "adresse {$ADDR} extraite + reformatée\n";
} else {
    echo "adresse {$ADDR} introuvable\n";
}

/* 3) extraire les réseaux sociaux */
$socialNode = $pull($tree, $SOCIAL);
if ($socialNode) {
    $socialNode['settings']['_css_classes'] = 'mdb-foot-social mdb-foot-social--bottom';
    echo "réseaux {$SOCIAL} extraits\n";
} else {
    echo "réseaux {$SOCIAL} introuvables\n";
}

/* 4) supprimer la cellule marque (désormais vide) */
$brandNode = $pull($tree, $BRANDCEL);
echo $brandNode ? "cellule marque {$BRANDCEL} supprimée (vide)\n" : "cellule marque {$BRANDCEL} introuvable\n";

/* 5) insérer l'adresse en tête de la colonne contact (après l'eyebrow) */
$placed = 0;
$insertAfter = function (&$els, $parentId, $afterId, $node) use (&$insertAfter, &$placed) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $parentId) {
            $out = []; $done = false;
            foreach (($el['elements'] ?? []) as $c) {
                $out[] = $c;
                if (($c['id'] ?? '') === $afterId) { $out[] = $node; $done = true; $placed++; }
            }
            if (!$done) { array_unshift($out, $node); $placed++; }
            $el['elements'] = $out;
            return true;
        }
        if (!empty($el['elements']) && $insertAfter($el['elements'], $parentId, $afterId, $node)) return true;
    }
    unset($el);
    return false;
};
if ($addrNode) $insertAfter($tree, $CONTACT, $EYE_CT, $addrNode);
echo "adresse placée dans contact : {$placed}\n";

/* 6) ajouter les réseaux à la fin du tier légal 878265c */
$socialPlaced = 0;
$appendTo = function (&$els, $parentId, $node) use (&$appendTo, &$socialPlaced) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $parentId) {
            $el['elements'][] = $node;
            $socialPlaced++;
            return true;
        }
        if (!empty($el['elements']) && $appendTo($el['elements'], $parentId, $node)) return true;
    }
    unset($el);
    return false;
};
if ($socialNode) $appendTo($tree, $LEGAL, $socialNode);
echo "réseaux placés dans le tier légal : {$socialPlaced}\n";

$changed = ($logoNode?1:0) + ($addrNode?1:0) + ($socialNode?1:0);
echo "changes: {$changed}\n";

if ($APPLY && $changed) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
