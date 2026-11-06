<?php
use Cooms\Bons\Store;
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local environment required.'); }
$path = dirname(__DIR__) . '/output/http-fixture.json';
if (!file_exists($path)) { echo "No fixture to remove.\n"; return; }
$f = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
if (home_url() !== $f['base']) { throw new RuntimeException('Wrong site.'); }
global $wpdb;
// Never treat a stale fixture account ID as ownership of another user’s data.
$owner = get_userdata($f['accounts']['admin']['id']);
if (!$owner || $owner->user_login !== $f['accounts']['admin']['login']) { throw new RuntimeException('Fixture owner mismatch.'); }
// The disposable administrator owns only this suite’s explicitly labelled lots.
$owned = $wpdb->get_results($wpdb->prepare('SELECT id, request_key FROM ' . Store::table('lots') . ' WHERE created_by=%d AND name IN (%s,%s)', $f['accounts']['admin']['id'], 'RECETTE HTTP — données synthétiques', 'RECETTE HTTP personnalisée'), ARRAY_A);
foreach ($owned as $item) {
    $lot = Store::lot($item['id']);
    if ($lot && $lot->request_key !== $item['request_key']) { throw new RuntimeException('Fixture identity mismatch.'); }
    if ($lot) {
        $wpdb->delete(Store::table('bons'), ['lot_id' => $lot->id]);
        $wpdb->delete(Store::table('lots'), ['id' => $lot->id]);
    }
}
require_once ABSPATH . 'wp-admin/includes/user.php';
foreach ($f['accounts'] as $account) {
    $user = get_user_by('id', $account['id']);
    if ($user && $user->user_login === $account['login'] && strpos($user->user_login, 'cooms-http-') === 0) { wp_delete_user($user->ID); }
}
unlink($path);
echo "Only the disposable HTTP lot/accounts have been removed.\n";
