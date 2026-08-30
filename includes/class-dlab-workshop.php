<?php
/**
 * Workshop display helpers.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Workshop {

    public static function get_type($post_id) {
        if (!function_exists('get_field')) {
            return DLab_Post_Types::TYPE_DESIGNLAB;
        }
        $type = get_field('workshop_type', $post_id);
        return $type ? sanitize_key($type) : DLab_Post_Types::TYPE_DESIGNLAB;
    }

    public static function get_type_label($post_id) {
        $type = self::get_type($post_id);
        if ($type === DLab_Post_Types::TYPE_DESIGNLAB) {
            return __('Design Lab', 'design-lab');
        }
        return __('Workshop', 'design-lab');
    }

    public static function is_booking_open($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return false;
        }
        if (!DLab_Post_Types::is_bookable_post_type($post->post_type)) {
            return false;
        }
        if (function_exists('get_field')) {
            $open = get_field('booking_open', $post_id);
            return !($open === false || $open === 0 || $open === '0');
        }
        return true;
    }

    public static function get_tile_image_id($post_id) {
        $thumb = get_post_thumbnail_id($post_id);
        if ($thumb) {
            return (int) $thumb;
        }
        if (function_exists('get_field')) {
            $place = get_field('place_photo', $post_id);
            if (is_array($place) && !empty($place['ID'])) {
                return (int) $place['ID'];
            }
            if (is_numeric($place)) {
                return (int) $place;
            }
        }
        return 0;
    }

    public static function get_workshop_date($post_id) {
        if (!function_exists('get_field')) {
            return '';
        }
        return (string) get_field('workshop_date', $post_id);
    }

    public static function get_time_from($post_id) {
        if (!function_exists('get_field')) {
            return '';
        }
        return (string) get_field('time_from', $post_id);
    }

    public static function get_time_to($post_id) {
        if (!function_exists('get_field')) {
            return '';
        }
        return (string) get_field('time_to', $post_id);
    }

    /**
     * Card date: 4/10/2026
     */
    public static function get_card_date($post_id) {
        $value = self::get_workshop_date($post_id);
        $ts    = $value !== '' ? strtotime($value) : false;
        if (!$ts) {
            return '';
        }
        return sprintf('%d/%d/%d', (int) date('j', $ts), (int) date('n', $ts), (int) date('Y', $ts));
    }

    /**
     * Detail / listing schedule: 4. 10. 2026 · 10.00–13.00
     */
    public static function get_schedule_summary($post_id) {
        $date = self::format_date(self::get_workshop_date($post_id));
        $from = self::format_time(self::get_time_from($post_id));
        $to   = self::format_time(self::get_time_to($post_id));
        if ($date === '') {
            return '';
        }
        if ($from && $to) {
            return sprintf('%s · %s–%s', $date, $from, $to);
        }
        if ($from) {
            return $date . ' · ' . $from;
        }
        return $date;
    }

    public static function format_date($value) {
        if ($value === '' || $value === null) {
            return '';
        }
        $ts = strtotime($value);
        return $ts ? date_i18n('j. n. Y', $ts) : (string) $value;
    }

    public static function format_time($value) {
        if ($value === '' || $value === null) {
            return '';
        }
        return str_replace(':', '.', (string) $value);
    }

    public static function format_price($amount) {
        $formatted = number_format_i18n((float) $amount, 0);
        $symbol    = DLab_Settings::currency_symbol();
        if (DLab_Settings::currency_position() === 'before') {
            return $symbol . ' ' . $formatted;
        }
        return $formatted . ' ' . $symbol;
    }

    public static function get_price_per_person($post_id) {
        if (!function_exists('get_field')) {
            return null;
        }
        $price = get_field('price_per_person', $post_id);
        if ($price === '' || $price === null) {
            return null;
        }
        return (float) $price;
    }

    public static function get_pass_price($post_id) {
        if (!function_exists('get_field')) {
            return null;
        }
        $price = get_field('pass_price', $post_id);
        if ($price === '' || $price === null) {
            return null;
        }
        return (float) $price;
    }

    public static function get_price_label($post_id) {
        $price = self::get_price_per_person($post_id);
        if ($price === null) {
            return '';
        }
        return self::format_price($price);
    }

    public static function get_pass_price_label($post_id) {
        $price = self::get_pass_price($post_id);
        if ($price === null) {
            return '';
        }
        return self::format_price($price);
    }

    public static function get_short_info($post_id) {
        $excerpt = get_the_excerpt($post_id);
        if (is_string($excerpt) && trim($excerpt) !== '') {
            return trim($excerpt);
        }
        if (function_exists('get_field')) {
            $synopsis = get_field('synopsis', $post_id);
            if (is_string($synopsis) && trim($synopsis) !== '') {
                return trim($synopsis);
            }
        }
        return '';
    }

    /**
     * First age-group term, e.g. 8+.
     */
    public static function get_age_label($post_id) {
        $terms = get_the_terms($post_id, DLab_Post_Types::TAX_AGE);
        if (!is_array($terms) || empty($terms)) {
            return '';
        }
        $term = reset($terms);
        return $term && !is_wp_error($term) ? $term->name : '';
    }

    /**
     * Occupancy for grid cards. Used counts come from booking tables (Phase 4).
     *
     * @return array{regular_limit:int,regular_used:int,alternate_limit:int,alternate_used:int,status:string,label:string}
     */
    public static function get_occupancy($post_id) {
        $regular_limit   = 0;
        $alternate_limit = 0;
        if (function_exists('get_field')) {
            $regular_limit   = (int) get_field('capacity_regular', $post_id);
            $alternate_limit = (int) get_field('capacity_alternate', $post_id);
        }

        $regular_used   = (int) apply_filters('dlab_occupancy_regular_used', 0, $post_id);
        $alternate_used = (int) apply_filters('dlab_occupancy_alternate_used', 0, $post_id);

        $regular_left   = $regular_limit > 0 ? max(0, $regular_limit - $regular_used) : null;
        $alternate_left = $alternate_limit > 0 ? max(0, $alternate_limit - $alternate_used) : 0;

        if ($regular_limit === 0) {
            $status = 'open';
            $label  = __('Volná místa', 'design-lab');
        } elseif ($regular_left > 0) {
            $status = 'open';
            $label  = sprintf(
                /* translators: %d: remaining regular spots */
                _n('%d volné místo', '%d volných míst', $regular_left, 'design-lab'),
                $regular_left
            );
        } elseif ($alternate_left > 0) {
            $status = 'alternate';
            $label  = __('Náhradníci', 'design-lab');
        } else {
            $status = 'full';
            $label  = __('Obsazeno', 'design-lab');
        }

        return array(
            'regular_limit'   => $regular_limit,
            'regular_used'    => $regular_used,
            'alternate_limit' => $alternate_limit,
            'alternate_used'  => $alternate_used,
            'status'          => $status,
            'label'           => $label,
        );
    }

    /**
     * @return WP_Term[]
     */
    public static function get_filter_terms($post_id) {
        $terms = array();
        foreach (array(DLab_Post_Types::TAX_AGE, DLab_Post_Types::TAX_FIELD) as $tax) {
            $post_terms = get_the_terms($post_id, $tax);
            if (is_array($post_terms)) {
                $terms = array_merge($terms, $post_terms);
            }
        }
        return $terms;
    }

    public static function render_tags($post_id, $args = array()) {
        $terms = self::get_filter_terms($post_id);
        if (empty($terms)) {
            return '';
        }

        $args = wp_parse_args($args, array(
            'class' => 'dlab-tags',
            'link'  => false,
        ));

        ob_start();
        echo '<ul class="' . esc_attr($args['class']) . '">';
        foreach ($terms as $term) {
            echo '<li>';
            if ($args['link']) {
                $listing = DLab_Settings::listing_page_url();
                $param   = $term->taxonomy === DLab_Post_Types::TAX_AGE
                    ? DLab_Query::GET_AGE
                    : DLab_Query::GET_FIELD;
                echo '<a href="' . esc_url(add_query_arg($param, $term->slug, $listing)) . '">' . esc_html($term->name) . '</a>';
            } else {
                echo esc_html($term->name);
            }
            echo '</li>';
        }
        echo '</ul>';
        return ob_get_clean();
    }

    /**
     * Instructor post IDs (theme CPT `instructor`).
     *
     * @return int[]
     */
    public static function get_instructor_ids($post_id) {
        if (!function_exists('get_field')) {
            return array();
        }
        $instructors = get_field('instructors', $post_id);
        if (empty($instructors)) {
            return array();
        }
        $ids = is_array($instructors) ? $instructors : array($instructors);
        return array_values(array_filter(array_map('intval', $ids)));
    }

    public static function get_optional_services($post_id) {
        if (!function_exists('get_field')) {
            return array();
        }
        $rows = get_field('optional_services', $post_id);
        return is_array($rows) ? $rows : array();
    }
}
