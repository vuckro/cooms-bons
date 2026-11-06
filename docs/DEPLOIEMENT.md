# Déploiement COOMS Bons

## Avant production

- Vérifier les versions PHP/WordPress et les extensions GD/FreeType/ZIP sur le serveur final.
- Confirmer HTTPS, domaine définitif, sauvegarde de la base et du code.
- Installer le ZIP de release avec dépendances. Vérifier les tables InnoDB et les rôles.
- Exclure `cooms_bon` de tous les caches/CDN et de la collecte des URLs analytiques.
- Créer les accès vendeurs et vérifier les permissions depuis leurs comptes.
- Faire la recette avec l’appareil réel : caméra → navigateur connecté → bon valide → confirmation → nouveau scan utilisé ; image sur smartphone et impression papier.
- Tester une expiration, une absence de droit et une perte de connexion ; contrôler le CSV et les totaux.
- Retirer les seules données de recette avant la première génération réelle (opération de maintenance ciblée, jamais TRUNCATE sur un site actif).

## Livraison

ZIP installable, sources versionnées, notice README, guide intégré « Aide boutique », tutoriel court et compte rendu de recette. La recette locale ne prouve ni les exclusions CDN de production ni l’ergonomie de l’appareil physique du magasin.

## Retour arrière

Désactiver le plugin arrête les validations sans effacer de données. Revenir au code précédent avec la base actuelle en priorité. Ne jamais restaurer une ancienne base contenant des bons disponibles alors que des retraits ont eu lieu depuis. Suspendre les remises pendant toute intervention sur l’état des bons et rapprocher les retraits avant réouverture.
