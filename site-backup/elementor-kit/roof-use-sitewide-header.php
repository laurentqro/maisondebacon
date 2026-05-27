<?php
/* La page « Le Roof Top » (106766) affichait l'ancien en-tête orange (header
 * Elementor 106823), épinglé via la condition include/singular/page/106766 —
 * ce qui prenait le pas sur l'en-tête général « MDB Header » (110538,
 * include/general). On retire cette condition pour que la page utilise le
 * même header sitewide que tout le reste du site.
 *
 * Conditions Elementor Pro stockées à deux endroits :
 *   - post meta _elementor_conditions du template 106823
 *   - option elementor_pro_theme_builder_conditions['header'][106823]
 * On vide les deux + on backup l'option. DRY par défaut ; MDB_APPLY=1. */
$APPLY  = getenv('MDB_APPLY') === '1';
$HEADER = 106823;          // ancien header orange à dé-épingler
$PAGE   = 106766;          // page Roof Top
$COND   = 'include/singular/page/' . $PAGE;

@mkdir('/tmp/mdb-evfix', 0775, true);
$opt = get_option('elementor_pro_theme_builder_conditions');
file_put_contents(
    '/tmp/mdb-evfix/tb-conditions-' . date('Ymd-His') . '.json',
    wp_json_encode($opt)
);

$meta = get_post_meta($HEADER, '_elementor_conditions', true);
echo "header {$HEADER} meta conditions (avant) : " . var_export($meta, true) . "\n";
echo "option[header][{$HEADER}] (avant)       : "
    . var_export($opt['header'][$HEADER] ?? null, true) . "\n";

$changed = 0;

// 1) post meta : retirer la condition page-spécifique
if (is_array($meta)) {
    $new = array_values(array_filter($meta, fn($c) => $c !== $COND));
    if ($new !== $meta) {
        echo $APPLY
            ? "  -> update_post_meta _elementor_conditions = " . var_export($new, true) . "\n"
            : "  (dry) update_post_meta _elementor_conditions = " . var_export($new, true) . "\n";
        if ($APPLY) {
            if ($new) { update_post_meta($HEADER, '_elementor_conditions', $new); }
            else      { delete_post_meta($HEADER, '_elementor_conditions'); }
        }
        $changed++;
    }
}

// 2) option index : retirer 106823 de la section header (plus aucune condition)
if (is_array($opt) && isset($opt['header'][$HEADER])) {
    $hdr = $opt['header'][$HEADER];
    $hdr = array_values(array_filter((array) $hdr, fn($c) => $c !== $COND));
    if (!$hdr) {
        unset($opt['header'][$HEADER]);
        echo $APPLY ? "  -> option : suppression de header[{$HEADER}]\n"
                    : "  (dry) option : suppression de header[{$HEADER}]\n";
    } else {
        $opt['header'][$HEADER] = $hdr;
        echo $APPLY ? "  -> option : header[{$HEADER}] = " . var_export($hdr, true) . "\n"
                    : "  (dry) option : header[{$HEADER}] = " . var_export($hdr, true) . "\n";
    }
    if ($APPLY) { update_option('elementor_pro_theme_builder_conditions', $opt); }
    $changed++;
}

echo "changes: {$changed}\n";
echo $APPLY ? "APPLIED\n" : "(dry run)\n";
