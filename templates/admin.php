<?php
namespace Cooms\Bons;
defined('ABSPATH') || exit;
$labels = ['available' => 'Disponibles', 'used' => 'Utilisés', 'expired' => 'Expirés'];
$singular = ['available' => 'Disponible', 'used' => 'Utilisé', 'expired' => 'Expiré'];
?>
<div class="wrap cb-app">
    <header class="cb-heading"><div><h1><?php echo esc_html($lot ? $lot->name : 'COOMS Bons'); ?></h1><p><?php echo $lot ? esc_html($lot->quantity . ' bons · ' . $lot->title . ' · Valables jusqu’au ' . Store::expiry_label($lot->expires_at)) : 'Préparez les lots de la Fondation et suivez les retraits en boutique.'; ?></p></div><a class="cb-button" href="<?php echo esc_url(admin_url('admin.php?page=cooms-bons-new')); ?>">Nouveau lot</a></header>
    <?php if ($lot && isset($_GET['created'])) : ?><div class="cb-notice" role="status"><strong>Le lot est prêt.</strong> Téléchargez les bons puis transmettez le ZIP à la Fondation. Aucun e-mail n’a été envoyé.</div><?php endif; ?>
    <?php if (!Export::ready()) : ?><div class="cb-error" role="alert">L’export PNG/ZIP nécessite GD, FreeType, ZIP et les dépendances du plugin. Demandez à l’administrateur de vérifier l’installation.</div><?php endif; ?>
    <dl class="cb-counts" aria-label="Récapitulatif <?php echo $lot ? 'du lot' : 'global'; ?>">
        <?php foreach (['total' => 'Bons générés'] + $labels as $key => $label) : ?><div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html(number_format_i18n($counts[$key])); ?></dd></div><?php endforeach; ?>
    </dl>
    <section class="cb-work" aria-labelledby="cb-list-title">
        <div class="cb-section-heading"><h2 id="cb-list-title"><?php echo $lot ? 'Bons de ce lot' : 'Tous les bons'; ?></h2><div class="cb-actions">
            <?php if ($lot) : ?><a class="cb-button cb-secondary" href="<?php echo esc_url(self::export_url('zip', $lot_id)); ?>">Télécharger les bons (ZIP)</a><?php endif; ?>
            <?php if ($total) : ?><a class="cb-button cb-secondary" href="<?php echo esc_url(self::export_url('csv', $lot_id, $status)); ?>">Exporter la sélection (CSV)</a><?php endif; ?>
        </div></div>
        <form method="get" class="cb-filters" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="cooms-bons">
            <div><label for="cb-lot">Lot</label><select id="cb-lot" name="lot"><option value="0">Tous les lots</option><?php foreach ($lots as $item) : ?><option value="<?php echo esc_attr($item->id); ?>" <?php selected($lot_id, $item->id); ?>><?php echo esc_html($item->name . ' — #' . $item->id); ?></option><?php endforeach; ?></select></div>
            <div><label for="cb-status">Statut</label><select id="cb-status" name="status"><option value="">Tous les statuts</option><?php foreach ($labels as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($status, $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></div>
            <button class="cb-button cb-secondary" type="submit">Filtrer</button>
            <?php if ($lot_id || $status) : ?><a href="<?php echo esc_url(admin_url('admin.php?page=cooms-bons')); ?>">Effacer les filtres</a><?php endif; ?>
        </form>
        <?php if ($rows) : ?>
        <div class="cb-table-scroll" role="region" aria-label="Liste des bons" tabindex="0"><table class="cb-table"><caption class="screen-reader-text">Bons cadeaux et retraits, heures de Genève</caption><thead><tr><th scope="col">Code</th><th scope="col">Lot</th><th scope="col">Création</th><th scope="col">Valable jusqu’au</th><th scope="col">Statut</th><th scope="col">Retrait · Genève</th></tr></thead><tbody>
        <?php foreach ($rows as $bon) : $state = Store::status($bon); ?>
        <tr><th scope="row"><?php echo esc_html($bon->code); ?></th><td><a href="<?php echo esc_url(add_query_arg(['page' => 'cooms-bons', 'lot' => $bon->lot_id], admin_url('admin.php'))); ?>"><?php echo esc_html($bon->lot_name); ?></a></td><td><?php echo esc_html(Store::format_date($bon->created_at)); ?></td><td><?php echo esc_html(Store::expiry_label($bon->expires_at)); ?></td><td><span class="cb-status cb-<?php echo esc_attr($state); ?>"><?php echo esc_html($singular[$state]); ?></span></td><td><?php echo $bon->redeemed_at ? esc_html(Store::format_date($bon->redeemed_at, true)) : '—'; ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
        <div class="cb-pagination"><span><?php echo esc_html($total . ' bon' . ($total > 1 ? 's' : '') . ' · Page ' . $page_num . ' sur ' . max(1, ceil($total / 30))); ?></span><nav aria-label="Pages des bons"><?php echo wp_kses_post(paginate_links(['base' => add_query_arg(['page' => 'cooms-bons', 'lot' => $lot_id, 'status' => $status, 'paged' => '%#%'], admin_url('admin.php')), 'total' => max(1, (int) ceil($total / 30)), 'current' => $page_num, 'prev_text' => 'Précédente', 'next_text' => 'Suivante'])); ?></nav></div>
        <?php else : ?>
        <div class="cb-empty"><h3><?php echo $counts['total'] ? 'Aucun bon pour ces filtres' : 'Les premiers bons commencent ici'; ?></h3><p><?php echo $counts['total'] ? 'Choisissez un autre statut ou affichez tous les lots.' : 'Créez un lot : chaque bénéficiaire recevra un bon anonyme, valable six mois et utilisable une seule fois.'; ?></p><?php if (!$lot_id && !$status) : ?><a class="cb-button" href="<?php echo esc_url(admin_url('admin.php?page=cooms-bons-new')); ?>">Créer le premier lot</a><?php endif; ?></div>
        <?php endif; ?>
    </section>
    <p class="cb-footnote">Un scan consulte le bon. Seule la confirmation du vendeur enregistre un retrait. <a href="<?php echo esc_url(admin_url('admin.php?page=cooms-bons-help')); ?>">Guide boutique</a></p>
</div>
