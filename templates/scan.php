<?php
namespace Cooms\Bons;
defined('ABSPATH') || exit;
$states = [
    'available' => ['Bon valide', 'Cette boîte peut être remise après votre confirmation.'],
    'used' => ['Bon déjà utilisé', 'Une remise a déjà été enregistrée pour ce bon.'],
    'success' => ['Remise enregistrée', 'Vous pouvez remettre la boîte de 4 cookies.'],
    'expired' => ['Bon expiré', 'La date limite est dépassée. Ce bon ne peut plus être utilisé.'],
    'missing' => ['Bon introuvable', 'Vérifiez le QR code présenté ou contactez le responsable COOMS.'],
    'login' => ['Accès équipe COOMS', 'Connectez-vous à votre compte vendeur pour vérifier le bon.'],
    'forbidden' => ['Accès non autorisé', 'Votre compte ne permet pas de valider les bons. Demandez un accès vendeur au responsable COOMS.'],
];
[$title, $description] = $states[$state];
?><!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive"><meta name="referrer" content="no-referrer"><title><?php echo esc_html($title); ?> · COOMS</title><link rel="stylesheet" href="<?php echo esc_url(plugins_url('assets/cooms.css', COOMS_BONS_FILE)); ?>?ver=<?php echo esc_attr(COOMS_BONS_VERSION); ?>"><script defer src="<?php echo esc_url(plugins_url('assets/cooms.js', COOMS_BONS_FILE)); ?>?ver=<?php echo esc_attr(COOMS_BONS_VERSION); ?>"></script></head>
<body class="cb-scan"><header class="cb-brand"><a href="<?php echo esc_url(Scanner::url()); ?>">COOMS</a><span>Retrait boutique</span></header><main class="cb-ticket cb-state-<?php echo esc_attr($state); ?>" id="main">
    <div class="cb-verdict"><svg aria-hidden="true" viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><?php if (in_array($state, ['available', 'success'], true)) : ?><path d="m7 12 3 3 7-7"/><?php else : ?><path d="M12 7v6m0 3v1"/><?php endif; ?></svg><h1><?php echo esc_html($title); ?></h1><p><?php echo esc_html($description); ?></p></div>
    <?php if ($message) : ?><div class="cb-error" role="alert"><?php echo esc_html($message); ?></div><?php endif; ?>
    <?php if ($bon) : ?>
        <?php if ($bon->redeemed_at) : ?><div class="cb-receipt"><strong>Retrait du <?php echo esc_html(Store::format_date($bon->redeemed_at, true)); ?></strong><span>Heure de Genève</span></div><?php endif; ?>
        <section class="cb-gift" aria-label="Détails du bon"><h2>Une boîte de 4 cookies</h2><p>Valeur 20 CHF · Retrait en boutique</p><dl class="cb-details"><div><dt>Référence</dt><dd><?php echo esc_html($bon->code); ?></dd></div><div><dt>Lot</dt><dd><?php echo esc_html($bon->lot_name); ?></dd></div><div><dt>Valable jusqu’au</dt><dd><?php echo esc_html(Store::expiry_label($bon->expires_at)); ?> inclus</dd></div></dl></section>
    <?php endif; ?>
    <div class="cb-ticket-actions">
    <?php if ($state === 'available' && !$message) : ?><form method="post" action="<?php echo esc_url(add_query_arg('cooms_bon', $token, home_url('/'))); ?>" data-cb-submit><?php wp_nonce_field('cooms_redeem_' . $token); ?><button class="cb-button cb-redeem" type="submit" data-busy="Enregistrement en cours…">Valider la remise de la boîte</button><p class="cb-submission-note" role="status" data-cb-feedback>Cette action enregistre le retrait définitif du bon.</p></form>
    <?php elseif ($state === 'login') : ?><a class="cb-button" href="<?php echo esc_url(wp_login_url(add_query_arg('cooms_bon', preg_match('/^[a-f0-9]{64}$/D', $token) ? $token : 'invalid', home_url('/')))); ?>">Se connecter pour vérifier</a><p class="cb-submission-note">Vous avez reçu ce bon ? Présentez-le en boutique. La connexion est réservée à l’équipe.</p>
    <?php elseif ($message) : ?><a class="cb-button cb-secondary" href="<?php echo esc_url(add_query_arg('cooms_bon', $token, home_url('/'))); ?>">Actualiser le statut</a>
    <?php endif; ?>
    <?php if (current_user_can(Store::REDEEM)) : ?><a class="cb-next-scan <?php echo $state === 'available' ? '' : 'cb-button cb-secondary'; ?>" href="<?php echo esc_url(Scanner::url()); ?>"><?php echo $state === 'available' ? 'Retour au scanner' : 'Scanner le bon suivant'; ?></a><?php endif; ?>
    </div>
</main><footer class="cb-scan-footer">COOMS · Un bon, une boîte, un seul retrait.<br><?php if (is_user_logged_in()) : ?>Connecté en tant que <?php echo esc_html(wp_get_current_user()->display_name); ?> · <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Se déconnecter</a><?php endif; ?></footer></body></html>
