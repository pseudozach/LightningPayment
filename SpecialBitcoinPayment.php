<?php
class SpecialBitcoinPayment extends SpecialPage {
	public function __construct() {
		parent::__construct('BitcoinPayment');
	}

	public function execute($par) {
		global $wgUser;
		global $wgBitcoinPaymentApiKey;


		if (!empty($_POST) && isset($_POST['key'])) {

			// Validate API key
			if (trim($_POST['key']) !== $wgBitcoinPaymentApiKey) {
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
			$wikitext .= "\n\n{{BitcoinPayment|status=nologin}}";
			$output->addWikiTextAsInterface( $wikitext );
			return;
		}

		$groups = $wgUser->getGroups();
		if (array_search('trusted', $groups) !== false) {
			$wikitext = 'You are already trusted, thank you!';
			$wikitext .= "\n\n{{BitcoinPayment|status=done}}";
			$output->addWikiTextAsInterface( $wikitext );
			return;
		}

		$btc_addr = $wgUser->getOption('bitcoinpayment-addr');
		$invoiceId = $wgUser->getOption('invoice-id');

		if (true || is_null($btc_addr)) {
//			$url = 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			//			$url = 'https://'.$_SERVER['HTTP_HOST'].'/wiki/Special:BitcoinPayment/callback';
			//
			//
			$ret = BitcoinPayment::getInvoice($wgUser->getId(), $invoiceId);
//			var_dump($ret);
			$addr = $ret['bolt11'];

			$btc_addr = ['result' => 'success', 'data' => ['addr' => $addr]];//BitcoinPayment::mtgox_query('2/money/bitcoin/address', array('ipn' => $url, 'description' => 'WP#'.$wgUser->getId()));
			if ($btc_addr['result'] != 'success') {
				$wikitext = 'An error occured, please retry later';
				$output->addWikiText( $wikitext );
				return;
			}
			$btc_addr = $btc_addr['data']['addr'];
			$wgUser->setOption('invoice-id', $ret['invoiceId']);
			$wgUser->setOption('bitcoinpayment-addr', $btc_addr);
			$wgUser->saveSettings();
		}

		$wikitext = 'In order to be able to edit pages on this wiki, you will need to send a payment of 1 satoshi to the lightning invoice: ' . 
		'<div style="overflow:wrap;word-break:break-all">' . $btc_addr . '</div> ' . 
		'<p><img src="https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=lightning:' . $btc_addr . '"></p>' . 
		'<p>[lightning:'.$btc_addr.' '. '(open in wallet)] expires in ' . $ret['expiry'] . '</p>';
		$wikitext .= "\n\n";
		$wikitext .= 'Please note that you will need to wait for your transfer to be confirmed.';
		$wikitext .= "\n\n{{BitcoinPayment|status=todo|addr=$btc_addr}}";

		$output->addWikiTextAsInterface( $wikitext );
	}
}

