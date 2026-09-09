<?php
/**
 * Plugin deactivation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Deactivator {

    public static function deactivate() {
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-cron.php';
        DLab_Cron::unschedule();
        flush_rewrite_rules();
    }
}
