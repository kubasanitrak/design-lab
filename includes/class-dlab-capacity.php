<?php
/**
 * Capacity (regular / alternate). Order holds land in Phase 4.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Capacity {

    const SPOT_REGULAR   = 'regular';
    const SPOT_ALTERNATE = 'alternate';

    const STATUS_HELD      = 'held';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';

    public function __construct() {
        add_filter('dlab_occupancy_regular_used', array($this, 'filter_regular_used'), 10, 2);
        add_filter('dlab_occupancy_alternate_used', array($this, 'filter_alternate_used'), 10, 2);
    }

    public function filter_regular_used($used, $post_id) {
        return self::count_spots($post_id, self::SPOT_REGULAR);
    }

    public function filter_alternate_used($used, $post_id) {
        return self::count_spots($post_id, self::SPOT_ALTERNATE);
    }

    public static function get_limits($post_id) {
        $regular   = 0;
        $alternate = 0;
        if (function_exists('get_field')) {
            $regular   = (int) get_field('capacity_regular', $post_id);
            $alternate = (int) get_field('capacity_alternate', $post_id);
        }
        return array(
            'regular'   => $regular,
            'alternate' => $alternate,
        );
    }

    /**
     * Count held + confirmed spots (non-expired holds).
     */
    public static function count_spots($post_id, $spot_type = self::SPOT_REGULAR) {
        global $wpdb;

        if (!DLab_DB::table_exists('dlab_booking_spots') || !DLab_DB::table_exists('dlab_orders')) {
            return 0;
        }

        $table      = $wpdb->prefix . 'dlab_booking_spots';
        $orders     = $wpdb->prefix . 'dlab_orders';
        $post_type  = get_post_type($post_id);

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table s
             INNER JOIN $orders o ON s.order_id = o.id
             WHERE s.object_id = %d AND s.object_type = %s AND s.spot_type = %s
             AND s.status IN ('held', 'confirmed')
             AND o.status NOT IN ('cancelled', 'expired', 'failed')
             AND (s.status = 'confirmed' OR o.expires_at IS NULL OR o.expires_at > %s)",
            $post_id,
            $post_type,
            $spot_type,
            current_time('mysql')
        ));
    }

    /**
     * @return array{spot_type:string, available:int}|WP_Error
     */
    public static function resolve_spot_type($post_id, $spots_needed) {
        $spots_needed = (int) $spots_needed;
        $limits       = self::get_limits($post_id);
        $regular_used = self::count_spots($post_id, self::SPOT_REGULAR);

        if ($limits['regular'] === 0 || ($regular_used + $spots_needed) <= $limits['regular']) {
            return array(
                'spot_type' => self::SPOT_REGULAR,
                'available' => $limits['regular'] === 0 ? PHP_INT_MAX : max(0, $limits['regular'] - $regular_used),
            );
        }

        if ($limits['alternate'] > 0) {
            $alt_used = self::count_spots($post_id, self::SPOT_ALTERNATE);
            if (($alt_used + $spots_needed) <= $limits['alternate']) {
                return array(
                    'spot_type' => self::SPOT_ALTERNATE,
                    'available' => max(0, $limits['alternate'] - $alt_used),
                );
            }
        }

        return new WP_Error('dlab_full', __('Kapacita je naplněna.', 'design-lab'));
    }

    /**
     * @return array{spot_type:string, available:int}|WP_Error
     */
    public static function can_reserve($post_id, $spots) {
        $spots = (int) $spots;
        if ($spots < 1) {
            return new WP_Error('dlab_invalid_spots', __('Neplatný počet míst.', 'design-lab'));
        }
        return self::resolve_spot_type($post_id, $spots);
    }

    /**
     * Shared headcount must fit every workshop in the pass.
     *
     * @param int[] $post_ids
     * @return true|WP_Error
     */
    public static function can_reserve_pass(array $post_ids, $spots) {
        foreach ($post_ids as $post_id) {
            $post_id = (int) $post_id;
            if (!$post_id) {
                continue;
            }
            $check = self::can_reserve($post_id, $spots);
            if (is_wp_error($check)) {
                $title = get_the_title($post_id);
                return new WP_Error(
                    $check->get_error_code(),
                    sprintf(
                        /* translators: %s: workshop title */
                        __('Workshop „%s“ nemá volnou kapacitu pro tento počet účastníků.', 'design-lab'),
                        $title
                    )
                );
            }
        }
        return true;
    }
}
