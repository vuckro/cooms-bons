# COOMS Bons 1.4.0 — finition et webapp, 7 novembre 2026

## Livré

Le scanner et les fiches de bons partagent un manifeste COOMS Boutique, des icônes192/512/maskable/Apple180 et les métadonnées du mode autonome. L’icône démarre au scanner, sans référence de bon ni jeton. Le scanner affiche une aide d’installation iOS/Android et le bouton natif lorsque le navigateur le propose. Aucun service worker, stockage hors ligne de bons ou cache applicatif privé.

Gouttières avec safe-area insets, viewport mobile, caméra moins haute et actions principales visibles sur les formats portrait de recette et1280×720. Retour de veille : nouvelle lecture serveur, sauf formulaire en cours, jamais rejoué. Perte réseau détectée : fermeture des pistes caméra, blocage des recherches et retraits ; une fiche disponible devient « Vérification en pause ». Le retour réseau exige une actualisation, sans réactiver un état périmé. Déconnexion depuis un bon : retour au scanner.

Gestion conservée dans AdminKit, avec reprise des tokens. L’accent local configuré produisait un contraste bouton de3,80:1 ; le fond de l’action est ajusté localement à4,63:1 en conservant l’encre et la famille de teinte. Aucun token AdminKit global n’est modifié. Les états de survol et le changement de thème sont également vérifiés ; une couleur déjà accessible reste exacte.

## Vérifications

- **94 contrôles PHP/métier**, sur WordPress local : règles de bons, dates, rôles, permissions, comptes employés, sessions, exports et dimensions réelles des icônes du paquet.
- **66 contrôles HTTP** : retraits concurrents, nonces, autorisations, ZIP/CSV, création personnalisée, accès employés et suspension d’une session connectée ; manifeste public avec le bon MIME, icônes décodables, métadonnées des pages de scanner et de bon.
- **38 contrôles Chromium** : décodage jsQR du PNG par caméra virtuelle, validation, arrêt des pistes, erreurs caméra, réseau coupé/rétabli, blocage des POST hors connexion, reprise de veille sans rejouer d’opération, manifeste et icône, aide au clavier et profil autonome iOS simulé. Contrats de contraste avec tokens hôtes volontairement défaillants puis changement de thème.
- Formats scanner Chromium :320×740,390×844,768×1024,844×390,1024×768,1280×720 ; pas de débordement horizontal du document. Bouton d’ouverture visible sans défilement sur les formats de hauteur supérieure à600px ; défilement vertical normal en paysage compact.
- **WebKit** : scanner à390×844,320×740,768×1024,1024×768 et844×390 ; pas de débordement et métadonnées Apple présentes. Formulaire de création sombre à390px avec aperçu du cadeau actif. Ceci teste le moteur, pas un iPhone physique.
- **20 vues d’administration** : lots, tous les bons, création, employés et aide, clair/sombre à1440/390px ; pas de débordement du document. Inspection des états valide, utilisé, expiré, succès, introuvable, connexion, droits refusés, hors connexion et texte long à320px.
- Manifeste accepté par Chromium sans erreur critique de parsing. Son contrôle d’installabilité ne relève que `in-incognito`, le navigateur de recette étant isolé ; aucune installation OS réelle revendiquée.
- Détecteur Impeccable : aucun anti-pattern sur le scan statique CSS/JS. Avis non bloquants sur des valeurs historiques de l’échelle ; les contrastes réels et les parcours sont vérifiés séparément, le détecteur ne les certifie pas.

Captures et journaux locaux : `output/playwright/`, `output/polish-*.log` et `output/webkit-smoke-results.txt` (hors ZIP livré). Les seuls lots et comptes jetables de cette recette sont supprimés après vérification ; les anciens lots présents avant la passe sont conservés.

## Limites avant production

Aucun déploiement en production dans cette passe. Il reste à installer l’app depuis Safari sur l’iPhone/iPad réel du magasin, se connecter dans cette app, scanner un bon sur écran et sur papier, vérifier la veille, la caméra et la reprise réseau sur le domaine final en HTTPS. La webapp exige Internet ; une perte réseau non signalée par `navigator.onLine` demeure soumise aux contrôles serveur. Aucune réception d’e-mail réelle ni exclusion CDN de production n’est certifiée par cette recette.

Références techniques : [installation des PWA](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps/Guides/Making_PWAs_installable), [métadonnées Safari](https://developer.apple.com/library/archive/documentation/AppleApplications/Reference/SafariHTMLRef/Articles/MetaTags.html).
