<?php
namespace Cooms\Bons;
defined('ABSPATH') || exit;

/** A small management surface over native WordPress accounts, never a second auth system. */
final class Staff {
    public const META = '_cooms_staff_account';

    public function boot(): void {
        add_action('admin_menu', [$this, 'menu'], 20);
        add_action('admin_post_cooms_staff', [$this, 'submit']);
        add_filter('login_redirect', [$this, 'login_redirect'], 10, 3);
    }

    public static function can_manage(): bool {
        return current_user_can(Store::MANAGE) && current_user_can('create_users') && current_user_can('promote_users') && current_user_can('list_users') && current_user_can('edit_users');
    }

    public function menu(): void {
        if (self::can_manage()) {
            add_submenu_page('cooms-bons', 'Accès employés', 'Accès employés', 'promote_users', 'cooms-bons-staff', [$this, 'page']);
        }
    }

    /** @return int|\WP_Error No email is sent by this domain operation. */
    public static function create(string $name, string $email) {
        if (!self::can_manage()) { return new \WP_Error('forbidden', 'Vous ne pouvez pas gérer les accès employés.'); }
        if (!get_role('cooms_seller')) { return new \WP_Error('missing_role', 'Le rôle vendeur manque. Réactivez COOMS Bons avant de créer un accès.'); }
        $name = sanitize_text_field($name);
        $email = trim($email);
        if ($name === '' || mb_strlen($name) > 100 || !is_email($email) || strlen($email) > 100) {
            return new \WP_Error('invalid_staff', 'Indiquez un nom de 100 caractères maximum et une adresse e-mail valide.');
        }
        if (email_exists($email)) {
            return new \WP_Error('existing_staff', 'Un compte utilise déjà cet e-mail. Modifiez son rôle depuis Comptes WordPress ; aucun accès existant n’a été changé.');
        }
        $id = wp_insert_user([
            'user_login' => 'cooms-' . bin2hex(random_bytes(8)),
            'user_email' => $email,
            'display_name' => $name,
            'nickname' => $name,
            'user_pass' => wp_generate_password(40),
            'role' => 'cooms_seller',
        ]);
        if (!is_wp_error($id)) { update_user_meta($id, self::META, '1'); }
        return $id;
    }

    public static function can_change(\WP_User $user): bool {
        $roles = array_values($user->roles);
        return self::can_manage() && $user->ID !== get_current_user_id() && current_user_can('edit_user', $user->ID)
            && !user_can($user, Store::MANAGE) && !user_can($user, 'manage_options') && !user_can($user, 'promote_users')
            && (in_array($roles, [['cooms_seller'], ['subscriber']], true))
            && (in_array('cooms_seller', $roles, true) || get_user_meta($user->ID, self::META, true) === '1');
    }

    /** Preserve account/history; revoke scanner capability and all existing sessions immediately. */
    public static function set_access(int $id, bool $enabled) {
        $user = get_userdata($id);
        if (!$user || !self::can_change($user)) { return new \WP_Error('forbidden', 'Ce compte ne peut pas être modifié depuis les accès employés.'); }
        update_user_meta($id, self::META, '1');
        $user->remove_cap(Store::REDEEM);
        $user->set_role($enabled ? 'cooms_seller' : 'subscriber');
        \WP_Session_Tokens::get_instance($id)->destroy_all();
        return true;
    }

    public function login_redirect(string $redirect, string $requested, $user): string {
        if ($user instanceof \WP_User && user_can($user, Store::REDEEM) && !user_can($user, Store::MANAGE)
            && ($requested === '' || untrailingslashit($requested) === untrailingslashit(admin_url()))) {
            return Scanner::url();
        }
        return $redirect;
    }

    public function submit(): void {
        if (!self::can_manage()) { wp_die('Accès réservé aux responsables autorisés à gérer les comptes.', '', ['response' => 403]); }
        check_admin_referer('cooms_staff');
        $operation = isset($_POST['operation']) && is_string($_POST['operation']) ? sanitize_key(wp_unslash($_POST['operation'])) : '';
        $message = '';
        $name = isset($_POST['staff_name']) && is_string($_POST['staff_name']) ? sanitize_text_field(wp_unslash($_POST['staff_name'])) : '';
        $email = isset($_POST['staff_email']) && is_string($_POST['staff_email']) ? trim(wp_unslash($_POST['staff_email'])) : '';
        if ($operation === 'create') {
            $result = self::create($name, $email);
            if (!is_wp_error($result)) {
                $message = 'Accès vendeur créé. L’employé peut se connecter avec son adresse e-mail.';
                if (isset($_POST['send_invitation']) && $_POST['send_invitation'] === '1') {
                    wp_new_user_notification($result, null, 'user');
                    $message .= ' La demande d’e-mail de création du mot de passe a été transmise à WordPress.';
                }
            }
        } elseif (in_array($operation, ['suspend', 'enable'], true)) {
            $id = isset($_POST['staff_id']) && is_scalar($_POST['staff_id']) ? absint($_POST['staff_id']) : 0;
            $result = self::set_access($id, $operation === 'enable');
            $message = $operation === 'enable' ? 'Accès réactivé. L’employé doit se reconnecter.' : 'Accès au scanner suspendu et sessions déconnectées. L’historique est conservé.';
        } else { $result = new \WP_Error('invalid_action', 'Action inconnue.'); }
        $key = bin2hex(random_bytes(16));
        set_transient('cooms_staff_' . get_current_user_id() . '_' . $key, [
            'error' => is_wp_error($result), 'message' => is_wp_error($result) ? $result->get_error_message() : $message,
            'name' => is_wp_error($result) ? mb_substr($name, 0, 100) : '', 'email' => is_wp_error($result) ? substr($email, 0, 100) : '',
            'invite' => isset($_POST['send_invitation']) && $_POST['send_invitation'] === '1',
        ], 600);
        wp_safe_redirect(add_query_arg(['page' => 'cooms-bons-staff', 'feedback' => $key], admin_url('admin.php')), 303);
        exit;
    }

    public function page(): void {
        if (!self::can_manage()) { wp_die('Accès réservé aux responsables autorisés à gérer les comptes.', '', ['response' => 403]); }
        $feedback = null;
        $key = isset($_GET['feedback']) && is_string($_GET['feedback']) ? sanitize_key(wp_unslash($_GET['feedback'])) : '';
        if (preg_match('/^[a-f0-9]{32}$/D', $key)) {
            $transient = 'cooms_staff_' . get_current_user_id() . '_' . $key;
            $feedback = get_transient($transient);
            delete_transient($transient);
        }
        $ids = array_unique(array_merge(
            get_users(['role' => 'cooms_seller', 'fields' => 'ID']),
            get_users(['meta_key' => self::META, 'meta_value' => '1', 'fields' => 'ID'])
        ));
        $total = count($ids);
        $page = max(1, min(isset($_GET['paged']) ? absint($_GET['paged']) : 1, max(1, (int) ceil($total / 30))));
        $users = $ids ? get_users(['include' => $ids, 'number' => 30, 'offset' => ($page - 1) * 30, 'orderby' => 'display_name', 'order' => 'ASC']) : [];
        require dirname(COOMS_BONS_FILE) . '/templates/staff.php';
    }
}
