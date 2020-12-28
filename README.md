# LightningPayment

This simple plugin allows users to join the mediawiki group "trusted" by paying.

Requirements
------------

The system requires either:
* A [c-lightning](https://github.com/ElementsProject/lightning) node with [lightning-charge](https://github.com/ElementsProject/lightning-charge) configured and installed.
* [lnbits.com](https://lnbits.com) account

Installation
------------

Copy the files to the `extensions/LightningPayment` folder on the mediawiki server/docker (e.g. /var/www/html/extensions/)


Configuration
-------------

Add these settings to the bottom of the LocalSettings.php file.
* You need to set the LightningBackend you are using and configure it: lightningcharge or lnbits.

* For [lightning-charge](https://github.com/ElementsProject/lightning-charge) 
  * Set $wgLightningPaymentNodeUrl to lightning-charge api endpoint where mediawiki server can access it.
  * Set $wgLightningPaymentApiToken to your lightning-charge api token.

* For [lnbits.com](https://lnbits.com) 
  * Set $wgLNBitsApiKey to "Invoice/read key" from your lnbits wallet dashboard.


```
wfLoadExtension( 'ParserFunctions' );
include("extensions/LightningPayment/LightningPayment.php");

$wgGroupPermissions['*']['edit'] = false;
$wgGroupPermissions['user']['edit'] = false;
$wgGroupPermissions['trusted']['edit'] = true;
$wgGroupPermissions['*']['createpage'] = false;
$wgGroupPermissions['user']['createpage'] = false;
$wgGroupPermissions['trusted']['createpage'] = true;

#choose one backend implementation: lightningcharge OR lnbits
$wgLightningBackend = 'lnbits';
//$wgLightningBackend = 'lightningcharge';

#c-lightning & lightning-charge
$wgLightningPaymentApiToken = 'mySecretToken';
$wgLightningPaymentNodeUrl = 'http://host.docker.internal:9112';

#lnbits
$wgLNBitsUrl = 'https://lnbits.com';
$wgLNBitsApiKey = 'lnbitsapikey';

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

Refresh the page after payment and user will have edit rights.

Demo
-----

<a target="_blank" href="http://www.youtube.com/watch?feature=player_embedded&v=J_P0SfQS5Gs"><img src="http://img.youtube.com/vi/J_P0SfQS5Gs/0.jpg" 
alt="IMAGE ALT TEXT HERE" width="240" height="180" border="10" /></a>



