<?php
namespace Cooms\Bons;
defined('ABSPATH') || exit;

final class Plugin {
    public function boot(): void {
        add_filter('plugin_row_meta', [$this, 'metadata'], 10, 2);
        add_filter('woocommerce_prevent_admin_access', [$this, 'allow_seller_pages']);
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('admin_post_cooms_create', [$this, 'create']);
        add_action('admin_post_cooms_description', [$this, 'description']);
        add_action('admin_post_cooms_export', [$this, 'export']);
        add_action('template_redirect', [$this, 'scan'], -100);
    }

    public function metadata(array $links, string $file): array {
        if ($file !== plugin_basename(COOMS_BONS_FILE)) { return $links; }
        $repository = 'https://github.com/vuckro/cooms-bons';
        $links = array_values(array_filter($links, static fn($link) => !str_contains($link, $repository)));
        $links[] = '<a href="' . esc_url($repository) . '">GitHub</a>';
        return $links;
    }

    /** WooCommerce normally redirects accounts without edit_posts to My Account. */
    public function allow_seller_pages(bool $prevent): bool {
        $page = isset($_GET['page']) && is_string($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if (current_user_can(Store::REDEEM) && in_array($page, ['cooms-bons', 'cooms-bons-scan', 'cooms-bons-help'], true)) {
            return false;
        }
        return $prevent;
    }

    public function menu(): void {
        add_menu_page('COOMS Bons', 'COOMS Bons', Store::REDEEM, 'cooms-bons', [$this, 'admin'], 'dashicons-tickets-alt', 57);
        if (current_user_can(Store::MANAGE)) {
            add_submenu_page('cooms-bons', 'Lots et bons', 'Lots et bons', Store::MANAGE, 'cooms-bons', [$this, 'admin']);
            add_submenu_page('cooms-bons', 'Nouveau lot', 'Nouveau lot', Store::MANAGE, 'cooms-bons-new', [$this, 'admin']);
        }
        $scan_hook = add_submenu_page('cooms-bons', 'Scanner un bon', 'Scanner un bon', Store::REDEEM, 'cooms-bons-scan', '__return_null');
        add_action('load-' . $scan_hook, static function () { wp_safe_redirect(Scanner::url()); exit; });
        add_action('load-toplevel_page_cooms-bons', static function () {
            if (!current_user_can(Store::MANAGE)) { wp_safe_redirect(Scanner::url()); exit; }
        });
        add_submenu_page('cooms-bons', 'Aide', 'Aide', Store::REDEEM, 'cooms-bons-help', [$this, 'admin']);
    }

    public function assets(): void {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if (!in_array($page, ['cooms-bons', 'cooms-bons-new', 'cooms-bons-help', 'cooms-bons-staff'], true)) { return; }
        wp_enqueue_style('cooms-bons', plugins_url('assets/cooms.css', COOMS_BONS_FILE), [], COOMS_BONS_VERSION);
        // Optional host bridge: never make the standalone plugin depend on AdminKit.
        $design_deps = ['cooms-bons'];
        foreach (['adminkit-tokens', 'adminkit-component-tokens'] as $handle) {
            if (wp_style_is($handle, 'enqueued')) { $design_deps[] = $handle; }
        }
        wp_enqueue_style('cooms-bons-adminkit', plugins_url('assets/adminkit.css', COOMS_BONS_FILE), $design_deps, COOMS_BONS_VERSION);
        wp_enqueue_script('cooms-bons', plugins_url('assets/cooms.js', COOMS_BONS_FILE), [], COOMS_BONS_VERSION, true);
    }

    public function admin(): void {
        if (!current_user_can(Store::REDEEM)) { wp_die('Accès réservé à l’équipe COOMS.', '', ['response' => 403]); }
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if (!current_user_can(Store::MANAGE) || $page === 'cooms-bons-help') {
            require dirname(COOMS_BONS_FILE) . '/templates/help.php';
            return;
        }
        if ($page === 'cooms-bons-new') {
            $error = '';
            $name = ''; $quantity = 25; $gift = Store::TITLE; $description = '';
            $valid_until = (new \DateTimeImmutable(Store::expiry(new \DateTimeImmutable('now')), new \DateTimeZone('UTC')))->modify('-1 second')->setTimezone(new \DateTimeZone('Europe/Zurich'))->format('Y-m-d');
            $key = bin2hex(random_bytes(32));
            $flash = isset($_GET['form_error']) && is_string($_GET['form_error']) ? sanitize_key(wp_unslash($_GET['form_error'])) : '';
            if (preg_match('/^[a-f0-9]{32}$/D', $flash)) {
                $flash_key = 'cooms_form_' . get_current_user_id() . '_' . $flash;
                $saved = get_transient($flash_key);
                delete_transient($flash_key);
                if (is_array($saved)) {
                    $error = $saved['error']; $name = $saved['name']; $quantity = $saved['quantity'];
                    $gift = $saved['gift']; $valid_until = $saved['valid_until']; $key = $saved['key'];
                    $description = $saved['description'] ?? '';
                }
            }
            require dirname(COOMS_BONS_FILE) . '/templates/new.php';
            return;
        }
        $lot_id = isset($_GET['lot']) ? absint($_GET['lot']) : 0;
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
        if (!in_array($status, ['available', 'used', 'expired'], true)) { $status = ''; }
        $page_num = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
        $counts = Store::counts($lot_id);
        $total = $status ? $counts[$status] : $counts['total'];
        $page_num = min($page_num, max(1, (int) ceil($total / 30)));
        $view = isset($_GET['view']) && $_GET['view'] === 'bons' ? 'bons' : 'lots';
        if ($lot_id || $status) { $view = 'bons'; }
        $rows = Store::bons($lot_id, $status, 30, ($page_num - 1) * 30);
        $lots = Store::lots();
        $lot = $lot_id ? Store::lot($lot_id) : null;
        if ($lot_id && !$lot) { wp_die('Lot introuvable.', '', ['response' => 404]); }
        $lot_page = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
        $lot_page = min($lot_page, max(1, (int) ceil(count($lots) / 30)));
        $summaries = $view === 'lots' ? Store::lot_summaries(30, ($lot_page - 1) * 30) : [];
        require dirname(COOMS_BONS_FILE) . '/templates/admin.php';
    }

    public function create(): void {
        if (!current_user_can(Store::MANAGE)) { wp_die('Vous ne pouvez pas créer de lots.', '', ['response' => 403]); }
        check_admin_referer('cooms_create');
        $name = isset($_POST['name']) && is_string($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $quantity = isset($_POST['quantity']) && is_scalar($_POST['quantity']) ? filter_var(wp_unslash($_POST['quantity']), FILTER_VALIDATE_INT) : 0;
        $key = isset($_POST['request_key']) && is_string($_POST['request_key']) ? sanitize_text_field(wp_unslash($_POST['request_key'])) : '';
        $gift = isset($_POST['gift']) && is_string($_POST['gift']) ? sanitize_text_field(wp_unslash($_POST['gift'])) : '';
        $valid_until = isset($_POST['valid_until']) && is_string($_POST['valid_until']) ? wp_unslash($_POST['valid_until']) : '';
        $description = isset($_POST['description']) && is_string($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
        $id = Store::create_lot($name, (int) $quantity, $key, $gift, $valid_until, $description);
        if (is_wp_error($id)) {
            $error = $id->get_error_message();
            if (!preg_match('/^[a-f0-9]{64}$/D', $key)) { $key = bin2hex(random_bytes(32)); }
            $flash = bin2hex(random_bytes(16));
            set_transient('cooms_form_' . get_current_user_id() . '_' . $flash, [
                'error' => $error, 'name' => mb_substr($name, 0, 160), 'quantity' => (int) $quantity,
                'gift' => mb_substr($gift, 0, 190), 'valid_until' => substr($valid_until, 0, 10), 'key' => $key,
                'description' => mb_substr($description, 0, 2000),
            ], 10 * MINUTE_IN_SECONDS);
            wp_safe_redirect(add_query_arg(['page' => 'cooms-bons-new', 'form_error' => $flash], admin_url('admin.php')), 303);
            exit;
        }
        wp_safe_redirect(add_query_arg(['page' => 'cooms-bons', 'lot' => $id, 'created' => 1], admin_url('admin.php')));
        exit;
    }

    public static function export_url(string $format, int $lot = 0, string $status = ''): string {
        return wp_nonce_url(add_query_arg(['action' => 'cooms_export', 'format' => $format, 'lot' => $lot, 'status' => $status], admin_url('admin-post.php')), 'cooms_export');
    }

    public function description(): void {
        if (!current_user_can(Store::MANAGE)) { wp_die('Accès réservé au responsable.', '', ['response' => 403]); }
        $id = isset($_POST['lot']) && is_scalar($_POST['lot']) ? absint($_POST['lot']) : 0;
        check_admin_referer('cooms_description_' . $id);
        if (!isset($_POST['description']) || !is_string($_POST['description'])) { wp_die('Description invalide.', '', ['response' => 400]); }
        $result = Store::update_description($id, wp_unslash($_POST['description']));
        if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message()), '', ['response' => $result->get_error_code() === 'missing' ? 404 : 400, 'back_link' => true]); }
        wp_safe_redirect(add_query_arg(['page' => 'cooms-bons', 'lot' => $id, 'description_saved' => 1], admin_url('admin.php')), 303);
        exit;
    }

