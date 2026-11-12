<?php
namespace Cooms\Bons;
defined('ABSPATH') || exit;
$booking_module = self::booking_available();
?>
<div class="wrap cb-app">
<header class="cb-heading"><div><h1>Accès employés</h1><p>Comptes sécurisés pour les boutiques (ex. Cornavin, Terrassière), sans accès complet au site WordPress.</p></div><a class="cb-button cb-secondary" href="<?php echo esc_url(Scanner::url()); ?>">Ouvrir le scanner</a></header>
<?php if (is_array($feedback)) : ?><div class="<?php echo $feedback['error'] ? 'cb-error' : 'cb-notice'; ?>" role="<?php echo $feedback['error'] ? 'alert' : 'status'; ?>"><?php echo esc_html($feedback['message']); ?></div><?php endif; ?>
<section class="cb-work" aria-labelledby="cb-staff-list">
    <header class="cb-section-heading"><div><h2 id="cb-staff-list">L’équipe boutique</h2><p>Chaque compte est cloisonné au scanner boutique (et aux réservations d’ateliers si activées). Suspendez un accès à tout moment sans perdre l’historique des retraits.</p></div><a href="#cb-add-staff">Ajouter un employé</a></header>
    <?php if ($users) : ?>
    <div class="cb-table-scroll" role="region" aria-label="Comptes employés" tabindex="0"><table class="cb-table cb-staff-table"><thead><tr><th scope="col">Employé / Boutique</th><th scope="col">Périmètre d’accès</th><th scope="col">Actions</th></tr></thead><tbody>
    <?php foreach ($users as $employee) :
        $active = user_can($employee, Store::REDEEM);
        $editable = self::can_change($employee);
        $has_booking = $active && self::has_booking_access($employee);
    ?>
        <tr>
            <th scope="row"><?php echo esc_html($employee->display_name); ?><span class="cb-row-meta"><?php echo esc_html($employee->user_email); ?></span></th>
            <td>
                <span class="cb-status <?php echo $active ? 'cb-available' : 'cb-used'; ?>"><?php echo $active ? 'Actif' : 'Suspendu'; ?></span>
                <span class="cb-row-meta"><?php echo esc_html($active ? ($has_booking ? 'Scanner + Réservations · Sans accès au site' : 'Scanner uniquement · Sans accès au site') : 'Accès coupé · Historique conservé'); ?></span>
            </td>
            <td>
                <div class="cb-actions">
                    <?php if ($editable) : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-cb-submit><input type="hidden" name="action" value="cooms_staff"><input type="hidden" name="operation" value="<?php echo $active ? 'suspend' : 'enable'; ?>"><input type="hidden" name="staff_id" value="<?php echo esc_attr($employee->ID); ?>"><?php wp_nonce_field('cooms_staff'); ?><button type="submit" class="cb-button cb-secondary" data-busy="Mise à jour…" aria-label="<?php echo esc_attr(($active ? 'Suspendre l’accès de ' : 'Réactiver l’accès de ') . $employee->display_name); ?>"><?php echo $active ? 'Suspendre l’accès' : 'Réactiver'; ?></button></form>
                        <?php if ($active && $booking_module) : ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-cb-submit><input type="hidden" name="action" value="cooms_staff"><input type="hidden" name="operation" value="<?php echo $has_booking ? 'booking_disable' : 'booking_enable'; ?>"><input type="hidden" name="staff_id" value="<?php echo esc_attr($employee->ID); ?>"><?php wp_nonce_field('cooms_staff'); ?><button type="submit" class="cb-button cb-secondary" data-busy="Mise à jour…"><?php echo $has_booking ? 'Retirer les réservations' : 'Autoriser les réservations'; ?></button></form>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (current_user_can('edit_user', $employee->ID)) : ?><a class="cb-staff-profile" href="<?php echo esc_url(get_edit_user_link($employee->ID)); ?>">Compte WordPress<span class="screen-reader-text"> de <?php echo esc_html($employee->display_name); ?></span></a><?php endif; ?>
                </div>
                <?php if ($editable) : ?>
                    <details class="cb-staff-pass">
                        <summary>Définir un mot de passe</summary>
                        <form class="cb-staff-pass-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-cb-submit>
                            <input type="hidden" name="action" value="cooms_staff"><input type="hidden" name="operation" value="password"><input type="hidden" name="staff_id" value="<?php echo esc_attr($employee->ID); ?>"><?php wp_nonce_field('cooms_staff'); ?>
                            <label class="screen-reader-text" for="cb-pass-<?php echo esc_attr($employee->ID); ?>">Nouveau mot de passe pour <?php echo esc_html($employee->display_name); ?></label>
                            <div class="cb-staff-pass-row">
                                <input id="cb-pass-<?php echo esc_attr($employee->ID); ?>" type="password" name="new_password" minlength="8" maxlength="128" required autocomplete="new-password" placeholder="Nouveau mot de passe (8 car. min.)">
                                <button type="submit" class="cb-button cb-secondary" data-busy="Enregistrement…">Enregistrer</button>
                            </div>
                        </form>
                    </details>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?></tbody></table></div>
    <footer class="cb-pagination"><span><?php echo esc_html($total . ($total > 1 ? ' comptes employés' : ' compte employé')); ?></span><nav aria-label="Pages des employés"><?php echo wp_kses_post(paginate_links(['base' => add_query_arg('paged', '%#%', admin_url('admin.php?page=cooms-bons-staff')), 'format' => '', 'current' => $page, 'total' => max(1, (int) ceil($total / 30)), 'prev_text' => 'Précédent', 'next_text' => 'Suivant'])); ?></nav></footer>
    <?php else : ?><div class="cb-empty"><h3>Aucun accès employé pour le moment</h3><p>Ajoutez la première personne ou boutique ci-dessous. Le rôle vendeur sécurisé est attribué automatiquement.</p></div><?php endif; ?>
