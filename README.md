# COOMS Bons

Plugin WordPress de bons anonymes à usage unique : lots pour la Fondation, QR codes individuels, retrait physique en boutique et réconciliation CSV. Version 1.2.0.

## Prérequis

WordPress 6.4+, PHP 8.1+, MySQL/MariaDB avec tables InnoDB, extensions PHP GD (avec FreeType), ZIP et iconv. HTTPS en production. Navigateur connecté et accès Internet en boutique. Pas de service externe de génération de QR ni d’envoi d’e-mail.

## Installer

Utiliser le ZIP **installable** de release (dépendances incluses), pas le ZIP automatique des sources GitHub. Extensions → Ajouter une extension → Téléverser, puis activer. La vérification de l’hébergement doit précéder la première génération.

Depuis les sources : `composer install --no-dev --prefer-dist --optimize-autoloader`, puis `python3 tools/build.py`. Le ZIP est produit dans `dist/`. Le poste de développement a besoin de Composer ; le serveur n’en a pas besoin.

L’activation crée deux tables avec le préfixe WordPress (`cooms_lots`, `cooms_bons`), le rôle « Vendeur COOMS » et deux capacités :

- `cooms_manage_bons` : création, suivi et exports, attribuée aux administrateurs ;
- `cooms_redeem_bons` : consultation et validation, attribuée aux administrateurs et au rôle vendeur.

Créer les comptes vendeurs dans Comptes → Ajouter ; choisir « Vendeur COOMS ». Un compte par personne permet d’identifier le compte ayant enregistré le retrait. Vérifier l’accès après tout changement de gestionnaire de rôles. Activation site par site ; activation réseau multisite non prise en charge dans cette V1.

## Espace boutique : scanner un bon

Ouvrir **COOMS Bons → Scanner un bon**, ou enregistrer le lien `?cooms_scan=1` sur l’écran d’accueil de l’iPhone/iPad du magasin. Le vendeur arrive directement à cet espace depuis le menu principal COOMS Bons. Les comptes sans permission sont refusés ; les visiteurs sont invités à se connecter.

1. Appuyer sur **Ouvrir la caméra** et autoriser son accès.
2. Présenter le QR entier. La lecture se fait localement dans le navigateur ; aucune image ni aucun son n’est envoyé au serveur. Le décodeur jsQR 1.4.0 est inclus dans le plugin, sans CDN.
3. La fiche du bon s’ouvre. Vérifier le statut puis confirmer la remise.
4. Après confirmation, **Scanner le bon suivant** revient au lecteur.

La caméra exige HTTPS en production (localhost est utilisable pour les tests). Un téléphone accédant à une simple adresse IP locale en HTTP peut se voir refuser la caméra : utiliser HTTPS ou la référence manuelle. Refus de permission, caméra absente/occupée et navigateur incompatible disposent de messages et d’une saisie de secours.

La référence `COOMS-XXXXXXXXXX` peut être saisie sous le lecteur sans JavaScript. La recherche est réservée aux vendeurs, protégée par nonce et ne consomme rien. Les QR externes ne provoquent jamais une redirection vers un autre site. La caméra est arrêtée avant toute navigation, à l’annulation et quand l’onglet est masqué, y compris si une autorisation arrive tard.

WooCommerce bloque habituellement l’administration des comptes sans droits d’édition. L’extension autorise uniquement ses pages de boutique (menu, scanner et guide) pour les vendeurs : leurs droits restent limités aux bons.

## Préparer un lot

COOMS Bons → Nouveau lot → saisir le nom et la quantité (1–250) → Créer le lot. Le cadeau et la date limite sont personnalisables pour chaque nouveau lot. Les valeurs proposées sont « Boîte de 4 cookies — 20 CHF » et six mois calendaires. La date est incluse jusqu’à 23 h 59 à Genève. Un lot déjà créé conserve son cadeau et ses dates. La quantité limite protège le temps et la mémoire de génération ; créer plusieurs lots pour davantage de bons.

Le lot est enregistré intégralement dans une transaction. Une soumission répétée du même formulaire renvoie le même lot ; un nouveau formulaire permet de créer volontairement un autre lot, même avec le même nom.

Télécharger le ZIP depuis le détail du lot. Chaque PNG (1000 × 1320) comporte un QR noir sur blanc, la référence, le cadeau et la date de validité. Transmettre le ZIP à la Fondation, qui distribue une image par personne. Un nouveau téléchargement restitue les mêmes bons : aucun renouvellement de validité et aucune duplication.

Par défaut, six mois **calendaires**, depuis la génération, jusqu’à la fin du jour de destination à Genève ; le jour est ramené à la fin du mois si nécessaire. Un bon du 31 août 2026 est ainsi valable jusqu’au 28 février 2027 inclus. Stockage UTC, affichage Europe/Zurich, limite exclusive au minuit suivant. L’expiration est vérifiée à chaque opération sans cron.

## Retirer en boutique

1. Se connecter au compte vendeur dans le navigateur de l’iPhone/iPad.
2. Scanner le QR avec l’appareil photo et ouvrir le lien dans ce navigateur.
3. Sur « Bon valide », appuyer sur « Valider la remise de la boîte ».
4. Attendre « Remise enregistrée » avant de remettre la boîte.

Le scan GET n’enregistre rien. La validation POST contrôle la connexion, la capacité et le nonce, puis effectue une écriture conditionnelle atomique. Deux requêtes simultanées ne peuvent pas consommer deux fois un bon. La date du premier retrait et le compte vendeur sont conservés.

