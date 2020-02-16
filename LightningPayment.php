<?php

if (!defined('MEDIAWIKI'))
    exit(1);

$wgExtensionCredits['antispam'][] = array(
    'path' => __FILE__,
    'name' => 'LightningPayment',
    'author' => 'Tim Horie',
    'url' => 'https://wiki.for-bitcoin.com/wiki/LightningPayment',
    'descriptionmsg' => 'lightningpayment-desc',
    'version' => '0.1.0',
);

$wgAutoloadClasses['LightningPayment'] = dirname(__FILE__) . '/LightningPayment.body.php';
$wgAutoloadClasses['SpecialLightningPayment'] = dirname(__FILE__) . '/SpecialLightningPayment.php';
$wgExtensionMessagesFiles['LightningPayment'] = dirname(__FILE__) . '/LightningPayment.i18n.php';

$wgSpecialPages['LightningPayment'] = 'SpecialLightningPayment';

$wgSpecialPageGroups['LightningPayment'] = 'other';

