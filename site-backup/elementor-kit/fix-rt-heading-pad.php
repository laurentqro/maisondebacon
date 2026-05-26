<?php
/* Normalise le padding du titre Roof Top (5ba5eec4) : les titres Restaurant
 * et Appartement ont padding-bottom:20px, pas celui-ci -> carte 20px plus
 * courte, titre/CTA désalignés. On aligne sur les deux autres. */
$APPLY = getenv("MDB_APPLY") === "1";
$POST = 106136; $W = "5ba5eec4";
$PAD = array("unit"=>"px","top"=>"0","right"=>"0","bottom"=>"20","left"=>"0","isLinked"=>false);
$data = get_post_meta($POST,"_elementor_data",true);
$bak="/tmp/mdb-evfix/home-106136-rtpad-".date("Ymd-His").".json";
file_put_contents($bak, is_string($data)?$data:wp_json_encode($data));
echo "backup -> $bak\n";
$tree = is_string($data)?json_decode($data,true):$data; $changed=0;
$walk=function(&$els) use(&$walk,$W,$PAD,&$changed){
  foreach($els as &$el){
    if(($el["id"]??"")===$W && ($el["widgetType"]??"")==="heading"){
      echo "  set _padding on $W: ".json_encode($el["settings"]["_padding"]??null)." -> ".json_encode($PAD)."\n";
      $el["settings"]["_padding"]=$PAD; $changed++;
    }
    if(!empty($el["elements"])) $walk($el["elements"]);
  }
  unset($el);
};
$walk($tree);
echo "changes: $changed\n";
if($APPLY && $changed){
  $json=wp_json_encode($tree,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  echo "update -> ".var_export(update_post_meta($POST,"_elementor_data",wp_slash($json)),true)."\n";
} else echo "(dry run)\n";
