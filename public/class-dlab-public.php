<?php
/**
 * Public-facing assets.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Public {

    private static $enqueued = false;

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_assets'), 20);
        add_action('wp_footer', array($this, 'maybe_enqueue_assets_late'), 1);
    }

    public function maybe_enqueue_assets() {
        if ($this->should_load_assets()) {
            $this->enqueue_assets();
        }
    }

    /**
     * Shortcodes render after wp_enqueue_scripts — load assets in footer if needed.
     */
    public function maybe_enqueue_assets_late() {
        if (apply_filters('dlab_enqueue_public_assets', false)) {
            $this->enqueue_assets();
        }
    }

    private function should_load_assets() {
        if (apply_filters('dlab_enqueue_public_assets', false)) {
            return true;
        }
        if (is_singular(DLab_Post_Types::POST_TYPE_WORKSHOP)) {
            return true;
        }
        if (is_singular('page') && $this->current_page_has_shortcode()) {
            return true;
        }
        return false;
    }

    private function current_page_has_shortcode() {
        $post = get_post();
        if (!$post || empty($post->post_content)) {
            return false;
        }
        $tags = array(
            'dlab_workshops_grid',
            'dlab_workshops_list',
            'dlab_workshop_detail',
            'dlab_add_to_pass',
            'dlab_basket_count',
            'dlab_pass',
        );
        foreach ($tags as $tag) {
            if (has_shortcode($post->post_content, $tag)) {
                return true;
            }
        }
        return false;
    }

    private function enqueue_assets() {
        if (self::$enqueued) {
            return;
        }
        self::$enqueued = true;

        wp_enqueue_style(
            'dlab-public',
            DLAB_PLUGIN_URL . 'public/css/public.css',
            array(),
            DLAB_VERSION
        );

        wp_enqueue_script(
            'dlab-public',
            DLAB_PLUGIN_URL . 'public/js/public.js',
            array('jquery'),
            DLAB_VERSION,
            true
        );

        wp_localize_script('dlab-public', 'dlab_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('dlab_public'),
            'pass_url'    => DLab_Settings::pass_page_url(),
            'listing_url' => DLab_Settings::listing_page_url(),
            'in_pass'     => DLab_Basket::current_in_pass_ids(),
            'count'       => DLab_Basket::current_count(),
            'i18n'        => array(
                'add_to_pass'  => __('Přidat do passu', 'design-lab'),
                'in_pass'      => __('V passu', 'design-lab'),
                'pass_count'   => __('Pass (%d)', 'design-lab'),
                'add_workshop' => __('Přidat další workshop', 'design-lab'),
                'added'        => __('Přidáno do passu.', 'design-lab'),
                'removed'      => __('Odebráno z passu.', 'design-lab'),
                'error'        => __('Něco se pokazilo. Zkuste to znovu.', 'design-lab'),
            ),
        ));
    }
}
