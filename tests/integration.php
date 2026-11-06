<?php
/** Run with wp eval-file tests/integration.php on LOCAL only. */
use Cooms\Bons\Store;
use Cooms\Bons\Export;
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local environment required.'); }
$passed = 0;
$check = static function ($ok, string $label) use (&$passed) {
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $passed++; echo "PASS: $label\n";
};
$original_user = get_current_user_id();
$lot_ids = []; $user_ids = [];
try {
    wp_set_current_user(1);
    $check(current_user_can(Store::MANAGE), 'Administrator can manage bons');
    $cases = [
        '2026-11-06 12:00:00' => '06/05/2027',
        '2026-08-31 12:00:00' => '28/02/2027',
        '2027-08-31 12:00:00' => '29/02/2028',
        '2026-12-31 12:00:00' => '30/06/2027',
        '2026-10-31 23:30:00' => '01/05/2027',
    ];
    foreach ($cases as $source => $expected) {
        $check(Store::expiry_label(Store::expiry(new DateTimeImmutable($source, new DateTimeZone('UTC')))) === $expected, 'Calendar expiry ' . $source);
    }
    $check(is_wp_error(Store::create_lot('', 25, bin2hex(random_bytes(32)))), 'Empty name refused');
    $check(is_wp_error(Store::create_lot('Invalid', 0, bin2hex(random_bytes(32)))), 'Zero quantity refused');
    $check(is_wp_error(Store::create_lot('Invalid', 251, bin2hex(random_bytes(32)))), 'Oversize batch refused');
    $key = bin2hex(random_bytes(32));
    $id = Store::create_lot('RECETTE AUTOMATISÉE — à supprimer', 25, $key);
    $check(is_int($id), 'Create batch of 25'); $lot_ids[] = $id;
    $check(Store::create_lot('RECETTE AUTOMATISÉE — à supprimer', 25, $key) === $id, 'Repeated request returns same lot');
    $bons = Store::bons($id, '', 250);
    $check(count($bons) === 25, 'Exactly 25 bons');
    $check(count(array_unique(array_column($bons, 'token'))) === 25, 'Unique secret tokens');
    $check(count(array_unique(array_column($bons, 'code'))) === 25, 'Unique printed references');
    $check(strlen($bons[0]->token) === 64, '256-bit encoded token');
    $check(Store::find('1') === null && Store::find(str_repeat('a', 64)) === null, 'Unknown tokens rejected');
    $check(Store::status(Store::find($bons[0]->token)) === 'available', 'Consulting leaves bon available');
    $counts = Store::counts($id);
    $check($counts === ['total' => 25, 'used' => 0, 'available' => 25, 'expired' => 0], 'Initial counts exact');
    $result = Store::redeem($bons[0]->token);
    $check($result['result'] === 'success', 'First redemption succeeds');
    $stamp = $result['bon']->redeemed_at;
    $check(Store::redeem($bons[0]->token)['result'] === 'used', 'Second redemption refused');
    $check(Store::find($bons[0]->token)->redeemed_at === $stamp, 'Original timestamp preserved');
    global $wpdb;
    $wpdb->update(Store::table('bons'), ['expires_at' => Store::now()], ['id' => $bons[1]->id]);
    $check(Store::redeem($bons[1]->token)['result'] === 'expired', 'Exclusive expiry boundary refused');
    $wpdb->update(Store::table('bons'), ['expires_at' => '2020-01-01 00:00:00'], ['id' => $bons[0]->id]);
    $check(Store::status(Store::find($bons[0]->token)) === 'used', 'Used status survives expiry');
    $counts = Store::counts($id);
    $check($counts === ['total' => 25, 'used' => 1, 'available' => 23, 'expired' => 1], 'Reconciliation counts exact');
    $check(count(Store::bons($id, 'used')) === 1 && count(Store::bons($id, 'expired')) === 1, 'Status filters scope correctly');
    $check(count(Store::bons($id, '', 10, 20)) === 5, 'Pagination');
    $seller_id = wp_insert_user(['user_login' => 'cooms-test-' . bin2hex(random_bytes(5)), 'user_pass' => wp_generate_password(32), 'role' => 'cooms_seller']);
    $check(!is_wp_error($seller_id), 'Create isolated seller fixture'); $user_ids[] = $seller_id;
    wp_set_current_user($seller_id);
    $check(current_user_can(Store::REDEEM) && !current_user_can(Store::MANAGE) && !current_user_can('edit_posts'), 'Least-privilege seller role');
    $check(Store::redeem($bons[2]->token)['result'] === 'success', 'Seller may redeem');
    $check((int) Store::find($bons[2]->token)->redeemed_by === $seller_id, 'Seller recorded');
    wp_set_current_user(0);
    $check(Store::redeem($bons[3]->token)['result'] === 'forbidden', 'Anonymous redemption refused');
    $check(Store::status(Store::find($bons[3]->token)) === 'available', 'Forbidden request did not consume bon');
    $check(Export::cell('=HYPERLINK("evil")')[0] === "'" && Export::cell('  +SUM(A1)')[0] === "'", 'CSV formula injection escaped');
    $check(Export::ready(), 'PNG and ZIP runtime prerequisites');
    require_once dirname(COOMS_BONS_FILE) . '/vendor/autoload.php';
    $png = Export::png($bons[3]);
    $info = getimagesizefromstring($png);
    $check($info[0] === 1000 && $info[1] === 1320 && $info['mime'] === 'image/png', 'Printable PNG generated');
    wp_mkdir_p(dirname(COOMS_BONS_FILE) . '/output');
    file_put_contents(dirname(COOMS_BONS_FILE) . '/output/test-bon.png', $png);
    echo "SUCCESS: $passed checks. Fixture lots/users removed below.\n";
} finally {
    global $wpdb;
    foreach ($lot_ids as $id) {
        $wpdb->delete(Store::table('bons'), ['lot_id' => $id]);
        $wpdb->delete(Store::table('lots'), ['id' => $id]);
    }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($user_ids as $id) { wp_delete_user($id); }
    wp_set_current_user($original_user);
}
