---
name: COOMS Bons
description: Gestion des distributions et scanner boutique, intégrés au cadre WordPress.
colors:
  paper: "#f6f7f8"
  surface: "#fff"
  ink: "#24282d"
  muted: "#626971"
  border: "#e3e6e9"
  accent: "#bc3022"
  accent-hover: "#9f271b"
  link: "#9f271b"
  link-dark: "#ff9386"
  green: "#16643b"
  green-bg: "#eaf5ee"
  redeem-hover: "#104d2d"
  red: "#9e2828"
  red-bg: "#fbeeed"
  amber: "#805000"
  amber-bg: "#fff4db"
  used: "#505861"
  used-bg: "#eef0f3"
typography:
  headline-admin:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif"
    fontSize: "24px"
    fontWeight: 650
    lineHeight: 1.2
    letterSpacing: "-0.025em"
  headline-scan:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif"
    fontSize: "30px"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "-0.025em"
  title-admin:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif"
    fontSize: "17px"
    fontWeight: 600
    lineHeight: 1.4
  title-gift:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif"
    fontSize: "22px"
    fontWeight: 700
    lineHeight: 1.3
  body-admin:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.6
  body-scan:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: 1.6
  reference:
    fontFamily: "ui-monospace, SFMono-Regular, Consolas, monospace"
    fontSize: "12px"
    fontWeight: 450
    lineHeight: 1.6
    letterSpacing: "0.015em"
  label:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif"
    fontSize: "14px"
    fontWeight: 600
    lineHeight: 1.6
rounded:
  control: "8px"
  panel: "12px"
  ticket: "16px"
spacing:
  sm: "8px"
  md: "16px"
  section: "24px"
  page: "32px"
components:
  button-primary:
    backgroundColor: "{colors.accent}"
    textColor: "#fff"
    rounded: "{rounded.control}"
    padding: "9px 16px"
    height: "42px"
  button-primary-hover:
    backgroundColor: "{colors.accent-hover}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.control}"
  button-redeem:
    backgroundColor: "{colors.green}"
    textColor: "#fff"
    height: "56px"
    width: "100%"
  button-next:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.control}"
    height: "52px"
    width: "100%"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.control}"
    padding: "8px 12px"
    height: "42px"
  panel:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.panel}"
  ticket:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.ticket}"
---

# Design System: COOMS Bons

## Overview

**Creative North Star: "Le registre de boutique"**

Une interface de travail neutre, précise et familière, accordée à AdminKit en gestion et autonome en boutique. Les surfaces blanches, le texte graphite et les alignements répétés facilitent la lecture des lots et des retraits. Le rouge COOMS signale les actions principales ; la typographie système et les contrôles natifs gardent le produit simple et maintenable.

Le grand panneau crème et le formulaire décoratif ont été rejetés par l’utilisateur. Le système 1.1 privilégie un espace fluide et des panneaux utiles, sans image décorative ni police externe.

**Key Characteristics:**

- Surfaces neutres et plates, bordures fines.
- Gestion dense et lisible ; boutique tactile et directe.
- Statuts écrits, chiffres tabulaires et références en chasse fixe.
- Lecture du bon et confirmation de remise distinctes.

## Colors

La palette neutre conserve un seul accent de marque rouge ; les couleurs fonctionnelles distinguent disponibilité, erreur et expiration. Le frontmatter définit les primitives de secours normatives.

### Primary

- **Rouge COOMS** (`accent`) : création du lot et ouverture explicite de la caméra. Texte blanc.
- **Rouge profond** (`accent-hover`, `link`) : survol de l’action principale et liens clairs.
- **Corail lisible** (`link-dark`) : liens de gestion lorsque le thème AdminKit est sombre.

### Secondary

- **Vert boutique** (`green`, `green-bg`, `redeem-hover`) : disponibilité discrète, confirmation et action de remise. Le verdict de réussite inverse le contraste : fond vert, texte blanc.

### Tertiary

