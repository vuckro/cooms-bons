# COOMS Bons
<!-- impeccable:product-schema 1 -->

## Platform
web

## Stack
Plugin WordPress PHP autonome, HTML rendu serveur, CSS et JavaScript progressif. Choix d’implémentation délégués par l’utilisateur.

## Users
Responsables COOMS : préparer chaque semaine des lots anonymes pour une Fondation. Vendeurs : vérifier puis enregistrer un retrait depuis l’iPhone/iPad du magasin. Fondation : distribuer les bons sans transmettre les identités des bénéficiaires.

## Product Purpose
Un cadeau défini par lot contre un bon anonyme à usage unique en boutique à Genève. Par défaut : boîte de quatre cookies, 20 CHF, valable six mois ; le responsable peut choisir un autre intitulé et une date limite.

## Operating Context
Compte WordPress vendeur, scanner caméra dans la page boutique (ou caméra native), saisie de référence en secours, navigateur connecté et Internet. Génération par lot, export ZIP PNG individuel, distribution externe, validation explicite, réconciliation CSV. Test WordPress localhost:10033 ; dépôt github.com/vuckro/cooms-bons.

## Capabilities and Constraints
Vue Lots et détail des bons, scanner réservé aux vendeurs, recherche de référence, tokens cryptographiques, date limite personnalisable (six mois calendaires proposés) en Europe/Zurich, retrait atomique, suivi et exports privés. Aucun nom/e-mail bénéficiaire. Gestion des accès vendeurs via les comptes WordPress : création, invitation native facultative, suspension/réactivation. Pas de caisse Lightspeed, WooCommerce, FluentBooking, envoi des bons par mail ou mode hors ligne. Livraison prévue le 13 novembre 2026. Tutoriel court prévu.

## Brand Commitments
COOMS Bons ; français clair, vocabulaire de boutique. L’utilisateur délègue les choix et demande simplicité, efficacité, fonctionnement prévisible et maintenabilité. La gestion doit s’intégrer à WordPress ; la caisse doit rester indépendante du thème.

## Evidence on Hand
Brief et plan détaillés dans la conversation. Copie locale du site existant ; capture d’administration COOMS montrant un accent rouge. Aucune preuve d’un retrait réel ni identité client à inventer.

## Product Principles
- Le scan consulte ; le bouton confirme la remise.
- Une seule remise même en cas de requêtes concurrentes.
- L’erreur explique la reprise sans inventer une réussite.
- Les exports ne créent ni ne prolongent les bons.
- La désactivation conserve les données.
