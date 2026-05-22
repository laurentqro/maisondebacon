# Diagnostic de couplage — thème Laurent
*2026-05-04 — analyse initiale à partir de `site-backup/www/`*
*2026-05-22 — invalidée partiellement après test réel sur staging (voir bloc rouge ci-dessous)*

---

## ⚠️ MISE À JOUR 2026-05-22 — Test réel sur staging

L'analyse statique du 2026-05-04 a **sous-estimé un point critique**. Lors du test sur `staging.maisondebacon.fr` le 2026-05-22 (installation Hello Elementor + activation du thème enfant `laurent-mdb`) :

1. **Les CPT `portfolio-item` et `testimonials` n'ont PAS été enregistrés** alors que `laurent-core` était bien actif.
2. **Toutes les URLs `/portfolio-item/...` sont retournées en 404**, y compris les 5 fiches cocktails (Bellini, French 75, Manhattan, etc.).

### Cause racine identifiée

`wp-content/plugins/laurent-core/post-types/post-types-register.php:82` contient un garde :

```php
public function register() {
    if ( laurent_core_theme_installed() && laurent_core_is_theme_registered() ) {
        // ... enregistre les CPT
    }
}
```

Si l'une des deux fonctions retourne `false` (cas où le thème **Laurent** n'est pas actif), **aucun CPT n'est créé**. Le plugin a un couplage *au runtime* avec le thème qui n'apparaissait pas dans l'analyse statique des fichiers.

### Tentatives de contournement

- **Stubs dans `functions.php` du child theme** (`laurent_core_theme_installed()` et `laurent_core_is_theme_registered()` qui retournent `true`) : **échec**. Les stubs se chargent après `init`, donc `laurent-core` a déjà exécuté son `register()` quand les stubs sont définis.
- **Stub `single_template` filter** + template minimal `single-portfolio-item.php` : ne corrige pas l'absence d'enregistrement CPT (le 404 vient de WP avant que le filtre ne soit appliqué).

### Décision

**Rollback vers le thème Laurent**, planification d'une **Phase 2** qui inclut la migration des contenus CPT avant la bascule :
- 5 fiches `portfolio-item` (cocktails) → pages Elementor sous `/cocktails/SLUG/`
- 9 fiches `testimonials` → widget Elementor Pro "Testimonial Carousel" intégré dans les pages utiles
- Redirections 301 depuis `/portfolio-item/...` vers `/cocktails/...`
- Puis désinstallation de `laurent-core` et `laurent`

### Workaround technique noté (non utilisé pour l'instant)

Créer un **mu-plugin** (`wp-content/mu-plugins/laurent-stubs.php`) qui définit `laurent_core_theme_installed()` et `laurent_core_is_theme_registered()` à `return true;`. Les mu-plugins sont chargés **avant** tous les plugins normaux, donc les stubs seraient en place quand `laurent-core` les appelle. À utiliser uniquement comme solution temporaire si on ne peut pas migrer les CPT à temps. Voir [memory/laurent-core-couplage-fort.md].

---

## TL;DR (rédigé le 2026-05-04 — voir bloc rouge ci-dessus pour révision 2026-05-22)

**Le couplage est faible.** La bascule du thème Laurent vers Hello Elementor est réalisable en **1 à 2 jours**, pas 4-5 comme je le craignais.

L'estimation initiale était pessimiste. Voici pourquoi.

---

## Ce que je redoutais (et qui n'existe pas)

### 1. Pas de shortcodes utilisés en façade

J'ai cherché des shortcodes `[laurent_*]` (très fréquents sur les thèmes Envato/EDGE de cette époque, qu'on retrouve pollués dans le contenu des pages). **Le thème ne définit aucun `add_shortcode()` direct dans son code accessible.**

Les seuls shortcodes existants sont enregistrés via Visual Composer (`vc_map`) **mais uniquement pour des modules WooCommerce et un blog list** que le client n'utilise pas. WPBakery est par ailleurs déjà désinstallé (seul `vc_clipboard` reste, qui est inoffensif).

→ **Aucun risque de "shortcode mort" dans le contenu des pages** après désactivation du thème.

### 2. Pas de templates CPT custom dans le thème

Aucun `single-portfolio-item.php`, `archive-portfolio-item.php` ou `single-testimonials.php` dans `wp-content/themes/laurent/`. **Les templates sont fournis par le plugin `laurent-core`** :

```
wp-content/plugins/laurent-core/post-types/portfolio/templates/
├── single-portfolio-item.php
└── archive-portfolio-item.php
```

→ **En basculant vers Hello Elementor, les fiches cocktails continueront à s'afficher correctement** tant que `laurent-core` reste actif.

### 3. Pas de fonction `laurent_*` appelée par un plugin externe

J'ai extrait toutes les fonctions `laurent_elated_*` définies par le thème (722 au total) et croisé avec leur usage dans les autres plugins (hors `laurent-core`/instagram-feed/twitter-feed). **Aucune correspondance.**

Toutes ces fonctions sont des helpers internes du thème (générateurs de styles, options du panneau admin, helpers de templates). Aucune n'est appelée par un plugin tiers ni par du contenu sauvegardé en base.

→ **Désactiver le thème ne casse aucun plugin tiers.**

---

## Ce qui est néanmoins à savoir

### 1. Le thème embarque des modules Visual Composer

Présence de `vc-templates/` et de `framework/modules/*/shortcodes/*/elementor-*.php` qui *enregistrent* des widgets **à la fois pour Visual Composer ET pour Elementor**. Cinq modules concernés :

