<?php
/* Page « Le Roof Top » (106766) : retirer le bloc de clôture Instagram
 * (demande client 2026-05-28). 3 conteneurs siblings à supprimer :
 *   adca52d  heading « Rejoignez-nous sur Instagram… »
 *   0f371ac  sous-texte « Des images valent mille mots… »
 *   61fe278  bouton « Instagram Le Roof Top »
 *
 *  Le CSS scellé à ces IDs (overrides.css ~2391-2426) devient mort code —
 *  on le nettoiera en suivi, ça n'a aucun effet sans les conteneurs.
 *  DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */

$APPLY  = getenv('MDB_APPLY') === '1';
$POST   = 106766;
$TARGETS = array('adca52d', '0f371ac', '61fe278');

global $wpdb;
$raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
    $POST, '_elementor_data'
));
$tree = json_decode($raw, true);
if (!is_array($tree)) { echo "ABORT: _elementor_data invalide\n"; return; }

@mkdir('/tmp/mdb-evfix', 0775, true);
$ts  = date('Ymd-His');
file_put_contents("/tmp/mdb-evfix/rt-106766-removeig-{$ts}.json", $raw);
echo "backup -> /tmp/mdb-evfix/rt-106766-removeig-{$ts}.json (" . strlen($raw) . " bytes)\n";

$removed = 0;
$walk = function (&$els) use (&$walk, $TARGETS, &$removed) {
    foreach ($els as $k => &$el) {
        if (in_array(($el['id'] ?? ''), $TARGETS, true)) {
            echo "  removing " . $el['id'] . " (" . ($el['elType'] ?? '?') . ")\n";
            unset($els[$k]);
            $removed++;
            continue;
        }
        if (!empty($el['elements'])) $walk($el['elements']);
    }
    unset($el);
    $els = array_values($els);
};
$walk($tree);
echo "removed : {$removed} / 3\n";

if (!$APPLY || !$removed) {
    echo $removed ? "(dry run)\n" : "(rien à faire)\n";
    return;
}

$json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$ok = update_post_meta($POST, '_elementor_data', wp_slash($json));
echo "update_post_meta -> " . var_export($ok, true) . "\n";
echo "APPLIED\n";
