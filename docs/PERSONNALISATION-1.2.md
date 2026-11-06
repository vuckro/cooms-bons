# Cadeau et validité par lot — 1.2.0

Créer un lot comporte quatre champs : nom, quantité, cadeau offert et date de fin de validité. Le cadeau historique et six mois calendaires restent proposés par défaut. La date sélectionnée est incluse jusqu’à23h59 à Genève ; le serveur conserve la limite exclusive UTC au minuit suivant. Les dates passées, impossibles ou vides sont refusées.

L’intitulé (texte simple,1–190caractères) est enregistré dans le lot. Il est repris dans les fiches de retrait, les PNG (composition sur plusieurs lignes si nécessaire) et la nouvelle dernière colonne Cadeau du CSV. L’aperçu se met à jour pendant la saisie ; une erreur serveur restitue le formulaire avec ses champs et sa clé de soumission, via un message privé temporaire lié au compte gestionnaire.

Les bons déjà créés conservent leurs caractéristiques. Aucun changement de schéma, de token, d’URL ou de règles d’usage unique. Cette évolution ne crée ni paiement ni réservation en ligne.

Recette locale :64 contrôles métier dont cadeau/date personnalisés, limites de saisie, dates impossibles, changements d’heure et PNG long ;42 contrôles HTTP dont création réelle, restauration après erreur, CSV et fiche vendeur personnalisée. Aperçu et responsive contrôlés dans Chromium. Les autres contrôles sont exécutés dans la matrice GitHub ; état à vérifier dans le dépôt.
