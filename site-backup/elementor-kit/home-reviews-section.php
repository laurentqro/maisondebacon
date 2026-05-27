<?php
/* Accueil (106136) : nouvelle section « avis » avant « Bon Cadeau ».
 *
 * Version compacte de la page /avis : en-tête (eyebrow + titre + note moyenne)
 * + carrousel horizontal auto-défilant d'avis, alimenté CÔTÉ CLIENT par l'API
 * publique Zenchef (le serveur OVH ne peut pas l'atteindre ; le navigateur si).
 *
 * Structure insérée (container mdb-homeavis) :
 *   - heading eyebrow  (mdb-homeavis-eyebrow) « Témoignages »      [TRP traduit]
 *   - heading titre    (mdb-homeavis-title)   « Ils parlent de nous » [TRP]
 *   - widget html (carrousel + script)   <- strings via dico JS (lang-aware)
 *
 * Insérée juste AVANT le container « Bon Cadeau » 611a8b9a.
 * Le titre top-level utilise les MÊMES chaînes FR que la page Avis -> réutilise
 * les traductions TRP déjà posées (Testimonials / In their words).
 *
 * DRY par défaut ; MDB_APPLY=1. Backup JSON dans /tmp/mdb-evfix/. */
$APPLY    = getenv('MDB_APPLY') === '1';
$POST     = 106136;
$BEFORE   = '611a8b9a';     // container « Bon Cadeau »
$SECTION  = 'hav0sect';     // id stable de la nouvelle section
$RID      = '354476';

$carousel = <<<'HTML'
<div class="mdb-homeavis-rev" data-rid="354476">
  <div class="mdb-homeavis-rev__rating" data-state="loading">
    <span class="mdb-homeavis-rev__avg" data-avg>—</span>
    <span class="mdb-homeavis-rev__avg-max">/5</span>
    <span class="mdb-homeavis-rev__stars" data-stars aria-hidden="true"></span>
    <span class="mdb-homeavis-rev__count"><span data-count>—</span> <span data-count-suffix>avis vérifiés sur</span> <span class="mdb-homeavis-rev__zlogo" role="img" aria-label="Zenchef"></span></span>
  </div>
  <div class="mdb-homeavis-rev__viewport">
    <div class="mdb-homeavis-rev__track" data-track>
      <p class="mdb-homeavis-rev__loading" data-loading>Chargement des avis…</p>
    </div>
  </div>
  <a class="mdb-homeavis-rev__more" data-more href="/avis/">
    <span data-more-label>Lire tous les avis</span>
  </a>
