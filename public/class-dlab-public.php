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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_widget_style'), 19);
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_assets'), 20);
        add_action('wp_footer', array($this, 'maybe_enqueue_assets_late'), 1);
        add_action('wp_footer', array($this, 'render_pass_widget'), 20);
    }

    /**
     * The pass link is on every front-end page, so its CSS loads everywhere.
     */
    public function enqueue_widget_style() {
        if (is_admin()) {
            return;
        }
        $this->enqueue_style();
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
            'dlab_checkout',
            'dlab_set_password',
            'dlab_dashboard',
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
        $this->enqueue_style();

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
            'pass_url'     => DLab_Settings::pass_page_url(),
            'listing_url'  => DLab_Settings::listing_page_url(),
            'checkout_url' => DLab_Settings::checkout_page_url(),
            'in_pass'      => DLab_Basket::current_in_pass_ids(),
            'count'        => DLab_Basket::current_count(),
            'i18n'         => array(
                'add_to_pass'       => __('Přidat do passu', 'design-lab'),
                'in_pass'           => __('V passu', 'design-lab'),
                'pass_count'        => __('Pass (%d)', 'design-lab'),
                'add_workshop'      => __('Přidat další workshop', 'design-lab'),
                'reserve'           => __('Rezervovat', 'design-lab'),
                'added'             => __('Přidáno do passu.', 'design-lab'),
                'removed'           => __('Odebráno z passu.', 'design-lab'),
                'copied'            => __('Zkopírováno', 'design-lab'),
                'error'             => __('Něco se pokazilo. Zkuste to znovu.', 'design-lab'),
                'login'             => __('Přihlásit se', 'design-lab'),
                'confirm_cancel'    => __('Opravdu chcete zrušit celou rezervaci?', 'design-lab'),
                'no_workshops'      => __('Žádný vhodný workshop k přesunu.', 'design-lab'),
                'pick_workshop'     => __('Vyberte workshop', 'design-lab'),
                'confirm_reschedule'=> __('Opravdu přesunout na vybraný workshop?', 'design-lab'),
            ),
        ));
    }

    private function enqueue_style() {
        if (wp_style_is('dlab-public', 'enqueued')) {
            return;
        }
        wp_enqueue_style(
            'dlab-public',
            DLAB_PLUGIN_URL . 'public/css/public.css',
            array(),
            DLAB_VERSION
        );
    }

    /**
     * Fixed link to checkout. Label opens on hover; no basket overlay.
     */
    public function render_pass_widget() {
        if (is_admin() || is_feed()) {
            return;
        }

        $checkout_id = DLab_Settings::checkout_page_id();
        if ($checkout_id && is_page($checkout_id)) {
            return;
        }

        $url   = DLab_Settings::checkout_page_url();
        $count = class_exists('DLab_Basket') ? (int) DLab_Basket::current_count() : 0;
        ?>
        <a class="dlab-pass-widget" href="<?php echo esc_url($url); ?>">
            <span class="dlab-pass-widget__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" focusable="false">
                    <path stroke-linejoin="round" d="M5 7.2h14v2.1a1.7 1.7 0 0 0 0 3.2v2.3H5v-2.3a1.7 1.7 0 0 0 0-3.2V7.2z"/>
                    <path stroke-linecap="round" stroke-dasharray="1.4 2.2" d="M9.2 8.6v6.6"/>
                </svg>
            </span>
            <span class="dlab-pass-widget__label"><?php esc_html_e('můj design pass', 'design-lab'); ?></span>
            <span class="dlab-pass-widget__count" data-dlab-pass-widget-count <?php echo $count > 0 ? '' : 'hidden'; ?>><?php echo esc_html((string) $count); ?></span>
        </a>
        <?php
    }
}
