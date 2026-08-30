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
    }

    public static function table_exists($table_suffix) {
        global $wpdb;
        $table = $wpdb->prefix . $table_suffix;
        $like  = $wpdb->esc_like($table);
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like)) === $table;
    }
}
