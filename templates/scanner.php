<?php
namespace Cooms\Bons;
defined('ABSPATH') || exit;
?>
<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive"><meta name="referrer" content="no-referrer"><meta name="theme-color" content="#ffffff"><title>Scanner un bon · COOMS</title>
<link rel="stylesheet" href="<?php echo esc_url(plugins_url('assets/cooms.css', COOMS_BONS_FILE)); ?>?ver=<?php echo esc_attr(COOMS_BONS_VERSION); ?>">
<?php if ($access === 'allowed') : ?>
<script defer src="<?php echo esc_url(plugins_url('assets/vendor/jsQR.js', COOMS_BONS_FILE)); ?>?ver=1.4.0"></script>
<script defer src="<?php echo esc_url(plugins_url('assets/scanner.js', COOMS_BONS_FILE)); ?>?ver=<?php echo esc_attr(COOMS_BONS_VERSION); ?>"></script>
<?php endif; ?></head>
<body class="cb-scan cb-scanner-page"><header class="cb-brand"><a href="<?php echo esc_url(self::url()); ?>">COOMS</a><span>Espace boutique</span></header>
<main class="cb-scanner" id="main">
<?php if ($access === 'allowed') : ?>
    <header class="cb-scanner-heading"><h1>Scanner un bon</h1><p>Vérifiez le bon avant de remettre le cadeau.</p></header>
    <?php if ($error) : ?><div class="cb-error" role="alert"><?php echo esc_html($error); ?> <a href="<?php echo esc_url(self::url()); ?>">Réessayer</a></div><?php endif; ?>
    <section class="cb-camera-section" aria-label="Lecture du QR code" data-scanner data-home="<?php echo esc_url(home_url('/')); ?>">
        <div class="cb-viewfinder" data-viewfinder>
            <video playsinline muted autoplay hidden aria-label="Aperçu de la caméra" data-video></video>
            <div class="cb-camera-idle" data-idle><svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true"><path d="M4 8V4h4m8 0h4v4M4 16v4h4m8 0h4v-4M7 7h3v3H7zm7 0h3v3h-3zM7 14h3v3H7zm7 0h3v3h-3z"/></svg><strong>Le QR code face à la caméra</strong><span>Sur téléphone ou sur papier</span></div>
            <div class="cb-camera-guide" data-guide hidden aria-hidden="true"></div>
        </div>
        <div class="cb-camera-controls"><button class="cb-button" type="button" data-start>Ouvrir la caméra</button><button class="cb-button cb-secondary" type="button" data-stop hidden>Arrêter la caméra</button></div>
        <p class="cb-camera-status" role="status" aria-live="polite" data-camera-status>La caméra s’ouvre uniquement à votre demande.</p>
        <form method="post" action="<?php echo esc_url(self::url()); ?>" hidden data-qr-form><input type="hidden" name="code" value=""><?php wp_nonce_field('cooms_lookup'); ?></form>
    </section>
    <section class="cb-manual" aria-labelledby="cb-manual-title"><h2 id="cb-manual-title">Ou saisir la référence</h2><p>Le code se trouve sous le QR du bon.</p><form method="post" action="<?php echo esc_url(self::url()); ?>" data-manual-form><?php wp_nonce_field('cooms_lookup'); ?><label for="cb-reference">Référence du bon</label><div class="cb-manual-row"><input id="cb-reference" type="text" name="code" value="<?php echo esc_attr($reference); ?>" maxlength="32" required autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="COOMS-XXXXXXXXXX"><button type="submit" class="cb-button cb-secondary">Vérifier le bon</button></div></form></section>
    <p class="cb-scanner-note">La lecture ne consomme pas le bon. Vous confirmerez la remise sur l’écran suivant.</p>
    <noscript><p class="cb-error">La caméra nécessite JavaScript. Vous pouvez saisir la référence ci-dessus.</p></noscript>
<?php else : ?>
    <section class="cb-access"><h1><?php echo $access === 'login' ? 'Accès équipe COOMS' : 'Accès non autorisé'; ?></h1><p><?php echo $access === 'login' ? 'Connectez-vous à votre compte vendeur pour ouvrir le scanner et vérifier les bons.' : 'Demandez un accès Vendeur COOMS au responsable de la boutique.'; ?></p><?php if ($access === 'login') : ?><a class="cb-button" href="<?php echo esc_url(wp_login_url(self::url())); ?>">Se connecter à l’espace boutique</a><?php endif; ?></section>
<?php endif; ?>
</main><footer class="cb-scan-footer"><?php if (is_user_logged_in()) : ?>Session de <?php echo esc_html(wp_get_current_user()->display_name); ?> · <a href="<?php echo esc_url(wp_logout_url(self::url())); ?>">Se déconnecter</a><br><a href="<?php echo esc_url(admin_url('admin.php?page=cooms-bons')); ?>">Administration COOMS Bons</a><?php else : ?>La connexion est réservée aux employés COOMS.<?php endif; ?></footer></body></html>
