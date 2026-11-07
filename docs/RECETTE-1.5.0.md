# Recette 1.5.0 — identité COOMS et attribution des retraits

Date : 7 novembre 2026. Cible : copie locale COOMS, localhost:10033. Aucune modification de production.

## Livrables

- Logotype officiel rouge actuellement utilisé dans l’en-tête du site COOMS, embarqué au format PNG transparent dans le plugin.
- Bons PNG de 1000 × 1320 : logo, bandeau et pied crème, cadeau réel, QR noir sur blanc avec marge préservée, référence et date inclusive. Le ZIP de tout lot existant utilise le nouveau modèle au prochain téléchargement ; les fichiers déjà distribués restent inchangés et leurs QR continuent de fonctionner.
- Scanner et écrans de vérification : vrai logo et palette crème/rouge/anthracite, mêmes statuts métier ; icônes installables et Apple assorties. Couleur d’action assombrie pour un contraste lisible avec le blanc. Assets locaux, sans dépendance distante.
- « Installer l’application » remplace « Installer sur cet appareil ».
- Colonne finale « Validé par » dans les bons d’un lot et dans tous les bons. Jointure utilisateurs sans requête par ligne. CSV : huitième colonne ajoutée, sept premières colonnes conservées ; formules neutralisées sur cette nouvelle valeur également.
- Compte supprimé : numéro d’audit conservé. Aucun employé affiché sur les bons imprimables ou les pages bénéficiaires. Les comptes partagés ne permettent pas de distinguer les personnes.
- Compatibilité AdminKit conservée sans modifier ses tokens.

## Vérifications locales

97 assertions métier, 68 contrôles HTTP et 52 contrôles navigateur : **217 contrôles réussis**. Les nouveaux contrôles vérifient l’attribution responsable/vendeur et son export, le repli pour compte supprimé et l’absence d’attribution sur les bons disponibles. Le scanner décode un véritable PNG du nouveau modèle via une caméra virtuelle et enregistre le retrait unique.

Rendu vérifié sous Chromium sur six formats (1440, 1024, 768, 390, 320 et paysage 844 px) : scanner, bon et tableau de lot en clair/sombre. Logo chargé, absence de débordement de page, aperçu caméra à la largeur du bouton et colonne employé présente. Captures inspectées en lot, y compris installation ouverte, bon mobile et tableau sombre.

Les tests créent et suppriment leurs propres lots et comptes ; les données existantes sont conservées. Les sauvegardes et captures sont privées, ignorées par Git et exclues du ZIP. La caméra physique, l’installation sur un appareil réel et la production restent hors de cette recette locale.
