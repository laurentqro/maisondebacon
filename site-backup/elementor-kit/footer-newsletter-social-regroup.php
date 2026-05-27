<?php
/* Footer (108363) : regrouper les moyens de rester en contact (demande client).
 *
 *  1) Eyebrow newsletter b73c13b : « Restez informés » -> « Rester informé ».
 *  2) Réseaux sociaux 4a942b2 : déplacés du tier légal 878265c vers la colonne
 *     newsletter 27fcfe8, juste après le formulaire f0d5e56. Classe passée de
 *     mdb-foot-social--bottom à mdb-foot-social--nl.
 *
 *  Le tier légal ne garde alors que le © / mentions (3867107).
 *  DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY  = getenv('MDB_APPLY') === '1';
$POST   = 108363;
$EYE    = 'b73c13b';   // eyebrow newsletter
$SOCIAL = '4a942b2';   // social-icons
$LEGAL  = '878265c';   // tier légal (source)
$NLCOL  = '27fcfe8';   // colonne newsletter (cible)
$FORM   = 'f0d5e56';   // formulaire (insérer après)

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/footer-108363-nl-social-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;

/* idempotence : social déjà dans la colonne newsletter ? */
$already = false;
(function ($els) use (&$already, $NLCOL, $SOCIAL) {
    $scan = function ($els, $pid) use (&$scan, $NLCOL, $SOCIAL, &$already) {
        foreach ($els as $e) {
            if (($e['id'] ?? '') === $SOCIAL && $pid === $NLCOL) $already = true;
            if (!empty($e['elements'])) $scan($e['elements'], $e['id'] ?? '');
        }
    };
    $scan($els, 'root');
})($tree);

/* 1) renommer l'eyebrow */
$renamed = 0;
$rename = function (&$els, $id, $txt) use (&$rename, &$renamed) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $id) { $el['settings']['title'] = $txt; $renamed++; return true; }
        if (!empty($el['elements']) && $rename($el['elements'], $id, $txt)) return true;
    }
    unset($el);
    return false;
};
$rename($tree, $EYE, 'Rester informé');
echo "eyebrow {$EYE} renommé : {$renamed}\n";

if ($already) {
    echo "réseaux {$SOCIAL} déjà dans la colonne newsletter — pas de déplacement\n";
} else {
    /* 2a) extraire le social du tier légal */
    $pull = function (&$els, $id) use (&$pull) {
        foreach ($els as $k => &$el) {
            if (($el['id'] ?? '') === $id) { $n=$el; unset($els[$k]); $els=array_values($els); return $n; }
            if (!empty($el['elements'])) { $n=$pull($el['elements'], $id); if ($n!==null) return $n; }
        }
        unset($el);
        return null;
    };
    $soc = $pull($tree, $SOCIAL);
    if ($soc) {
        $soc['settings']['_css_classes'] = 'mdb-foot-social mdb-foot-social--nl';
        echo "réseaux {$SOCIAL} extraits du tier légal\n";
    } else {
        echo "réseaux {$SOCIAL} introuvables\n";
    }

    /* 2b) insérer après le formulaire dans la colonne newsletter */
    $placed = 0;
    $insertAfter = function (&$els, $parentId, $afterId, $node) use (&$insertAfter, &$placed) {
        foreach ($els as &$el) {
            if (($el['id'] ?? '') === $parentId) {
                $out=[]; $done=false;
                foreach (($el['elements'] ?? []) as $c) { $out[]=$c; if (($c['id'] ?? '')===$afterId){ $out[]=$node; $done=true; $placed++; } }
                if (!$done) { $out[]=$node; $placed++; }
                $el['elements']=$out;
                return true;
            }
            if (!empty($el['elements']) && $insertAfter($el['elements'], $parentId, $afterId, $node)) return true;
        }
        unset($el);
        return false;
    };
    if ($soc) $insertAfter($tree, $NLCOL, $FORM, $soc);
    echo "réseaux placés dans la colonne newsletter : {$placed}\n";
}

$changed = $renamed + ($already ? 0 : 1);
echo "changes: {$changed}\n";

if ($APPLY && $changed) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
