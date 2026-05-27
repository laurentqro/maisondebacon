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
    <p class="mdb-reviews__count"><span data-count>—</span> <span data-count-suffix>avis vérifiés sur</span> <span class="mdb-reviews__count-logo" role="img" aria-label="Zenchef"></span></p>
    <ul class="mdb-reviews__breakdown" data-breakdown></ul>
  </div>
  <div class="mdb-reviews__grid" data-grid>
    <p class="mdb-reviews__loading" data-loading>Chargement des avis…</p>
  </div>
  <button type="button" class="mdb-reviews__more" data-more hidden>
    <span data-more-label>Voir plus d'avis</span>
  </button>
</div>
<script>
(function(){
  var ROOT = document.querySelector('.mdb-reviews[data-rid="354476"]');
  if(!ROOT || ROOT.dataset.mdbInit) return; ROOT.dataset.mdbInit='1';
  var RID = ROOT.dataset.rid;

  /* Locale : la page Avis existe en FR (/avis/) et EN (/en/avis/) via
     TranslatePress, qui pose <html lang>. Les cartes étant injectées par JS,
     TRP ne peut pas les traduire -> on porte ici un mini-dictionnaire. */
  var EN = ((document.documentElement.getAttribute('lang')||'').toLowerCase().indexOf('en') === 0);
  var T = EN ? {
    locale:'en-GB', dec:'.',
    countSuffix:'verified reviews on',
    loading:'Loading reviews…',
    empty:'Reviews coming soon.',
    fail:'Find all our reviews on Zenchef.',
    anon:'Guest', outOf:'out of 5',
    covers:function(n){ return n + (n>1?' guests':' guest'); },
    more:'Show more reviews', moreLoading:'Loading…',
    bd:{service:'Service', ambiance:'Atmosphere', menu:'The menu', vfm:'Value for money'}
  } : {
    locale:'fr-FR', dec:',',
    countSuffix:'avis vérifiés sur',
    loading:'Chargement des avis…',
    empty:'Avis bientôt disponibles.',
    fail:'Retrouvez tous nos avis sur Zenchef.',
    anon:'Client', outOf:'sur 5',
    covers:function(n){ return n + (n>1?' couverts':' couvert'); },
    more:'Voir plus d\'avis', moreLoading:'Chargement…',
    bd:{service:'Service', ambiance:'Ambiance', menu:'La carte', vfm:'Qualité / prix'}
  };
  var API = 'https://api.zenchef.com/api/v1/restaurants/'+RID;

  /* applique les libellés statiques de la locale courante */
  (function(){
    var s = ROOT.querySelector('[data-count-suffix]'); if(s) s.textContent = T.countSuffix;
    var l = ROOT.querySelector('[data-loading]'); if(l) l.textContent = T.loading;
    var ml = ROOT.querySelector('[data-more-label]'); if(ml) ml.textContent = T.more;
  })();

  function num(n){ return Number(n).toFixed(1).replace('.', T.dec); }
  function stars(n){ n=Math.round(n||0); var s=''; for(var i=0;i<5;i++){ s+= i<n ? '★' : '☆'; } return s; }
  function esc(t){ var d=document.createElement('div'); d.textContent=t==null?'':String(t); return d.innerHTML; }
  function fmtDate(d){ try{ var x=new Date(d); return x.toLocaleDateString(T.locale,{day:'numeric',month:'long',year:'numeric'});}catch(e){return '';} }
  function name(b){ if(!b) return T.anon; var f=(b.firstname||'').trim(); var l=(b.lastname||'').trim(); return (f? f : T.anon) + (l? ' '+l : ''); }

  function renderSummary(p){
    var sum = ROOT.querySelector('.mdb-reviews__summary'); if(!sum) return;
    var avg = (p.average_global||0);
    ROOT.querySelector('[data-avg]').textContent = num(avg);
    ROOT.querySelector('[data-stars]').textContent = stars(avg);
    ROOT.querySelector('[data-count]').textContent = (p.reviews_count||0).toLocaleString(T.locale);
    var bd = ROOT.querySelector('[data-breakdown]');
    var rows = [[T.bd.service,p.average_service],[T.bd.ambiance,p.average_ambiance],[T.bd.menu,p.average_menu],[T.bd.vfm,p.average_value_for_money]];
    bd.innerHTML = rows.map(function(r){
      var v=(r[1]||0); var pct=Math.max(0,Math.min(100,v/5*100));
      return '<li><span class="mdb-reviews__bd-label">'+esc(r[0])+'</span>'
        +'<span class="mdb-reviews__bd-bar"><span style="width:'+pct.toFixed(0)+'%"></span></span>'
        +'<span class="mdb-reviews__bd-val">'+num(v)+'</span></li>';
    }).join('');
    sum.setAttribute('data-state','ready');
  }

  function fmtTime(t){ if(!t) return ''; var m=String(t).match(/^(\d{1,2}):(\d{2})/); if(!m) return ''; return T.dec===',' ? (m[1]+'h'+m[2]) : (m[1]+':'+m[2]); }

  function cat(label, v){
    v = v||0; var pct=Math.max(0,Math.min(100,v/5*100));
    return '<li class="mdb-review__cat"><span class="mdb-review__cat-label">'+esc(label)+'</span>'
      +'<span class="mdb-review__cat-bar"><span style="width:'+pct.toFixed(0)+'%"></span></span>'
      +'<span class="mdb-review__cat-val">'+num(v)+'</span></li>';
  }

  function card(r){
    var b=r.booking||{}; var body=(r.body||'').trim();
    /* ligne de réservation : date · heure · couverts */
    var bits=[];
    var dt=fmtDate(b.day||r.source_date||r.created_at); if(dt) bits.push(esc(dt));
    var tm=fmtTime(b.time); if(tm) bits.push(esc(tm));
    if(b.nb_guests) bits.push(esc(T.covers(b.nb_guests)));
    var bookingLine = bits.length ? '<p class="mdb-review__booking">'+bits.join(' · ')+'</p>' : '';
    /* notes par catégorie */
    var cats='<ul class="mdb-review__cats">'
      +cat(T.bd.service,r.service)+cat(T.bd.ambiance,r.ambiance)
      +cat(T.bd.menu,r.menu)+cat(T.bd.vfm,r.value_for_money)+'</ul>';

    return '<figure class="mdb-review">'
      +'<div class="mdb-review__head">'
        +'<span class="mdb-review__name">'+esc(name(b))+'</span>'
        +'<span class="mdb-review__stars" aria-label="'+(r.global||0)+' '+T.outOf+'">'+stars(r.global)+'</span>'
      +'</div>'
      + bookingLine
      + (body ? '<blockquote class="mdb-review__body">'+esc(body)+'</blockquote>' : '')
      + cats
      +'</figure>';
  }

  /* fetch + cache sessionStorage (TTL 30 min) pour limiter les appels à l'API
     publique Zenchef (cf. doc : « all calls are monitored »). Une vue répétée
     ou une bascule FR/EN dans la même session ne re-télécharge pas. */
  var TTL = 30*60*1000;
  function fetchJSON(u){
    var key='mdbz:'+u, now=Date.now();
    try{ var c=sessionStorage.getItem(key); if(c){ var o=JSON.parse(c); if(o&&(now-o.t)<TTL) return Promise.resolve(o.d); } }catch(e){}
    return fetch(u,{credentials:'omit'}).then(function(r){ if(!r.ok) throw 0; return r.json(); }).then(function(d){
      try{ sessionStorage.setItem(key, JSON.stringify({t:now,d:d})); }catch(e){}
      return d;
    });
  }

  /* pagination : 1 page API = 10 avis (tous, y c. note seule), bouton « voir plus » */
  var grid   = ROOT.querySelector('[data-grid]');
  var moreBtn= ROOT.querySelector('[data-more]');
  var moreLbl= ROOT.querySelector('[data-more-label]');
  var page=0, lastPage=1, busy=false, loadedAny=false;

  function setMore(state){
    if(!moreBtn) return;
    if(state==='hidden'){ moreBtn.hidden=true; return; }
    moreBtn.hidden=false;
    moreBtn.disabled = (state==='loading');
    if(moreLbl) moreLbl.textContent = (state==='loading') ? T.moreLoading : T.more;
  }

  function loadPage(){
    if(busy) return; busy=true;
    if(page>0) setMore('loading');
    page++;
    fetchJSON(API+'/reviews?page='+page).then(function(j){
      lastPage = j.last_page || lastPage;
      var data=(j&&j.data)||[];
      if(page===1 && grid) grid.innerHTML='';
      if(data.length){
        loadedAny=true;
        if(grid) grid.insertAdjacentHTML('beforeend', data.map(card).join(''));
      }
      if(page===1 && !loadedAny && grid){ grid.innerHTML='<p class="mdb-reviews__loading">'+esc(T.empty)+'</p>'; }
      setMore(page < lastPage ? 'idle' : 'hidden');
      busy=false;
    }).catch(function(){ busy=false; if(page===1) fail(); else setMore('idle'); });
  }

  if(moreBtn) moreBtn.addEventListener('click', loadPage);

  function fail(){
    if(grid) grid.innerHTML='<p class="mdb-reviews__loading">'+esc(T.fail)+'</p>';
    setMore('hidden');
    var sum=ROOT.querySelector('.mdb-reviews__summary'); if(sum) sum.setAttribute('data-state','error');
  }

  /* On ne déclenche le fetch que lorsque le bloc entre dans le viewport :
     les bots qui ne scrollent pas (et le premier paint) n'appellent pas l'API. */
  var started=false;
  function start(){ if(started) return; started=true;
    fetchJSON(API+'/reviewParams').then(renderSummary).catch(function(){});
    loadPage();
  }
  if('IntersectionObserver' in window){
    var io=new IntersectionObserver(function(entries){
      if(entries.some(function(e){return e.isIntersecting;})){ io.disconnect(); start(); }
    }, {rootMargin:'200px 0px'});
    io.observe(ROOT);
  } else { start(); }
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
