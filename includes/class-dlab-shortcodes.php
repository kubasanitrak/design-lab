<?php
/**
 * Front-end shortcodes.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Shortcodes {

    private static $assets_needed = false;

    /** Prevent the_content recursion when injecting the singular layout. */
    private static $rendering_detail = false;

    public function __construct() {
        add_shortcode('dlab_workshops_grid', array($this, 'workshops_grid'));
        add_shortcode('dlab_workshops_list', array($this, 'workshops_list'));
        add_shortcode('dlab_workshop_detail', array($this, 'workshop_detail'));
        add_shortcode('dlab_add_to_pass', array($this, 'add_to_pass'));
        add_shortcode('dlab_basket_count', array($this, 'basket_count'));
        add_filter('the_content', array($this, 'inject_singular_detail'), 8);
        add_filter('dlab_enqueue_public_assets', array($this, 'force_enqueue_assets'));
    }

    public function force_enqueue_assets($load) {
        return $load || self::$assets_needed;
    }

    private function flag_assets() {
        self::$assets_needed = true;
    }

    /**
     * On CPT singles, replace raw content with the detail layout
     * unless the editor already placed [dlab_workshop_detail].
     */
    public function inject_singular_detail($content) {
        if (self::$rendering_detail) {
            return $content;
        }
        if (!is_singular(DLab_Post_Types::POST_TYPE_WORKSHOP) || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        if (has_shortcode($content, 'dlab_workshop_detail')) {
            return $content;
        }
        self::$rendering_detail = true;
        $html = $this->workshop_detail(array('id' => (string) get_the_ID()));
        self::$rendering_detail = false;
        return $html;
    }

    /**
     * [dlab_workshops_grid ids="1,2,3" limit="6" title="…"]
     */
    public function workshops_grid($atts) {
        $this->flag_assets();
        $atts = shortcode_atts(array(
            'ids'             => '',
            'limit'           => 6,
            'title'           => '',
            'only_open'       => 'false',
            'age'             => '',
            'field'           => '',
            'use_url_filters' => 'false',
            'show_filters'    => 'false',
        ), $atts, 'dlab_workshops_grid');

        $query = new WP_Query(DLab_Query::build_query_args($atts));

        ob_start();
        $this->load_partial('workshops-grid', array(
            'query'         => $query,
            'title'         => $atts['title'],
            'show_filters'  => filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN),
            'filter_action' => '',
            'preset_atts'   => $atts,
        ));
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * [dlab_workshops_list limit="14" show_filters="true" filter_action="/design-lab/"]
     */
    public function workshops_list($atts) {
        $this->flag_assets();
        $atts = shortcode_atts(array(
            'limit'           => 14,
            'title'           => '',
            'only_open'       => 'false',
            'age'             => '',
            'field'           => '',
            'use_url_filters' => 'true',
            'show_filters'    => 'true',
            'filter_action'   => '',
        ), $atts, 'dlab_workshops_list');

        $query = new WP_Query(DLab_Query::build_query_args($atts));

        ob_start();
        $this->load_partial('workshops-list', array(
            'query'         => $query,
            'title'         => $atts['title'],
            'show_filters'  => filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN),
            'filter_action' => $this->resolve_filter_action($atts['filter_action']),
            'preset_atts'   => $atts,
        ));
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * [dlab_workshop_detail id=""]
     */
    public function workshop_detail($atts) {
        $this->flag_assets();
        $atts = shortcode_atts(array(
            'id'             => 0,
            'show_gallery'   => 'true',
            'show_program'   => 'true',
            'show_attendees' => 'true',
            'show_services'  => 'true',
        ), $atts, 'dlab_workshop_detail');

        $post_id = (int) $atts['id'];
        if (!$post_id && is_singular(DLab_Post_Types::POST_TYPE_WORKSHOP)) {
            $post_id = get_the_ID();
        }

        if (!$post_id || !DLab_Post_Types::is_bookable_post_type(get_post_type($post_id))) {
            return '<p class="dlab-notice">' . esc_html__('Workshop nenalezen.', 'design-lab') . '</p>';
        }

        ob_start();
        $this->load_partial('workshop-detail', array(
            'post_id'        => $post_id,
            'show_gallery'   => filter_var($atts['show_gallery'], FILTER_VALIDATE_BOOLEAN),
            'show_program'   => filter_var($atts['show_program'], FILTER_VALIDATE_BOOLEAN),
            'show_attendees' => filter_var($atts['show_attendees'], FILTER_VALIDATE_BOOLEAN),
            'show_services'  => filter_var($atts['show_services'], FILTER_VALIDATE_BOOLEAN),
        ));
        return ob_get_clean();
    }

    /**
     * [dlab_add_to_pass id=""]
     */
    public function add_to_pass($atts) {
        $this->flag_assets();
        $atts = shortcode_atts(array(
            'id'    => 0,
            'class' => '',
        ), $atts, 'dlab_add_to_pass');

        $post_id = (int) $atts['id'];
        if (!$post_id && is_singular(DLab_Post_Types::POST_TYPE_WORKSHOP)) {
            $post_id = get_the_ID();
        }
        if (!$post_id) {
            return '';
        }

        ob_start();
        $this->load_partial('add-to-pass', array(
            'post_id' => $post_id,
            'class'   => $atts['class'],
        ));
        return ob_get_clean();
    }

    /**
     * [dlab_basket_count] — placeholder until Phase 2.
     */
    public function basket_count() {
        $this->flag_assets();
        $count = (int) apply_filters('dlab_basket_count', 0);
        return '<span class="dlab-basket-count" data-dlab-basket-count="' . esc_attr((string) $count) . '">' . esc_html((string) $count) . '</span>';
    }

    /**
     * @param string $filter_action
     * @return string
     */
    private function resolve_filter_action($filter_action) {
        $filter_action = trim((string) $filter_action);

        if ($filter_action === '') {
            $permalink = get_permalink();
            return $permalink ? $permalink : '';
        }

        if (preg_match('#^https?://#i', $filter_action)) {
            return esc_url($filter_action);
        }

        if (strpos($filter_action, '/') === 0) {
            return esc_url(home_url($filter_action));
        }

        return esc_url($filter_action);
    }

    /**
     * @param string $name
     * @param array  $context
     */
    private function load_partial($name, $context) {
        $file = DLAB_PLUGIN_DIR . 'public/partials/' . $name . '.php';
        if (!is_readable($file)) {
            return;
        }
        extract($context, EXTR_SKIP);
        include $file;
    }
}
