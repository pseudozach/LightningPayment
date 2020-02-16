<?php

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
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

    public static function mtgox_check_post() {
        // API settings
        $key = 'your_key';
        $secret = 'your_secret';

        if ($_SERVER['HTTP_REST_KEY'] != $key)
            return false;
        $post_data = file_get_contents('php://input');
        $hash = hash_hmac('sha512', $post_data, base64_decode($secret), true);
        if (base64_decode($_SERVER['HTTP_REST_SIGN']) != $hash)
            return false;

        return true;
    }

    public static function getInvoiceByLabel($label) {
        global $wgLightningPaymentNodeUrl;
        $params = http_build_query([
            "method" => "listinvoices",
            "label" => $label,
        ]);

        $ret = file_get_contents($wgLightningPaymentNodeUrl . "/?" . $params);
        return json_decode($ret);
    }

    public static function createInvoice($msatoshi, $label, $description) {
        global $wgLightningPaymentNodeUrl;
        $params = http_build_query([
            "method" => "invoice",
            "msatoshi" => $msatoshi,
            "label" => $label,
            "description" => $description,
        ]);

        $ret = file_get_contents($wgLightningPaymentNodeUrl . "/?" . $params);
        return json_decode($ret);
    }

    public static function generateLabel($wgUserId) {
        $rand = rand(0, 9999999);
        $label = 'wiki-pay|' . $wgUserId . '|' . $rand;
        return $label;
    }

    public static function getInvoice($wgUserId, $invoiceId) {
        if (empty($invoiceId)) {
            $label = self::generateLabel($wgUserId);
        } else {
            $label = $invoiceId;
        }
        $desc = 'Anti-spam payment for wiki';

        $ret = self::getInvoiceByLabel($label);

        if (count($ret->result->invoices) == 0) {
            $ret = self::createInvoice(1000, $label, $desc);
//			var_dump($ret);
            $bolt11 = $ret->result->bolt11;
            $paid = false;
            $expiry = $ret->result->expires_at;
            $expired = false;
            $status = 'unpaid';
        } else {
//			var_dump($ret);
            $bolt11 = $ret->result->invoices[0]->bolt11;
            $paid = $ret->result->invoices[0]->status == 'paid';
            $expiry = $ret->result->invoices[0]->expires_at;
            $expired = $ret->result->invoices[0]->status == 'expired';
            $status = $ret->result->invoices[0]->status;
        }

        if ($expired) {
            $label = self::generateLabel($wgUserId);
            $ret = self::createInvoice(1000, $label, $desc);
//var_dump($ret);
            $bolt11 = $ret->result->bolt11;
            $paid = false;
            $expiry = $ret->result->expires_at;
            $expired = false;
        }

        $invoice = [
            'expiry' => time_elapsed_string("@$expiry"),
            'invoiceId' => $label,
            'bolt11' => $bolt11,
            'status' => $status,
        ];

        return $invoice;

        var_dump(date("Y-m-d H:i:s", $expiry));
        var_dump(date("Y-m-d H:i:s"));
        var_dump(date_default_timezone_get());
        var_dump($invoice);
        echo "X";
        die;
    }

    public static function mtgox_query($path, array $req = array()) {
        // API settings
        $key = 'your_key';
        $secret = 'your_secret';

        // generate a nonce as microtime, with as-string handling to avoid problems with 32bits systems
        $mt = explode(' ', microtime());
        $req['nonce'] = $mt[1] . substr($mt[0], 2, 6);

        // generate the POST data string
        $post_data = http_build_query($req, '', '&');

        $prefix = '';
        if (substr($path, 0, 2) == '2/') {
            $prefix = substr($path, 2) . "\0";
        }

        // generate the extra headers
        $headers = array(
            'Rest-Key: ' . $key,
            'Rest-Sign: ' . base64_encode(hash_hmac('sha512', $prefix . $post_data, base64_decode($secret), true)),
        );

        // our curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MtGox PHP client; ' . php_uname('s') . '; PHP/' . phpversion() . ')');
        }
        curl_setopt($ch, CURLOPT_URL, 'https://mtgox.com/api/' . $path);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        // run the query
        $res = curl_exec($ch);
        if ($res === false)
            throw new Exception('Could not get reply: ' . curl_error($ch));
        $dec = json_decode($res, true);
        if (!$dec)
            throw new Exception('Invalid data received, please make sure connection is working and requested API exists');
        return $dec;
    }

}
