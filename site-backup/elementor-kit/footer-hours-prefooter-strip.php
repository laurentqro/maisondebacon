<?php
/* Footer (108363) : rééquilibrage (demande client — la 4e colonne était trop
 * longue, gros vide au centre).
 *
 *  - On SORT le widget horaires (e37b287) de la colonne « Nous contacter »
 *    (qui ne garde donc que Réservations + 3 e-mails).
 *  - On INSÈRE en tête de #mdb-footer un nouveau conteneur horizontal
 *    « pre-footer » (.mdb-foot-hours-strip) contenant deux blocs côte à côte :
 *    Horaires Restaurant | Horaires Le Roof Top.
 *
 * Le strip est fait de vrais widgets text-editor (éditables par l'éditrice).
 * Le CSS de .mdb-foot-hours-strip est ajouté séparément dans overrides.css.
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY  = getenv('MDB_APPLY') === '1';
$POST   = 108363;
$FOOTER = '19412bc';   // conteneur racine #mdb-footer
$HOURS  = 'e37b287';   // widget horaires à retirer de la colonne contact

$STRIP_ID = '97020d4';
$REST_ID  = '8f55450';
$ROOF_ID  = '29e34d7';

$REST_HTML = '<p class="mdb-foot-sub">Horaires Restaurant</p>'
           . '<p>Ouvert tous les jours<br>12h &ndash; 14h &middot; 19h &ndash; 22h</p>';
$ROOF_HTML = '<p class="mdb-foot-sub">Horaires Le Roof Top</p>'
           . '<p>Du mercredi au dimanche &middot; d&egrave;s 18h<br>Tenue &eacute;l&eacute;gante</p>';

$mk_text = function ($id, $html, $cls) {
    return [
        'id'         => $id,
        'elType'     => 'widget',
        'widgetType' => 'text-editor',
        'settings'   => ['editor' => $html, '_css_classes' => $cls],
        'elements'   => [],
    ];
};

$strip = [
    'id'       => $STRIP_ID,
    'elType'   => 'container',
    'settings' => [
        '_css_classes'   => 'mdb-foot-hours-strip',
        'flex_direction' => 'row',
        'content_width'  => 'full',
    ],
    'elements' => [
        $mk_text($REST_ID, $REST_HTML, 'mdb-foot-hours mdb-foot-hours--rest'),
        $mk_text($ROOF_ID, $ROOF_HTML, 'mdb-foot-hours mdb-foot-hours--roof'),
    ],
];

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/footer-108363-prefooter-strip-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;

/* 1) retire le widget horaires e37b287 partout où il se trouve */
$removed = 0;
$strip_walk = function (&$els) use (&$strip_walk, $HOURS, &$removed) {
    foreach ($els as $k => &$el) {
        if (($el['id'] ?? '') === $HOURS) {
            unset($els[$k]);
            $removed++;
            continue;
        }
        if (!empty($el['elements'])) $strip_walk($el['elements']);
    }
    unset($el);
    $els = array_values($els);
};
$strip_walk($tree);
echo "horaires {$HOURS} retire de la colonne contact : {$removed}\n";

/* 2) insère le strip en tête des enfants de #mdb-footer (19412bc) */
$inserted    = 0;
$already      = false;
$insert_walk = function (&$els) use (&$insert_walk, $FOOTER, $strip, $STRIP_ID, &$inserted, &$already) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $FOOTER) {
            foreach (($el['elements'] ?? []) as $child) {
                if (($child['id'] ?? '') === $STRIP_ID) { $already = true; }
            }
            if (!$already) {
                array_unshift($el['elements'], $strip);
                $inserted++;
            }
            return; // racine unique
        }
        if (!empty($el['elements'])) $insert_walk($el['elements']);
    }
    unset($el);
};
$insert_walk($tree);
echo $already ? "strip {$STRIP_ID} deja present, pas de double insertion\n"
              : "strip {$STRIP_ID} insere en tete de #mdb-footer : {$inserted}\n";

$changed = $removed + $inserted;
echo "changes: {$changed}\n";

if ($APPLY && $changed) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
