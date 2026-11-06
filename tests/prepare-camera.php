<?php
/** Generate a real printable voucher image for the virtual-camera test. */
if (wp_get_environment_type() !== 'local') { throw new RuntimeException('Local environment required.'); }
$fixture = json_decode(file_get_contents(dirname(__DIR__) . '/output/http-fixture.json'), true, 512, JSON_THROW_ON_ERROR);
if (home_url() !== $fixture['base']) { throw new RuntimeException('Wrong fixture target.'); }
wp_set_current_user($fixture['accounts']['admin']['id']);
require_once dirname(COOMS_BONS_FILE) . '/vendor/autoload.php';
$bon = \Cooms\Bons\Store::find($fixture['bons'][7]['token']);
file_put_contents(dirname(__DIR__) . '/output/camera-bon.png', \Cooms\Bons\Export::png($bon));
echo "Virtual-camera PNG generated from an existing test voucher.\n";
