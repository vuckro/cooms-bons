# COOMS Bons 1.1.0 — recette du 6 novembre 2026

## Changement livré

Scanner réservé aux vendeurs, caméra intégrée avec jsQR local, saisie de référence et retour au bon suivant. Aucun scan ne consomme un bon ; la confirmation du retrait reste atomique. Accueil Lots et détail des bons, gestion plus fluide et statuts retirés neutres. Mise à niveau sans changement du schéma ni modification des bons existants.

## Contrôles locaux

Copie WordPress COOMS sur localhost:10033, PHP web8.3, avec WooCommerce et AdminKit actifs. Installation du ZIP et recette sur des lots/comptes jetables identifiés.

- 47 contrôles métier : règles existantes, références manuelles, URLs QR locales, refus externes/fragment/chemin/injection de tableau, volumes par lot et exception WooCommerce limitée aux pages COOMS autorisées.
- 38 contrôles HTTP : accès et nonces, exports, six validations concurrentes, recherche sans consommation, refus des QR externes, lien du menu vendeur et accueil Lots.
- 14 contrôles Chromium : véritable décodeur jsQR, flux caméra virtuel contenant le PNG d’un bon exporté, pistes vidéo arrêtées avant redirection, confirmation puis bon suivant, référence manuelle, bon déjà utilisé, refus/absence de caméra, contexte non sécurisé, annulation d’une permission tardive et responsive à320/390px. Captures également à768 et1440px.
- Les refus caméra sont simulés ; le décodage du flux vidéo ne l’est pas. Aucune caméra physique de l’utilisateur n’est ouverte.
- Détecteur Impeccable exécuté une fois sur les templates et scanner.js : aucune détection. Les contrôles visuels ne reposent pas sur ce seul résultat.
- La première mesure a confirmé des champs32px imposés par AdminKit face aux boutons42px. Sélecteurs COOMS corrigés pour conserver des composants42px cohérents sur bureau et44px sur mobile.

## Limites et production

Pas de déploiement en production. Vérifier la caméra et le navigateur réellement utilisés en magasin ; l’émulation et le flux virtuel ne prouvent pas le comportement de l’iPhone/iPad physique. HTTPS nécessaire à la caméra, hors exception localhost. Exclure les deux paramètres de scanner et de bon des caches/CDN. Les documents de la version1.0 décrivent la recette historique, pas une validation actuelle de production.
