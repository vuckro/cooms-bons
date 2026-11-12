<?php
namespace Cooms\Bons;
defined('ABSPATH') || exit;

/** A small management surface over native WordPress accounts, never a second auth system. */
final class Staff {
    public const META = '_cooms_staff_account';
    public const BOOKING_BACKUP_META = '_cooms_staff_booking_perms';
    public const BOOKING_PERMS = [
        'manage_own_calendar',
        'read_all_bookings',
        'manage_all_bookings',
        'read_other_calendars',
        'manage_other_calendars',
    ];

    public function boot(): void {
        add_action('admin_init', [$this, 'guard_admin'], 1);
        add_action('admin_menu', [$this, 'menu'], 20);
        add_action('admin_menu', [$this, 'clean_admin_menu'], 999);
        add_action('admin_bar_menu', [$this, 'clean_admin_bar'], 999);
        add_action('in_admin_header', [$this, 'suppress_notices'], 1);
        add_action('admin_post_cooms_staff', [$this, 'submit']);
        add_filter('login_redirect', [$this, 'login_redirect'], 10, 3);
        add_filter('show_admin_bar', [$this, 'filter_admin_bar']);
        add_filter('wp_is_application_passwords_available_for_user', [$this, 'filter_app_passwords'], 10, 2);
    }

    public static function can_manage(): bool {
        return current_user_can(Store::MANAGE) && current_user_can('create_users') && current_user_can('promote_users') && current_user_can('list_users') && current_user_can('edit_users');
    }

    /** True only for boutique seller accounts with zero editorial or site-management capabilities. */
    public static function is_restricted_seller(?\WP_User $user = null): bool {
        $user = $user ?? wp_get_current_user();
        if (!$user instanceof \WP_User || !$user->ID) { return false; }
        return user_can($user, Store::REDEEM)
            && !user_can($user, Store::MANAGE)
            && !user_can($user, 'manage_options')
            && !user_can($user, 'edit_posts')
            && !user_can($user, 'promote_users');
    }

    public static function booking_available(): bool {
        return class_exists('\\FluentBooking\\App\\Services\\PermissionManager')
            && class_exists('\\FluentBooking\\App\\Models\\Meta');
    }

    public static function has_booking_access(?\WP_User $user = null): bool {
        $user = $user ?? wp_get_current_user();
        if (!$user instanceof \WP_User || !$user->ID || !user_can($user, Store::REDEEM) || !self::booking_available()) {
            return false;
        }
        $perms = \FluentBooking\App\Services\PermissionManager::getMetaPermissions($user->ID);
        return is_array($perms) && !empty($perms);
    }

    /** @return string[] */
    public static function allowed_admin_pages(?\WP_User $user = null): array {
        $pages = ['cooms-bons', 'cooms-bons-scan', 'cooms-bons-help'];
        if (self::has_booking_access($user)) {
            $pages[] = 'fluent-booking';
        }
        return $pages;
    }

