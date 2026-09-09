<?php
/**
 * Database schema and migrations.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_DB {

    public static function maybe_upgrade() {
        self::create_tables();

        $installed = get_option('dlab_db_version', '');
        if (version_compare((string) $installed, DLAB_VERSION, '>=')) {
            return;
        }
        update_option('dlab_db_version', DLAB_VERSION);
    }

    public static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        $basket = $wpdb->prefix . 'dlab_basket';
        dbDelta("CREATE TABLE $basket (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            guest_token varchar(64) NOT NULL DEFAULT '',
            object_id bigint(20) UNSIGNED NOT NULL,
            object_type varchar(32) NOT NULL,
            line_meta longtext DEFAULT NULL,
            added_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY owner_object (user_id, guest_token, object_id, object_type),
            KEY user_id (user_id),
            KEY guest_token (guest_token)
        ) $charset;");

        $meta = $wpdb->prefix . 'dlab_basket_meta';
        dbDelta("CREATE TABLE $meta (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            guest_token varchar(64) NOT NULL DEFAULT '',
            spots int(11) NOT NULL DEFAULT 1,
            meta longtext DEFAULT NULL,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY owner (user_id, guest_token)
        ) $charset;");

        $orders = $wpdb->prefix . 'dlab_orders';
        dbDelta("CREATE TABLE $orders (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            guest_token varchar(64) NOT NULL DEFAULT '',
            access_token varchar(64) NOT NULL DEFAULT '',
            order_number varchar(32) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            payment_method varchar(50) NOT NULL DEFAULT 'bank_transfer',
            total decimal(10,2) NOT NULL DEFAULT 0.00,
            discount decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(3) NOT NULL DEFAULT 'CZK',
            spots int(11) NOT NULL DEFAULT 1,
            contact_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            paid_at datetime DEFAULT NULL,
            expiry_notified_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY order_number (order_number),
            KEY user_id (user_id),
            KEY guest_token (guest_token),
            KEY access_token (access_token),
            KEY status (status)
        ) $charset;");

        $order_items = $wpdb->prefix . 'dlab_order_items';
        dbDelta("CREATE TABLE $order_items (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            object_id bigint(20) UNSIGNED NOT NULL,
            object_type varchar(32) NOT NULL,
            qty int(11) NOT NULL DEFAULT 1,
            unit_price decimal(10,2) NOT NULL DEFAULT 0.00,
            line_total decimal(10,2) NOT NULL DEFAULT 0.00,
            line_meta longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY object (object_id, object_type)
        ) $charset;");

        $spots = $wpdb->prefix . 'dlab_booking_spots';
        dbDelta("CREATE TABLE $spots (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            order_item_id bigint(20) UNSIGNED NOT NULL,
            object_id bigint(20) UNSIGNED NOT NULL,
            object_type varchar(32) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            spot_type varchar(16) NOT NULL DEFAULT 'regular',
            status varchar(16) NOT NULL DEFAULT 'held',
            attendee_index int(11) NOT NULL DEFAULT 0,
            attendee_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY object_status (object_id, object_type, status),
            KEY order_id (order_id),
            KEY user_id (user_id)
        ) $charset;");
    }

    public static function table_exists($table_suffix) {
        global $wpdb;
        $table = $wpdb->prefix . $table_suffix;
        $like  = $wpdb->esc_like($table);
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like)) === $table;
    }
}
