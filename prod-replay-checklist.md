# Checklist de bascule staging → PROD (Maison de Bacon)

> Tout ce qui suit a été réalisé sur **staging** mais vit dans la **base de
> données** (`_elementor_data`, options, dictionnaire TRP, méta du kit) — donc
> **PAS dans git**. À rejouer manuellement en prod au lancement.
> Le code du thème (`laurent-mdb`) est, lui, versionné dans git : il suffit de
> déployer le thème à la bonne version (actuellement **0.9.22**).
>
> Dernière mise à jour : 2026-05-26.

---

## 0. Préalables prod (à adapter aux specifics prod)

- **WP-CLI OVH** : `wp db query` est cassé → toujours passer par `wp eval-file`.
  Préfixe de table en prod **à vérifier** (staging = `mod35_`).
- **Backup complet (DB + fichiers) AVANT toute opération.**
- Les **IDs diffèrent en prod** : ne pas présumer que la home = 106136, le
  footer = 108363, le kit = 103327, etc. **Retrouver les IDs par slug/titre**
  avant de lancer chaque script (cf. notes par étape).
- Les scripts réutilisables sont dans `site-backup/elementor-kit/`. La plupart
  sont en **DRY-RUN par défaut** ; passer `MDB_APPLY=1` pour écrire. Ils font un
  backup JSON dans `/tmp/` avant d'écrire. **Adapter les IDs en tête de script.**
- Après chaque modif Elementor : `wp elementor flush-css && wp cache flush`.

---

## 1. Thème laurent-mdb (versionné — git)

- [ ] Déployer le thème `laurent-mdb` à la version **0.9.22** (Hello Elementor
      comme parent). Tout le CSS/JS de refonte (header, mega-menu, hero, home,
      events, footer, reCAPTCHA, etc.) est dans `assets/css/overrides.css`,
      `assets/js/*.js`, `functions.php`.
- [ ] Vérifier le filtre `gettext` FR des libellés ECTBE
      (« Find out more » → « En savoir plus », « All day » → « Toute la journée »)
      — purement code, rien à rejouer côté DB.

## 2. Typographie / couleurs (kit Elementor + overrides per-widget)

> Cf. mémoire `typo-restyle-concept-mdb`. Kit = post 103327 sur staging.
- [ ] **Backup** la méta `_elementor_page_settings` du kit prod avant.
- [ ] Remapper Global Fonts + Global Colors du kit sur le concept
      (Cormorant / Tenor / Inter ; navy/cream/brass).
      → scripts : `survey-typo.php` (audit), `remap-typo.php` (remap in-place).
- [ ] `wp elementor flush-css`.

## 3. Hero home (widgets Elementor reconstruits)

> Cf. mémoire `hero-home-refonte-mdb`. Container hero 167c86ed (staging).
- [ ] Reconstruire le hero façon concept → script `rebuild-hero.php`
      (adapter l'ID de page + l'ID du container hero en prod).
- [ ] CSS scellé à l'ID `.elementor-element-167c86ed` dans overrides.css :
      **l'ID prod différera** → soit garder le même ID en recréant, soit
      mettre à jour le sélecteur CSS. ⚠️ Point d'attention.

## 4. Home — section ÉVÉNEMENTS (ElementsKit ECTBE)

- [ ] Titre de section → « Prochains Événements » (title case + accent).
      → script `fix-events-title.php` (widget heading e36233f sur staging).
- [ ] Retirer le **carrousel photos** de la section events
      → script `remove-events-carousel.php` (widget image-carousel 9a5112d).
- [ ] Le reste (fond navy, neutralisation du `::before` blanc, agenda éditorial,
      titre cliquable, masquage excerpt/“En savoir plus”, alignement date) est
      **100% CSS** dans overrides.css → rien à rejouer côté DB.

## 5. Home — section PROMO / blog-posts (supprimée)

- [ ] Supprimer la section promo `a8f323f` (cartes Recrutement / RSE)
      → script `remove-promo-section.php`.
      (Le contenu Recrutement + RSE est désormais dans le **footer**, cf. §7.)
- [ ] (Si on avait gardé la section : `enable-blogposts-content.php` +
      `fix-blogposts-imgsize.php` étaient les scripts de restyle. Inutiles
      maintenant que la section est retirée — pour mémoire seulement.)

## 6. Home — sections vides / images pleine largeur (supprimées)

- [ ] Supprimer les 4 sections entre Événements et Nos Univers :
      `535de6f4` (photo terrasse), `5e6ef985` (photo aquarium),
      `36b4ff0` (spacer → bande blanche), `3281ff98` (heading vide caché).
      → script `remove-image-pseudosections.php` (les 4 IDs sont dans `$TARGETS`).
- [ ] Vérifier qu'Événements enchaîne directement sur Nos Univers (0 gap).

## 7. Footer (template Elementor reconstruit)

