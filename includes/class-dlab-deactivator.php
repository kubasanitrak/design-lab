<?php
/**
 * Plugin deactivation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Deactivator {

    public static function deactivate() {
        flush_rewrite_rules();
    }
}
