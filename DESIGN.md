---
name: COOMS Bons
description: Une interface de boutique claire, précise et familière.
colors:
  paper: "#fbf8f2"
  surface: "#fff"
  ink: "#30251f"
  muted: "#665b51"
  border: "#ded6cc"
  accent: "#bc3022"
  link: "#9f271b"
  green: "#16643b"
  green-bg: "#eaf5ee"
  redeem-hover: "#104d2d"
  red: "#9e2828"
  red-bg: "#fbeeed"
  amber: "#805000"
  amber-bg: "#fff4db"
typography:
  headline-admin:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
    fontSize: "28px"
    fontWeight: 650
    lineHeight: 1.2
  headline-scan:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
    fontSize: "30px"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "-0.02em"
  title-admin:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
    fontSize: "19px"
    lineHeight: 1.4
  title-gift:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
    fontSize: "22px"
    lineHeight: 1.3
  body-admin:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
    fontSize: "14px"
    lineHeight: 1.6
  body-scan:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
    fontSize: "16px"
    lineHeight: 1.6
  label:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
    fontSize: "14px"
    fontWeight: 600
    lineHeight: 1.5
  table:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
    fontSize: "13px"
    lineHeight: 1.6
rounded:
  page-number: "4px"
  control: "6px"
  panel: "12px"
  ticket: "16px"
  status: "99px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "12px"
  base: "16px"
  section: "24px"
  panel: "28px"
  page: "32px"
  columns: "40px"
  empty: "48px"
components:
  button-primary:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.surface}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "10px 18px"
  button-primary-hover:
    backgroundColor: "{colors.link}"
    textColor: "{colors.surface}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.control}"
    padding: "10px 18px"
  button-redeem:
    backgroundColor: "{colors.green}"
    textColor: "{colors.surface}"
    rounded: "{rounded.control}"
    width: "100%"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.control}"
    padding: "8px 12px"
  status-available:
    backgroundColor: "{colors.green-bg}"
    textColor: "{colors.green}"
    rounded: "{rounded.status}"
    padding: "3px 10px"
  panel:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.panel}"
  ticket:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.ticket}"
---

# Design System: COOMS Bons

## Overview

**Creative North Star: « Le registre de boutique »**

Une interface calme, précise et familière, issue de la clarté des éditions suisses de recettes. Le papier crème, l’encre brune et la répétition des alignements donnent une présence COOMS sans détourner l’attention des actions. La gestion conserve le cadre WordPress ; l’écran de remise a sa propre mise en page, indépendante du thème.

Le système privilégie une lecture immédiate, des contrôles natifs et des états écrits en français. Il ne requiert ni image décorative ni police externe. La simplicité et le fonctionnement prévisible guident les choix.

**Key Characteristics:**
- Hiérarchie courte et espace utile.
- Accent rouge pour les actions de gestion, vert pour la remise disponible.
- Surfaces plates, bordures discrètes et chiffres tabulaires.
- Statuts formulés en toutes lettres et confirmation explicite.

## Colors

Le papier chaud et l’encre brune encadrent une palette fonctionnelle ; les valeurs normatives sont dans le frontmatter et proviennent de `assets/cooms.css`.

### Primary
- **Rouge COOMS — accent :** action principale de gestion.
- **Rouge profond — link :** liens et survol de l’action principale.

### Secondary
- **Vert boutique — green / green-bg :** bon disponible, confirmation et action de remise. `redeem-hover` assombrit cette action au survol.

### Tertiary
- **Rouge indisponible — red / red-bg :** déjà utilisé, introuvable, accès refusé et erreurs.
- **Ambre expiration — amber / amber-bg :** bon expiré.

### Neutral
- **Papier — paper :** toile de l’application et zones de regroupement.
- **Surface — surface :** champs et panneaux.
- **Encre — ink :** contenu principal et focus.
- **Encre secondaire — muted :** aides, métadonnées et titres de colonnes.
- **Trait — border :** contours et séparateurs.

**The Written State Rule.** Un état est toujours nommé ; la couleur seule ne porte jamais sa signification.

## Typography

**Display Font / Body Font :** pile système native, définie dans le frontmatter. Aucune police distante. Les données numériques des compteurs, tableaux et références utilisent des chiffres tabulaires.

### Hierarchy
- **Headline admin :** titre de page ; devient plus compact sur petit écran (24px).
- **Headline scan :** verdict principal ; devient plus compact sur petit écran (28px).
- **Title admin / title gift :** sections de gestion et désignation du cadeau.
- **Body admin / body scan :** densité de gestion et confort de lecture en boutique.
- **Label :** boutons et libellés de champs, sans capitales forcées.
- **Table :** données denses et aides. Les descriptions de page restent limitées à une largeur lisible (70ch) ; le texte du verdict à une courte mesure (44ch).