> Footer = template Elementor (staging : post **108363**, type wp-post, rendu via
> `elementor_theme_do_location('footer')` du parent Hello Elementor).
> **NE PAS créer de footer.php enfant** : le parent fait déjà le bon travail.
- [ ] Retrouver l'ID du template footer en prod (chercher type footer / titre
      “footer”, ou l'ID rendu sur la home via `data-elementor-id`).
- [ ] Adapter `$POST` (et l'ID dans le sélecteur CSS `.elementor-108363`,
      cf. ci-dessous) puis lancer `rebuild-footer.php` (MDB_APPLY=1).
- [ ] Structure produite : 4 colonnes
      **Marque** (logo blanc `Logo_Blanc.svg` + tagline italique + adresse +
      icônes Instagram/Facebook) ·
      **La Maison** (Restaurant, Roof Top Club, La Carte, Le Chef, Recrutement,
      Engagement RSE) ·
      **Privé & Événements** (Privatisations, Villa Les Roches de Bacon,
      L'Appartement de Victor) ·
      **Nous contacter** (tél + email) ·
      barre légale (© · Mentions légales · Politique de confidentialité) +
      mention reCAPTCHA sur une ligne.
- [ ] ⚠️ **Sélecteur CSS** : le footer CSS dans overrides.css est scopé à
      `.elementor-108363`. **En prod l'ID changera** → mettre à jour ce préfixe
      dans overrides.css (toutes occurrences `.elementor-108363`) OU recréer le
      template avec le même ID. ~31 occurrences.
- [ ] ⚠️ Elementor **n'émet pas** les `_css_classes` sur les CONTAINERS (mais
      bien sur les widgets) → la grille 4 colonnes est ciblée via la structure
      `.e-con > .e-con:first-child > .e-con-inner` (pas via `.mdb-foot-top`).
      Vérifier visuellement après bascule.
- [ ] Liens : adapter les URLs `$BASE` (https://www.maisondebacon.fr/ en prod).

## 8. reCAPTCHA (badge masqué + mention légale)

- [ ] Badge `.grecaptcha-badge` masqué via CSS (overrides.css) — code, rien à
      rejouer. La **mention légale Google** est injectée par le template footer
      (cf. §7) → vient automatiquement avec le rebuild-footer.
- [ ] ⚠️ Contact Form 7 charge reCAPTCHA v3 globalement (clé `6LcC_Fck…` dans
      l'option `wpcf7`). En prod, la clé peut différer — pas besoin d'y toucher,
      juste savoir que c'est CF7 (et NON Elementor) qui charge le badge.

## 9. Traductions EN (TranslatePress)

> Cf. mémoires `feedback-brand-maison-de-bacon`, etc. Dictionnaire prod :
> `<préfixe>_trp_dictionary_fr_fr_en_us` (IDs différents de staging).
- [ ] Traduire le chrome + contenu éditorial en EN (déjà fait staging).
- [ ] **Marque « Maison de Bacon » jamais traduite** : balayer
      `LIKE '%Bacon House%' / '%House of Bacon%' / "%Bacon's House%"` et corriger
      (scripts `trp-fix-brand.php` & co — IDs à réadapter).
- [ ] Eyebrows / hero EN : `trp-eyebrow*.php`, `trp-hero-rest.php`, `trp-maison*.php`.

## 10. Pages manquantes (à créer côté contenu, hors thème)

- [ ] **Politique de confidentialité** : le footer pointe vers
      `/politique-de-confidentialite/` → **page inexistante (404)**. Créer la page
      ou ajuster le lien.
- [ ] **Bon Cadeau** : pas de page dédiée (lien volontairement omis du footer).

## 11. Reste du chantier (déjà tracké ailleurs)

- [ ] WP Rocket : réactiver/optimiser avant prod (désactivé sur staging).
- [ ] GA4 (G-L4W5J047W4) : migrer depuis insert-headers-and-footers vers le thème.
- [ ] Retirer l'item menu « Fête des mères » après le 2026-05-31 (menu saisonnier).
- [ ] Désinstaller définitivement les plugins en DEACTIVATE après observation.

---

### Récap des scripts (dans `site-backup/elementor-kit/`)

| Script | Rôle | IDs à adapter |
|---|---|---|
| `remap-typo.php` / `survey-typo.php` | Remap fonts per-widget | kit + pages |
| `rebuild-hero.php` | Reconstruire le hero | page home + container hero |
| `fix-events-title.php` | Titre « Prochains Événements » | home + heading e36233f |
| `remove-events-carousel.php` | Retirer carrousel events | home + widget 9a5112d |
| `remove-promo-section.php` | Retirer section promo | home + section a8f323f |
| `remove-image-pseudosections.php` | Retirer 4 sections vides/images | home + 4 IDs |
| `rebuild-footer.php` | Reconstruire le footer concept | template footer + $BASE |
| `trp-*.php` | Corrections TRP (EN, marque) | IDs dictionnaire TRP |
