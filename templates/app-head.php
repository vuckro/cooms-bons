<?php
namespace Cooms\Bons;
defined('ABSPATH') || exit;
?>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#fff8f2">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="COOMS">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="format-detection" content="telephone=no">
<link rel="manifest" href="<?php echo esc_url(Webapp::manifest_url()); ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url(add_query_arg('ver', COOMS_BONS_VERSION, plugins_url('assets/app/apple-touch-icon.png', COOMS_BONS_FILE))); ?>">
<link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url(add_query_arg('ver', COOMS_BONS_VERSION, plugins_url('assets/app/icon-192.png', COOMS_BONS_FILE))); ?>">
<script defer src="<?php echo esc_url(plugins_url('assets/webapp.js', COOMS_BONS_FILE)); ?>?ver=<?php echo esc_attr(COOMS_BONS_VERSION); ?>"></script>