## Layout

La gestion est contenue dans une enveloppe de largeur maximale (1400px), avec un retrait intérieur généreux sur grand écran (32px). La création juxtapose le formulaire et son guide : première colonne jusqu’à (680px), seconde au moins (240px), intervalle (40px). Les compteurs forment quatre colonnes ; les filtres s’alignent puis reviennent à la ligne.

À (1000px) et moins, la création passe en une colonne et l’enveloppe se resserre. À (600px) et moins, l’en-tête et la pagination s’empilent, les compteurs passent à deux colonnes et les actions d’en-tête prennent toute la largeur. Le tableau conserve ses colonnes sémantiques et défile horizontalement dans son propre conteneur.

En boutique, la marque, le ticket et le pied de page partagent une largeur maximale (520px). Les marges latérales passent de (20px) à (12px) sur petit écran. Les sections du ticket passent de (28px) à (24px) de retrait. L’action de remise occupe toute la largeur disponible avec une hauteur minimale (56px), tandis que les contrôles de gestion ont une hauteur minimale (44px).

## Elevation & Depth

Aucune ombre dans les composants de l’application. Les surfaces blanches se distinguent du papier par un trait fin ; les états occupent une zone teintée, sans effet de relief. Le cadre WordPress visible autour de l’application appartient à l’hôte et ne fait pas partie de ces tokens.

**The Flat Surface Rule.** La profondeur provient du contraste des surfaces et des séparateurs.

## Shapes

Les contrôles ont des angles doucement arrondis ; les panneaux sont plus ouverts et le ticket de boutique possède le rayon le plus ample. Les statuts sont des pastilles compactes. Les contours sont fins (1px) et les panneaux coupent proprement leur contenu aux angles. Les détails du ticket restent sur deux colonnes et permettent le retour à la ligne des valeurs longues.

## Components

### Buttons

Des actions explicites, stables et faciles à viser. Le primaire rouge sert à la gestion, le secondaire blanc aux actions complémentaires, et le vert à la confirmation de remise. Le survol assombrit le primaire ; le secondaire reprend le papier et renforce son contour. Les boutons désactivés ont une opacité réduite (.65) et un curseur d’attente. Le focus visible est un contour d’encre (3px), décalé de (4px), sans ombre.

### Inputs / Fields

Champs blancs à contour fin et libellé placé au-dessus. Le texte occupe la largeur du formulaire ; le nombre reste compact (140px). Les aides sont placées juste sous le contrôle et les listes déroulantes restent natives. Les erreurs apparaissent dans une zone rouge écrite. Le même focus visible s’applique aux éléments interactifs.

### Chips

Pastilles de statut à fond clair, texte soutenu et espace intérieur compact. Disponible est vert, utilisé rouge, expiré ambre. Elles ne sont pas des boutons et ne reçoivent pas d’effet de survol artificiel.

### Cards / Containers

Panneaux blancs bordés, sans ombre. Le formulaire utilise un retrait ample (28px), ramené à (20px) sur petit écran. Les tableaux, filtres et titres se regroupent dans un même panneau, avec des séparateurs plutôt qu’une multiplication des cartes.

### Navigation

Le menu principal reste celui de WordPress. À l’intérieur du produit, les liens sont soulignés avec un léger décalage ; la pagination espace ses liens et signale la page courante par l’encre sur fond plein. Elle revient à la ligne si nécessaire. Le pied de page boutique conserve des liens textuels discrets et un focus visible.

### Ticket de remise

La zone de verdict porte un pictogramme SVG et un statut textuel. Le bloc cadeau réunit désignation, valeur et métadonnées alignées ; la confirmation serveur transforme l’écran en reçu daté. Seul le pictogramme de réussite reçoit une courte apparition à l’échelle (.25s, ease-out), lorsque l’utilisateur autorise le mouvement. Aucun effet ne précède la confirmation serveur.

## Do's and Don'ts

### Do:
- **Do** conserver les contrôles familiers et les libellés explicites en français.
- **Do** associer chaque état à un texte, même avec un pictogramme.
- **Do** préserver les chiffres tabulaires, le focus visible et le défilement local des tableaux.
- **Do** réserver le vert de l’action de boutique à la remise et sa confirmation.

### Don't:
- **Don't** ajouter d’emoji de statut, d’ombre décorative ou de dépendance à une police externe.
- **Don't** confondre le rouge de marque et une erreur sans libellé explicite.
- **Don't** montrer une réussite avant la réponse de confirmation du serveur.
- **Don't** faire dépendre la présentation boutique du thème ou d’AdminKit.