« Déjà utilisé » indique la date et l’heure du retrait. « Expiré » ne propose pas d’action. Un lien inconnu n’expose pas d’informations. Les visiteurs sans session sont invités à se connecter sans voir le lot. Aucun mode hors ligne : si la connexion est interrompue, rouvrir le bon pour vérifier son statut avant toute nouvelle tentative.

## Suivi

Accueil par lots, détail d’une distribution, vue transversale des bons, filtres lot/statut et compteurs total/disponibles/retirés/expirés. Les compteurs ouvrent les bons concernés. Le libellé de gestion « Retiré » correspond au statut historique « Utilisé » conservé dans le CSV. Un bon utilisé reste utilisé après expiration. Le compteur « Disponibles » exclut les expirés. Les compteurs portent sur le lot sélectionné ou tous les lots ; le statut filtre le tableau et le CSV.

CSV UTF-8 avec BOM et séparateur point-virgule, compatible Excel, contenant code, lot, création, validité, statut, retrait et cadeau (colonne ajoutée à la fin pour préserver l’ordre des six colonnes historiques). Les tokens et liens utilisables ne figurent pas dans le CSV. Les noms de lot commençant par un caractère de formule sont neutralisés.

## Cache, confidentialité et exploitation

- Exclure explicitement les URLs contenant le paramètre `cooms_bon` ou `cooms_scan` du cache de page, reverse proxy et CDN, **avant** que WordPress ne soit exécuté. Les en-têtes no-store et DONOTCACHEPAGE protègent les caches coopératifs, mais ne peuvent pas corriger une réponse servie en amont sans PHP.
- Ces pages ne chargent ni le thème ni ses scripts de mesure ; elles portent noindex/nofollow/noarchive et Referrer-Policy: no-referrer. Cela ne remplace pas les exclusions du CDN et des outils de journalisation.
- Les bons sont au porteur. Une copie transférée peut être présentée par quelqu’un d’autre ; seul le premier retrait gagne. Sans identité, le bénéficiaire ne peut pas être vérifié.
- Le token secret est conservé en base pour permettre les réexports. Protéger la base, les sauvegardes, les ZIP et les journaux d’URL. Ne pas copier de liens de bons réels dans les tickets publics. Ne pas journaliser les paramètres `cooms_bon` dans les outils de mesure/support.
- Aucun nom/e-mail bénéficiaire n’est collecté. Le compte vendeur et l’heure de retrait constituent une trace interne ; fixer avec COOMS une durée de conservation et les droits d’accès aux sauvegardes. Aucun effacement automatique n’est activé.
- Les archives temporaires sont générées dans le répertoire temporaire système et supprimées après téléchargement, hors médiathèque publique.
- Désactiver ou supprimer le plugin conserve ses données pour éviter une perte de suivi. La purge nécessite une opération de maintenance explicite, sauvegardée. Ne pas supprimer les comptes vendeurs avant le rapprochement sans conserver leur correspondance d’identifiants.
- Ne pas changer le domaine des bons déjà distribués sans prévoir une redirection et un transfert cohérent des données. Émettre les vrais bons uniquement sur le domaine final.
- Aucune réactivation, suppression de lot ou annulation de retrait dans la V1 : évite les corrections non traçables. Une erreur de remise se traite avec le responsable, hors modification silencieuse du bon.

## Déploiement et retour arrière

Voir [la procédure](docs/DEPLOIEMENT.md). La copie locale est une recette, pas la production. Ne pas importer les bons de test en production. Après des retraits réels, restaurer une ancienne base risque de réactiver des bons consommés : conserver la base actuelle et revenir au code précédent en priorité.

## Tests

Sur une **copie locale uniquement** : `wp eval-file tests/integration.php` (64 assertions métier ; crée et retire ses seules données de test). Suite HTTP reproductible sur localhost (42 contrôles supplémentaires) : `wp eval-file tests/prepare-http.php`, puis `python3 tests/http_checks.py`, enfin `wp eval-file tests/cleanup-http.php`, même en cas d’échec. Elle crée ses propres comptes temporaires et lot, vérifie les accès, ZIP, CSV et six validations concurrentes. Le fichier `output/http-fixture.json` contient des cookies éphémères : il est privé, ignoré par Git et supprimé au nettoyage. GitHub Actions exécute ces tests avec PHP 8.1, 8.3 et 8.4. Les captures mobiles/tablettes et le décodage Apple Vision complètent cette suite. Le test Swift optionnel `swift tests/decode-qr.swift chemin/bon.png` vérifie le décodage d’un PNG sous macOS.

## Recette caméra et responsive

Après les tests HTTP et avant leur nettoyage : `wp eval-file tests/prepare-camera.php`, puis `python3 tests/browser_checks.py`. Nécessite Chrome, ffmpeg et Playwright CLI. Le script ouvre et ferme sa propre session dédiée, utilise une vidéo virtuelle d’un PNG réellement exporté et ne touche pas la caméra physique. `PWCLI` permet de définir le chemin du CLI. Les 14 contrôles couvrent le décodage, la fermeture des pistes, la remise, le bon suivant, la saisie manuelle, les permissions refusées, l’absence de caméra, HTTP non sécurisé, annulation tardive et débordements responsive. Ces tests sont exécutés dans le job PHP 8.3 de GitHub Actions.

La recette physique avec le matériel de caisse reste nécessaire avant la mise en production. Le tutoriel vidéo1.1 montre les nouvelles pages et le scanner intégré ; voir [le guide du tutoriel](docs/TUTORIEL-1.1.md). Le tutoriel1.0 est conservé pour l’historique.

## Hors périmètre

Lightspeed, paiements, WooCommerce, FluentBooking, e-mails bénéficiaires, PDF additionnel, PIN partagé, fonctionnement hors ligne.
