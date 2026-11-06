<?php
/** Local-only disposable fixture for tests/http_checks.py. Contains short-lived cookies. */
use Cooms\Bons\Store;
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local environment required.'); }
$path = dirname(__DIR__) . '/output/http-fixture.json';
if (file_exists($path)) { throw new RuntimeException('Clean up the previous HTTP fixture first.'); }
$fixture = ['base' => home_url(), 'accounts' => []];
foreach (['admin' => 'administrator', 'seller' => 'cooms_seller', 'subscriber' => 'subscriber'] as $label => $role) {
    $login = 'cooms-http-' . $label . '-' . bin2hex(random_bytes(5));
    $id = wp_insert_user(['user_login' => $login, 'user_pass' => wp_generate_password(40), 'role' => $role]);
    if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
    $expiration = time() + 3600;
    $token = WP_Session_Tokens::get_instance($id)->create($expiration);
    $fixture['accounts'][$label] = ['id' => $id, 'login' => $login, 'cookies' => [
        ['name' => LOGGED_IN_COOKIE, 'value' => wp_generate_auth_cookie($id, $expiration, 'logged_in', $token), 'domain' => wp_parse_url(home_url(), PHP_URL_HOST), 'path' => '/', 'httpOnly' => true, 'secure' => is_ssl(), 'sameSite' => 'Lax', 'expires' => $expiration],
        ['name' => is_ssl() ? SECURE_AUTH_COOKIE : AUTH_COOKIE, 'value' => wp_generate_auth_cookie($id, $expiration, is_ssl() ? 'secure_auth' : 'auth', $token), 'domain' => wp_parse_url(home_url(), PHP_URL_HOST), 'path' => '/', 'httpOnly' => true, 'secure' => is_ssl(), 'sameSite' => 'Lax', 'expires' => $expiration],
    ]];
}
wp_set_current_user($fixture['accounts']['admin']['id']);
$fixture['request_key'] = bin2hex(random_bytes(32));
$fixture['lot'] = Store::create_lot('RECETTE HTTP — données synthétiques', 25, $fixture['request_key']);
if (is_wp_error($fixture['lot'])) { throw new RuntimeException($fixture['lot']->get_error_message()); }
$fixture['bons'] = Store::bons($fixture['lot'], '', 250);
wp_mkdir_p(dirname($path));
file_put_contents($path, wp_json_encode($fixture, JSON_UNESCAPED_UNICODE));
chmod($path, 0600);
echo "Disposable HTTP fixture ready in output/http-fixture.json. Clean up after tests.\n";
