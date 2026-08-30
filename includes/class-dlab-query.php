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
            'field'           => '',
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
        $map = array(
            'age' => array(
                'tax'  => DLab_Post_Types::TAX_AGE,
                'get'  => self::GET_AGE,
                'slug' => $atts['age'],
            ),
            'field' => array(
                'tax'  => DLab_Post_Types::TAX_FIELD,
                'get'  => self::GET_FIELD,
                'slug' => $atts['field'],
            ),
        );

        $tax_query = array();
        foreach ($map as $row) {
            $slug = $use_url ? self::get_param($row['get'], $row['slug']) : sanitize_title($row['slug']);
            $slug = sanitize_title($slug);
            if ($slug === '') {
                continue;
            }
            $tax_query[] = array(
                'taxonomy' => $row['tax'],
                'field'    => 'slug',
                'terms'    => $slug,
            );
        }

        return $tax_query;
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
        return array(
            'age'   => self::get_param(self::GET_AGE, ''),
            'field' => self::get_param(self::GET_FIELD, ''),
        );
    }

    /**
     * Pills from current taxonomy terms (age + field).
     *
     * @return array<int, array{key:string,param:string,slug:string,label:string,active:bool}>
     */
    public static function get_filter_pills() {
        $active = self::get_active_filters();
        $pills  = array();

        $groups = array(
            array(
                'key'   => 'age',
                'param' => self::GET_AGE,
                'tax'   => DLab_Post_Types::TAX_AGE,
            ),
            array(
                'key'   => 'field',
                'param' => self::GET_FIELD,
                'tax'   => DLab_Post_Types::TAX_FIELD,
            ),
        );

        foreach ($groups as $group) {
            $terms = get_terms(array(
                'taxonomy'   => $group['tax'],
                'hide_empty' => false,
            ));
            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }
            foreach ($terms as $term) {
                $pills[] = array(
                    'key'    => $group['key'],
                    'param'  => $group['param'],
                    'slug'   => $term->slug,
                    'label'  => $term->name,
                    'active' => (($active[$group['key']] ?? '') === $term->slug),
                );
            }
        }

        return $pills;
    }

    public static function get_active_filter_query_args() {
        $map = array(
            self::GET_AGE   => self::get_param(self::GET_AGE, ''),
            self::GET_FIELD => self::get_param(self::GET_FIELD, ''),
        );
        $args = array();
        foreach ($map as $param => $slug) {
            if ($slug !== '') {
                $args[$param] = $slug;
            }
        }
        return $args;
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
