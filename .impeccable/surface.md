# Gestion et espace boutique
MODE: Operate
FORM: seed:fba18aec7cb244e2 ; évolution demandée et choix délégués par l’utilisateur.

THESIS: générer une distribution hebdomadaire puis enregistrer une seule remise par bon anonyme. La gestion travaille par lots ; le vendeur accède directement au scanner.
OWN-WORLD: le registre de boutique, clarifié pour l’usage logiciel. Accent COOMS rouge, surfaces blanches, fond neutre en accord avec AdminKit, chiffres tabulaires, typographie système et contrôles familiers. L’utilisateur a demandé de retirer le grand panneau crème et l’effet formulaire habillé pour une interface SaaS plus aboutie.
STORY: Lots → détail des bons → téléchargement ; boutique : scanner ou référence → vérifier → confirmer → bon suivant. Aucun effet de scan ne consomme un bon.
FIRST VIEWPORT: gestion fluide jusqu’à 1800px, titre/action, compteurs ouvrant les bons associés, onglets Lots/Bons et tableau. Scanner : verdict de tâche, zone caméra compacte, action explicite et référence de secours ; pas de demande caméra automatique.
PALETTE: gestion et boutique blanc/gris neutre, texte graphite, accent rouge COOMS. Disponible vert discret ; Retiré neutre dans le tableau ; Expiré ambre. Une tentative de second retrait demeure refusée clairement. La confirmation utilise un vert plus affirmé que la disponibilité.
TYPE: système natif, titres courts, corps gestion 14px, boutique 16px, références en chasse fixe car ce sont des données.
SIGNATURE: le même bloc cadeau devient une confirmation datée ; « Scanner le bon suivant » clôt chaque retrait. La caméra est fermée avant navigation et quand la page est masquée.
PATH: code-first, sans image décorative ni police externe. Décodeur jsQR1.4 embarqué ; images traitées uniquement dans le navigateur.
RISK: le scanner sur iPhone/iPad réel et les exclusions de cache de production nécessitent une recette physique avant mise en service. Tests locaux avec caméra virtuelle et images de vrais bons de recette.
