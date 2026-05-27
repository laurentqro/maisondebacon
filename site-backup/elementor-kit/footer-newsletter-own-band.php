<?php
/* Footer (108363) : sortir la newsletter de la colonne marque (demande client).
 *
 * Dans le tier 1, la colonne marque contenait logo + adresse + social +
 * eyebrow « Restez informés » (b73c13b) + formulaire (f0d5e56). Le formulaire
 * rendait la cellule beaucoup plus haute que les blocs horaires voisins ->
 * gros vide à droite + filets verticaux traversant le vide.
 *
 * On DÉPLACE b73c13b + f0d5e56 hors de 5a1bc48 et on les place dans un NOUVEAU
 * conteneur-bandeau (mdb-foot-nl-band, id 09fb99f) inséré entre le tier nav
 * (303d9d2) et la barre légale (878265c). Le bandeau est centré (CSS).
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY  = getenv('MDB_APPLY') === '1';
$POST   = 108363;
$FOOTER = '19412bc';   // racine
$NAVROW = '303d9d2';   // tier nav (le bandeau s'insère juste après)
$EYE    = 'b73c13b';   // eyebrow « Restez informés »
$FORM   = 'f0d5e56';   // shortcode newsletter
$BAND   = '09fb99f';

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/footer-108363-newsletter-band-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;

/* idempotence : si le bandeau existe déjà, on ne refait rien */
$exists = false;
$scan = function ($els) use (&$scan, $BAND, &$exists) {
    foreach ($els as $el) {
        if (($el['id'] ?? '') === $BAND) $exists = true;
        if (!empty($el['elements'])) $scan($el['elements']);
    }
};
$scan($tree);
if ($exists) { echo "bandeau {$BAND} déjà présent — rien à faire\n(dry run)\n"; return; }

/* 1) extraire les 2 widgets (dans l'ordre eyebrow puis form) */
$grabbed = [];
$want    = [$EYE, $FORM];
$extract = function (&$els) use (&$extract, $want, &$grabbed) {
    foreach ($els as $k => &$el) {
        if (in_array(($el['id'] ?? ''), $want, true)) {
            $grabbed[$el['id']] = $el;
            unset($els[$k]);
            continue;
        }
        if (!empty($el['elements'])) $extract($el['elements']);
    }
    unset($el);
    $els = array_values($els);
};
$extract($tree);
echo "widgets extraits : " . implode(', ', array_keys($grabbed)) . "\n";

$ordered = [];
foreach ($want as $id) { if (isset($grabbed[$id])) $ordered[] = $grabbed[$id]; }

/* 2) construire le bandeau */
$band = [
    'id'       => $BAND,
    'elType'   => 'container',
    'settings' => [
        '_css_classes'   => 'mdb-foot-nl-band',
        'flex_direction' => 'column',
        'content_width'  => 'full',
    ],
    'elements' => $ordered,
];

/* 3) insérer le bandeau juste après le tier nav 303d9d2, dans #mdb-footer */
$inserted = 0;
$insert = function (&$els) use (&$insert, $FOOTER, $NAVROW, $band, &$inserted) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $FOOTER) {
            $children = $el['elements'] ?? [];
            $out = [];
            foreach ($children as $c) {
                $out[] = $c;
                if (($c['id'] ?? '') === $NAVROW) { $out[] = $band; $inserted++; }
            }
            if (!$inserted) { $out[] = $band; $inserted++; } // fallback : en fin
            $el['elements'] = $out;
            return true;
        }
        if (!empty($el['elements']) && $insert($el['elements'])) return true;
    }
    unset($el);
    return false;
};
$insert($tree);
echo "bandeau {$BAND} inséré après {$NAVROW} : {$inserted}\n";

$changed = count($ordered) && $inserted ? 1 : 0;
echo "changes: {$changed}\n";

if ($APPLY && $changed) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
