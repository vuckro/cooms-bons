# COOMS Bons1.4.1 — revue visuelle, 7 novembre2026

## Correction

Le plafonnement de hauteur de la zone caméra, combiné au ratio4/3 et à une largeur automatique, pouvait aussi réduire sa largeur. À1440×780, l’aperçu mesurait436,78px pour un panneau640px et une largeur utile606px. Il laissait donc une marge blanche excessive à droite ; son contenu semblait décalé à gauche.

La largeur est maintenant explicite à100%. L’aperçu et le bouton mesurent tous deux606px ; l’instruction est centrée sur le panneau entier. La hauteur plafonnée, les fonctions de caméra et le design existant sont conservés.

## Revue

-60vues d’administration : lots, tous les bons, détail d’un lot prêt, création, employés et aide ; clair/sombre à1440,1024,768,390et320px. Vérification des débordements, bornes des panneaux, champs et captures.
-États de bon : disponible, utilisé, expiré, remise enregistrée, introuvable, connexion requise, droits refusés et perte réseau ; libellés longs à320px.
-Chromium : géométrie contrôlée à320×740,390×844,768×1024,844×390,1024×768,1280×720,1440×780,1440×1000et1920×1080. Largeur/bord gauche de l’aperçu identiques au bouton et centrage du libellé sur le panneau, tolérance inférieure à1px. La vidéo active doit également remplir exactement l’aperçu.
-WebKit : mêmes mesures à1440×780,1920×1080,1280×720,1024×768,768×1024,390×844,320×740et844×390. Aucun débordement ; largeur606px sur les grands formats,340px à390et270px à320. Écart de centrage mesuré inférieur à0,01px sur ces captures.
-51contrôles navigateur et66HTTP réussis localement ; les94contrôles métier sont conservés dans la matrice CI PHP8.1/8.3/8.4 et sur le ZIP réellement installé.

Les anciens contrôles d’absence de débordement ne suffisaient pas à détecter un aperçu trop étroit. Les contrôles de géométrie ajoutés couvrent précisément cette régression. Les captures et mesures sont dans `output/playwright/align-*`, `output/playwright/alignment-scanner-*` et `output/alignment-*`, hors du ZIP livré.

Aucun changement des bons, permissions ou règles de remise. Les seules données jetables créées pour cette recette sont supprimées à la fin. Cette revue navigateur ne remplace pas la recette physique iPhone/iPad sur le domaine HTTPS de production.
