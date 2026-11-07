=== COOMS Bons ===
Contributors: vuckro
Tags: qr-code, gift-vouchers
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bons anonymes par lots, QR codes PNG et validation sécurisée en boutique COOMS.

== Description ==
Génération de lots, cadeau et date limite personnalisables (six mois proposés), export ZIP PNG, rôle vendeur, validation à usage unique, suivi et export CSV. Aucun service externe ni paiement. Invitation facultative des employés via les e-mails natifs WordPress.

== Installation ==
Téléverser le ZIP de release avec ses dépendances et activer. Nécessite GD/FreeType, ZIP et InnoDB. Lire README.md et configurer les exclusions de cache avant utilisation réelle.

== Frequently Asked Questions ==
= Le scan utilise-t-il le bon ? =
Non. Seule la confirmation explicite d’un vendeur autorisé enregistre la remise.

= La désinstallation efface-t-elle les bons ? =
Non. Les données sont conservées pour préserver le rapprochement des retraits.

== Changelog ==

= 1.5.0 =
* Identité officielle COOMS sur les bons, le scanner et les icônes webapp.
* Attribution des retraits à l’employé dans les listes et le CSV.
* Libellé « Installer l’application ».

= 1.4.1 =
* Alignement de l’aperçu caméra sur toute la largeur du panneau et contrôles visuels de géométrie étendus.

= 1.4.0 =
* Scanner installable en webapp, icônes dédiées et zones de sécurité iPhone/iPad.
* Blocage des actions hors connexion, actualisation au retour et finition des interfaces.

= 1.3.0 =
* Accès employés : création de comptes vendeurs, invitation WordPress facultative, suspension/réactivation et accès direct au scanner.

= 1.2.1 =
* Pont optionnel vers le design system AdminKit : accents, typographie, contrôles, focus et statuts clair/sombre.
= 1.2.0 =
Cadeau et date de validité personnalisables par lot, repris sur les bons, retraits et exports.

= 1.1.0 =
Scanner caméra intégré, saisie manuelle de secours, vue Lots et interface de gestion affinée.

= 1.0.0 =
Première version : lots anonymes, ZIP PNG, suivi CSV et retraits sécurisés.
