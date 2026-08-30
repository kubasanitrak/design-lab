<?php
/**
 * Admin menu and CPT integration.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Admin {

    const MENU_SLUG = 'dlab-main-menu';

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 5);
        add_action('admin_menu', array($this, 'reorder_submenu'), 999);
        add_filter('register_post_type_args', array($this, 'attach_cpts_to_menu'), 10, 2);
    }

    public function attach_cpts_to_menu($args, $post_type) {
        if ($post_type === DLab_Post_Types::POST_TYPE_WORKSHOP) {
            $args['show_in_menu'] = self::MENU_SLUG;
        }
        return $args;
    }

    public function register_menu() {
        add_menu_page(
            __('Design Lab', 'design-lab'),
            __('Design Lab', 'design-lab'),
            'edit_posts',
            self::MENU_SLUG,
            array($this, 'render_dashboard'),
            'dashicons-art',
            26
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Přehled', 'design-lab'),
            __('Přehled', 'design-lab'),
            'edit_posts',
            self::MENU_SLUG,
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Workshopy', 'design-lab'),
            __('Workshopy', 'design-lab'),
            'edit_posts',
            'edit.php?post_type=' . DLab_Post_Types::POST_TYPE_WORKSHOP
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Věk', 'design-lab'),
            __('Věk', 'design-lab'),
            'manage_categories',
            sprintf(
                'edit-tags.php?taxonomy=%s&post_type=%s',
                DLab_Post_Types::TAX_AGE,
                DLab_Post_Types::POST_TYPE_WORKSHOP
            )
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Obor', 'design-lab'),
            __('Obor', 'design-lab'),
            'manage_categories',
            sprintf(
                'edit-tags.php?taxonomy=%s&post_type=%s',
                DLab_Post_Types::TAX_FIELD,
                DLab_Post_Types::POST_TYPE_WORKSHOP
            )
        );
    }

    public function reorder_submenu() {
        global $submenu;
        if (!isset($submenu[self::MENU_SLUG])) {
            return;
        }

        $order  = array();
        $wanted = array(
            self::MENU_SLUG,
            'edit.php?post_type=' . DLab_Post_Types::POST_TYPE_WORKSHOP,
            'post-new.php?post_type=' . DLab_Post_Types::POST_TYPE_WORKSHOP,
        );

        foreach ($wanted as $slug) {
            foreach ($submenu[self::MENU_SLUG] as $item) {
                if (isset($item[2]) && $item[2] === $slug) {
                    $order[] = $item;
                    break;
                }
            }
        }

        foreach ($submenu[self::MENU_SLUG] as $item) {
            if (!in_array($item, $order, true)) {
                $order[] = $item;
            }
        }

        $submenu[self::MENU_SLUG] = $order;
    }

    public function render_dashboard() {
        if (!current_user_can('edit_posts')) {
            echo '<div class="wrap"><p>' . esc_html__('Nemáte oprávnění k tomuto přehledu.', 'design-lab') . '</p></div>';
            return;
        }

        $counts = wp_count_posts(DLab_Post_Types::POST_TYPE_WORKSHOP);
        $workshop_count = (is_object($counts) && isset($counts->publish)) ? (int) $counts->publish : 0;
        include DLAB_PLUGIN_DIR . 'admin/partials/dashboard-page.php';

        if (!DLab_ACF::is_active()) {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__('ACF Pro není aktivní — synchronizujte field groups z acf-json/.', 'design-lab');
            echo '</p></div>';
        }
    }
}
