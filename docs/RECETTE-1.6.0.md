# Recette 1.6.0 — descriptions internes et commandes caméra

7 novembre 2026. Descriptions de lot facultatives, multiligne, 2 000 caractères maximum, réservées aux responsables. Saisie à la création et modification dans la fiche du lot, avec nonce et contrôle de capacité. Le formulaire conserve la description lors d’une erreur de validation. Aucun texte interne dans les données de bon, PNG, CSV ou pages de scan.

Migration schéma 1 vers 2 : ajout de `lots.description` nullable. Sauvegarde préalable et comparaison des données réelles avant/après : tous les lots, tokens, dates et retraits existants sont identiques. Les anciennes descriptions sont vides. Aucun import de données de recette vers la production.

La demande de permission caméra affiche ses deux commandes en colonne, à largeur complète. Un contrôle navigateur vérifie leur géométrie avant l’annulation et conserve le test de fermeture d’un flux autorisé tardivement.

Vérifications locales : 106 contrôles métier et 75 HTTP réussis, dont confidentialité, modification responsable, refus vendeur et nonce invalide. Le workflow CI couvre également PHP 8.5 utilisé par la production.

Le correctif du helper Local Live Link reste séparé du plugin : utilisation de `map_deep` avant sérialisation par WordPress, au lieu d’un remplacement d’URL dans une chaîne sérialisée. Erreur reproduite avant correction, test de tableau/objet imbriqués, Unicode, types scalaires et enregistrement d’option réussi après correction. Ce helper de développement n’est pas déployé en production.
