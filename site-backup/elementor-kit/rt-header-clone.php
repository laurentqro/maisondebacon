<?php
/* Clone du template Elementor header 110538 « MDB Header » en version Roof Top.
 *
 *  - Crée un NOUVEAU post elementor_library (type=header) en copiant le
 *    post_content + tous les meta `_elementor_*` du 110538.
 *  - Dans la copie : swap du logo (widget image `mdbhdrlogo`) vers le logo
 *    orange RT (attachment 110396, RT-…-orange_fonce.webp), lien vers
 *    /le-rooftop-club-bacon/.
 *  - Burger `mdbhdrtgl` et CTA `mdbhdrcta` laissés tels quels (wiring
 *    `data-mdb-menu-open`/`aria-controls=mdb-panel` intact).
 *  - L'accent terracotta du header RT vient du CSS thème scellé au
 *    body.page-id-106766, PAS de couleurs en dur ici (pour rester pilotable).
 *  - NE branche PAS la condition Theme Builder ici : c'est rt-header-condition.php
 *    qui s'en charge (deux étapes = backup propre entre les deux).
 *  - Idempotent : si un post titré « RT Header » existe déjà, on MET À JOUR au
 *    lieu de créer un doublon. L'ID du clone est imprimé pour l'étape suivante.
 *  DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */

$APPLY      = getenv('MDB_APPLY') === '1';
$SRC_ID     = 110538;              // MDB Header
$CLONE_TITLE = 'RT Header';
$RT_LOGO_ID = 110396;              // RT-maisondebacon-ss-blason-orange_fonce.webp
$RT_LOGO_URL = 'https://staging.maisondebacon.fr/wp-content/uploads/2026/05/RT-maisondebacon-ss-blason-orange_fonce.webp';
$RT_HOME_URL = 'https://staging.maisondebacon.fr/le-rooftop-club-bacon/';

global $wpdb;
@mkdir('/tmp/mdb-evfix', 0775, true);
$ts = date('Ymd-His');

/* 1) Récupérer le post source + ses meta. */
$src = get_post($SRC_ID);
if (!$src) { echo "ABORT: source post {$SRC_ID} introuvable\n"; return; }
$src_meta = get_post_meta($SRC_ID);
echo "src 110538 status={$src->post_status} type={$src->post_type}\n";

/* 2) Récupérer / décoder le _elementor_data source. */
$src_data_raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
    $SRC_ID, '_elementor_data'
));
$tree = json_decode($src_data_raw, true);
if (!is_array($tree)) { echo "ABORT: _elementor_data source invalide\n"; return; }
file_put_contents("/tmp/mdb-evfix/rt-clone-src-tree-{$ts}.json", $src_data_raw);
echo "source tree dumped (" . strlen($src_data_raw) . " bytes)\n";

/* 3) Patch : remplacer le logo dans la copie. Recherche récursive de l'élément
   image id=mdbhdrlogo. Modifications minimales : url, id (attachment),
   alt, et lien éventuel. */
$logo_patched = 0;
$patch_logo = function (&$els) use (&$patch_logo, &$logo_patched, $RT_LOGO_ID, $RT_LOGO_URL, $RT_HOME_URL) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === 'mdbhdrlogo' && ($el['widgetType'] ?? '') === 'image') {
            $el['settings']['image'] = array(
                'id'  => $RT_LOGO_ID,
                'url' => $RT_LOGO_URL,
                'alt' => 'Le Roof Top — par la Maison de Bacon',
                'source' => 'library',
            );
            // S'il y a un lien sur l'image, le pointer vers la home RT.
            if (!empty($el['settings']['link']) && is_array($el['settings']['link'])) {
                $el['settings']['link']['url'] = $RT_HOME_URL;
            }
            $logo_patched++;
            return true;
        }
        if (!empty($el['elements']) && $patch_logo($el['elements'])) return true;
    }
    unset($el);
    return false;
};
$patch_logo($tree);
echo "logo mdbhdrlogo patché : {$logo_patched}\n";
if (!$logo_patched) { echo "ABORT: widget mdbhdrlogo introuvable dans le tree\n"; return; }

$patched_json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
file_put_contents("/tmp/mdb-evfix/rt-clone-patched-tree-{$ts}.json", $patched_json);

/* 4) Idempotence : un post titré « RT Header » existe-t-il déjà ? */
$existing = get_posts(array(
    'post_type'   => 'elementor_library',
    'post_status' => array('publish','draft','private'),
    'title'       => $CLONE_TITLE,
    'numberposts' => 1,
    'fields'      => 'ids',
));
$clone_id = !empty($existing) ? (int) $existing[0] : 0;

if ($clone_id) {
    echo "EXISTING clone trouvé : ID={$clone_id} (mise à jour)\n";
} else {
    echo "Aucun clone existant — création\n";
}

if (!$APPLY) {
    echo "(dry run) — clone_id cible: " . ($clone_id ?: 'NEW') . "\n";
    return;
}

/* 5) Créer ou mettre à jour le post. */
$postarr = array(
    'post_title'   => $CLONE_TITLE,
    'post_status'  => 'publish',
    'post_type'    => 'elementor_library',
    'post_content' => $src->post_content,
);
if ($clone_id) {
    $postarr['ID'] = $clone_id;
    $clone_id = wp_update_post($postarr, true);
} else {
    $clone_id = wp_insert_post($postarr, true);
}
if (is_wp_error($clone_id) || !$clone_id) {
    echo "ABORT: création/maj clone échouée: " . (is_wp_error($clone_id) ? $clone_id->get_error_message() : 'unknown') . "\n";
    return;
}
echo "clone post id = {$clone_id}\n";

/* 6) Copier tous les meta _elementor_* de la source vers le clone, puis écraser
   _elementor_data avec la version patchée. */
$copied = 0;
foreach ($src_meta as $key => $values) {
    if (strpos($key, '_elementor') !== 0) continue;
    foreach ($values as $val) {
        $maybe = maybe_unserialize($val);
        update_post_meta($clone_id, $key, $maybe);
        $copied++;
    }
}
echo "meta _elementor_* copiés : {$copied}\n";

// Forcer le type header (au cas où la copie ait introduit autre chose).
update_post_meta($clone_id, '_elementor_template_type', 'header');

// Écraser _elementor_data avec la version logo-patchée (wp_slash pour update_post_meta).
update_post_meta($clone_id, '_elementor_data', wp_slash($patched_json));

echo "_elementor_data RT écrit (" . strlen($patched_json) . " bytes)\n";
echo "APPLIED clone_id={$clone_id}\n";
