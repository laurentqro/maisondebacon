<?php
/* Déprécier la page 109283 (/le-rooftop-club-bacon/) :
 *  1) Passer son post_status à 'draft' (la page disparaît du front, mais reste
 *     éditable dans l'admin si besoin de la restaurer).
 *  2) Enregistrer une redirection 301 /le-rooftop-club-bacon/ → /le-roof-top/
 *     via l'option mdb_redirects (option custom lue par un add_action('template_redirect')
 *     ajouté dans functions.php).
 *
 *  La page canonique est 106766 (/le-roof-top/), décision Laurent 2026-05-28.
 *  DRY par défaut ; MDB_APPLY=1. Backup post + redirects dans /tmp/mdb-evfix/. */

$APPLY = getenv('MDB_APPLY') === '1';
$POST  = 109283;
$FROM  = '/le-rooftop-club-bacon/';
$TO    = '/le-roof-top/';

global $wpdb;
@mkdir('/tmp/mdb-evfix', 0775, true);
$ts = date('Ymd-His');

/* 1) Backup du post (status + slug). */
$p = get_post($POST);
if (!$p) { echo "ABORT: post {$POST} introuvable\n"; return; }
echo "current: status={$p->post_status} slug={$p->post_name}\n";
file_put_contents("/tmp/mdb-evfix/depr-{$POST}-{$ts}.json", wp_json_encode(array(
    'id' => $POST,
    'status' => $p->post_status,
    'slug' => $p->post_name,
    'title' => $p->post_title,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

/* 2) Backup de l'option mdb_redirects (au cas où). */
$existing = get_option('mdb_redirects', array());
if (!is_array($existing)) $existing = array();
file_put_contents("/tmp/mdb-evfix/mdb-redirects-pre-{$ts}.json", wp_json_encode($existing, JSON_UNESCAPED_SLASHES));
echo "existing redirects: " . wp_json_encode($existing) . "\n";

$new_redirects = $existing;
$new_redirects[$FROM] = $TO;

echo "plan:\n";
echo "  - post {$POST} -> status=draft\n";
echo "  - redirect {$FROM} -> {$TO} (option mdb_redirects)\n";

if (!$APPLY) { echo "(dry run)\n"; return; }

/* 3) Appliquer. */
$ok1 = wp_update_post(array('ID' => $POST, 'post_status' => 'draft'), true);
echo "post status update -> " . (is_wp_error($ok1) ? $ok1->get_error_message() : 'ok (id ' . $ok1 . ')') . "\n";

$ok2 = update_option('mdb_redirects', $new_redirects);
echo "option mdb_redirects update -> " . var_export($ok2, true) . "\n";

echo "APPLIED\n";
