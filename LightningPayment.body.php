<?php

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;

    // $ago = new DateTime($datetime);
    $ago = new DateTime();
    $ago->setTimestamp(intval($datetime));

    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full)
        $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ' : 'just now';
}

class LightningPayment {

    #not used?!
    public static function getAllInvoices() {
        global $wgLightningBackend;

        switch ($wgLightningBackend) {
            case 'lnbits':
                global $wgLNBitsUrl;
                global $wgLNBitsApiKey;
                $context = stream_context_create([
                    "http" => [
                        'header' => 'X-Api-Key: '.$wgLNBitsApiKey,
                    ]
                ]);
                $ret = file_get_contents($wgLNBitsUrl . "/invoices", false, $context);
                break;

            case 'lightningcharge':
                global $wgLightningPaymentNodeUrl;
                global $wgLightningPaymentApiToken;
                $auth = base64_encode("api-token:$wgLightningPaymentApiToken");
                $context = stream_context_create([
                    "http" => [
                        'header' => 'Authorization: Basic '.$auth,
                    ]
                ]);
                $ret = file_get_contents($wgLightningPaymentNodeUrl . "/invoices", false, $context);
                break;

            default:
                #clightning + lightning-charge by default
                global $wgLightningPaymentNodeUrl;
                global $wgLightningPaymentApiToken;
                $auth = base64_encode("api-token:$wgLightningPaymentApiToken");
                $context = stream_context_create([
                    "http" => [
                        'header' => 'Authorization: Basic '.$auth,
                    ]
                ]);
                $ret = file_get_contents($wgLightningPaymentNodeUrl . "/invoices", false, $context);
                break;
        }

        return json_decode($ret);
    }

    public static function getInvoiceByLabel($label) {
        global $wgLightningBackend;

        switch ($wgLightningBackend) {
            case 'lnbits':
                global $wgLNBitsUrl;
                global $wgLNBitsApiKey;
                $context = stream_context_create([
                    "http" => [
                        'header' => 'X-Api-Key: '.$wgLNBitsApiKey,
                    ]
                ]);
                $ret = @file_get_contents($wgLNBitsUrl . "/api/v1/payments/" . $label, false, $context);
                $ret = json_decode($ret, true);
                break;

            case 'lightningcharge':
                global $wgLightningPaymentNodeUrl;
                global $wgLightningPaymentApiToken;
                $auth = base64_encode("api-token:$wgLightningPaymentApiToken");
                $context = stream_context_create([
                    "http" => [
                        'header' => 'Authorization: Basic '.$auth,
                    ]
                ]);
                $ret = file_get_contents($wgLightningPaymentNodeUrl . "/invoice/" . $label, false, $context);
                $ret = json_decode($ret);
                break;

            default:
                #clightning + lightning-charge by default
                global $wgLightningPaymentNodeUrl;
                global $wgLightningPaymentApiToken;
                $auth = base64_encode("api-token:$wgLightningPaymentApiToken");
                $context = stream_context_create([
                    "http" => [
                        'header' => 'Authorization: Basic '.$auth,
                    ]
                ]);
                $ret = file_get_contents($wgLightningPaymentNodeUrl . "/invoice/" . $label, false, $context);
                $ret = json_decode($ret);
                break;
        }

        return $ret;
    }

    public static function createInvoice($msatoshi, $label, $description) {

        global $wgLightningBackend;

        switch ($wgLightningBackend) {
            case 'lnbits':
                global $wgLNBitsUrl;
                global $wgLNBitsApiKey;
                $amountinsats = $msatoshi/1000;
                $postdata = json_encode(array(
                        'amount' => $amountinsats,
                        'memo' => $description,
                        'out' => false
                    )
                );
                $opts = array('http' =>
                    array(
                        'method'  => 'POST',
                        "header" => ["X-Api-Key: " . $wgLNBitsApiKey,
                            "Content-Type: application/json"],
                        'content' => $postdata
                    )
                );                
                $context = stream_context_create($opts);
                $ret = file_get_contents($wgLNBitsUrl . "/api/v1/payments", false, $context);
                break;

            case 'lightningcharge':
                global $wgLightningPaymentNodeUrl;
                global $wgLightningPaymentApiToken;
                $auth = base64_encode("api-token:$wgLightningPaymentApiToken");
                $postdata = http_build_query(
                    array(
                        'msatoshi' => $msatoshi,
                        'description' => $description
                    )
                );
                $opts = array('http' =>
                    array(
                        'method'  => 'POST',
                        'header' => 'Authorization: Basic '.$auth,
                        'content' => $postdata
                    )
                );

                $context  = stream_context_create($opts);
                $ret = file_get_contents($wgLightningPaymentNodeUrl . "/invoice", false, $context);
                break;

            default:
                #clightning + lightning-charge by default
                global $wgLightningPaymentNodeUrl;
                global $wgLightningPaymentApiToken;
                $auth = base64_encode("api-token:$wgLightningPaymentApiToken");
                $postdata = http_build_query(
                    array(
                        'msatoshi' => $msatoshi,
                        'description' => $description
                    )
                );
                $opts = array('http' =>
                    array(
                        'method'  => 'POST',
                        'header' => 'Authorization: Basic '.$auth,
                        'content' => $postdata
                    )
                );

                $context  = stream_context_create($opts);
                $ret = file_get_contents($wgLightningPaymentNodeUrl . "/invoice", false, $context);
                break;
        }
        return json_decode($ret, true);
    }