</section>
<div class="cb-create-layout cb-staff-create">
<section class="cb-work cb-form-panel" aria-labelledby="cb-add-staff"><h2 id="cb-add-staff">Ajouter un employé ou une boutique</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-cb-submit>
<input type="hidden" name="action" value="cooms_staff"><input type="hidden" name="operation" value="create"><?php wp_nonce_field('cooms_staff'); ?>
<div class="cb-field"><label for="cb-staff-name">Nom de l’employé ou de la boutique</label><input id="cb-staff-name" type="text" name="staff_name" maxlength="100" required autocomplete="name" placeholder="Boutique Cornavin" value="<?php echo esc_attr($feedback['name'] ?? ''); ?>"><p>Ce nom permet d’identifier la boutique ou la personne qui valide les retraits.</p></div>
<div class="cb-field"><label for="cb-staff-email">Adresse e-mail</label><input id="cb-staff-email" type="email" name="staff_email" maxlength="100" required autocomplete="email" value="<?php echo esc_attr($feedback['email'] ?? ''); ?>" aria-describedby="cb-staff-email-help"><p id="cb-staff-email-help">Utilisée comme identifiant sur le téléphone de la boutique. Un compte existant n’est jamais remplacé.</p></div>
<div class="cb-field"><label for="cb-staff-password">Mot de passe initial <span class="cb-cell-secondary">(facultatif)</span></label><input id="cb-staff-password" type="password" name="staff_password" minlength="8" maxlength="128" autocomplete="new-password" aria-describedby="cb-staff-password-help"><p id="cb-staff-password-help">8 caractères minimum pour connecter immédiatement l’appareil de la boutique sans consulter la boîte e-mail. Laissez vide pour envoyer un lien par e-mail.</p></div>
<?php if ($booking_module) : ?>
<div class="cb-field"><label class="cb-check" for="cb-booking-access"><input id="cb-booking-access" type="checkbox" name="booking_access" value="1" <?php checked((bool) ($feedback['booking'] ?? false)); ?>><span>Autoriser aussi l’accès aux réservations d’ateliers</span></label><p>Permet de consulter et d’ajouter une réservation depuis la boutique, sans accès aux paiements ni aux réglages du site.</p></div>
<?php endif; ?>
<div class="cb-field"><label class="cb-check" for="cb-invite"><input id="cb-invite" type="checkbox" name="send_invitation" value="1" <?php checked((bool) ($feedback['invite'] ?? true)); ?>><span>Envoyer l’e-mail de création du mot de passe</span></label><p>Utilisé uniquement si vous laissez le mot de passe initial vide ci-dessus.</p></div>
<button type="submit" class="cb-button" data-busy="Création…">Créer l’accès vendeur</button><p class="cb-submission-note" data-cb-feedback role="status">Le compte est strictement cloisonné : aucun accès aux pages, commandes, extensions ou réglages WordPress.</p>
</form></section>
<aside class="cb-guide" aria-labelledby="cb-staff-start"><h2 id="cb-staff-start">À installer sur le téléphone boutique</h2><p>Ouvrez ce lien sur l’iPhone, l’iPad ou le téléphone Android de la boutique et connectez le compte avec son adresse e-mail et son mot de passe.</p><div class="cb-field"><label for="cb-scanner-link">Lien du scanner boutique</label><input id="cb-scanner-link" type="text" readonly value="<?php echo esc_attr(Scanner::url()); ?>"><button type="button" class="cb-button cb-secondary" data-copy-scanner>Copier le lien</button><p role="status" data-copy-feedback>Sélectionnez le lien pour le copier, ou utilisez le bouton.</p></div><p>Ajoutez cette page aux favoris ou à l’écran d’accueil du téléphone pour la retrouver à chaque service.</p><h2>Sécurité et périmètre</h2><p>Le rôle <strong>Vendeur COOMS</strong> bloque l’accès au tableau de bord WordPress, aux commandes de la boutique en ligne et aux réglages. Vous pouvez modifier le mot de passe d’une boutique directement depuis le tableau ci-dessus sans donner les droits Administrateur.</p></aside>
</div></div>
