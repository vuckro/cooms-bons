# COOMS Bons 1.4.1 — revue visuelle du 7 novembre 2026

## Corrections

Le plafonnement de hauteur de la zone caméra, combiné au ratio 4/3 et à une largeur automatique, pouvait aussi réduire sa largeur. À 1440 × 780, l’aperçu mesurait 436,78 px pour un panneau de 640 px et une largeur utile de 606 px. Il laissait une marge blanche excessive à droite et son contenu semblait décalé à gauche.

La largeur est maintenant explicite à 100 %. L’aperçu et le bouton mesurent tous deux 606 px ; l’instruction est centrée sur le panneau entier. La hauteur plafonnée, les fonctions de caméra et le design existant sont conservés.

La bannière de référence introuvable héritait d’une marge prévue pour l’intérieur des fiches de bons. Elle était décalée de 28 px et mesurait 584 px contre 640 px pour le panneau caméra. Elle suit désormais la même colonne que le titre et le panneau, sans modifier les messages internes aux fiches.

## Revue

- **60 vues de gestion** : lots, tous les bons, détail d’un lot prêt, création, employés et aide ; clair/sombre à 1440, 1024, 768, 390 et 320 px. Vérification des débordements, bornes des panneaux, champs et captures.
- **États des bons** : disponible, utilisé, expiré, remise enregistrée, introuvable, connexion requise, droits refusés et perte réseau ; libellés longs à 320 px. Vérification du retour d’erreur de recherche manuelle.
- **Chromium** : géométrie contrôlée à 320 × 740, 390 × 844, 768 × 1024, 844 × 390, 1024 × 768, 1280 × 720, 1440 × 780, 1440 × 1000 et 1920 × 1080. Les bords et la largeur de l’aperçu correspondent au bouton ; le libellé reste centré sur le panneau, avec une tolérance inférieure à 1 px. La vidéo active remplit également l’aperçu.
- **WebKit** : mesures à 1440 × 780, 1920 × 1080, 1280 × 720, 1024 × 768, 768 × 1024, 390 × 844, 320 × 740 et 844 × 390. Aucun débordement ; largeur de 606 px sur les grands formats, 340 px à 390 et 270 px à 320. Écart de centrage inférieur à 0,01 px sur ces captures.
- **52 contrôles navigateur, 66 HTTP et 94 contrôles métier** dans la matrice de validation. Les contrôles portent également sur le ZIP réellement installé.

Les anciens contrôles d’absence de débordement ne suffisaient pas à détecter un aperçu trop étroit. Les nouveaux contrôles de géométrie couvrent cette régression et l’alignement des retours d’erreur. Les preuves locales sont dans `output/playwright/align-*`, `output/playwright/alignment-*` et `output/alignment-*`, hors du ZIP livré.

Aucun changement des permissions ou règles de remise. Les seules données jetables créées pour cette recette sont supprimées à la fin. Cette revue navigateur ne remplace pas la recette physique sur l’iPhone/iPad du magasin, avec le domaine HTTPS de production.
