<?php
/**
 * Scheduled maintenance: expiring reservations.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Cron {

    const HOOK = 'dlab_hourly_cron';

    public function __construct() {
        add_action(self::HOOK, array($this, 'hourly_tasks'));
    }

    public static function schedule() {
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time(), 'hourly', self::HOOK);
        }
    }

    public static function unschedule() {
        wp_clear_scheduled_hook(self::HOOK);
    }

    public function hourly_tasks() {
        $this->send_expiry_notifications();
        $this->expire_old_orders();
    }

    private function send_expiry_notifications() {
        global $wpdb;

        if (!DLab_DB::table_exists('dlab_orders')) {
            return;
        }

        $table = $wpdb->prefix . 'dlab_orders';
        $now   = current_time('mysql');
        $soon  = wp_date('Y-m-d H:i:s', time() + (2 * HOUR_IN_SECONDS));

        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM $table
             WHERE status = 'awaiting_payment'
             AND expires_at IS NOT NULL
             AND expiry_notified_at IS NULL
             AND expires_at BETWEEN %s AND %s",
            $now,
            $soon
        ));

        if (!is_array($orders)) {
            return;
        }

        foreach ($orders as $row) {
            $order_id = (int) $row->id;
            DLab_Emails::send_expiry_notification($order_id);
            $wpdb->update(
                $table,
                array('expiry_notified_at' => current_time('mysql')),
                array('id' => $order_id),
                array('%s'),
                array('%d')
            );
        }
    }

    private function expire_old_orders() {
        global $wpdb;

        if (!DLab_DB::table_exists('dlab_orders')) {
            return;
        }

        $table = $wpdb->prefix . 'dlab_orders';
        $ids   = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $table
             WHERE status IN ('pending', 'awaiting_payment')
             AND expires_at IS NOT NULL
             AND expires_at < %s",
            current_time('mysql')
        ));

        if (!is_array($ids)) {
            return;
        }

        foreach ($ids as $order_id) {
            DLab_Checkout::update_order_status((int) $order_id, 'expired');
        }
    }
}
