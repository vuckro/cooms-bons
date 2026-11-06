<?php
namespace Cooms\Bons;
defined('ABSPATH') || exit;
$expiry = Store::expiry(new \DateTimeImmutable('now'));
?>
<div class="wrap cb-app">
    <header class="cb-heading"><div><h1>Créer un lot</h1><p>Préparez une distribution. Chaque bon sera unique et valable six mois.</p></div><a href="<?php echo esc_url(admin_url('admin.php?page=cooms-bons')); ?>">Retour aux bons</a></header>
    <div class="cb-create-layout"><section class="cb-work cb-form-panel" aria-labelledby="cb-form-title"><h2 id="cb-form-title">Préparer le lot</h2>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-cb-submit>
        <input type="hidden" name="action" value="cooms_create"><input type="hidden" name="request_key" value="<?php echo esc_attr($key); ?>"><?php wp_nonce_field('cooms_create'); ?>
        <div class="cb-field"><label for="cb-name">Nom du lot</label><input id="cb-name" name="name" type="text" required maxlength="160" placeholder="Fondation — Semaine 46" autocomplete="off" aria-describedby="cb-name-help"><p id="cb-name-help">Un nom reconnaissable pour retrouver les retraits et les exports.</p></div>
        <div class="cb-field"><label for="cb-quantity">Nombre de bons</label><input id="cb-quantity" name="quantity" type="number" min="1" max="<?php echo esc_attr(Store::MAX_BATCH); ?>" step="1" value="25" required inputmode="numeric" aria-describedby="cb-quantity-help"><p id="cb-quantity-help">De 1 à <?php echo esc_html(Store::MAX_BATCH); ?> bons par lot. Un bon correspond à une boîte.</p></div>
        <dl class="cb-summary"><div><dt>Cadeau</dt><dd><?php echo esc_html(Store::TITLE); ?></dd></div><div><dt>Validité</dt><dd>Jusqu’au <?php echo esc_html(Store::expiry_label($expiry)); ?> inclus<br><span>Six mois calendaires · heure de Genève</span></dd></div></dl>
        <button class="cb-button" type="submit" data-busy="Création du lot…">Créer le lot</button><p class="cb-submission-note" role="status" data-cb-feedback>Vous téléchargerez les bons à l’étape suivante. Aucun envoi automatique.</p>
    </form></section><aside class="cb-guide" aria-labelledby="cb-next-title"><h2 id="cb-next-title">Dans chaque bon</h2><div class="cb-preview"><strong>Une boîte de 4 cookies</strong><span>20 CHF · Un seul retrait en boutique</span><span>Valable jusqu’au <?php echo esc_html(Store::expiry_label($expiry)); ?> inclus</span></div><h2>Après la création</h2><ol><li><strong>Télécharger le ZIP</strong><p>Une image par bon, avec son QR code et sa date de validité.</p></li><li><strong>Transmettre à la Fondation</strong><p>Elle distribue les images aux bénéficiaires. Aucune identité n’est collectée ici.</p></li></ol><p>Le même lot peut être téléchargé à nouveau, sans créer de nouveaux bons.</p></aside></div>
</div>
