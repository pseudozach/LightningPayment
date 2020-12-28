# LightningPayment

This simple plugin allows users to join the mediawiki group "trusted" by paying.

Requirements
------------

The system requires either:
1. A c-lightning node with the [lightning-charge](https://github.com/ElementsProject/lightning-charge) configured and installed.
2. [lnbits.com](https://lnbits.com) account

Installation
------------

Copy the files to the `extensions/LightningPayment` folder.

You will need a "LightningPayment" template. You can create it by copying
the contents of the Template:LightningPayment.txt file into your wiki like:

http://127.0.0.1:8080/index.php?title=Template:LightningPayment&action=edit


Configuration
-------------

Add these settings to the bottom of the LocalSettings.php file.
* You need to set the LightningBackend you are using and configure it: lightningcharge or lnbits.

* For [lightning-charge](https://github.com/ElementsProject/lightning-charge) 
⋅⋅* Set $wgLightningPaymentNodeUrl to lightning-charge api endpoint where mediawiki server can access it.
⋅⋅* Set $wgLightningPaymentNodeUrl to lightning-charge api endpoint where mediawiki server can access it.

* For [lnbits.com](https://lnbits.com) 
⋅⋅* Set $wgLNBitsApiKey to "Invoice/read key" from your lnbits wallet dashboard.


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

