<?php
/* Footer (108363) : la newsletter devient une 4e colonne du tier nav (demande
 * client).
 *
 * Avant : bandeau centré 09fb99f (eyebrow b73c13b + form f0d5e56) entre le tier
 * nav et la barre légale.
 * Après : on déplace eyebrow + form dans un NOUVEAU conteneur-colonne (27fcfe8,
 * classe mdb-foot-col) ajouté en 4e position du tier nav 303d9d2, puis on
 * supprime le bandeau 09fb99f devenu vide.
 *
 * L'eyebrow reçoit la classe colonne standard (mdb-foot-eyebrow, plus --nl) et
 * sera aligné à gauche comme les autres colonnes (CSS).
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY  = getenv('MDB_APPLY') === '1';
$POST   = 108363;
$NAVROW = '303d9d2';
$BAND   = '09fb99f';
$EYE    = 'b73c13b';
$FORM   = 'f0d5e56';
$COL    = '27fcfe8';

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/footer-108363-nl-4th-col-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;

/* idempotence */
$exists = false;
(function ($els) use (&$exists, $COL) {
    $scan = function ($els) use (&$scan, $COL, &$exists) {
        foreach ($els as $el) {
            if (($el['id'] ?? '') === $COL) $exists = true;
            if (!empty($el['elements'])) $scan($el['elements']);
        }
    };
    $scan($els);
})($tree);
if ($exists) { echo "colonne {$COL} déjà présente — rien à faire\n(dry run)\n"; return; }

/* helper pull-by-id */
$pull = function (&$els, $id) use (&$pull) {
    foreach ($els as $k => &$el) {
        if (($el['id'] ?? '') === $id) { $n = $el; unset($els[$k]); $els = array_values($els); return $n; }
        if (!empty($el['elements'])) { $n = $pull($el['elements'], $id); if ($n !== null) return $n; }
    }
    unset($el);
    return null;
};

/* 1) extraire eyebrow + form */
$eyeNode  = $pull($tree, $EYE);
$formNode = $pull($tree, $FORM);
echo ($eyeNode ? "eyebrow {$EYE} extrait\n" : "eyebrow {$EYE} introuvable\n");
echo ($formNode ? "form {$FORM} extrait\n" : "form {$FORM} introuvable\n");

/* l'eyebrow garde --nl mais redevient un eyebrow de colonne normal */
if ($eyeNode) {
    $eyeNode['settings']['_css_classes'] = 'mdb-foot-eyebrow mdb-foot-eyebrow--nl';
}

/* 2) supprimer le bandeau vide */
$bandNode = $pull($tree, $BAND);
echo ($bandNode ? "bandeau {$BAND} supprimé\n" : "bandeau {$BAND} introuvable\n");

/* 3) construire la 4e colonne */
$col = [
    'id'       => $COL,
    'elType'   => 'container',
    'settings' => [
        '_css_classes'   => 'mdb-foot-col mdb-foot-col--nl',
        'flex_direction' => 'column',
    ],
    'elements' => array_values(array_filter([$eyeNode, $formNode])),
];

/* 4) ajouter la colonne en fin du tier nav */
$added = 0;
$append = function (&$els, $parentId, $node) use (&$append, &$added) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $parentId) { $el['elements'][] = $node; $added++; return true; }
        if (!empty($el['elements']) && $append($el['elements'], $parentId, $node)) return true;
    }
    unset($el);
    return false;
};
$append($tree, $NAVROW, $col);
echo "4e colonne {$COL} ajoutée au tier nav : {$added}\n";

$changed = ($eyeNode || $formNode) && $added ? 1 : 0;
echo "changes: {$changed}\n";

if ($APPLY && $changed) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
