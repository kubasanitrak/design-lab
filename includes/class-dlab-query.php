<?php
/**
 * Build WP_Query for listings and shortcodes.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Query {

    const GET_AGE   = 'dlab_vek';
    const GET_FIELD = 'dlab_obor';

    /**
     * @param array $atts Shortcode attributes.
     * @return array WP_Query args.
     */
    public static function build_query_args($atts) {
        $atts = wp_parse_args($atts, array(
            'ids'             => '',
            'limit'           => 14,
            'orderby'         => 'workshop_date',
            'order'           => 'ASC',
            'only_open'       => '',
            'age'             => '',
            'use_url_filters' => 'true',
        ));

        $paged = (int) get_query_var('paged');
        if ($paged < 1) {
            $paged = (int) get_query_var('page');
        }
        $paged = max(1, $paged);

        $args = array(
            'post_type'      => DLab_Post_Types::POST_TYPE_WORKSHOP,
            'post_status'    => 'publish',
            'posts_per_page' => max(1, (int) $atts['limit']),
            'paged'          => $paged,
            'order'          => strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC',
            'no_found_rows'  => false,
        );

        if (!empty($atts['ids'])) {
            $ids = array_filter(array_map('intval', explode(',', $atts['ids'])));
            if ($ids) {
                $args['post__in'] = $ids;
                $args['orderby']  = 'post__in';
            }
        } elseif ($atts['orderby'] === 'workshop_date') {
            $args['meta_key']  = 'workshop_date';
            $args['orderby']   = 'meta_value';
            $args['meta_type'] = 'DATE';
        } else {
            $args['orderby'] = sanitize_key($atts['orderby']);
        }

        $use_url   = filter_var($atts['use_url_filters'], FILTER_VALIDATE_BOOLEAN);
        $tax_query = self::build_tax_query($atts, $use_url);
        if (!empty($tax_query)) {
            $args['tax_query'] = array_merge(array('relation' => 'AND'), $tax_query);
        }

        if (filter_var($atts['only_open'], FILTER_VALIDATE_BOOLEAN)) {
            $args = self::append_booking_open_meta($args);
        }

        return apply_filters('dlab_query_args', $args, $atts);
    }

    private static function build_tax_query($atts, $use_url) {
        $fallback = isset($atts['age']) ? $atts['age'] : '';
        $slug     = $use_url ? self::get_param(self::GET_AGE, $fallback) : sanitize_title($fallback);
        $slug     = sanitize_title($slug);
        if ($slug === '' || !in_array($slug, DLab_Post_Types::AGE_FILTER_SLUGS, true)) {
            return array();
        }

        return array(
            array(
                'taxonomy' => DLab_Post_Types::TAX_AGE,
                'field'    => 'slug',
                'terms'    => $slug,
            ),
        );
    }

    private static function get_param($key, $fallback) {
        if (isset($_GET[$key]) && $_GET[$key] !== '') {
            return sanitize_title(wp_unslash($_GET[$key]));
        }
        return sanitize_title($fallback);
    }

    private static function append_booking_open_meta($args) {
        $meta = isset($args['meta_query']) && is_array($args['meta_query']) ? $args['meta_query'] : array();
        $meta[] = array(
            'relation' => 'OR',
            array(
                'key'     => 'booking_open',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => 'booking_open',
                'value'   => '0',
                'compare' => '!=',
            ),
        );
        $args['meta_query'] = $meta;
        return $args;
    }

    public static function get_active_filters() {
        $slug = self::get_param(self::GET_AGE, '');
        if ($slug !== '' && !in_array($slug, DLab_Post_Types::AGE_FILTER_SLUGS, true)) {
            $slug = '';
        }
        return array(
            'age' => $slug,
        );
    }

    /**
     * Pills from age categories only (6+, 8+, 10+).
     *
     * @return array<int, array{key:string,param:string,slug:string,label:string,active:bool}>
     */
    public static function get_filter_pills() {
        $active = self::get_active_filters();
        $pills  = array();
        $terms  = get_terms(array(
            'taxonomy'   => DLab_Post_Types::TAX_AGE,
            'hide_empty' => false,
            'slug'       => DLab_Post_Types::AGE_FILTER_SLUGS,
        ));
        if (is_wp_error($terms) || empty($terms)) {
            return $pills;
        }

        $by_slug = array();
        foreach ($terms as $term) {
            $by_slug[$term->slug] = $term;
        }

        foreach (DLab_Post_Types::AGE_FILTER_SLUGS as $slug) {
            if (!isset($by_slug[$slug])) {
                continue;
            }
            $term    = $by_slug[$slug];
            $pills[] = array(
                'key'    => 'age',
                'param'  => self::GET_AGE,
                'slug'   => $term->slug,
                'label'  => $term->name,
                'active' => (($active['age'] ?? '') === $term->slug),
            );
        }

        return $pills;
    }

    public static function get_active_filter_query_args() {
        $active = self::get_active_filters();
        if ($active['age'] === '') {
            return array();
        }
        return array(self::GET_AGE => $active['age']);
    }

    public static function get_filter_base_url($page_url) {
        $args = self::get_active_filter_query_args();
        if (empty($args)) {
            return $page_url;
        }
        return add_query_arg($args, $page_url);
    }

    public static function get_filter_toggle_url($base_url, $param, $slug, $group_key) {
        $active = self::get_active_filters();
        if (($active[$group_key] ?? '') === $slug) {
            return remove_query_arg($param, $base_url);
        }
        return add_query_arg($param, $slug, $base_url);
    }

    public static function get_filter_reset_url($base_url) {
        return remove_query_arg(self::get_all_filter_params(), $base_url);
    }

    /**
     * @return string[]
     */
    public static function get_all_filter_params() {
        return array(self::GET_AGE, self::GET_FIELD);
    }
}
