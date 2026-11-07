<?php
/** Generate a real printable voucher image for the virtual-camera test. */
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local environment required.'); }
$fixture = json_decode(file_get_contents(dirname(__DIR__) . '/output/http-fixture.json'), true, 512, JSON_THROW_ON_ERROR);
if (home_url() !== $fixture['base']) { throw new RuntimeException('Wrong fixture target.'); }
wp_set_current_user($fixture['accounts']['admin']['id']);
require_once dirname(COOMS_BONS_FILE) . '/vendor/autoload.php';
$bon = \Cooms\Bons\Store::find($fixture['bons'][7]['token']);
$lot = \Cooms\Bons\Store::lot($fixture['lot']);
if (!$bon || !$lot || (int) $bon->lot_id !== (int) $lot->id || $lot->request_key !== $fixture['request_key']) { throw new RuntimeException('Wrong camera fixture identity.'); }
// Make only this disposable test voucher reusable when rerunning the camera suite.
global $wpdb;
$wpdb->update(\Cooms\Bons\Store::table('bons'), ['redeemed_at' => null, 'redeemed_by' => null], ['id' => $bon->id, 'lot_id' => $lot->id]);
file_put_contents(dirname(__DIR__) . '/output/camera-bon.png', \Cooms\Bons\Export::png($bon));
echo "Virtual-camera PNG generated from an existing test voucher.\n";