    public function export(): void {
        if (!current_user_can(Store::MANAGE)) { wp_die('Vous ne pouvez pas exporter les bons.', '', ['response' => 403]); }
        check_admin_referer('cooms_export');
        $lot = isset($_GET['lot']) ? absint($_GET['lot']) : 0;
        $format = isset($_GET['format']) ? sanitize_key(wp_unslash($_GET['format'])) : '';
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
        if (!in_array($status, ['', 'used', 'available', 'expired'], true)) { wp_die('Filtre invalide.', '', ['response' => 400]); }
        if ($lot && !Store::lot($lot)) { wp_die('Lot introuvable.', '', ['response' => 404]); }
        if ($format === 'zip' && $lot) { Export::zip($lot); }
        if ($format === 'csv') { Export::csv($lot, $status); }
        wp_die('Format invalide.', '', ['response' => 400]);
    }

    public function scan(): void {
        if (!isset($_GET['cooms_bon'])) { return; }
        if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
        nocache_headers();
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        $token = is_string($_GET['cooms_bon']) ? wp_unslash($_GET['cooms_bon']) : '';
        $bon = null; $state = 'login'; $message = '';
        if (is_user_logged_in()) {
            if (!current_user_can(Store::REDEEM)) {
                $state = 'forbidden'; status_header(403);
            } else {
                $bon = Store::find($token);
                $state = $bon ? Store::status($bon) : 'missing';
                if (!$bon) { status_header(404); }
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $nonce = isset($_POST['_wpnonce']) && is_string($_POST['_wpnonce']) ? wp_unslash($_POST['_wpnonce']) : '';
                    if (!wp_verify_nonce($nonce, 'cooms_redeem_' . $token)) {
                        $message = 'Votre session de confirmation a expiré. Actualisez la page avant de recommencer.';
                        status_header(403);
                    } else {
                        $result = Store::redeem($token);
                        if ($result['result'] === 'error') {
                            $message = 'La remise n’a pas pu être confirmée. Actualisez le statut avant de remettre le cadeau.';
                            status_header(503);
                        } elseif ($result['result'] === 'success') {
                            // Post/Redirect/Get: refreshing never resubmits a withdrawal.
                            $notice = wp_create_nonce('cooms_receipt_' . $token . '_' . $result['bon']->redeemed_at);
                            wp_safe_redirect(add_query_arg(['cooms_bon' => $token, 'receipt' => $notice], home_url('/')), 303);
                            exit;
                        } else {
                            $bon = $result['bon'];
                            $state = $bon ? Store::status($bon) : 'missing';
                        }
                    }
                }
                $receipt = isset($_GET['receipt']) && is_string($_GET['receipt']) ? wp_unslash($_GET['receipt']) : '';
                if ($bon && $bon->redeemed_at && (int) $bon->redeemed_by === get_current_user_id() && wp_verify_nonce($receipt, 'cooms_receipt_' . $token . '_' . $bon->redeemed_at)) {
                    $state = 'success';
                }
            }
        }
        require dirname(COOMS_BONS_FILE) . '/templates/scan.php';
        exit;
    }
}
