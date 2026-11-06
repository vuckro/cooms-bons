<?php
/**
 * Plugin Name: COOMS Bons
 * Description: Bons cadeaux anonymes par lots et retraits sécurisés en boutique.
 * Version: 1.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Valentin Vuckovic
 * License: GPL-2.0-or-later
 * Text Domain: cooms-bons
 */
namespace Cooms\Bons;
defined('ABSPATH') || exit;
define('COOMS_BONS_FILE', __FILE__);
define('COOMS_BONS_VERSION', '1.1.0');
require_once __DIR__ . '/includes/class-store.php';
require_once __DIR__ . '/includes/class-scanner.php';
register_activation_hook(__FILE__, [Store::class, 'install']);
add_action('plugins_loaded', static function () {
    if (get_option('cooms_bons_schema') !== '1') {
        Store::install();
    }
    require_once __DIR__ . '/includes/class-export.php';
    require_once __DIR__ . '/includes/class-plugin.php';
    (new Plugin())->boot();
    add_action('template_redirect', [new Scanner(), 'route'], -101);
});
