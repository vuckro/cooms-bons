<?php
namespace Cooms\Bons;
defined('ABSPATH') || exit;

/** Seller-only entry point. A lookup never consumes a voucher. */
final class Scanner {
    public static function url(): string {
        return add_query_arg('cooms_scan', '1', home_url('/'));
    }

    public static function resolve(string $value): ?object {
        if (!current_user_can(Store::REDEEM) || strlen($value) > 1024) { return null; }
        $value = trim($value);
        if (preg_match('/^COOMS[\s-]*([A-F0-9]{10})$/iD', $value, $match)) {
            return Store::find_code('COOMS-' . strtoupper($match[1]));
        }
        $url = wp_parse_url($value);
        $home = wp_parse_url(home_url('/'));
        if (!is_array($url) || isset($url['user']) || isset($url['pass']) || isset($url['fragment'])) { return null; }
        foreach (['scheme', 'host', 'port'] as $part) {
            if (strtolower((string) ($url[$part] ?? '')) !== strtolower((string) ($home[$part] ?? ''))) { return null; }
        }
        if (rtrim($url['path'] ?? '', '/') !== rtrim($home['path'] ?? '', '/')) { return null; }
        parse_str($url['query'] ?? '', $query);
        return isset($query['cooms_bon']) && is_string($query['cooms_bon']) ? Store::find($query['cooms_bon']) : null;
    }

    public function route(): void {
        if (!isset($_GET['cooms_scan'])) { return; }
        if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
        nocache_headers();
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Permissions-Policy: camera=(self), microphone=()');
        $access = is_user_logged_in() ? (current_user_can(Store::REDEEM) ? 'allowed' : 'forbidden') : 'login';
        $error = '';
        $reference = '';

        if ($access === 'forbidden') { status_header(403); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($access !== 'allowed') { status_header(403); }
            else {
                $nonce = isset($_POST['_wpnonce']) && is_string($_POST['_wpnonce']) ? wp_unslash($_POST['_wpnonce']) : '';
                if (!wp_verify_nonce($nonce, 'cooms_lookup')) {
                    $error = 'Votre session a expiré. Actualisez cette page avant de rechercher le bon.';
                    status_header(403);
                } else {
                    $value = isset($_POST['code']) && is_string($_POST['code']) ? wp_unslash($_POST['code']) : '';
                    if (strlen($value) <= 32 && preg_match('/^COOMS[\s-]*[A-F0-9]{10}$/iD', trim($value))) { $reference = trim($value); }
                    $bon = self::resolve($value);
                    if ($bon) { wp_safe_redirect(Store::url($bon), 303); exit; }
                    $error = 'Bon introuvable. Vérifiez la référence imprimée ou scannez un QR code COOMS.';
                    status_header(404);
                }
            }
        }
        require dirname(COOMS_BONS_FILE) . '/templates/scanner.php';
        exit;
    }
}
