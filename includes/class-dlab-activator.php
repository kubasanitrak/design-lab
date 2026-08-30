<?php
/**
 * Plugin activation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Activator {

    public static function activate() {
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-settings.php';
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-post-types.php';

        $post_types = new DLab_Post_Types();
        $post_types->register_post_types();
        $post_types->register_taxonomies();
        DLab_Post_Types::seed_default_terms();

        DLab_Settings::ensure_defaults();
        require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-db.php';
        DLab_DB::create_tables();
        self::create_pages();

        flush_rewrite_rules();

        update_option('dlab_plugin_activated', true);
    }

    /**
     * Create front-end pages with shortcodes (Czech slugs).
     */
    public static function create_pages() {
        $pages = array(
            'listing' => array(
                'title'   => __('Design Lab', 'design-lab'),
                'slug'    => 'design-lab',
                'content' => '[dlab_workshops_list show_filters="true" filter_action="/design-lab/"]',
            ),
            'pass' => array(
                'title'   => __('Pass', 'design-lab'),
                'slug'    => 'pass',
                'content' => '[dlab_pass]',
            ),
        );

        $page_ids = get_option('dlab_page_ids', array());
        if (!is_array($page_ids)) {
            $page_ids = array();
        }

        foreach ($pages as $key => $page) {
            $existing = get_page_by_path($page['slug']);
            if (!$existing) {
                $page_id = wp_insert_post(array(
                    'post_title'   => $page['title'],
                    'post_name'    => $page['slug'],
                    'post_content' => $page['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_author'  => 1,
                ));
                if (!is_wp_error($page_id) && $page_id) {
                    $page_ids[$key] = (int) $page_id;
                }
            } else {
                $page_ids[$key] = (int) $existing->ID;
            }
        }

        update_option('dlab_page_ids', $page_ids);

        if (!empty($page_ids['listing']) && !(int) get_option('dlab_listing_page', 0)) {
            update_option('dlab_listing_page', (int) $page_ids['listing']);
        }
        if (!empty($page_ids['pass']) && !(int) get_option('dlab_pass_page', 0)) {
            update_option('dlab_pass_page', (int) $page_ids['pass']);
        }
    }

    /**
     * Ensure front-end pages exist (upgrade without re-activation).
     */
    public static function maybe_create_pages() {
        $ids = get_option('dlab_page_ids', array());
        if (!is_array($ids)) {
            $ids = array();
        }
        foreach (array('listing', 'pass') as $key) {
            if (empty($ids[$key]) || !get_post($ids[$key])) {
                self::create_pages();
                flush_rewrite_rules();
                return;
            }
        }
    }
}
