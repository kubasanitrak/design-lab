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
    }

    public function sanitize_checkbox($value) {
        return $value ? 1 : 0;
    }

    public function sanitize_position($value) {
        $value = sanitize_key($value);
        return $value === 'before' ? 'before' : 'after';
    }
}