- **Rouge indisponible** (`red`, `red-bg`) : bon déjà utilisé dans la fiche, bon introuvable, accès refusé et erreurs.
- **Ambre expiration** (`amber`, `amber-bg`) : bon expiré, accompagné d’un libellé explicite.

### Neutral

- **Fond neutre** (`paper`), **Surface** (`surface`) et **Trait** (`border`) : niveaux de travail et séparateurs.
- **Graphite** (`ink`) et **Texte secondaire** (`muted`) : titres, contenu et aides.
- **Retrait enregistré** (`used`, `used-bg`) : badge « Retiré » neutre dans la gestion ; un retrait accompli n’est pas une erreur du tableau.

**The Host Palette Rule.** En gestion seulement, reprendre `--ak-surface-muted`, `--ak-surface`, `--ak-text`, `--ak-text-muted` et `--ak-border` si présents. Les valeurs de secours restent utilisables sans AdminKit. Le lien sombre est distinct du survol du bouton rouge, qui garde son texte blanc. Les écrans boutique restent clairs, indépendants du thème.

## Typography

**Display Font / Body Font:** pile système native du frontmatter. **Label/Mono Font:** chasse fixe système pour les références.

La hiérarchie est compacte, sans capitales décoratives. Les volumes et les dates utilisent des chiffres tabulaires.

### Hierarchy

- **Headline admin** : titre de gestion ; passe à (26px) à (600px) et moins.
- **Headline scan** : titre du scanner ; passe à (28px) à (600px) et moins. La fiche de bon utilise la même taille, avec un interlettrage de (-0.02em).
- **Title admin / gift** : titre du tableau et désignation du cadeau. Les sections du formulaire et le guide utilisent (19px).
- **Body admin / scan** : gestion compacte et lecture boutique. Les aides de gestion sont en (13px), les en-têtes de tableau en (12px), les lignes en (14px).
- **Reference** : donnée technique, pas titre. La saisie manuelle utilise la chasse fixe en (16px).
- **Label** : libellés au-dessus des champs. Les champs de gestion passent à (16px) sur mobile.

## Layout

La gestion occupe la largeur disponible, limitée à (1800px), sans enveloppe colorée propre. Marges de bureau : (28px 32px 0 12px). Les panneaux utilisent des espacements de (20–28px) et les sections un rythme de (24px). Les compteurs sont alignés sur quatre colonnes, puis deux à (600px) et moins.

La création associe un formulaire de (680px) maximum à un guide de (240–380px), séparés de (48px). Elle passe sur une colonne à (800px). À (1000px), l’en-tête de panneau s’empile ; à (1100px), les marges de gestion se réduisent. Les actions d’en-tête s’empilent à (800px). À (600px), filtres et groupes d’actions se déploient sur la largeur disponible. Les tableaux défilent dans leur région dédiée, accessible au clavier.

Le scanner est centré sur (640px) maximum ; la fiche de bon sur (520px). L’aperçu caméra garde le ratio (4/3). La saisie manuelle est alignée au bouton sur bureau puis empilée à (600px). La boutique possède des gouttières de (20px), réduites à (12px) sur petit écran. La fiche réduit son remplissage de (28px) à (24px).

## Elevation & Depth

Les surfaces restent plates, sans ombre de panneau ou de bouton. Les bordures et les changements de fond structurent l’espace. L’unique masque ombré encadre la visée vidéo : il assombrit l’extérieur de la zone utile et ne sert pas à élever un composant.

## Shapes

Les contrôles de gestion, de caméra et de référence ont les angles du token `control`. Les panneaux utilisent `panel` ; la fiche boutique et le panneau caméra utilisent `ticket`. Le bouton de validation du bon conserve son rayon de (6px). L’aperçu vidéo est arrondi à (10px), sa visée à (12px). Les statuts sont des pastilles, avec un point coloré et un libellé ; les icônes sont des SVG géométriques, sans emoji.

## Components

### Buttons