- `blog-list` (liste d'articles)
- `product-info`, `product-list`, `product-list-carousel`, `product-list-simple` (Woo)

Ces widgets ne servent que si le client les utilise dans Elementor. À vérifier via le crawl front (audit précédent) : **aucun de ces widget_type n'apparaissait dans les pages auditées**. Disparition sans impact.

→ **Élément à valider dans le wp-admin** : ouvrir Elementor et chercher s'il y a une catégorie "Laurent" avec des widgets activement utilisés.

### 2. `laurent-core` enregistre 2 CPT et leurs taxonomies

```
register_post_type('testimonials')   → CPT 'testimonials'
register_post_type($this->base)      → CPT 'portfolio-item' (pour les cocktails)
register_taxonomy(...)                → 'portfolio-category', 'portfolio-tag', 'testimonials-category'
```

Ces deux CPT survivent indépendamment du thème : les templates sont **dans le plugin**, et les CPT sont **dans le plugin**. La bascule du thème n'a aucun effet sur eux tant que `laurent-core` reste actif.

→ **`laurent-core` doit rester installé**, même après bascule vers Hello Elementor. C'est un coût minime (le plugin est petit) qui préserve les 6 fiches cocktails et tous les avis sauvegardés en CPT testimonials.

### 3. `functions.php` du thème (37 KB)

Ne contient que des `require_once` de fichiers du dossier `framework/`. C'est-à-dire que le thème externalise tout son code dans un sous-framework EDGE Themes. C'est de la mauvaise architecture (PHP procédural avec des centaines d'helpers) mais pas accroché à du contenu utilisateur.

→ **À jeter sans regret le jour où on bascule.**

### 4. Le thème surcharge des templates WooCommerce

Trois fichiers dans `themes/laurent/woocommerce/` (`content-product.php`, `global/`, `product-searchform.php`). Comme on désactive WooCommerce en Phase 1 (cf. audit principal), **ces overrides deviennent caducs à ce moment-là** — pas après.

→ **À valider** : aucun template-override Woo n'est utilisé sur le front, et désactiver Woo ne fait pas de fatal error.

---

## Plan de bascule actualisé

| Étape | Tâche | Durée |
|---|---|---|
| 1 | ✅ FAIT — Audit des widgets Elementor sur prod : 18 pages, aucun widget Laurent utilisé. Voir `audit-widgets-elementor.md`. | — |
| 2 | Tester sur staging : désactiver Woo (Phase 1 déjà prévue) | (couvert ailleurs) |
| 3 | Installer Hello Elementor en parent. Modifier `laurent-mdb/style.css` ligne `Template:` de `laurent` vers `hello-elementor` | 15 min |
| 4 | Installer/activer le thème enfant `laurent-mdb` à la place du thème Laurent. Le parent Laurent peut rester désactivé pour l'instant. | 15 min |
| 5 | Test exhaustif sur staging : 6 fiches cocktails, ~70 fiches événements, fiches recrutement, formulaires, FR/EN, sticky CTA, mega menu | 3-4 h |
| 6 | Correction des régressions (souvent : icônes manquantes parce que Laurent chargeait FontAwesome/Ionicons que rien d'autre ne charge maintenant — facile à enqueue dans le thème enfant) | 1-2 h |
| 7 | Désinstallation finale du thème Laurent (de `wp-content/themes/laurent/`) | 5 min |
| 8 | Mise en prod (clone staging → prod) | 1-2 h |

**Total : 1 à 2 jours** dans un scénario standard.

---

## Modules du thème à reconnecter dans `laurent-mdb` ou `mdb-widgets` ?

| Module Laurent | Utilisé sur prod ? | Sort proposé |
|---|---|---|
| Icon packs (FontAwesome, Ionicons, Linea, Linear, Simple Line, Dripicons, Elegant) | À vérifier — Elementor charge déjà FA + eicons | **Ne charger qu'au besoin** dans `laurent-mdb` |
| `framework/modules/blog/blog-list` (Elementor widget) | Probablement non | Ignorer |
| `framework/modules/woocommerce/*` (Elementor widgets) | Non (Woo désinstallé) | Ignorer |
| `framework/lib/icons-pack/` | Backbone du système d'icônes | Si besoin, port vers mdb-widgets |
| Fonctions `laurent_elated_back_to_top_button` etc. | Affichage front spécifique | Réécrire en custom dans `laurent-mdb` si on veut garder |

---

## Conclusion révisée

L'estimation initiale (2-3 jours, jusqu'à 4-5) supposait :
- des templates CPT dans le thème (✗ ils sont dans `laurent-core`)
- des shortcodes `[laurent_*]` en base (✗ aucun)
- des fonctions du thème appelées par des plugins (✗ aucune)

Aucune de ces craintes ne se matérialise. **Le thème Laurent est mal écrit mais bien isolé** — il fait beaucoup de choses, mais il les fait pour lui-même, sans contaminer le reste du site.

**Estimation ferme : 1 à 2 jours pour bascule complète** vers Hello Elementor, et on peut le faire **n'importe quand**, pas forcément après le redesign.

Cela ouvre une option intéressante : **basculer le thème AVANT le redesign visuel.** Comme ça, `laurent-mdb` se construit directement enfant de Hello Elementor, qui est minimal et propre, sans avoir à composer avec les CSS/JS du thème Laurent. C'est mieux comme point de départ.

À discuter.
