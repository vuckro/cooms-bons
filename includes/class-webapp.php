<?php
namespace Cooms\Bons;
defined('ABSPATH') || exit;

/** Installable, connected boutique shell. No service worker owns/caches voucher URLs. */
final class Webapp {
    public static function manifest_url(): string {
        return add_query_arg('cooms_manifest', '1', home_url('/'));
    }

    public static function manifest(): array {
        $icons = plugins_url('assets/app/', COOMS_BONS_FILE);
        return [
            'id' => Scanner::url(),
            'name' => 'COOMS Boutique',
            'short_name' => 'COOMS',
            'description' => 'Scanner les bons et confirmer les retraits en boutique COOMS.',
            'lang' => 'fr',
            'start_url' => Scanner::url(),
            'scope' => home_url('/'),
            'display' => 'standalone',
            'background_color' => '#fff8f2',
            'theme_color' => '#fff8f2',
            'icons' => [
                ['src' => add_query_arg('ver', COOMS_BONS_VERSION, $icons . 'icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => add_query_arg('ver', COOMS_BONS_VERSION, $icons . 'icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => add_query_arg('ver', COOMS_BONS_VERSION, $icons . 'icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ];
    }

    public static function route(): void {
        if (!isset($_GET['cooms_manifest'])) { return; }
        if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
        nocache_headers();
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Robots-Tag: noindex, nofollow');
        echo wp_json_encode(self::manifest(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function head(): void {
        require dirname(COOMS_BONS_FILE) . '/templates/app-head.php';
    }

    public static function connection_notice(): void {
        ?>
        <div class="cb-connection" role="status" aria-live="polite" data-connection hidden><p data-connection-message></p><button type="button" class="cb-button cb-secondary" data-connection-refresh hidden>Actualiser le statut</button></div>
        <?php
    }
}
