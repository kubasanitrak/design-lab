<?php
/**
 * QR platby (Paylibo SPAYD) pro český bankovní převod.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_QR {

    private $api_url = 'https://api.paylibo.com/paylibo/generator/czech/image';

    public function generate_payment_qr($amount, $currency = 'CZK', $variable_symbol = '', $message = '', $size = 250) {
        $bank = DLab_Settings::bank();
        if ($bank['account_number'] === '' || $bank['bank_code'] === '') {
            return null;
        }

        $vs      = DLab_Settings::variable_symbol($variable_symbol);
        $account = $this->parse_account_number($bank['account_number']);

        $params = array(
            'accountNumber' => $account['base'],
            'bankCode'      => $this->sanitize_bank_code($bank['bank_code']),
            'amount'        => number_format((float) $amount, 2, '.', ''),
            'currency'      => strtoupper($currency ?: DLab_Settings::currency_code()),
            'size'          => (int) $size,
        );

        if ($account['prefix'] !== '') {
            $params['accountPrefix'] = $account['prefix'];
        }

        if ($vs !== '') {
            $params['vs'] = $vs;
        }

        if ($message !== '') {
            $params['message'] = $this->sanitize_message($message);
        }

        return add_query_arg($params, $this->api_url);
    }

    public function generate_order_qr($order, $size = 250) {
        if (!$order) {
            return null;
        }

        $message = __('Místo držíme. Jakmile platbu přijmeme, pošleme potvrzení.', 'design-lab');
        $vs      = DLab_Checkout::variable_symbol($order);

        return $this->generate_payment_qr(
            $order->total,
            $order->currency ?: DLab_Settings::currency_code(),
            $vs,
            $message,
            $size
        );
    }

    public function render_qr_html($amount, $variable_symbol = '', $size = 250, $message = '') {
        $url = $this->generate_payment_qr($amount, DLab_Settings::currency_code(), $variable_symbol, $message, $size);

        if (!$url) {
            return '<p class="dlab-qr-error">' . esc_html__('QR kód nelze vygenerovat. Použijte údaje výše.', 'design-lab') . '</p>';
        }

        return sprintf(
            '<div class="dlab-qr-code"><img src="%s" alt="%s" width="%d" height="%d" loading="lazy"></div>',
            esc_url($url),
            esc_attr__('QR platba', 'design-lab'),
            (int) $size,
            (int) $size
        );
    }

    private function parse_account_number($account_number) {
        $account_number = preg_replace('/\s+/', '', (string) $account_number);

        if (strpos($account_number, '/') !== false) {
            $account_number = explode('/', $account_number, 2)[0];
        }

        $prefix = '';
        $base   = $account_number;

        if (strpos($account_number, '-') !== false) {
            list($prefix, $base) = explode('-', $account_number, 2);
            $prefix = ltrim($prefix, '0');
        }

        $base = ltrim($base, '0');
        if ($base === '') {
            $base = '0';
        }

        return array(
            'prefix' => $prefix,
            'base'   => $base,
        );
    }

    private function sanitize_bank_code($bank_code) {
        return str_pad(preg_replace('/\D/', '', (string) $bank_code), 4, '0', STR_PAD_LEFT);
    }

    private function sanitize_message($message) {
        $converted = function_exists('iconv')
            ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $message)
            : false;
        $message = $converted !== false ? $converted : (string) $message;
        $message = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $message);
        return substr(trim($message), 0, 60);
    }
}