    /** Block Dashboard, Profile, Tools and any non-boutique wp-admin screen for seller accounts. */
    public function guard_admin(): void {
        if (!self::is_restricted_seller() || wp_doing_ajax() || wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
            return;
        }
        global $pagenow;
        $script = is_string($pagenow ?? null) && $pagenow !== '' ? $pagenow : basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if (in_array($script, ['admin-ajax.php', 'admin-post.php'], true)) {
            return;
        }
        if ($script === 'admin.php') {
            $page = isset($_GET['page']) && is_string($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
            if (in_array($page, self::allowed_admin_pages(), true)) {
                return;
            }
        }
        wp_safe_redirect(self::has_booking_access() ? admin_url('admin.php?page=fluent-booking') : Scanner::url(), 302);
        exit;
    }

    public function clean_admin_menu(): void {
        if (!self::is_restricted_seller()) { return; }
        remove_menu_page('index.php');
        remove_menu_page('profile.php');
        remove_menu_page('tools.php');
    }

    public function clean_admin_bar(\WP_Admin_Bar $bar): void {
        if (!self::is_restricted_seller()) { return; }
        foreach (['wp-logo', 'about', 'wporg', 'documentation', 'support-forums', 'feedback', 'site-name', 'view-site', 'updates', 'comments', 'new-content', 'edit-profile'] as $id) {
            $bar->remove_node($id);
        }
        foreach (['my-account', 'user-info'] as $id) {
            $node = $bar->get_node($id);
            if ($node) {
                $bar->add_node(['id' => $node->id, 'href' => Scanner::url()]);
            }
        }
        $bar->add_node([
            'id' => 'cooms-scanner-shortcut',
            'title' => 'Scanner COOMS',
            'href' => Scanner::url(),
        ]);
    }

    public function suppress_notices(): void {
        if (!self::is_restricted_seller()) { return; }
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
    }

    public function filter_admin_bar(bool $show): bool {
        return self::is_restricted_seller() ? false : $show;
    }

    public function filter_app_passwords(bool $available, \WP_User $user): bool {
        return self::is_restricted_seller($user) ? false : $available;
    }

    public function menu(): void {
        if (self::can_manage()) {
            add_submenu_page('cooms-bons', 'Accès employés', 'Accès employés', 'promote_users', 'cooms-bons-staff', [$this, 'page']);
        }
    }

    /** @return string|\WP_Error */
    private static function validate_password(string $password) {
        $length = mb_strlen($password);
        if ($length < 8 || $length > 128) {
            return new \WP_Error('weak_password', 'Choisissez un mot de passe de 8 à 128 caractères pour l’accès boutique.');
        }
        return $password;
    }

    /** @return int|\WP_Error No email is sent by this domain operation. */
    public static function create(string $name, string $email, string $password = '') {
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
        if ($password !== '') {
            $valid = self::validate_password($password);
            if (is_wp_error($valid)) { return $valid; }
        }
        $id = wp_insert_user([
            'user_login' => 'cooms-' . bin2hex(random_bytes(8)),
            'user_email' => $email,
            'display_name' => $name,
            'nickname' => $name,
            'user_pass' => $password !== '' ? $password : wp_generate_password(40),
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

    /** Set or replace a boutique password without opening WordPress users.php; active sessions are revoked. */
    public static function set_password(int $id, string $password) {
        $user = get_userdata($id);
        if (!$user || !self::can_change($user)) {
            return new \WP_Error('forbidden', 'Ce compte ne peut pas être modifié depuis les accès employés.');
        }
        $valid = self::validate_password($password);
        if (is_wp_error($valid)) { return $valid; }
        update_user_meta($id, self::META, '1');
        wp_set_password($password, $id);
        \WP_Session_Tokens::get_instance($id)->destroy_all();
        return true;
    }

    /** Grant or revoke limited workshop booking permissions (never settings/payments/manage_all_data). */
    public static function set_booking_access(int $id, bool $enabled, ?array $custom_perms = null) {
        $user = get_userdata($id);
        if (!$user || !self::can_change($user)) {
            return new \WP_Error('forbidden', 'Ce compte ne peut pas être modifié depuis les accès employés.');
        }
        if (!self::booking_available()) {
            return new \WP_Error('booking_unavailable', 'Le module de réservations n’est pas actif sur ce site.');
        }
        update_user_meta($id, self::META, '1');
        $query = \FluentBooking\App\Models\Meta::where('object_type', 'user_meta')
            ->where('object_id', $id)
            ->where('key', '_access_permissions');
        if (!$enabled) {
            $query->delete();
            delete_user_meta($id, self::BOOKING_BACKUP_META);
            return true;
        }
        $allowed = array_values(array_intersect($custom_perms ?? self::BOOKING_PERMS, self::BOOKING_PERMS));
        if (empty($allowed)) { $allowed = self::BOOKING_PERMS; }
        $existing = $query->first();
        if ($existing) {
            $existing->value = $allowed;
            $existing->save();
        } else {
            \FluentBooking\App\Models\Meta::create([
                'object_type' => 'user_meta',
                'object_id' => $id,
                'key' => '_access_permissions',
                'value' => $allowed,
            ]);
        }
        return true;
    }

    /** Preserve account/history; revoke scanner + booking capabilities and all existing sessions immediately. */
    public static function set_access(int $id, bool $enabled) {
        $user = get_userdata($id);
        if (!$user || !self::can_change($user)) { return new \WP_Error('forbidden', 'Ce compte ne peut pas être modifié depuis les accès employés.'); }
        update_user_meta($id, self::META, '1');
        if (self::booking_available()) {
            if (!$enabled) {
                $current = \FluentBooking\App\Services\PermissionManager::getMetaPermissions($id);
                if (is_array($current) && !empty($current)) {
                    update_user_meta($id, self::BOOKING_BACKUP_META, array_values(array_intersect($current, self::BOOKING_PERMS)));
                }
                \FluentBooking\App\Models\Meta::where('object_type', 'user_meta')
                    ->where('object_id', $id)
                    ->where('key', '_access_permissions')
                    ->delete();
            } else {
                $backup = get_user_meta($id, self::BOOKING_BACKUP_META, true);
                if (is_array($backup) && !empty($backup)) {
                    self::set_booking_access($id, true, $backup);
                    delete_user_meta($id, self::BOOKING_BACKUP_META);
                }
            }
        }
        $user->remove_cap(Store::REDEEM);
        $user->set_role($enabled ? 'cooms_seller' : 'subscriber');
        \WP_Session_Tokens::get_instance($id)->destroy_all();
        return true;
    }

    public function login_redirect(string $redirect, string $requested, $user): string {
        if ($user instanceof \WP_User && self::is_restricted_seller($user)) {
            if ($requested === '') {
                return Scanner::url();
            }
            if (untrailingslashit($requested) === untrailingslashit(admin_url())) {
                return self::has_booking_access($user) ? admin_url('admin.php?page=fluent-booking') : Scanner::url();
            }
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
            $password = isset($_POST['staff_password']) && is_string($_POST['staff_password']) ? wp_unslash($_POST['staff_password']) : '';
            $result = self::create($name, $email, $password);
            if (!is_wp_error($result)) {
                $message = $password !== ''
                    ? 'Accès boutique créé avec son mot de passe. Vous pouvez connecter l’appareil avec son adresse e-mail.'
                    : 'Accès vendeur créé. L’employé peut se connecter avec son adresse e-mail.';
                if (self::booking_available() && isset($_POST['booking_access']) && $_POST['booking_access'] === '1') {
                    self::set_booking_access($result, true);
                    $message .= ' L’accès aux réservations est activé.';
                }
                if ($password === '' && isset($_POST['send_invitation']) && $_POST['send_invitation'] === '1') {
                    wp_new_user_notification($result, null, 'user');
                    $message .= ' La demande d’e-mail de création du mot de passe a été transmise à WordPress.';
                }
            }
        } elseif (in_array($operation, ['suspend', 'enable'], true)) {
            $id = isset($_POST['staff_id']) && is_scalar($_POST['staff_id']) ? absint($_POST['staff_id']) : 0;
            $result = self::set_access($id, $operation === 'enable');
            $message = $operation === 'enable' ? 'Accès réactivé. L’employé doit se reconnecter.' : 'Accès au scanner suspendu et sessions déconnectées. L’historique est conservé.';
        } elseif ($operation === 'password') {
            $id = isset($_POST['staff_id']) && is_scalar($_POST['staff_id']) ? absint($_POST['staff_id']) : 0;
            $new_password = isset($_POST['new_password']) && is_string($_POST['new_password']) ? wp_unslash($_POST['new_password']) : '';
            $result = self::set_password($id, $new_password);
            $message = 'Nouveau mot de passe enregistré. Reconnectez le téléphone de la boutique avec ce mot de passe.';
        } elseif (in_array($operation, ['booking_enable', 'booking_disable'], true)) {
            $id = isset($_POST['staff_id']) && is_scalar($_POST['staff_id']) ? absint($_POST['staff_id']) : 0;
            $result = self::set_booking_access($id, $operation === 'booking_enable');
            $message = $operation === 'booking_enable'
                ? 'Accès limité aux réservations activé pour ce compte boutique.'
                : 'Accès aux réservations retiré ; le compte conserve uniquement le scanner.';
        } else {
            $result = new \WP_Error('invalid_action', 'Action inconnue.');
        }
        $preserve_create = $operation === 'create' && is_wp_error($result);
        $key = bin2hex(random_bytes(16));
        set_transient('cooms_staff_' . get_current_user_id() . '_' . $key, [
            'error' => is_wp_error($result),
            'message' => is_wp_error($result) ? $result->get_error_message() : $message,
            'name' => $preserve_create ? mb_substr($name, 0, 100) : '',
            'email' => $preserve_create ? substr($email, 0, 100) : '',
            'invite' => $preserve_create ? (isset($_POST['send_invitation']) && $_POST['send_invitation'] === '1') : true,
            'booking' => $preserve_create && isset($_POST['booking_access']) && $_POST['booking_access'] === '1',
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
