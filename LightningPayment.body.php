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
            $bolt11 = $ret->result->bolt11;
            $expiry = $ret->result->expires_at;
            $status = 'unpaid';
        } else {
            $bolt11 = $ret->result->invoices[0]->bolt11;
            $expiry = $ret->result->invoices[0]->expires_at;
            $status = $ret->result->invoices[0]->status;
        }

        if ($status == 'expired') {
            // Labels can't be recycled, so we need a new one.
            $label = self::generateLabel($wgUserId);
            $ret = self::createInvoice(1000, $label, $desc);
            $bolt11 = $ret->result->bolt11;
            $expiry = $ret->result->expires_at;
        }

        $invoice = [
            'expiry' => time_elapsed_string("@$expiry"),
            'invoiceId' => $label,
            'bolt11' => $bolt11,
            'status' => $status,
        ];

        return $invoice;
    }
}
