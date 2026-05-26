# Note client — Formulaire newsletter du pied de page

> Brouillon à relire / adapter avant envoi au client.

---

Objet : Newsletter du site — une question avant la mise en ligne

Bonjour,

En refondant le pied de page du nouveau site, j'ai examiné de près le
formulaire « Restez informés » qui s'y trouvait. Deux constats :

1. **L'ancien formulaire ne fonctionnait pas réellement.** Techniquement,
   c'était un formulaire de contact (Nom, Prénom, Société, Téléphone, Email)
   mal configuré : aucune destination n'était paramétrée et il n'était relié
   à aucun outil d'emailing (Mailchimp ou autre). Autrement dit, les
   inscriptions n'étaient envoyées nulle part — rien n'a donc été « perdu »
   en le retirant.

2. **J'ai remis en place un formulaire propre et fonctionnel**, mais simplifié :
   un seul champ e-mail + une case de consentement (RGPD), ce qui correspond
   à l'usage d'une newsletter en pied de page. Il est déjà en ligne sur la
   préproduction.

**Ma question :** où souhaitez-vous que ces inscriptions arrivent /
soient gérées ? Trois possibilités :

- **a) Simplement par e-mail** — chaque inscription vous est envoyée dans
  une boîte mail (par défaut j'ai mis `contact@maisondebacon.fr`, à confirmer).
  Le plus simple, mais c'est à vous de constituer/maintenir la liste à la main.

- **b) Dans un vrai outil de newsletter** (type Brevo — l'équivalent français
  de Mailchimp). Les inscrits arrivent automatiquement dans une liste, et vous
  pouvez ensuite leur envoyer des campagnes (actualités, événements, menus…).
  C'est la solution recommandée si vous comptez communiquer régulièrement.
  Cela nécessite la création d'un compte Brevo de votre côté (offre gratuite
  jusqu'à un certain volume).

- **c) Pas de newsletter pour l'instant** — je retire le bloc du pied de page.

En attendant votre retour, le formulaire est **opérationnel en mode (a)** :
les e-mails partent vers `contact@maisondebacon.fr`. Dites-moi simplement
la bonne adresse, ou si vous préférez l'option (b), et je fais le
raccordement.

Bien à vous,
Laurent

---

## Notes techniques (interne — ne pas envoyer)

- Formulaire = Contact Form 7 #110553 « Newsletter pied de page (MdB) ».
  Champ `email*` requis + `acceptance` (consentement, optionnel pour ne pas
  bloquer mais affiché). Destinataire **placeholder** `contact@maisondebacon.fr`
  (c'est l'adresse qu'utilisait l'ancienne version CF7 #23 repurposée).
- Délivrabilité : plugin **Site Mailer** (Elementor) actif sur staging →
  envoi transactionnel sans SMTP à configurer.
- Anti-spam : **reCAPTCHA v3** est actif sur tout le site (CF7) → un POST
  scripté est classé `spam` (normal). À **vérifier en conditions réelles**
  par une soumission humaine depuis un navigateur avant la prod.
- Injecté dans le footer (template 108363) colonne Marque, sous les réseaux,
  via `add-footer-newsletter.php`. CSS dans overrides.css (`.mdb-foot-newsletter`).
- Si option (b) Brevo : installer le plugin Brevo, créer une liste, et
  remplacer/compléter l'action CF7 par l'intégration Brevo (ou utiliser le
  formulaire d'inscription Brevo natif). Mailchimp a été désinstallé (validé
  client), donc ne pas y revenir.
- **Prod** : le formulaire CF7 #110553 et l'injection footer n'existent que
  sur staging → à rejouer en prod (cf. checklist de bascule).
