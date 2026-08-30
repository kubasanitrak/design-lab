<?php
/**
 * Design Lab pass basket (guest cookie + logged-in user).
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Basket {

    const COOKIE_NAME = 'dlab_guest';
    const COOKIE_TTL  = 2592000; // 30 days
    const TOKEN_LEN   = 32;

    /** @var self|null */
    private static $instance = null;

    public function __construct() {
        self::$instance = $this;
        add_action('wp_ajax_dlab_add_to_pass', array($this, 'ajax_add_to_pass'));
        add_action('wp_ajax_nopriv_dlab_add_to_pass', array($this, 'ajax_add_to_pass'));
        add_action('wp_ajax_dlab_remove_from_pass', array($this, 'ajax_remove_from_pass'));
        add_action('wp_ajax_nopriv_dlab_remove_from_pass', array($this, 'ajax_remove_from_pass'));
        add_action('wp_ajax_dlab_update_pass', array($this, 'ajax_update_pass'));
        add_action('wp_ajax_nopriv_dlab_update_pass', array($this, 'ajax_update_pass'));
        add_action('wp_ajax_dlab_get_pass', array($this, 'ajax_get_pass'));
        add_action('wp_ajax_nopriv_dlab_get_pass', array($this, 'ajax_get_pass'));
        add_action('wp_login', array($this, 'merge_guest_on_login'), 10, 2);
        add_shortcode('dlab_basket_count', array($this, 'shortcode_basket_count'));
        add_shortcode('dlab_pass', array($this, 'shortcode_pass'));
        add_filter('dlab_enqueue_public_assets', array($this, 'force_enqueue_assets'));
        add_filter('dlab_basket_count', array($this, 'filter_basket_count'));
    }

    public static function instance() {
        return self::$instance;
    }

    public static function current_in_pass_ids() {
        return self::$instance ? self::$instance->get_in_pass_ids() : array();
    }

    public static function current_count() {
        return self::$instance ? self::$instance->get_count() : 0;
    }

    public static function is_current_in_pass($post_id) {
        return self::$instance ? self::$instance->is_in_basket($post_id) : false;
    }

    public function force_enqueue_assets($load) {
        return true;
    }

    public function filter_basket_count($count) {
        return $this->get_count();
    }

    /**
     * @return array{user_id:int, guest_token:string}
     */
    public static function get_owner($create_guest = false) {
        if (is_user_logged_in()) {
            return array(
                'user_id'     => get_current_user_id(),
                'guest_token' => '',
            );
        }
        return array(
            'user_id'     => 0,
            'guest_token' => self::get_guest_token($create_guest),
        );
    }

    public static function get_guest_token($create = false) {
        $token = '';
        if (!empty($_COOKIE[self::COOKIE_NAME])) {
            $token = self::sanitize_token(wp_unslash($_COOKIE[self::COOKIE_NAME]));
        }
        if ($token !== '') {
            return $token;
        }
        if (!$create) {
            return '';
        }
        $token = wp_generate_password(self::TOKEN_LEN, false, false);
        self::set_guest_cookie($token);
        return $token;
    }

    public static function sanitize_token($token) {
        $token = preg_replace('/[^a-zA-Z0-9]/', '', (string) $token);
        if (strlen($token) !== self::TOKEN_LEN) {
            return '';
        }
        return $token;
    }

    public static function set_guest_cookie($token) {
        $_COOKIE[self::COOKIE_NAME] = $token;
        if (headers_sent()) {
            return;
        }
        $options = array(
            'expires'  => time() + self::COOKIE_TTL,
            'path'     => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        );
        if (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) {
            $options['domain'] = COOKIE_DOMAIN;
        }
        setcookie(self::COOKIE_NAME, $token, $options);
    }

    public static function clear_guest_cookie() {
        unset($_COOKIE[self::COOKIE_NAME]);
        if (headers_sent()) {
            return;
        }
        setcookie(self::COOKIE_NAME, '', array(
            'expires'  => time() - YEAR_IN_SECONDS,
            'path'     => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ));
    }

    public static function default_line_meta() {
        return array(
            'services'  => array(),
            'spot_type' => DLab_Capacity::SPOT_REGULAR,
        );
    }

    public static function can_add_post($post_id) {
        return DLab_Workshop::is_booking_open($post_id);
    }

    public function add_item($post_id) {
        global $wpdb;

        $post_id = (int) $post_id;
        if (!$post_id || !self::can_add_post($post_id)) {
            return new WP_Error('dlab_invalid_item', __('Workshop nelze přidat do passu.', 'design-lab'));
        }

        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('dlab_invalid_item', __('Workshop nelze přidat do passu.', 'design-lab'));
        }

        $owner = self::get_owner(true);
        if ($owner['user_id'] === 0 && $owner['guest_token'] === '') {
            return new WP_Error('dlab_guest', __('Pass se nepodařilo uložit.', 'design-lab'));
        }

        if ($this->is_in_basket($post_id, $owner)) {
            return true;
        }

        $spots    = $this->get_spots($owner);
        $capacity = DLab_Capacity::can_reserve($post_id, $spots);
        if (is_wp_error($capacity)) {
            return $capacity;
        }

        $meta               = self::default_line_meta();
        $meta['spot_type']  = $capacity['spot_type'];

        $table = $wpdb->prefix . 'dlab_basket';
        $ok    = $wpdb->insert(
            $table,
            array(
                'user_id'     => $owner['user_id'],
                'guest_token' => $owner['guest_token'],
                'object_id'   => $post_id,
                'object_type' => $post->post_type,
                'line_meta'   => wp_json_encode($meta),
                'added_at'    => current_time('mysql'),
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s')
        );

        if ($ok) {
            return true;
        }
        if ($this->is_in_basket($post_id, $owner)) {
            return true;
        }
        return new WP_Error('dlab_db_error', __('Pass se nepodařilo uložit.', 'design-lab'));
    }

    public function remove_item($post_id, $owner = null) {
        global $wpdb;

        $owner = $owner ?: self::get_owner(false);
        $post  = get_post($post_id);
        if (!$post || !DLab_DB::table_exists('dlab_basket')) {
            return false;
        }

        $table = $wpdb->prefix . 'dlab_basket';
        return (bool) $wpdb->delete(
            $table,
            array(
                'user_id'     => $owner['user_id'],
                'guest_token' => $owner['guest_token'],
                'object_id'   => $post_id,
                'object_type' => $post->post_type,
            ),
            array('%d', '%s', '%d', '%s')
        );
    }

    public function update_line_services($post_id, array $services, $owner = null) {
        global $wpdb;

        $owner = $owner ?: self::get_owner(false);
        $post  = get_post($post_id);
        if (!$post || !$this->is_in_basket($post_id, $owner)) {
            return new WP_Error('dlab_not_in_basket', __('Workshop není v passu.', 'design-lab'));
        }

        $keys = array();
        foreach (DLab_Pricing::get_optional_services($post_id) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = DLab_Pricing::service_key($row);
            if ($key !== '') {
                $keys[$key] = true;
            }
        }
        $clean = array();
        foreach ($services as $value) {
            $key = DLab_Pricing::service_key_from_value($value);
            if ($key !== '' && isset($keys[$key])) {
                $clean[] = $key;
            }
        }

        $meta             = $this->get_line_meta($post_id, $owner);
        $meta['services'] = array_values(array_unique($clean));

        $table = $wpdb->prefix . 'dlab_basket';
        $wpdb->update(
            $table,
            array('line_meta' => wp_json_encode($meta)),
            array(
                'user_id'     => $owner['user_id'],
                'guest_token' => $owner['guest_token'],
                'object_id'   => $post_id,
            ),
            array('%s'),
            array('%d', '%s', '%d')
        );

        return true;
    }

    public function get_line_meta($post_id, $owner = null) {
        $items = $this->get_items($owner);
        foreach ($items as $item) {
            if ((int) $item->object_id === (int) $post_id) {
                return is_array($item->line_meta) ? $item->line_meta : self::default_line_meta();
            }
        }
        return self::default_line_meta();
    }

    public function get_items($owner = null) {
        global $wpdb;

        $owner = $owner ?: self::get_owner(false);
        if (!DLab_DB::table_exists('dlab_basket')) {
            return array();
        }
        if ($owner['user_id'] === 0 && $owner['guest_token'] === '') {
            return array();
        }

        $table = $wpdb->prefix . 'dlab_basket';
        $rows  = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, p.post_title
             FROM $table b
             LEFT JOIN {$wpdb->posts} p ON b.object_id = p.ID
             WHERE b.user_id = %d AND b.guest_token = %s
             ORDER BY b.added_at ASC",
            $owner['user_id'],
            $owner['guest_token']
        ));

        if (!is_array($rows)) {
            return array();
        }

        foreach ($rows as &$row) {
            $row->line_meta = json_decode($row->line_meta, true);
            if (!is_array($row->line_meta)) {
                $row->line_meta = self::default_line_meta();
            }
            $row->permalink = get_permalink($row->object_id);
            $row->schedule  = DLab_Workshop::get_schedule_summary($row->object_id);
        }

        return $rows;
    }

    public function get_count($owner = null) {
        return count($this->get_items($owner));
    }

    public function is_in_basket($post_id, $owner = null) {
        foreach ($this->get_items($owner) as $item) {
            if ((int) $item->object_id === (int) $post_id) {
                return true;
            }
        }
        return false;
    }

    public function get_in_pass_ids($owner = null) {
        $ids = array();
        foreach ($this->get_items($owner) as $item) {
            $ids[] = (int) $item->object_id;
        }
        return $ids;
    }

    public function get_spots($owner = null) {
        global $wpdb;

        $owner = $owner ?: self::get_owner(false);
        if (!DLab_DB::table_exists('dlab_basket_meta')) {
            return 1;
        }
        if ($owner['user_id'] === 0 && $owner['guest_token'] === '') {
            return 1;
        }

        $table = $wpdb->prefix . 'dlab_basket_meta';
        $spots = $wpdb->get_var($wpdb->prepare(
            "SELECT spots FROM $table WHERE user_id = %d AND guest_token = %s",
            $owner['user_id'],
            $owner['guest_token']
        ));

        return max(1, (int) $spots);
    }

    public function set_spots($spots, $owner = null) {
        global $wpdb;

        $spots = max(1, (int) $spots);
        $owner = $owner ?: self::get_owner(true);

        $ids = $this->get_in_pass_ids($owner);
        if (!empty($ids)) {
            $check = DLab_Capacity::can_reserve_pass($ids, $spots);
            if (is_wp_error($check)) {
                return $check;
            }
        }

        $table = $wpdb->prefix . 'dlab_basket_meta';
        $wpdb->replace(
            $table,
            array(
                'user_id'     => $owner['user_id'],
                'guest_token' => $owner['guest_token'],
                'spots'       => $spots,
                'updated_at'  => current_time('mysql'),
            ),
            array('%d', '%s', '%d', '%s')
        );

        return $spots;
    }

    public function get_summary($owner = null) {
        $owner = $owner ?: self::get_owner(false);
        $items = $this->get_items($owner);
        $spots = $this->get_spots($owner);
        $calc  = DLab_Pricing::calculate_pass($items, $spots);

        foreach ($items as $index => $item) {
            if (isset($calc['lines'][$index])) {
                $item->line_total = $calc['lines'][$index]['line_total'];
                $item->list_total = $calc['lines'][$index]['list_total'];
                $item->pass_unit  = $calc['lines'][$index]['pass_unit'];
                $item->list_unit  = $calc['lines'][$index]['list_unit'];
            }
        }

        $calc['items']     = $items;
        $calc['in_pass']   = $this->get_in_pass_ids($owner);
        $calc['pass_url']  = DLab_Settings::pass_page_url();
        $calc['count']     = count($items);
        $calc['total_formatted']    = DLab_Workshop::format_price($calc['total']);
        $calc['list_formatted']     = DLab_Workshop::format_price($calc['list_total']);
        $calc['discount_formatted'] = DLab_Workshop::format_price($calc['discount']);

        return $calc;
    }

    /**
     * Move guest rows onto the logged-in user.
     *
     * @param string  $user_login
     * @param WP_User $user
     */
    public function merge_guest_on_login($user_login, $user) {
        global $wpdb;

        unset($user_login);
        $token = self::get_guest_token(false);
        if ($token === '' || !DLab_DB::table_exists('dlab_basket')) {
            return;
        }

        $user_id = (int) $user->ID;
        $table   = $wpdb->prefix . 'dlab_basket';
        $guest   = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = 0 AND guest_token = %s",
            $token
        ));

        if (is_array($guest)) {
            foreach ($guest as $row) {
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table WHERE user_id = %d AND guest_token = '' AND object_id = %d AND object_type = %s",
                    $user_id,
                    $row->object_id,
                    $row->object_type
                ));
                if ($exists) {
                    $wpdb->delete($table, array('id' => (int) $row->id), array('%d'));
                    continue;
                }
                $wpdb->update(
                    $table,
                    array(
                        'user_id'     => $user_id,
                        'guest_token' => '',
                    ),
                    array('id' => (int) $row->id),
                    array('%d', '%s'),
                    array('%d')
                );
            }
        }

        if (DLab_DB::table_exists('dlab_basket_meta')) {
            $meta_table  = $wpdb->prefix . 'dlab_basket_meta';
            $guest_spots = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT spots FROM $meta_table WHERE user_id = 0 AND guest_token = %s",
                $token
            ));
            $user_spots = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT spots FROM $meta_table WHERE user_id = %d AND guest_token = ''",
                $user_id
            ));
            $merged = max(1, $guest_spots, $user_spots);
            $wpdb->delete($meta_table, array('user_id' => 0, 'guest_token' => $token), array('%d', '%s'));
            $wpdb->replace(
                $meta_table,
                array(
                    'user_id'     => $user_id,
                    'guest_token' => '',
                    'spots'       => $merged,
                    'updated_at'  => current_time('mysql'),
                ),
                array('%d', '%s', '%d', '%s')
            );
        }

        self::clear_guest_cookie();
    }

    public function ajax_add_to_pass() {
        check_ajax_referer('dlab_public', 'nonce');

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $result  = $this->add_item($post_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success($this->ajax_payload(__('Přidáno do passu.', 'design-lab')));
    }

    public function ajax_remove_from_pass() {
        check_ajax_referer('dlab_public', 'nonce');

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $this->remove_item($post_id);

        wp_send_json_success($this->ajax_payload(__('Odebráno z passu.', 'design-lab')));
    }

    public function ajax_update_pass() {
        check_ajax_referer('dlab_public', 'nonce');

        if (isset($_POST['spots'])) {
            $result = $this->set_spots((int) $_POST['spots']);
            if (is_wp_error($result)) {
                $payload            = $this->ajax_payload();
                $payload['message'] = $result->get_error_message();
                wp_send_json_error($payload);
            }
        }

        if (!empty($_POST['lines'])) {
            $lines = json_decode(wp_unslash($_POST['lines']), true);
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $post_id  = isset($line['post_id']) ? (int) $line['post_id'] : 0;
                    $services = isset($line['services']) && is_array($line['services']) ? $line['services'] : array();
                    if ($post_id) {
                        $this->update_line_services($post_id, $services);
                    }
                }
            }
        }

        wp_send_json_success($this->ajax_payload());
    }

    public function ajax_get_pass() {
        check_ajax_referer('dlab_public', 'nonce');
        wp_send_json_success($this->ajax_payload());
    }

    private function ajax_payload($message = '') {
        $summary = $this->get_summary();
        return array(
            'message'            => $message,
            'count'              => $summary['count'],
            'in_pass'            => $summary['in_pass'],
            'spots'              => $summary['spots'],
            'pass_applied'       => $summary['pass_applied'],
            'pass_min'           => $summary['pass_min'],
            'total'              => $summary['total'],
            'total_formatted'    => $summary['total_formatted'],
            'list_formatted'     => $summary['list_formatted'],
            'discount_formatted' => $summary['discount_formatted'],
            'discount'           => $summary['discount'],
            'pass_url'           => $summary['pass_url'],
            'html'               => $this->render_pass_inner($summary),
        );
    }

    public function shortcode_basket_count() {
        $count    = $this->get_count();
        $pass_url = DLab_Settings::pass_page_url();
        ob_start();
        ?>
        <a class="dlab-basket-count" href="<?php echo esc_url($pass_url); ?>" data-dlab-basket-count="<?php echo esc_attr((string) $count); ?>">
            <?php
            printf(
                /* translators: %d: workshops in the pass */
                esc_html__('Pass (%d)', 'design-lab'),
                $count
            );
            ?>
        </a>
        <?php
        return ob_get_clean();
    }

    public function shortcode_pass() {
        $summary = $this->get_summary();
        ob_start();
        include DLAB_PLUGIN_DIR . 'public/partials/pass-page.php';
        return ob_get_clean();
    }

    public function render_pass_inner(array $summary) {
        ob_start();
        $this->include_pass_lines($summary);
        return ob_get_clean();
    }

    public function include_pass_lines(array $summary) {
        include DLAB_PLUGIN_DIR . 'public/partials/pass-lines.php';
    }
}
