# LightningPayment

This simple plugin allows users to join the mediawiki group "trusted" by paying.

Requirements
------------

The system requires a c-lightning node with the lightning-php API configured and installed.

Installation
------------

Copy the files to the `extensions/LightningPayment` folder.

You will need a "LightningPayment" template. You can create it by copying
the contents of the Template:LightningPayment.txt file into your wiki like:

http://127.0.0.1:8080/index.php?title=Template:LightningPayment&action=edit


Configuration
-------------

Add these settings to the bottom of the LocalSettings.php file. Be sure to change `$wgBitcoinPaymentNodeUrl` to the URL of the c-lightning php api. If you are using a remote server, there is an example of using stunnel4 to create a certificate-based connection between your wiki and the lightning node available in the mediawiki-docker-lightning repo.

```
wfLoadExtension( 'ParserFunctions' );
include("extensions/LightningPayment/LightningPayment.php");

$wgGroupPermissions['*']['edit'] = false;
$wgGroupPermissions['user']['edit'] = false;
$wgGroupPermissions['trusted']['edit'] = true;
$wgGroupPermissions['*']['createpage'] = false;
$wgGroupPermissions['user']['createpage'] = false;
$wgGroupPermissions['trusted']['createpage'] = true;

$wgBitcoinPaymentApiKey = 'aaaaaabbbbbbbccccccc111';
$wgBitcoinPaymentNodeUrl = 'http://ln-gateway:3000';
#$wgMainCacheType = CACHE_NONE;
#$wgCacheDirectory = false;
#$wgShowExceptionDetails = true;

$wgUrlProtocols[] = 'lightning:';

$wgAllowImageTag = true;
$wgAllowExternalImagesFrom = ['https://chart.googleapis.com/'];
```

Usage
-----

You should be able to access the payment page here:

http://localhost:8080/index.php/Special:LightningPayment

It should display a lightning invoice and QR code image.

Newly created accounts won't be able to edit until they have paid.
