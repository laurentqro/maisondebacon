<?php
/* Brancher le clone RT Header (110554) à la condition Theme Builder
 * « header → include/singular/page/106766 ». Le 110538 reste général.
 *
 *  Elementor stocke ses conditions dans DEUX endroits :
 *   - option `elementor_pro_theme_builder_conditions` (cache global)
 *   - postmeta `_elementor_conditions` du template lui-même
 *  On met à jour LES DEUX pour être cohérent avec ce que fait l'UI.
 *
 *  Idempotent : si la condition est déjà présente, ne rebascule rien.
 *  DRY par défaut ; MDB_APPLY=1. Backup option dans /tmp/mdb-evfix/. */

$APPLY    = getenv('MDB_APPLY') === '1';
$CLONE_ID = 110554;                          // RT Header
$COND     = 'include/singular/page/106766';  // syntax Elementor TB

$opt_key = 'elementor_pro_theme_builder_conditions';
$conditions = get_option($opt_key, array());
@mkdir('/tmp/mdb-evfix', 0775, true);
$ts  = date('Ymd-His');
file_put_contents("/tmp/mdb-evfix/tb-conditions-pre-{$ts}.json", wp_json_encode($conditions, JSON_UNESCAPED_SLASHES));
echo "backup -> /tmp/mdb-evfix/tb-conditions-pre-{$ts}.json\n";

if (!is_array($conditions)) $conditions = array();
if (!isset($conditions['header']) || !is_array($conditions['header'])) $conditions['header'] = array();

/* 1) Vérifier que 110538 reste en include/general (déjà attendu). */
echo "header.110538 actuel = " . wp_json_encode($conditions['header'][110538] ?? null) . "\n";

/* 2) Brancher / actualiser le clone. */
$existing = $conditions['header'][$CLONE_ID] ?? array();
$already  = is_array($existing) && in_array($COND, $existing, true);
echo "header.{$CLONE_ID} actuel = " . wp_json_encode($existing) . " — déjà branché ? " . ($already ? 'OUI' : 'NON') . "\n";

if (!$already) {
    $conditions['header'][$CLONE_ID] = array($COND);
}

/* 3) Synchroniser le postmeta `_elementor_conditions` du clone (l'UI s'y fie). */
$meta_existing = get_post_meta($CLONE_ID, '_elementor_conditions', true);
echo "postmeta _elementor_conditions actuel = " . wp_json_encode($meta_existing) . "\n";
$meta_new = array($COND);

echo "\ndelta attendu :\n";
echo "  option header.{$CLONE_ID} = " . wp_json_encode($conditions['header'][$CLONE_ID]) . "\n";
echo "  postmeta {$CLONE_ID} _elementor_conditions = " . wp_json_encode($meta_new) . "\n";

if (!$APPLY) {
    echo "(dry run)\n";
    return;
}

/* 4) Écrire. */
$ok1 = update_option($opt_key, $conditions);
$ok2 = update_post_meta($CLONE_ID, '_elementor_conditions', $meta_new);
echo "update option -> " . var_export($ok1, true) . "\n";
echo "update postmeta -> " . var_export($ok2, true) . "\n";

/* 5) Confirmation. */
$fresh = get_option($opt_key, array());
echo "header.{$CLONE_ID} après écriture = " . wp_json_encode($fresh['header'][$CLONE_ID] ?? null) . "\n";
echo "APPLIED\n";
