<?php
/* Page « Avis » (104781) : remplace l'iframe Zenchef par notre propre rendu.
 *
 * Contexte : l'iframe widget-reviews.zenchef.com est moche et non stylable
 * (cross-origin). Le serveur OVH NE PEUT PAS appeler api.zenchef.com (CloudFront
 * refuse l'IP datacenter). MAIS l'API est CORS-ouverte pour notre origine
 * (access-control-allow-origin reflète le domaine) -> on fetch CÔTÉ CLIENT.
 *
 * On réécrit le widget html 3f82a0d : markup d'agrégat + grille + lien Zenchef,
 * et un <script> qui fetch /reviews?page=1 + /reviewParams, filtre les avis
 * RÉCENTS AVEC TEXTE (~12), et rend des cartes stylées (CSS scoped .elementor-104781).
 *
 * Endpoints (publics, CORS-ok) :
 *   https://api.zenchef.com/api/v1/restaurants/354476/reviews?page=N
 *   https://api.zenchef.com/api/v1/restaurants/354476/reviewParams
 *
 * RGPD : on n'affiche QUE firstname + initiale nom + note + commentaire + date.
 * Jamais le bloc customersheet (email/phone/ids).
 *
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY   = getenv('MDB_APPLY') === '1';
$POST    = 104781;
$HTMLW   = '3f82a0d';   // widget html (iframe -> notre markup)
$RID     = '354476';

$markup = <<<'HTML'
<div class="mdb-reviews" data-rid="354476" data-zenchef="https://bookings.zenchef.com/results?rid=354476&pid=1001">
  <div class="mdb-reviews__summary" data-state="loading">
    <div class="mdb-reviews__score">
      <span class="mdb-reviews__avg" data-avg>—</span><span class="mdb-reviews__avg-max">/5</span>
    </div>
    <div class="mdb-reviews__stars" data-stars aria-hidden="true"></div>
    <p class="mdb-reviews__count"><span data-count>—</span> avis vérifiés sur Zenchef</p>
    <ul class="mdb-reviews__breakdown" data-breakdown></ul>
  </div>
  <div class="mdb-reviews__grid" data-grid>
    <p class="mdb-reviews__loading" data-loading>Chargement des avis…</p>
  </div>
  <a class="mdb-reviews__source" data-source href="https://bookings.zenchef.com/results?rid=354476&pid=1001" target="_blank" rel="noopener">
    <span class="mdb-reviews__source-label">Voir tous les avis sur</span>
    <span class="mdb-reviews__source-logo" role="img" aria-label="Zenchef"></span>
  </a>
</div>
<script>
(function(){
  var ROOT = document.querySelector('.mdb-reviews[data-rid="354476"]');
  if(!ROOT || ROOT.dataset.mdbInit) return; ROOT.dataset.mdbInit='1';
  var RID = ROOT.dataset.rid;
  var API = 'https://api.zenchef.com/api/v1/restaurants/'+RID;
  var WANT = 12, MAXPAGES = 4;
  function stars(n){ n=Math.round(n||0); var s=''; for(var i=0;i<5;i++){ s+= i<n ? '★' : '☆'; } return s; }
  function esc(t){ var d=document.createElement('div'); d.textContent=t==null?'':String(t); return d.innerHTML; }
  function fmtDate(d){ try{ var x=new Date(d); return x.toLocaleDateString('fr-FR',{day:'numeric',month:'long',year:'numeric'});}catch(e){return '';} }
  function name(b){ if(!b) return 'Client'; var f=(b.firstname||'').trim(); var l=(b.lastname||'').trim(); return (f? f : 'Client') + (l? ' '+l : ''); }

  function renderSummary(p){
    var sum = ROOT.querySelector('.mdb-reviews__summary'); if(!sum) return;
    var avg = (p.average_global||0);
    ROOT.querySelector('[data-avg]').textContent = avg.toFixed(1).replace('.',',');
    ROOT.querySelector('[data-stars]').textContent = stars(avg);
    ROOT.querySelector('[data-count]').textContent = (p.reviews_count||0).toLocaleString('fr-FR');
    var bd = ROOT.querySelector('[data-breakdown]');
    var rows = [['Service',p.average_service],['Ambiance',p.average_ambiance],['La carte',p.average_menu],['Qualité / prix',p.average_value_for_money]];
    bd.innerHTML = rows.map(function(r){
      var v=(r[1]||0); var pct=Math.max(0,Math.min(100,v/5*100));
      return '<li><span class="mdb-reviews__bd-label">'+esc(r[0])+'</span>'
        +'<span class="mdb-reviews__bd-bar"><span style="width:'+pct.toFixed(0)+'%"></span></span>'
        +'<span class="mdb-reviews__bd-val">'+v.toFixed(1).replace('.',',')+'</span></li>';
    }).join('');
    sum.setAttribute('data-state','ready');
  }

  function card(r){
    var b=r.booking||{}; var body=(r.body||'').trim();
    return '<figure class="mdb-review">'
      +'<div class="mdb-review__stars" aria-label="'+(r.global||0)+' sur 5">'+stars(r.global)+'</div>'
      +'<blockquote class="mdb-review__body">'+esc(body)+'</blockquote>'
      +'<figcaption class="mdb-review__meta"><span class="mdb-review__name">'+esc(name(b))+'</span>'
      +'<span class="mdb-review__date">'+esc(fmtDate(r.source_date||r.created_at))+'</span></figcaption>'
      +'</figure>';
  }

  function fetchJSON(u){ return fetch(u,{credentials:'omit'}).then(function(r){ if(!r.ok) throw 0; return r.json(); }); }

  function gatherReviews(){
    var acc=[];
    function go(page){
      return fetchJSON(API+'/reviews?page='+page).then(function(j){
        var data=(j&&j.data)||[];
        data.forEach(function(r){ if((r.body||'').trim().length>0) acc.push(r); });
        if(acc.length>=WANT || page>=MAXPAGES || !j.next_page_url) return acc;
        return go(page+1);
      });
    }
    return go(1);
  }

  function renderGrid(list){
    var grid=ROOT.querySelector('[data-grid]'); if(!grid) return;
    if(!list.length){ grid.innerHTML='<p class="mdb-reviews__loading">Avis bientôt disponibles.</p>'; return; }
    grid.innerHTML = list.slice(0,WANT).map(card).join('');
  }

  function fail(){
    var grid=ROOT.querySelector('[data-grid]');
    if(grid) grid.innerHTML='<p class="mdb-reviews__loading">Retrouvez tous nos avis sur Zenchef.</p>';
    var sum=ROOT.querySelector('.mdb-reviews__summary'); if(sum) sum.setAttribute('data-state','error');
  }

  Promise.all([ fetchJSON(API+'/reviewParams').then(renderSummary).catch(function(){}), gatherReviews().then(renderGrid) ])
    .catch(fail);
})();
</script>
HTML;

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/avis-104781-custom-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;

$done = 0;
$set = function (&$els, $id, $html) use (&$set, &$done) {
    foreach ($els as &$el) {
        if (($el['id'] ?? '') === $id) {
            $el['settings']['html'] = $html;
            $done++;
            return true;
        }
        if (!empty($el['elements']) && $set($el['elements'], $id, $html)) return true;
    }
    unset($el);
    return false;
};
$set($tree, $HTMLW, $markup);
echo "widget html {$HTMLW} réécrit : {$done}\n";
echo "changes: {$done}\n";

if ($APPLY && $done) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
