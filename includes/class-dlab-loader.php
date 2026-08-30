<?php
/**
 * Plugin loader.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Loader {

    public function __construct() {
        $this->load_dependencies();
    }

    private function load_dependencies() {
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-activator.php';
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-settings.php';
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-post-types.php';
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-acf.php';
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-workshop.php';
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-query.php';
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-db.php';
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-capacity.php';
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-pricing.php';
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-basket.php';
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-shortcodes.php';

        if (is_admin()) {
            require_once DLAB_PLUGIN_DIR . 'admin/class-dlab-admin.php';
            require_once DLAB_PLUGIN_DIR . 'admin/class-dlab-admin-settings.php';
        }

        require_once DLAB_PLUGIN_DIR . 'public/class-dlab-public.php';
    }

    public function run() {
        DLab_Settings::ensure_defaults();
        DLab_DB::maybe_upgrade();
        add_action('init', array('DLab_Activator', 'maybe_create_pages'), 15);

        new DLab_Post_Types();
        new DLab_Settings();
        new DLab_Capacity();
        new DLab_Basket();
        new DLab_Shortcodes();

        if (class_exists('ACF')) {
            new DLab_ACF();
        }

        if (is_admin()) {
            new DLab_Admin();
            new DLab_Admin_Settings();
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }

        new DLab_Public();
    }

    public function enqueue_admin_assets($hook) {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $allowed = array(DLab_Admin::MENU_SLUG, 'dlab-settings');
        if (!in_array($page, $allowed, true) && strpos($hook, 'dlab') === false) {
            return;
        }
        wp_enqueue_style('dlab-admin', DLAB_PLUGIN_URL . 'admin/css/admin.css', array(), DLAB_VERSION);
    }
}
