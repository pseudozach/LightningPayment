<?php

class SpecialLightningPayment extends SpecialPage {

    public function __construct() {
        parent::__construct('LightningPayment');
    }

    public function grantTrusted($wgUserId) {

        if (false === $wgUserId > 0) {
            throw new \Exception('Bad userId');
        }

        $user = User::newFromId($wgUserId);
        $user->addGroup('trusted');
    }

    public function execute($par) {
        global $wgUser;
        global $wgLightningPaymentApiKey;


        if (!empty($_POST) && isset($_POST['key'])) {

            // Validate API key
            if (trim($_POST['key']) !== $wgLightningPaymentApiKey) {
                echo 'Bad key';
                die;
            }

            $userId = intval($_POST['wgUserId']);

            if (false === $userId > 0) {
                echo 'Bad userId';
                die;
            }

            $user = User::newFromId($userId);
            $user->addGroup('trusted');

            return;
        }

        $request = $this->getRequest();
        $output = $this->getOutput();
        $this->setHeaders();

        if ($wgUser->isAnon()) {
            $wikitext = 'You need to [[Special:UserLogin|login]] to access this page';
            $wikitext .= "\n\n{{LightningPayment|status=nologin}}";
            $output->addWikiTextAsInterface($wikitext);
            return;
        }

        $groups = $wgUser->getGroups();
        if (array_search('trusted', $groups) !== false) {
            $wikitext = 'You are already trusted, thank you!';
            $wikitext .= "\n\n{{LightningPayment|status=done}}";
            $output->addWikiTextAsInterface($wikitext);
            return;
        }

        $lightning_addr = $wgUser->getOption('lightningpayment-addr');
        $invoiceId = $wgUser->getOption('invoice-id');

        if (true || is_null($lightning_addr)) {
//			$url = 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            //			$url = 'https://'.$_SERVER['HTTP_HOST'].'/wiki/Special:LightningPayment/callback';
            //
			//
			$ret = LightningPayment::getInvoice($wgUser->getId(), $invoiceId);

            $addr = $ret['bolt11'];

            $lightning_addr = ['result' => 'success', 'data' => ['addr' => $addr]]; //LightningPayment::mtgox_query('2/money/lightning/address', array('ipn' => $url, 'description' => 'WP#'.$wgUser->getId()));
            if ($lightning_addr['result'] != 'success') {
                $wikitext = 'An error occured, please retry later';
                $output->addWikiText($wikitext);
                return;
            }

            if ($ret['status'] == 'paid') {
                $this->grantTrusted($wgUser->getId());
                $wikitext = 'Payment detected! You are now trusted, thank you!';
                $wikitext .= "\n\n{{LightningPayment|status=done}}";
                $output->addWikiTextAsInterface($wikitext);
                return;
            }

            $lightning_addr = $lightning_addr['data']['addr'];
            $wgUser->setOption('invoice-id', $ret['invoiceId']);
            $wgUser->setOption('lightningpayment-addr', $lightning_addr);
            $wgUser->saveSettings();
        }

        $wikitext = 'In order to be able to edit pages on this wiki, you will need to send a payment of 1 satoshi to the lightning invoice: ' .
                '<div style="overflow:wrap;word-break:break-all">' . $lightning_addr . '</div> ' .
                '<p><img src="https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=lightning:' . $lightning_addr . '"></p>' .
                '<p>[lightning:' . $lightning_addr . ' ' . '(open in wallet)] expires in ' . $ret['expiry'] . '</p>';
        $wikitext .= "\n\n";
        $wikitext .= 'Please note that you will need to wait for your transfer to be confirmed. Once payment has been sent, please refresh this page to check if the payment was detected.';
        $wikitext .= "\n\n{{LightningPayment|status=todo|addr=$lightning_addr}}";

        $output->addWikiTextAsInterface($wikitext);
    }

}
