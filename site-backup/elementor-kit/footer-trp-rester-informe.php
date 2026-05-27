<?php
/* TRP (fr_fr -> en_us) : nouvelle chaîne newsletter « Rester informé »
 * (remplace « Restez informés »). EN : « Stay informed », status 2 (revu). */
$APPLY = getenv('MDB_APPLY') === '1';
global $wpdb;
$t = $wpdb->prefix . 'trp_dictionary_fr_fr_en_us';
$fr = 'Rester informé'; $en = 'Stay informed';
$row = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$t} WHERE original=%s", $fr), ARRAY_A);
if ($row) {
    echo "EXISTS {$fr}\n"; if ($APPLY) $wpdb->update($t, ['translated'=>$en,'status'=>2], ['id'=>$row['id']]);
} else {
    echo "INSERT {$fr} => {$en}\n"; if ($APPLY) $wpdb->insert($t, ['original'=>$fr,'translated'=>$en,'status'=>2,'block_type'=>0,'original_id'=>null]);
}
echo $APPLY ? "APPLIED\n" : "(dry run)\n";
