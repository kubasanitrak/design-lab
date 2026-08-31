<?php
/**
 * Plugin settings and feature flags.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Settings {

    const OPT_PASS_MIN_WORKSHOPS    = 'dlab_pass_min_workshops';
    const OPT_PASS_SHARED_HEADCOUNT = 'dlab_pass_shared_headcount';
    const OPT_CURRENCY_CODE         = 'dlab_currency_code';
    const OPT_CURRENCY_SYMBOL       = 'dlab_currency_symbol';
    const OPT_CURRENCY_POSITION     = 'dlab_currency_position';
    const OPT_LISTING_PAGE          = 'dlab_listing_page';
    const OPT_PASS_PAGE             = 'dlab_pass_page';
    const OPT_GDPR_PAGE             = 'dlab_gdpr_page';
    const OPT_TERMS_PAGE            = 'dlab_terms_page';

    const OPT_BANK_ACCOUNT_NAME     = 'dlab_bank_account_name';
    const OPT_BANK_ACCOUNT_NUMBER   = 'dlab_bank_account_number';
    const OPT_BANK_CODE             = 'dlab_bank_code';
    const OPT_BANK_IBAN             = 'dlab_bank_iban';
    const OPT_BANK_BIC              = 'dlab_bank_bic';
    const OPT_INVOICE_SEQUENCE_START   = 'dlab_invoice_sequence_start';
    const OPT_INVOICE_SEQUENCE_CURRENT = 'dlab_invoice_sequence_current';

    const OPT_RESERVATION_EXPIRY_HOURS = 'dlab_reservation_expiry_hours';
    const OPT_CANCEL_HOURS_BEFORE_START = 'dlab_cancel_hours_before_start';

    const OPT_EMAIL_SENDER_NAME            = 'dlab_email_sender_name';
    const OPT_EMAIL_SENDER_EMAIL           = 'dlab_email_sender_email';
    const OPT_EMAIL_TEMPLATE_TYPE         = 'dlab_email_template_type';
    const OPT_ADMIN_NOTIFICATION_ENABLED = 'dlab_admin_notification_enabled';
    const OPT_ADMIN_NOTIFICATION_EMAIL    = 'dlab_admin_notification_email';

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    public static function defaults() {
        return array(
            self::OPT_PASS_MIN_WORKSHOPS    => 2,
            self::OPT_PASS_SHARED_HEADCOUNT => 1,
            self::OPT_CURRENCY_CODE         => 'CZK',
            self::OPT_CURRENCY_SYMBOL       => 'Kč',
            self::OPT_CURRENCY_POSITION     => 'after',
            self::OPT_LISTING_PAGE          => 0,
            self::OPT_PASS_PAGE             => 0,
            self::OPT_GDPR_PAGE             => 0,
            self::OPT_TERMS_PAGE            => 0,
            self::OPT_BANK_ACCOUNT_NAME     => '',
            self::OPT_BANK_ACCOUNT_NUMBER   => '',
            self::OPT_BANK_CODE             => '',
            self::OPT_BANK_IBAN             => '',
            self::OPT_BANK_BIC              => '',
            self::OPT_INVOICE_SEQUENCE_START => 1,
            self::OPT_RESERVATION_EXPIRY_HOURS    => 72,
            self::OPT_CANCEL_HOURS_BEFORE_START    => 24,
            self::OPT_EMAIL_SENDER_NAME            => '',
            self::OPT_EMAIL_SENDER_EMAIL           => '',
            self::OPT_EMAIL_TEMPLATE_TYPE         => 'html',
            self::OPT_ADMIN_NOTIFICATION_ENABLED => 1,
            self::OPT_ADMIN_NOTIFICATION_EMAIL    => '',
        );
    }

    public static function ensure_defaults() {
        foreach (self::defaults() as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }

    /**
     * Minimum unique workshops in the basket for Design Lab pass pricing.
     */
    public static function pass_min_workshops() {
        $min = (int) get_option(self::OPT_PASS_MIN_WORKSHOPS, 2);
        return max(2, (int) apply_filters('dlab_pass_min_workshops', $min));
    }

    /**
     * One attendee headcount for the whole pass (not per workshop).
     */
    public static function pass_shared_headcount() {
        $shared = (bool) get_option(self::OPT_PASS_SHARED_HEADCOUNT, 1);
        return (bool) apply_filters('dlab_pass_shared_headcount', $shared);
    }

    public static function currency_code() {
        $code = strtoupper((string) get_option(self::OPT_CURRENCY_CODE, 'CZK'));
        return $code !== '' ? $code : 'CZK';
    }

    public static function currency_symbol() {
        $symbol = (string) get_option(self::OPT_CURRENCY_SYMBOL, 'Kč');
        return $symbol !== '' ? $symbol : 'Kč';
    }

    public static function currency_position() {
        $position = (string) get_option(self::OPT_CURRENCY_POSITION, 'after');
        return $position === 'before' ? 'before' : 'after';
    }

    public static function listing_page_id() {
        $opt = (int) get_option(self::OPT_LISTING_PAGE, 0);
        if ($opt) {
            return $opt;
        }
        $ids = get_option('dlab_page_ids', array());
        if (is_array($ids) && !empty($ids['listing'])) {
            return (int) $ids['listing'];
        }
        return 0;
    }

    public static function listing_page_url() {
        $id = self::listing_page_id();
        if ($id) {
            $url = get_permalink($id);
            if ($url) {
                return $url;
            }
        }
        return home_url('/design-lab/');
    }

    public static function pass_page_id() {
        $opt = (int) get_option(self::OPT_PASS_PAGE, 0);
        if ($opt) {
            return $opt;
        }
        $ids = get_option('dlab_page_ids', array());
        if (is_array($ids) && !empty($ids['pass'])) {
            return (int) $ids['pass'];
        }
        return 0;
    }

    public static function pass_page_url() {
        $id = self::pass_page_id();
        if ($id) {
            $url = get_permalink($id);
            if ($url) {
                return $url;
            }
        }
        return home_url('/pass/');
    }

    /**
     * Bank details for QR platba (Paylibo) and payment recap.
     *
     * @return array{account_name:string,account_number:string,bank_code:string,iban:string,bic:string,account_full:string}
     */
    public static function bank() {
        $number = (string) get_option(self::OPT_BANK_ACCOUNT_NUMBER, '');
        $code   = (string) get_option(self::OPT_BANK_CODE, '');
        $full   = ($number !== '' && $code !== '') ? ($number . '/' . $code) : '';

        return array(
            'account_name'   => (string) get_option(self::OPT_BANK_ACCOUNT_NAME, ''),
            'account_number' => $number,
            'bank_code'      => $code,
            'iban'           => (string) get_option(self::OPT_BANK_IBAN, ''),
            'bic'            => (string) get_option(self::OPT_BANK_BIC, ''),
            'account_full'   => $full,
        );
    }

    /**
     * Account number + bank code are required for Czech SPAYD QR.
     */
    public static function bank_ready_for_qr() {
        $bank = self::bank();
        return $bank['account_number'] !== '' && $bank['bank_code'] !== '';
    }

    public static function invoice_sequence_start() {
        return max(1, (int) get_option(self::OPT_INVOICE_SEQUENCE_START, 1));
    }

    /**
     * Hours a pending reservation stays valid (awaiting payment).
     */
    public static function reservation_expiry_hours() {
        $hours = (int) get_option(self::OPT_RESERVATION_EXPIRY_HOURS, 72);
        return max(1, (int) apply_filters('dlab_reservation_expiry_hours', $hours));
    }

    /**
     * Hours before workshop start when a member may still cancel.
     */
    public static function cancel_hours_before_start() {
        $hours = (int) get_option(self::OPT_CANCEL_HOURS_BEFORE_START, 24);
        return max(0, (int) apply_filters('dlab_cancel_hours_before_start', $hours));
    }

    public static function email_sender_name() {
        $name = (string) get_option(self::OPT_EMAIL_SENDER_NAME, '');
        if ($name === '') {
            $name = (string) get_bloginfo('name');
        }
        return $name;
    }

    public static function email_sender_email() {
        $email = (string) get_option(self::OPT_EMAIL_SENDER_EMAIL, '');
        if ($email === '' || !is_email($email)) {
            $email = (string) get_option('admin_email');
        }
        return $email;
    }

    /**
     * @return string 'html'|'plain'
     */
    public static function email_template_type() {
        $type = (string) get_option(self::OPT_EMAIL_TEMPLATE_TYPE, 'html');
        return $type === 'plain' ? 'plain' : 'html';
    }

    public static function admin_notification_enabled() {
        return (bool) get_option(self::OPT_ADMIN_NOTIFICATION_ENABLED, 1);
    }

    public static function admin_notification_email() {
        $email = (string) get_option(self::OPT_ADMIN_NOTIFICATION_EMAIL, '');
        if ($email === '' || !is_email($email)) {
            $email = (string) get_option('admin_email');
        }
        return $email;
    }

    /**
     * Next document number without consuming the sequence (admin preview).
     * Format: YY-##### (e.g. 26-00001).
     */
    public static function peek_next_invoice_number() {
        return self::format_invoice_number(self::next_sequence_value());
    }

    /**
     * Consume and return the next document number (checkout / invoices).
     */
    public static function next_invoice_number() {
        $current = self::next_sequence_value();
        update_option(self::OPT_INVOICE_SEQUENCE_CURRENT, $current + 1);
        return self::format_invoice_number($current);
    }

    /**
     * Digits-only variable symbol (max 10) from an invoice / order number.
     */
    public static function variable_symbol($number) {
        return substr(preg_replace('/\D/', '', (string) $number), 0, 10);
    }

    public function register_settings() {
        $ints = array(
            self::OPT_PASS_MIN_WORKSHOPS,
            self::OPT_LISTING_PAGE,
            self::OPT_PASS_PAGE,
            self::OPT_GDPR_PAGE,
            self::OPT_TERMS_PAGE,
        );
        foreach ($ints as $option) {
            register_setting('dlab_settings', $option, array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ));
        }

        register_setting('dlab_settings', self::OPT_PASS_SHARED_HEADCOUNT, array(
            'type'              => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        register_setting('dlab_settings', self::OPT_CURRENCY_CODE, array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('dlab_settings', self::OPT_CURRENCY_SYMBOL, array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('dlab_settings', self::OPT_CURRENCY_POSITION, array(
            'type'              => 'string',
            'sanitize_callback' => array($this, 'sanitize_position'),
        ));
        register_setting('dlab_settings', self::OPT_BANK_ACCOUNT_NAME, array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('dlab_settings', self::OPT_BANK_ACCOUNT_NUMBER, array(
            'type'              => 'string',
            'sanitize_callback' => array($this, 'sanitize_account_number'),
        ));
        register_setting('dlab_settings', self::OPT_BANK_CODE, array(
            'type'              => 'string',
            'sanitize_callback' => array($this, 'sanitize_bank_code'),
        ));
        register_setting('dlab_settings', self::OPT_BANK_IBAN, array(
            'type'              => 'string',
            'sanitize_callback' => array($this, 'sanitize_iban'),
        ));
        register_setting('dlab_settings', self::OPT_BANK_BIC, array(
            'type'              => 'string',
            'sanitize_callback' => array($this, 'sanitize_bic'),
        ));
        register_setting('dlab_settings', self::OPT_INVOICE_SEQUENCE_START, array(
            'type'              => 'integer',
            'sanitize_callback' => array($this, 'sanitize_sequence_start'),
        ));
        register_setting('dlab_settings', self::OPT_RESERVATION_EXPIRY_HOURS, array(
            'type'              => 'integer',
            'sanitize_callback' => array($this, 'sanitize_hours_min_one'),
        ));
        register_setting('dlab_settings', self::OPT_CANCEL_HOURS_BEFORE_START, array(
            'type'              => 'integer',
            'sanitize_callback' => array($this, 'sanitize_hours_min_zero'),
        ));
        register_setting('dlab_settings', self::OPT_EMAIL_SENDER_NAME, array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('dlab_settings', self::OPT_EMAIL_SENDER_EMAIL, array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_email',
        ));
        register_setting('dlab_settings', self::OPT_EMAIL_TEMPLATE_TYPE, array(
            'type'              => 'string',
            'sanitize_callback' => array($this, 'sanitize_email_template_type'),
        ));
        register_setting('dlab_settings', self::OPT_ADMIN_NOTIFICATION_ENABLED, array(
            'type'              => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        register_setting('dlab_settings', self::OPT_ADMIN_NOTIFICATION_EMAIL, array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_email',
        ));
    }

    public function sanitize_checkbox($value) {
        return $value ? 1 : 0;
    }

    public function sanitize_position($value) {
        $value = sanitize_key($value);
        return $value === 'before' ? 'before' : 'after';
    }

    public function sanitize_account_number($value) {
        $value = preg_replace('/\s+/', '', (string) $value);
        return sanitize_text_field($value);
    }

    public function sanitize_bank_code($value) {
        $digits = preg_replace('/\D/', '', (string) $value);
        if ($digits === '') {
            return '';
        }
        return substr(str_pad($digits, 4, '0', STR_PAD_LEFT), -4);
    }

    public function sanitize_iban($value) {
        $value = strtoupper(preg_replace('/\s+/', '', (string) $value));
        $value = preg_replace('/[^A-Z0-9]/', '', $value);
        return substr($value, 0, 34);
    }

    public function sanitize_bic($value) {
        $value = strtoupper(preg_replace('/\s+/', '', (string) $value));
        $value = preg_replace('/[^A-Z0-9]/', '', $value);
        return substr($value, 0, 11);
    }

    public function sanitize_sequence_start($value) {
        return max(1, (int) $value);
    }

    public function sanitize_hours_min_one($value) {
        return max(1, (int) $value);
    }

    public function sanitize_hours_min_zero($value) {
        return max(0, (int) $value);
    }

    public function sanitize_email_template_type($value) {
        $value = sanitize_key($value);
        return $value === 'plain' ? 'plain' : 'html';
    }

    private static function next_sequence_value() {
        $start   = self::invoice_sequence_start();
        $current = get_option(self::OPT_INVOICE_SEQUENCE_CURRENT, false);
        if ($current === false) {
            return $start;
        }
        return max((int) $current, $start);
    }

    private static function format_invoice_number($sequence) {
        return gmdate('y') . '-' . str_pad((string) (int) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
