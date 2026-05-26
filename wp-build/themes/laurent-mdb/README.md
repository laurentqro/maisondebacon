# `laurent-mdb` — thème enfant Maison de Bacon

Thème enfant de **Laurent** (parent ThemeForest installé sur le site existant).
Centralise les surcharges visuelles + les fonctionnalités globales de la refonte.

## Structure

```
laurent-mdb/
├── style.css                  — header WP du thème enfant (Template: laurent)
├── functions.php              — bootstrap : enqueue, sticky CTA, cap upload, etc.
├── screenshot.png             — vignette affichée dans Apparence → Thèmes
└── assets/
    ├── css/
    │   ├── tokens.css         — variables CSS partagées (couleurs, typo, échelles)
    │   └── overrides.css      — surcharges du parent + composants globaux
    └── js/
        └── sticky-cta.js      — apparition/masquage du CTA réservation flottant
```

## Ce que ce thème fait aujourd'hui

- Charge la palette/typographie de `/concept/styles.css` via les tokens CSS.
- Injecte un **CTA "Réserver" sticky** sur toutes les pages (point #2 du brief).
- **Cap automatique des dimensions images à l'upload** (2400px max, qualité 82%) — point #4 du brief.
- Désactive les emojis WordPress (gain perf).

## Ce qui reste à brancher

- Migrer la totalité du CSS de `/concept/styles.css` vers `assets/css/overrides.css`, en référencant les tokens.
- Implémenter la normalisation des titres H1/H2 si l'audit page par page le confirme (point #3).
- Surcharger le `header.php` / `footer.php` si nécessaire (probablement non, Theme Builder Elementor Pro suffit).
- Générer une vraie `screenshot.png` (1200×900).

## Déploiement

1. Zipper le dossier `laurent-mdb/`.
2. WP Admin → Apparence → Thèmes → Ajouter → Téléverser un thème.
3. **Ne pas activer en prod sans staging.** Le parent `laurent` doit rester installé.

## Hooks/filtres exposés

| Filtre | Usage |
|---|---|
| `mdb_reservation_url` | Override l'URL Zenchef du sticky CTA (par exemple page rooftop → `rid=367528`) |
| `mdb_reservation_label` | Texte du bouton sticky |
| `mdb_upload_max_dimension` | Taille max d'upload (défaut 2400px) |
| `mdb_upload_jpeg_quality` | Qualité JPEG (défaut 82) |