Actions solides, libellés courts et explicites. En gestion : hauteur minimale (42px), portée à (44px) sur mobile ; la spécificité CSS protège les champs et boutons du rétrécissement par l’habillage admin. Le secondaire utilise surface, texte et bordure neutres ; son survol renforce la bordure. Les boutons désactivés passent à une opacité de (.65), avec curseur d’attente et texte de progression.

Le focus général est un contour graphite de (3px), décalé de (4px). Les champs de gestion utilisent un contour rouge de (2px), décalé de (2px). Ne pas supprimer ces repères.

### Chips

Disponibilité verte discrète, retrait neutre dans les tableaux, expiration ambre. Le point et le texte accompagnent la couleur. Les pastilles sont en (12px), graisse (550), avec un remplissage de (3px 9px).

### Cards / Containers

Panneaux bordés, fond de surface, sans ombre. La fiche boutique regroupe verdict, cadeau, détails et actions. La disponibilité utilise le vert pâle ; la réussite utilise le vert plein avec texte blanc et une confirmation datée en heure de Genève. Le pictogramme de réussite s’anime pendant (.25s) uniquement si la préférence de réduction des animations le permet.

### Inputs / Fields

Champs natifs, libellé visible et aide associée. Le formulaire de création demande le nom du lot et la quantité, puis expose cadeau et validité. La recherche manuelle reste un champ texte, hauteur minimale (48px), sans correction orthographique ni autocomplétion. Sa progression affiche « Vérification… ». Les erreurs expliquent la reprise sans remplacer le verdict par une fausse réussite.

### Navigation

Onglets « Lots » / « Tous les bons », soulignement rouge de l’onglet courant et compteurs compacts. Les compteurs de synthèse ouvrent les bons correspondants. Les liens de lot ouvrent le détail ; celui-ci évite de répéter le même nom à chaque ligne. La pagination revient à la ligne et indique la page courante.

### Scanner boutique

La caméra est inactive à l’arrivée. « Ouvrir la caméra » est une action de (52px) minimum. Pendant la demande, l’état est annoncé et « Annuler l’ouverture » reste disponible ; en lecture, le bouton devient « Arrêter la caméra ». Refus de permission, absence de caméra, incompatibilité, lecteur non chargé et QR extérieur affichent une explication ; la référence manuelle demeure accessible. Sans JavaScript, la recherche manuelle reste disponible.

**The Lookup Rule.** Le scan et la référence consultent le bon ; seul « Valider la remise de la boîte » enregistre le retrait. Ce bouton vert, pleine largeur et de (56px) minimum, expose le caractère définitif de l’action puis son chargement. Les états de fiche sont : valide, déjà utilisé, remise enregistrée, expiré, introuvable, connexion requise et accès refusé. Une erreur de mutation propose d’actualiser le statut.

Après un retrait, un bon déjà utilisé ou expiré, « Scanner le bon suivant » est un bouton secondaire pleine largeur de (52px) minimum. Sur un bon disponible, « Retour au scanner » reste un lien discret afin de préserver la priorité de confirmation.

La caméra s’arrête avant la navigation et la recherche, à l’annulation, à l’arrêt manuel et lorsque la page est masquée. Le retour à une page masquée exige une nouvelle action explicite. Les images sont décodées dans le navigateur ; le parcours ne demande aucune identité de bénéficiaire. Le nom de session affiché est celui de l’employé connecté.

## Do's and Don'ts

### Do:

- Do conserver les contrastes et les libellés explicites dans chaque état.
- Do conserver les références et les URLs des bons existants.
- Do garder la saisie manuelle disponible après une erreur de caméra.
- Do utiliser les variables AdminKit avec leurs valeurs de secours en gestion.

### Don't:

- Don’t réintroduire le grand panneau crème ou un habillage décoratif du formulaire.
- Don’t demander la caméra à l’arrivée ni relancer automatiquement après une pause.
- Don’t ouvrir une URL QR externe ou enregistrer un retrait au simple scan.
- Don’t collecter le nom ou l’e-mail du bénéficiaire dans les parcours de bons.
- Don’t présenter les captures et essais virtuels comme une recette sur iPhone physique ou une certification de sécurité.