    public static function generateLabel($wgUserId) {
        $rand = rand(0, 9999999);
        $label = 'wiki-pay|' . $wgUserId . '|' . $rand;
        return $label;
    }

    public static function getInvoice($wgUserId, $invoiceId) {
        global $wgLightningBackend;

        if (empty($invoiceId)) {
            $label = self::generateLabel($wgUserId);
        } else {
            $label = $invoiceId;
        }
        $desc = 'Anti-spam payment for wiki';

        if (!empty($invoiceId)) {
            $ret = self::getInvoiceByLabel($label);   
        }
        
        // || count($ret->result->invoices) == 0
        if (empty($ret) || $ret == "Not Found" || $ret == "" || $ret == "false") {
            $ret = self::createInvoice(1000, $label, $desc);
            // $bolt11 = $ret->result->bolt11;
            // $retjson = json_decode($ret, true);

            switch ($wgLightningBackend) {
                case 'lnbits':
                    $bolt11 = $ret["payment_request"];
                    // lnbits does not return expiry
                    // $expiry = 1609653136;
                    $id = $ret["checking_id"];
                    break;

                case 'lightningcharge':
                    $bolt11 = $ret["payreq"];
                    $expiry = $ret["expires_at"];
                    $id = $ret["id"];
                    break;

                default:
                    #clightning + lightning-charge by default
                    $bolt11 = $ret["payreq"];
                    $expiry = $ret["expires_at"];
                    $id = $ret["id"];
                    break;
            }

            // $bolt11 = $ret->result->payreq;
            // $expiry = $ret->result->expires_at;
            // $id = $ret->result->id;
            $status = 'unpaid';
        } else {
            // there's an existing invoice for this user!
            switch ($wgLightningBackend) {
                case 'lnbits':
                    $bolt11 = "existing lnbits invoice: ". $invoiceId;
                    // // lnbits does not return expiry
                    // $expiry = 1609653136;
                    // $id = $ret["checking_id"];
                    $status = $ret["paid"];
                    break;

                case 'lightningcharge':
                    $bolt11 = $ret["payreq"];
                    $expiry = $ret["expires_at"];
                    $id = $ret["id"];
                    break;

                default:
                    #clightning + lightning-charge by default
                    $bolt11 = $ret["payreq"];
                    $expiry = $ret["expires_at"];
                    $id = $ret["id"];
                    break;
            }            
        }
        //  else {
        //     // $bolt11 = $ret->result->invoices[0]->bolt11;
        //     $bolt11 = $ret->result->invoices[0]->payreq;
        //     $expiry = $ret->result->invoices[0]->expires_at;
        //     $id = $ret->result->invoices[0]->id;
        //     // $status = $ret->result->invoices[0]->status;
        //     $status = 'notunpaid';
        // }

        // if ($status == 'expired') {
        //     // Labels can't be recycled, so we need a new one.
        //     $label = self::generateLabel($wgUserId);
        //     $ret = self::createInvoice(1000, $label, $desc);
        //     // $bolt11 = $ret->result->bolt11;
        //     $bolt11 = $ret->result->payreq;
        //     $expiry = $ret->result->expires_at;
        //     $id = $ret->result->id;
        //     $status = 'expired';
        // }

        $invoice = [
            // 'expiry' => time_elapsed_string("@$expiry"),
            'expiry' => time_elapsed_string($expiry),
            // 'invoiceId' => $label,
            'invoiceId' => $id,
            'bolt11' => $bolt11,
            'status' => $status,
        ];

        return $invoice;
        // return $ret;
    }
}