</div>
<script>
(function(){
  var ROOT = document.querySelector('.mdb-homeavis-rev[data-rid="354476"]');
  if(!ROOT || ROOT.dataset.mdbInit) return; ROOT.dataset.mdbInit='1';
  var RID = ROOT.dataset.rid;
  var API = 'https://api.zenchef.com/api/v1/restaurants/'+RID;
  var EN = ((document.documentElement.getAttribute('lang')||'').toLowerCase().indexOf('en') === 0);
  var T = EN ? {
    locale:'en-GB', dec:'.', countSuffix:'verified reviews on', loading:'Loading reviews…',
    fail:'Find all our reviews on Zenchef.', moreLabel:'Read all reviews', anon:'Guest', moreHref:'/en/avis/'
  } : {
    locale:'fr-FR', dec:',', countSuffix:'avis vérifiés sur', loading:'Chargement des avis…',
    fail:'Retrouvez tous nos avis sur Zenchef.', moreLabel:'Lire tous les avis', anon:'Client', moreHref:'/avis/'
  };
  (function(){
    var s=ROOT.querySelector('[data-count-suffix]'); if(s) s.textContent=T.countSuffix;
    var l=ROOT.querySelector('[data-loading]'); if(l) l.textContent=T.loading;
    var m=ROOT.querySelector('[data-more-label]'); if(m) m.textContent=T.moreLabel;
    var a=ROOT.querySelector('[data-more]'); if(a) a.setAttribute('href', T.moreHref);
  })();

  function num(n){ return Number(n).toFixed(1).replace('.', T.dec); }
  function stars(n){ n=Math.round(n||0); var s=''; for(var i=0;i<5;i++){ s+= i<n?'★':'☆'; } return s; }
  function esc(t){ var d=document.createElement('div'); d.textContent=t==null?'':String(t); return d.innerHTML; }
  function fmtDate(d){ try{ return new Date(d).toLocaleDateString(T.locale,{month:'long',year:'numeric'});}catch(e){return '';} }
  function name(b){ if(!b) return T.anon; var f=(b.firstname||'').trim(); var l=(b.lastname||'').trim(); return (f?f:T.anon)+(l?' '+l:''); }
  function fetchJSON(u){ return fetch(u,{credentials:'omit'}).then(function(r){ if(!r.ok) throw 0; return r.json(); }); }

  function renderRating(p){
    var avg=(p.average_global||0);
    ROOT.querySelector('[data-avg]').textContent=num(avg);
    ROOT.querySelector('[data-stars]').textContent=stars(avg);
    ROOT.querySelector('[data-count]').textContent=(p.reviews_count||0).toLocaleString(T.locale);
    ROOT.querySelector('.mdb-homeavis-rev__rating').setAttribute('data-state','ready');
  }

  function card(r){
    var b=r.booking||{}; var body=(r.body||'').trim();
    return '<figure class="mdb-homeavis-card">'
      +'<div class="mdb-homeavis-card__stars">'+stars(r.global)+'</div>'
      +'<blockquote class="mdb-homeavis-card__body">'+esc(body)+'</blockquote>'
      +'<figcaption class="mdb-homeavis-card__meta"><span class="mdb-homeavis-card__name">'+esc(name(b))+'</span>'
      +'<span class="mdb-homeavis-card__date">'+esc(fmtDate(r.source_date||r.created_at))+'</span></figcaption>'
      +'</figure>';
  }

  var WANT=10, MAXPAGES=4, MINLEN=40, MAXLEN=320;
  function gather(){
    var acc=[];
    function go(page){
      return fetchJSON(API+'/reviews?page='+page).then(function(j){
        ((j&&j.data)||[]).forEach(function(r){
          var t=(r.body||'').trim();
          if(t.length>=MINLEN && t.length<=MAXLEN && (r.global||0)>=5) acc.push(r);
        });
        if(acc.length>=WANT || page>=MAXPAGES || !j.next_page_url) return acc;
        return go(page+1);
      });
    }
    return go(1);
  }

  function startMarquee(track){
    // duplique la piste pour une boucle continue
    var items=track.innerHTML; track.innerHTML=items+items;
    var pos=0, raw=track.scrollWidth/2, paused=false;
    if(!raw) return;
    track.parentNode.addEventListener('mouseenter',function(){paused=true;});
    track.parentNode.addEventListener('mouseleave',function(){paused=false;});
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if(reduce) return;
    function step(){
      if(!paused){ pos+=0.4; if(pos>=raw){ pos=0; } track.style.transform='translateX('+(-pos)+'px)'; }
      requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  function renderTrack(list){
    var track=ROOT.querySelector('[data-track]'); if(!track) return;
    if(!list.length){ track.innerHTML='<p class="mdb-homeavis-rev__loading">'+esc(T.fail)+'</p>'; return; }
    track.innerHTML=list.slice(0,WANT).map(card).join('');
    startMarquee(track);
  }

  function fail(){
    var track=ROOT.querySelector('[data-track]');
    if(track) track.innerHTML='<p class="mdb-homeavis-rev__loading">'+esc(T.fail)+'</p>';
  }

  Promise.all([ fetchJSON(API+'/reviewParams').then(renderRating).catch(function(){}), gather().then(renderTrack) ]).catch(fail);
})();
</script>
HTML;

$data = get_post_meta($POST, '_elementor_data', true);
@mkdir('/tmp/mdb-evfix', 0775, true);
$bak = '/tmp/mdb-evfix/home-106136-reviews-' . date('Ymd-His') . '.json';
file_put_contents($bak, is_string($data) ? $data : wp_json_encode($data));
echo "backup -> {$bak}\n";

$tree = is_string($data) ? json_decode($data, true) : $data;

/* idempotence : si la section existe déjà, on se contente de rafraîchir le
   HTML du carrousel (widget hav0html) — utile pour redéployer le markup/JS. */
$exists = false;
foreach ($tree as $el) { if (($el['id'] ?? '') === $SECTION) $exists = true; }
if ($exists) {
    $refreshed = 0;
    $set = function (&$els, $id, $html) use (&$set, &$refreshed) {
        foreach ($els as &$el) {
            if (($el['id'] ?? '') === $id) { $el['settings']['html'] = $html; $refreshed++; return true; }
            if (!empty($el['elements']) && $set($el['elements'], $id, $html)) return true;
        }
        unset($el);
        return false;
    };
    $set($tree, 'hav0html', $carousel);
    echo "section {$SECTION} déjà présente — HTML carrousel rafraîchi : {$refreshed}\n";
    if ($APPLY && $refreshed) {
        $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
    } else {
        echo "(dry run)\n";
    }
    return;
}

$mk_heading = function ($id, $cls, $title, $tag) {
    return [
        'id' => $id, 'elType' => 'widget', 'widgetType' => 'heading',
        'settings' => ['_css_classes' => $cls, 'title' => $title, 'header_size' => $tag],
        'elements' => [],
    ];
};
$section = [
    'id' => $SECTION, 'elType' => 'container',
    'settings' => ['_css_classes' => 'mdb-homeavis', 'flex_direction' => 'column'],
    'elements' => [
        $mk_heading('hav0eye', 'mdb-homeavis-eyebrow', 'Témoignages', 'p'),
        $mk_heading('hav0ttl', 'mdb-homeavis-title',   'Ils parlent de nous', 'h2'),
        [
            'id' => 'hav0html', 'elType' => 'widget', 'widgetType' => 'html',
            'settings' => ['html' => $carousel],
            'elements' => [],
        ],
    ],
];

/* insérer avant le container « Bon Cadeau » */
$out = []; $placed = 0;
foreach ($tree as $el) {
    if (($el['id'] ?? '') === $BEFORE && !$placed) { $out[] = $section; $placed = 1; }
    $out[] = $el;
}
if (!$placed) { $out[] = $section; echo "ANCRE {$BEFORE} introuvable — ajout en fin\n"; }
$tree = $out;
echo "section {$SECTION} insérée avant {$BEFORE} : {$placed}\n";

if ($APPLY) {
    $json = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "update -> " . var_export(update_post_meta($POST, '_elementor_data', wp_slash($json)), true) . "\n";
} else {
    echo "(dry run)\n";
}
