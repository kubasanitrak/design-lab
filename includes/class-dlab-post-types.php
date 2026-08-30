<?php
/**
 * Custom post types and taxonomies (Czech rewrite slugs).
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Post_Types {

    const POST_TYPE_WORKSHOP = 'dlab_workshop';

    /** Theme CPT “Lektorka” — do not register a new instructor type. */
    const POST_TYPE_INSTRUCTOR = 'instructor';

    const TAX_AGE   = 'dlab_vek';
    const TAX_FIELD = 'dlab_obor';

    const TYPE_DESIGNLAB = 'designlab';

    public function __construct() {
        add_action('init', array($this, 'register_post_types'), 5);
        add_action('init', array($this, 'register_taxonomies'), 6);
        add_action('init', array($this, 'maybe_seed_default_terms'), 20);
        add_filter('manage_' . self::POST_TYPE_WORKSHOP . '_posts_columns', array($this, 'add_list_columns'));
        add_action('manage_' . self::POST_TYPE_WORKSHOP . '_posts_custom_column', array($this, 'render_list_columns'), 10, 2);
        add_filter('manage_edit-' . self::POST_TYPE_WORKSHOP . '_sortable_columns', array($this, 'sortable_columns'));
        add_action('pre_get_posts', array($this, 'admin_orderby_workshop_date'));
    }

    /**
     * @return string[]
     */
    public static function get_bookable_post_types() {
        return array(self::POST_TYPE_WORKSHOP);
    }

    public static function is_bookable_post_type($post_type) {
        return $post_type === self::POST_TYPE_WORKSHOP;
    }

    public function register_post_types() {
        $this->register_workshop();
    }

    private function register_workshop() {
        $labels = array(
            'name'               => __('Workshopy', 'design-lab'),
            'singular_name'      => __('Workshop', 'design-lab'),
            'menu_name'          => __('Workshopy', 'design-lab'),
            'add_new'            => __('Přidat workshop', 'design-lab'),
            'add_new_item'       => __('Přidat nový workshop', 'design-lab'),
            'edit_item'          => __('Upravit workshop', 'design-lab'),
            'new_item'           => __('Nový workshop', 'design-lab'),
            'view_item'          => __('Zobrazit workshop', 'design-lab'),
            'search_items'       => __('Hledat workshopy', 'design-lab'),
            'not_found'          => __('Žádné workshopy nenalezeny', 'design-lab'),
            'not_found_in_trash' => __('V koši nejsou žádné workshopy', 'design-lab'),
        );

        register_post_type(self::POST_TYPE_WORKSHOP, array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'show_in_rest'       => true,
            'query_var'          => true,
            'rewrite'            => array(
                'slug'       => 'design-lab',
                'with_front' => false,
            ),
            // Archive disabled: /design-lab/ is a WP page with [dlab_workshops_list].
            'has_archive'        => false,
            'capability_type'    => 'post',
            'hierarchical'       => false,
            'menu_icon'          => 'dashicons-art',
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
        ));
    }

    public function register_taxonomies() {
        register_taxonomy(self::TAX_AGE, self::POST_TYPE_WORKSHOP, array(
            'labels' => array(
                'name'          => __('Věk', 'design-lab'),
                'singular_name' => __('Věk', 'design-lab'),
            ),
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array(
                'slug'       => 'vek',
                'with_front' => false,
            ),
        ));

        register_taxonomy(self::TAX_FIELD, self::POST_TYPE_WORKSHOP, array(
            'labels' => array(
                'name'          => __('Obor', 'design-lab'),
                'singular_name' => __('Obor', 'design-lab'),
            ),
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array(
                'slug'       => 'obor',
                'with_front' => false,
            ),
        ));
    }

    public function maybe_seed_default_terms() {
        if (get_option('dlab_default_terms_seeded')) {
            return;
        }
        self::seed_default_terms();
        update_option('dlab_default_terms_seeded', 1);
    }

    /**
     * Seed default filter terms (idempotent).
     */
    public static function seed_default_terms() {
        $groups = array(
            self::TAX_AGE => array(
                '6-plus'  => __('6+', 'design-lab'),
                '8-plus'  => __('8+', 'design-lab'),
                '10-plus' => __('10+', 'design-lab'),
            ),
            self::TAX_FIELD => array(
                'design' => __('Design', 'design-lab'),
                'moda'   => __('Móda', 'design-lab'),
            ),
        );

        foreach ($groups as $taxonomy => $terms) {
            foreach ($terms as $slug => $name) {
                if (!term_exists($slug, $taxonomy)) {
                    wp_insert_term($name, $taxonomy, array('slug' => $slug));
                }
            }
        }
    }

    public function add_list_columns($columns) {
        $new = array();
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'title') {
                $new['dlab_date']    = __('Datum', 'design-lab');
                $new['dlab_booking'] = __('Rezervace', 'design-lab');
            }
        }
        return $new;
    }

    public function sortable_columns($columns) {
        $columns['dlab_date'] = 'dlab_date';
        return $columns;
    }

    public function admin_orderby_workshop_date($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        if ($query->get('post_type') !== self::POST_TYPE_WORKSHOP) {
            return;
        }
        if ($query->get('orderby') !== 'dlab_date') {
            return;
        }
        $query->set('meta_key', 'workshop_date');
        $query->set('orderby', 'meta_value');
        $query->set('meta_type', 'DATE');
    }

    public function render_list_columns($column, $post_id) {
        if ($column === 'dlab_date') {
            echo esc_html(DLab_Workshop::get_card_date($post_id) ?: '—');
            return;
        }
        if ($column !== 'dlab_booking') {
            return;
        }
        if (function_exists('get_field') && get_field('booking_open', $post_id) !== null) {
            echo get_field('booking_open', $post_id)
                ? esc_html__('Otevřeno', 'design-lab')
                : esc_html__('Zavřeno', 'design-lab');
            return;
        }
        echo '—';
    }
}
