<?php
/* RT Header (110554) : remplacer le logo orange (attach 110396) par le logo
 *  sand (attach 110555) pour qu'il reste lisible sur la barre terracotta.
 *  Burger + CTA inchangés.
 *  DRY par défaut ; MDB_APPLY=1. Backup dans /tmp/mdb-evfix/. */

$APPLY     = getenv('MDB_APPLY') === '1';
$HEADER_ID = 110554;
$NEW_ID    = 110555;

global $wpdb;
@mkdir('/tmp/mdb-evfix', 0775, true);
$ts = date('Ymd-His');

$raw = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s",
    $HEADER_ID, '_elementor_data'
));
$tree = json_decode($raw, true);
if (!is_array($tree)) { echo "ABORT: _elementor_data invalide\n"; return; }
file_put_contents("/tmp/mdb-evfix/rt-header-{$HEADER_ID}-sand-pre-{$ts}.json", $raw);
echo "backup -> /tmp/mdb-evfix/rt-header-{$HEADER_ID}-sand-pre-{$ts}.json\n";

$new_url = wp_get_attachment_url($NEW_ID);
if (!$new_url) { echo "ABORT: attachment {$NEW_ID} introuvable\n"; return; }
echo "new logo url: {$new_url}\n";

$hits = 0;
$patch = function (&$els) use (&$patch, $NEW_ID, $new_url, &$hits) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === 'mdbhdrlogo' && ($el['widgetType'] ?? '') === 'image') {
            $cur = $el['settings']['image'] ?? array();
            echo "  current image: id=" . ($cur['id'] ?? '?') . " url=" . ($cur['url'] ?? '?') . "\n";
            $el['settings']['image'] = array(
                'id'  => $NEW_ID,
                'url' => $new_url,
                'alt' => 'Le Roof Top — par la Maison de Bacon',
                'source' => 'library',
            );
            $hits++;
            return true;
        }
        if (!empty($el['elements']) && $patch($el['elements'])) return true;
    }
    unset($el);
    return false;
};
$patch($tree);
echo "patched : {$hits}\n";

if (!$APPLY || !$hits) {
    echo $hits ? "(dry run)\n" : "(rien à faire)\n";
    return;
}

$json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$ok = update_post_meta($HEADER_ID, '_elementor_data', wp_slash($json));
echo "update_post_meta -> " . var_export($ok, true) . "\n";

// Bust element cache so Elementor re-renders the header.
delete_post_meta($HEADER_ID, '_elementor_element_cache');
echo "element cache nuked\n";
echo "APPLIED\n";
