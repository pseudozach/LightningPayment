<?php

 SpecialLightningPayment extends SpecialPage {

      __construct() {
        parent::__construct('LightningPayment');
    }

     grantTrusted($wgUserId) {

         (false === $wgUserId > 0) {
                \Exception('Bad userId');
        }

        $user = User::newFromId($wgUserId);
        $user->addGroup('trusted');
    }

            execute($subPage) {
         $wgUser;
         $wgLightningPaymentApiKey;

        $request = $this->getRequest();
        $output = $this->getOutput();
        $this->setHeaders();

         ($wgUser->isAnon()) {
            $wikitext = 'You need to [[Special:UserLogin|login]] to access this page';
            $wikitext .= "\n\n{{LightningPayment|status=nologin}}";
            $output->addWikiTextAsInterface($wikitext);
            ;
        }

        $groups = $wgUser->getGroups();
         (array_search('trusted', $groups) !== false) {
            $wikitext = 'You are already trusted, thank you!';
            $wikitext .= "\n\n{{LightningPayment|status=done}}";
            $output->addWikiTextAsInterface($wikitext);
            ;
        }

        $invoiceId = $wgUser->getOption('invoice-id');
    
        $ret = LightningPayment::getInvoice($wgUser->getId(), $invoiceId);

         (!isset($ret['bolt11'])) {
            $arrtext = implode(" ",$ret);
            $wikitext = 'An error occured, please retry later';
            // $output->addWikiText($wikitext);
            $wikitext = $arrtext;
            $output->addWikiTextAsInterface($wikitext);
            ;
        }

         ($ret['status'] == 'paid' || $ret['status'] == 1) {
            $this->grantTrusted($wgUser->getId());
            $wikitext = 'Payment detected! You are now trusted, thank you!';
            $wikitext .= "\n\n{{LightningPayment|status=done}}";
            $output->addWikiTextAsInterface($wikitext);
            ;
        }

        $wgUser->setOption('invoice-id', $ret['invoiceId']);
        $wgUser->saveSettings();
//        
        $wikitext = <<<EOT
In order to be able to edit pages on this wiki, you will need to send a payment of 1 satoshi to the lightning invoice: 
<div style="overflow:wrap;word-break:break-all">{$ret['bolt11']}</div> 
<p>
    <img src="https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=lightning:{$ret['bolt11']}">
</p>
<p>
    [lightning:{$ret['bolt11']} (open in wallet)]
    expires in {$ret['expiry']}
</p>
<p>
    Please note that you will need to wait for your transfer to be confirmed. 
    Once payment has been sent, please refresh this page to check if the payment was detected.
</p>
<p>{{LightningPayment|status=todo|addr={$ret['bolt11']}}}</p>
\n\n
EOT;
        $output->addWikiTextAsInterface($wikitext);
    }

}
