<?php
use Cooms\Bons\Store;
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local environment required.'); }
$path = dirname(__DIR__) . '/output/http-fixture.json';
if (!file_exists($path)) { echo "No fixture to remove.\n"; return; }
$f = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
if (home_url() !== $f['base']) { throw new RuntimeException('Wrong site.'); }
$lot = Store::lot($f['lot']);
if ($lot && $lot->request_key !== $f['request_key']) { throw new RuntimeException('Fixture identity mismatch.'); }
global $wpdb;
if ($lot) {
    $wpdb->delete(Store::table('bons'), ['lot_id' => $lot->id]);
    $wpdb->delete(Store::table('lots'), ['id' => $lot->id]);
}
require_once ABSPATH . 'wp-admin/includes/user.php';
foreach ($f['accounts'] as $account) {
    $user = get_user_by('id', $account['id']);
    if ($user && $user->user_login === $account['login'] && strpos($user->user_login, 'cooms-http-') === 0) { wp_delete_user($user->ID); }
}
unlink($path);
echo "Only the disposable HTTP lot/accounts have been removed.\n";
