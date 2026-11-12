<?php
/** Run with wp eval-file tests/integration.php on LOCAL only. */
use Cooms\Bons\Store;
use Cooms\Bons\Export;
use Cooms\Bons\Scanner;
use Cooms\Bons\Staff;
use Cooms\Bons\Webapp;
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local environment required.'); }
$passed = 0;
$check = static function ($ok, string $label) use (&$passed) {
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $passed++; echo "PASS: $label\n";
};
$original_user = get_current_user_id();
$lot_ids = []; $user_ids = [];
$intercept_mail = null;
try {
    wp_set_current_user(1);
    $check(current_user_can(Store::MANAGE), 'Administrator can manage bons');
    $check(get_option('cooms_bons_schema') === '3', 'Schema v3 migration installed');
    $manifest = Webapp::manifest();
    $check($manifest['display'] === 'standalone' && $manifest['start_url'] === Scanner::url(), 'Webapp opens the scanner in standalone mode');
    $check($manifest['scope'] === home_url('/') && $manifest['id'] === Scanner::url(), 'Webapp identity and scope use the actual WordPress home');
    $check(count($manifest['icons']) === 3 && $manifest['icons'][2]['purpose'] === 'maskable', 'Webapp has regular and maskable icons');
    foreach (['icon-192.png' => 192, 'icon-512.png' => 512, 'icon-maskable-512.png' => 512, 'apple-touch-icon.png' => 180] as $file => $size) {
        $image = getimagesize(dirname(COOMS_BONS_FILE) . '/assets/app/' . $file);
        $check($image[0] === $size && $image[1] === $size && $image['mime'] === 'image/png', 'Packaged webapp icon ' . $file);
    }
    $check(Staff::can_manage(), 'Administrator can manage staff accounts');
    $check(is_wp_error(Staff::create('', 'invalid')), 'Invalid employee data refused');
    $check(is_wp_error(Staff::create('Example', 'invalid')), 'Invalid employee email refused');
    $staff_email = 'cooms-staff-' . bin2hex(random_bytes(8)) . '@example.invalid';
    $staff_id = Staff::create('RECETTE accès employé', $staff_email);
    $check(is_int($staff_id), 'Create dedicated employee account'); $user_ids[] = $staff_id;
    $staff = get_userdata($staff_id);
    $test_password = wp_generate_password(40);
    wp_set_password($test_password, $staff_id);
    $login = wp_authenticate($staff_email, $test_password);
    $check($login instanceof WP_User && $login->ID === $staff_id, 'Employee can log in using email and password');
    $mail = [];
    $intercept_mail = static function ($return, $attributes) use (&$mail) { $mail[] = $attributes; return true; };
    add_filter('pre_wp_mail', $intercept_mail, 10, 2);
    wp_new_user_notification($staff_id, null, 'user');
    remove_filter('pre_wp_mail', $intercept_mail, 10);
    $intercept_mail = null;
    $check(count($mail) === 1 && (array) $mail[0]['to'] === [$staff_email], 'Native invitation targets only employee; test mail intercepted');
    $check(str_contains($mail[0]['message'], 'action=rp') && !str_contains($mail[0]['message'], $test_password), 'Invitation contains native password setup link, never password');
    $check($staff->roles === ['cooms_seller'] && user_can($staff, Store::REDEEM), 'Employee receives only seller role');
    $check(!user_can($staff, Store::MANAGE) && !user_can($staff, 'edit_posts') && !user_can($staff, 'promote_users'), 'Employee cannot manage site, lots or accounts');
    $check(is_wp_error(Staff::create('Duplicate', $staff_email)), 'Existing email never replaces or changes an account');
    $sessions = WP_Session_Tokens::get_instance($staff_id);
    $session = $sessions->create(time() + 3600);
    $check($sessions->verify($session), 'Employee session fixture exists');
    $check(Staff::set_access($staff_id, false) === true, 'Suspend employee access');
    $check(!user_can(get_userdata($staff_id), Store::REDEEM), 'Suspended account cannot redeem');
    $check(!$sessions->verify($session), 'Suspension invalidates existing sessions');
    $check(get_userdata($staff_id)->user_email === $staff_email, 'Suspension retains account identity');
    $check(Staff::set_access($staff_id, true) === true && user_can(get_userdata($staff_id), Store::REDEEM), 'Reactivate dedicated employee');
    $check(is_wp_error(Staff::set_access(1, false)), 'Cannot suspend own administrator account');
    $staff->set_role('administrator');
    $check(is_wp_error(Staff::set_access($staff_id, false)), 'Externally elevated staff account protected from role replacement');
    $staff->set_role('cooms_seller');
    $redirect = new Staff();
    $check($redirect->login_redirect(admin_url(), '', get_userdata($staff_id)) === Scanner::url(), 'Seller default login goes to scanner');
    $check($redirect->login_redirect(home_url('/?cooms_bon=test'), home_url('/?cooms_bon=test'), get_userdata($staff_id)) === home_url('/?cooms_bon=test'), 'Explicit voucher login destination preserved');
    $check(Staff::is_restricted_seller(get_userdata($staff_id)) && !Staff::is_restricted_seller(get_userdata(1)), 'Restricted seller detected without flagging administrators');
    $check($redirect->filter_admin_bar(true) === true && $redirect->filter_app_passwords(true, get_userdata($staff_id)) === false, 'Application passwords disabled for restricted sellers');
    $check(is_wp_error(Staff::create('Weak Pass', 'weak-' . bin2hex(random_bytes(4)) . '@example.invalid', 'short')), 'Short initial boutique password refused');
    $direct_email = 'cooms-direct-' . bin2hex(random_bytes(8)) . '@example.invalid';
    $direct_initial = wp_generate_password(18, false);
    $direct_id = Staff::create('Boutique Cornavin', $direct_email, $direct_initial);
    $check(is_int($direct_id), 'Create boutique account with initial password'); $user_ids[] = $direct_id;
    $check(wp_authenticate($direct_email, $direct_initial) instanceof WP_User, 'Initial boutique password authenticates immediately');
    $direct_sessions = WP_Session_Tokens::get_instance($direct_id);
    $direct_token = $direct_sessions->create(time() + 3600);
    $check(is_wp_error(Staff::set_password($direct_id, '1234567')), 'Short replacement password refused');
    $check(is_wp_error(Staff::set_password(1, wp_generate_password(18, false))), 'Administrator password protected from staff endpoint');
    $rotated_pass = wp_generate_password(18, false);
    $check(Staff::set_password($direct_id, $rotated_pass) === true, 'Boutique password updated from staff management');
    $check(!$direct_sessions->verify($direct_token) && wp_authenticate($direct_email, $rotated_pass) instanceof WP_User, 'Password update revokes old sessions and activates new password');
    wp_set_current_user($staff_id);
    $check($redirect->filter_admin_bar(true) === false, 'Frontend admin bar hidden for restricted sellers');
    $check(!Staff::can_manage() && is_wp_error(Staff::create('No', 'no@example.invalid')) && is_wp_error(Staff::set_access($staff_id, false)) && is_wp_error(Staff::set_password($direct_id, wp_generate_password(18, false))), 'Seller cannot grant or change employee access or passwords');
    wp_set_current_user(0);
    $check(is_wp_error(Staff::create('No', 'no@example.invalid')), 'Anonymous account creation refused');
    wp_set_current_user(1);
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
    $future = (new DateTimeImmutable('now', new DateTimeZone('Europe/Zurich')))->modify('+45 days')->format('Y-m-d');
    $custom = Store::create_lot('RECETTE cadeau personnalisé', 2, bin2hex(random_bytes(32)), 'Coffret de 8 cookies — 40 CHF', $future, "<b>Réservé</b>\nDistribution équipe 🍪");
    $check(is_int($custom), 'Create custom gift/date lot'); $lot_ids[] = $custom;
    $custom_bons = Store::bons($custom);
    $check(Store::lot($custom)->description === "Réservé\nDistribution équipe 🍪", 'Internal description stores sanitized multiline Unicode');
    $check(Store::lot($id)->description === '', 'Description remains optional');
    $check(!property_exists(Store::find($custom_bons[0]->token), 'description') && !property_exists($custom_bons[0], 'description'), 'Private description excluded from public voucher data');
    $check(is_wp_error(Store::create_lot('Too long', 1, bin2hex(random_bytes(32)), Store::TITLE, $future, str_repeat('é', 2001))), 'Oversized internal description refused');
    $check(Store::update_description($custom, 'Suivi interne') === true && Store::lot($custom)->description === 'Suivi interne', 'Responsible can edit an existing lot description');
    $check(is_wp_error(Store::update_description($custom, str_repeat('é', 2001))) && Store::lot($custom)->description === 'Suivi interne', 'Invalid update retains previous internal note');
    $check(Store::update_description($custom, '') === true && Store::lot($custom)->description === '', 'Responsible can clear an internal description');
    $check(Store::lot($custom)->title === 'Coffret de 8 cookies' && (int) Store::lot($custom)->value_cents === 4000 && Store::gift_label(Store::lot($custom)) === 'Coffret de 8 cookies — 40 CHF' && $custom_bons[0]->title === Store::lot($custom)->title && (int) $custom_bons[0]->value_cents === 4000, 'Legacy gift string splits cleanly into title + value_cents while preserving gift_label');
    $check(Store::parse_value('20') === 2000 && Store::parse_value('24,50') === 2450 && Store::parse_value('0') === 0 && is_wp_error(Store::parse_value('-1')) && is_wp_error(Store::parse_value('100001')) && is_wp_error(Store::parse_value('abc')), 'CHF unit value parser validates bounds and decimals');
    $cornavin = Store::create_lot('RECETTE Cornavin', 3, bin2hex(random_bytes(32)), 'Boîte découverte', $future, '', 3500, 'Retrait en boutique Cornavin');
    $check(is_int($cornavin), 'Create separated gift/value/pickup lot'); $lot_ids[] = $cornavin;
    $cornavin_bon = Store::bons($cornavin)[0];
    $check($cornavin_bon->title === 'Boîte découverte' && (int) $cornavin_bon->value_cents === 3500 && $cornavin_bon->pickup_text === 'Retrait en boutique Cornavin', 'Separated title, value_cents and pickup_text stored on lot and joined to bons');
    $check(Store::gift_label($cornavin_bon) === 'Boîte découverte — 35 CHF' && Store::pickup_line($cornavin_bon) === 'Usage unique · Retrait en boutique Cornavin', 'Formatted gift and pickup labels rendered accurately');
    $check(Store::update_pickup($cornavin, 'Retrait aux boutiques Cornavin et Terrassière') === true && Store::lot($cornavin)->pickup_text === 'Retrait aux boutiques Cornavin et Terrassière', 'Responsible can update pickup text on existing lot');
    $check(is_wp_error(Store::update_pickup($cornavin, '')) && is_wp_error(Store::update_pickup($cornavin, str_repeat('x', 161))), 'Invalid pickup text refused');
    global $wpdb;
    $wpdb->update(Store::table('lots'), ['title' => 'Boîte de 4 cookies — 20 CHF', 'value_cents' => 0, 'pickup_text' => ''], ['id' => $cornavin]);
    Store::migrate_legacy_lots();
    $migrated = Store::lot($cornavin);
    $check($migrated->title === 'Boîte de 4 cookies' && (int) $migrated->value_cents === 2000 && $migrated->pickup_text === Store::DEFAULT_PICKUP, 'migrate_legacy_lots splits historical CHF title and backfills default pickup');
    $wpdb->update(Store::table('lots'), ['title' => 'Boîte découverte', 'value_cents' => 3500, 'pickup_text' => 'Retrait aux boutiques Cornavin et Terrassière'], ['id' => $cornavin]);
    $check(Store::expiry_label($custom_bons[0]->expires_at) === (new DateTimeImmutable($future))->format('d/m/Y'), 'Custom expiry date inclusive');
    $check($custom_bons[0]->expires_at === Store::lot($custom)->expires_at, 'Lot and every bon share exact expiry');
    $check(is_wp_error(Store::create_lot('Invalid gift', 1, bin2hex(random_bytes(32)), '', $future)), 'Empty gift refused');
    $check(is_wp_error(Store::create_lot('Invalid gift', 1, bin2hex(random_bytes(32)), str_repeat('A',191), $future)), 'Oversize gift refused');
    foreach (['2020-01-01', '2027-02-30', '2027-02-29', '', 'not-a-date', '9999-12-31', "2027-02-28\0"] as $bad_date) {
        $check(is_wp_error(Store::create_lot('Invalid expiry', 1, bin2hex(random_bytes(32)), 'Cadeau', $bad_date)), 'Reject date '.$bad_date);
    }
    $year = (int) gmdate('Y') + 1;
    $march = new DateTimeImmutable('last sunday of March '.$year, new DateTimeZone('Europe/Zurich'));
    $october = new DateTimeImmutable('last sunday of October '.$year, new DateTimeZone('Europe/Zurich'));
    $check(substr(Store::selected_expiry($march->format('Y-m-d')),11) === '22:00:00', 'Custom DST-start expiry uses Geneva midnight');
    $check(substr(Store::selected_expiry($october->format('Y-m-d')),11) === '23:00:00', 'Custom DST-end expiry uses Geneva midnight');

    $check(Store::create_lot('RECETTE AUTOMATISÉE — à supprimer', 25, $key) === $id, 'Repeated request returns same lot');
    $bons = Store::bons($id, '', 250);
    $check(count($bons) === 25, 'Exactly 25 bons');
    $check(count(array_unique(array_column($bons, 'token'))) === 25, 'Unique secret tokens');
    $check(count(array_unique(array_column($bons, 'code'))) === 25, 'Unique printed references');
    $check(strlen($bons[0]->token) === 64, '256-bit encoded token');
    $check(Scanner::resolve($bons[0]->code)->id === $bons[0]->id, 'Manual reference resolves bon');
    $check(Scanner::resolve(strtolower(str_replace('-', ' ', $bons[0]->code)))->id === $bons[0]->id, 'Case and space reference normalization');
    $check(Scanner::resolve(Store::url($bons[0]))->id === $bons[0]->id, 'Own QR URL resolves bon');
    $check(Scanner::resolve('https://example.invalid/?cooms_bon=' . $bons[0]->token) === null, 'External QR origin refused');
    $check(Scanner::resolve(home_url('/other/') . '?cooms_bon=' . $bons[0]->token) === null, 'Wrong QR path refused');
    $check(Scanner::resolve(Store::url($bons[0]) . '#fragment') === null, 'Unexpected URL fragment refused');
    $check(Scanner::resolve(home_url('/') . '?cooms_bon[]=bad') === null, 'Array token refused');
    $check(Scanner::resolve(str_repeat('A', 1025)) === null, 'Oversize input refused');
    $summary = array_values(array_filter(Store::lot_summaries(1000), static fn($row) => (int) $row->id === $id))[0];
    $check((int) $summary->available === 25 && (int) $summary->used === 0, 'Lot summary initial volumes');

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
    $original_page = $_GET['page'] ?? null;
    $_GET['page'] = 'cooms-bons-scan';
    $check((new \Cooms\Bons\Plugin())->allow_seller_pages(true) === false, 'WooCommerce permits the seller scanner page');
    $_GET['page'] = 'users';
    $check((new \Cooms\Bons\Plugin())->allow_seller_pages(true) === true, 'WooCommerce restriction retained outside COOMS pages');
    if ($original_page === null) { unset($_GET['page']); } else { $_GET['page'] = $original_page; }

    $check(Store::redeem($bons[2]->token)['result'] === 'success', 'Seller may redeem');
    $check((int) Store::find($bons[2]->token)->redeemed_by === $seller_id, 'Seller recorded');
    $check(is_wp_error(Store::update_description($custom, 'Seller edit')) && is_wp_error(Store::update_pickup($custom, 'Seller pickup')), 'Seller cannot edit private lot notes or pickup text');
    $lot_amounts = Store::amounts($id);
    $check($lot_amounts === ['total_cents' => 50000, 'used_cents' => 4000, 'available_cents' => 44000, 'expired_cents' => 2000], 'Per-lot CHF amounts track total, used, available and expired');
    $global_amounts = Store::amounts(0);
    $check($global_amounts['total_cents'] >= 50000 + 8000 + 10500 && $global_amounts['used_cents'] >= 4000, 'Global CHF amounts aggregate accurately across multi-value lots');
    $attributed = array_values(array_filter(Store::bons($id), static fn($row) => $row->token === $bons[2]->token))[0];
    $check(Store::redeemer_label($attributed) === get_userdata($seller_id)->display_name, 'Management lists the actual employee who redeemed');
    $deleted_account = clone $attributed; $deleted_account->redeemer_name = null;
    $check(Store::redeemer_label($deleted_account) === 'Compte supprimé (#' . $seller_id . ')', 'Deleted employee keeps identifiable audit attribution');
    $check(Store::redeemer_label($bons[3]) === '', 'Available voucher has no employee attribution');
    wp_set_current_user(0);
    $check(Store::redeem($bons[3]->token)['result'] === 'forbidden', 'Anonymous redemption refused');
    $check(Scanner::resolve($bons[3]->code) === null && Store::find_code($bons[3]->code) === null, 'Anonymous manual lookup refused');
    $check(Store::status(Store::find($bons[3]->token)) === 'available', 'Forbidden request did not consume bon');
    $check(Export::cell('=HYPERLINK("evil")')[0] === "'" && Export::cell('  +SUM(A1)')[0] === "'", 'CSV formula injection escaped');
    $check(Export::ready(), 'PNG and ZIP runtime prerequisites');
    require_once dirname(COOMS_BONS_FILE) . '/vendor/autoload.php';
    $png = Export::png($bons[3]);
    $info = getimagesizefromstring($png);
    $check($info[0] === 1000 && $info[1] === 1320 && $info['mime'] === 'image/png', 'Printable PNG generated');
    wp_mkdir_p(dirname(COOMS_BONS_FILE) . '/output');
    file_put_contents(dirname(COOMS_BONS_FILE) . '/output/test-bon.png', $png);
    $custom_png = Export::png($custom_bons[0]);
    file_put_contents(dirname(COOMS_BONS_FILE) . '/output/test-custom-bon.png', $custom_png);
    $check(getimagesizefromstring($custom_png)['mime'] === 'image/png', 'Custom gift PNG generated');
    $long_bon = clone $custom_bons[0]; $long_bon->title = str_repeat('W',190); $long_bon->pickup_text = str_repeat('R', 160);
    $check(getimagesizefromstring(Export::png($long_bon))['mime'] === 'image/png', '190-character gift and 160-character pickup fit printable export');
    $check(Store::lot($id)->title === Store::TITLE && (int) Store::lot($id)->value_cents === Store::DEFAULT_CENTS && Store::lot($id)->pickup_text === Store::DEFAULT_PICKUP, 'Default lot gift, value and pickup remain consistent');

    echo "SUCCESS: $passed checks. Fixture lots/users removed below.\n";
} finally {
    if ($intercept_mail) { remove_filter('pre_wp_mail', $intercept_mail, 10); }
    global $wpdb;
    foreach ($lot_ids as $id) {
        $wpdb->delete(Store::table('bons'), ['lot_id' => $id]);
        $wpdb->delete(Store::table('lots'), ['id' => $id]);
    }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($user_ids as $id) { wp_delete_user($id); }
    wp_set_current_user($original_user);
}
