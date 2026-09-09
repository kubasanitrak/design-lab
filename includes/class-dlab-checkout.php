<?php
/**
 * Checkout: reservation, bank transfer recap, QR.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Checkout {

    public function __construct() {
        add_shortcode('dlab_checkout', array($this, 'shortcode_checkout'));
        add_action('wp_ajax_dlab_process_checkout', array($this, 'ajax_process_checkout'));
        add_action('wp_ajax_nopriv_dlab_process_checkout', array($this, 'ajax_process_checkout'));
        add_filter('dlab_enqueue_public_assets', array($this, 'force_enqueue_assets'));
    }

    public function force_enqueue_assets($load) {
        if ($load) {
            return true;
        }
        $post = get_post();
        return $post && has_shortcode((string) $post->post_content, 'dlab_checkout');
    }

    public function shortcode_checkout() {
        if (isset($_GET['order'])) {
            $order_id = (int) $_GET['order'];
            $token    = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
            $order    = self::get_order($order_id);
            if ($order && self::can_view_order($order, $token)) {
                return $this->render_transfer_info($order);
            }
            return '<p class="dlab-notice">' . esc_html__('Rezervace nenalezena.', 'design-lab') . '</p>';
        }

        $basket  = DLab_Basket::instance();
        $summary = $basket ? $basket->get_summary() : array('items' => array());

        if (empty($summary['items'])) {
            $listing = DLab_Settings::listing_page_url();
            ob_start();
            ?>
            <div data-theme="DD-beige" class="section scroll-trigger section-content section-content--dilna section-full-width pad-B-4 dlab-checkout" id="dlab-checkout">
                <div class="inner-content">
                    <div class="wp-block-group single-col single-col--narrow">
                        <div class="wp-block-group__inner-container is-layout-constrained">
                            <h1 class="wp-block-heading has-text-align-center"><strong><?php esc_html_e('Rezervace', 'design-lab'); ?></strong></h1>
                            <p class="dlab-empty"><?php esc_html_e('Pass je prázdný.', 'design-lab'); ?></p>
                            <a class="btn dlab-btn" href="<?php echo esc_url($listing); ?>">
                                <?php esc_html_e('Vybrat workshopy', 'design-lab'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        $user        = is_user_logged_in() ? wp_get_current_user() : null;
        $terms_page  = DLab_Settings::terms_page_id();
        $gdpr_page   = DLab_Settings::gdpr_page_id();
        $contact     = array(
            'name'  => $user ? trim($user->first_name . ' ' . $user->last_name) : '',
            'email' => $user ? (string) $user->user_email : '',
            'phone' => $user ? (string) get_user_meta($user->ID, 'billing_phone', true) : '',
        );
        if ($contact['name'] === '' && $user) {
            $contact['name'] = (string) $user->display_name;
        }

        ob_start();
        include DLAB_PLUGIN_DIR . 'public/partials/checkout-page.php';
        return ob_get_clean();
    }

    public function ajax_process_checkout() {
        check_ajax_referer('dlab_public', 'nonce');

        $basket = DLab_Basket::instance();
        if (!$basket) {
            wp_send_json_error(array('message' => __('Pass se nepodařilo načíst.', 'design-lab')));
        }

        $summary = $basket->get_summary();
        if (empty($summary['items'])) {
            wp_send_json_error(array('message' => __('Pass je prázdný.', 'design-lab')));
        }

        $contact = $this->parse_contact($_POST, (int) ($summary['spots'] ?? 1));
        if (is_wp_error($contact)) {
            wp_send_json_error(array('message' => $contact->get_error_message()));
        }

        $terms_page = DLab_Settings::terms_page_id();
        if ($terms_page && empty($_POST['agree_terms'])) {
            wp_send_json_error(array('message' => __('Souhlaste s obchodními podmínkami.', 'design-lab')));
        }

        $gdpr_page = DLab_Settings::gdpr_page_id();
        if ($gdpr_page && empty($_POST['agree_gdpr'])) {
            wp_send_json_error(array('message' => __('Souhlaste se zpracováním osobních údajů.', 'design-lab')));
        }

        $order_id = self::create_order($contact);
        if (is_wp_error($order_id)) {
            wp_send_json_error(array('message' => $order_id->get_error_message()));
        }
        if (!$order_id) {
            wp_send_json_error(array('message' => __('Rezervaci se nepodařilo vytvořit.', 'design-lab')));
        }

        DLab_Emails::send_order_placed_email($order_id);
        $order = self::get_order($order_id);

        wp_send_json_success(array(
            'redirect' => self::payment_url($order),
        ));
    }

    /**
     * @param array $post
     * @param int   $spots
     * @return array|WP_Error
     */
    private function parse_contact($post, $spots) {
        $name  = isset($post['contact_name']) ? sanitize_text_field(wp_unslash($post['contact_name'])) : '';
        $email = isset($post['contact_email']) ? sanitize_email(wp_unslash($post['contact_email'])) : '';
        $phone = isset($post['contact_phone']) ? sanitize_text_field(wp_unslash($post['contact_phone'])) : '';

        if ($name === '' || $email === '' || !is_email($email)) {
            return new WP_Error('dlab_contact', __('Vyplňte jméno a platný e-mail.', 'design-lab'));
        }
        if ($phone === '') {
            return new WP_Error('dlab_contact', __('Vyplňte telefon.', 'design-lab'));
        }

        $spots     = max(1, (int) $spots);
        $attendees = array();
        $raw       = isset($post['attendees']) ? wp_unslash($post['attendees']) : array();
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw     = is_array($decoded) ? $decoded : array();
        }
        if (!is_array($raw)) {
            $raw = array();
        }
        for ($i = 0; $i < $spots; $i++) {
            $label = isset($raw[$i]) ? sanitize_text_field($raw[$i]) : '';
            if ($label === '') {
                return new WP_Error('dlab_attendees', __('Vyplňte jména všech účastníků.', 'design-lab'));
            }
            $attendees[] = $label;
        }

        return array(
            'name'      => $name,
            'email'     => $email,
            'phone'     => $phone,
            'attendees' => $attendees,
        );
    }

    /**
     * @param array $contact
     * @return int|WP_Error
     */
    public static function create_order(array $contact) {
        global $wpdb;

        $basket = DLab_Basket::instance();
        if (!$basket || !DLab_DB::table_exists('dlab_orders')) {
            return new WP_Error('dlab_db', __('Rezervaci se nepodařilo vytvořit.', 'design-lab'));
        }

        $summary = $basket->get_summary();
        $items   = $summary['items'] ?? array();
        $spots   = max(1, (int) ($summary['spots'] ?? 1));

        if (empty($items)) {
            return new WP_Error('dlab_empty', __('Pass je prázdný.', 'design-lab'));
        }

        $capacity = DLab_Capacity::can_reserve_pass($basket->get_in_pass_ids(), $spots);
        if (is_wp_error($capacity)) {
            return $capacity;
        }

        foreach ($items as $item) {
            if (!DLab_Basket::can_add_post((int) $item->object_id)) {
                return new WP_Error(
                    'dlab_closed',
                    sprintf(
                        /* translators: %s: workshop title */
                        __('Workshop „%s“ už nelze rezervovat.', 'design-lab'),
                        $item->post_title
                    )
                );
            }
        }

        $owner        = DLab_Basket::get_owner(false);
        $order_number = DLab_Settings::next_invoice_number();
        $access_token = wp_generate_password(32, false, false);
        $hours        = DLab_Settings::reservation_expiry_hours();
        $expires_at   = wp_date('Y-m-d H:i:s', time() + ($hours * HOUR_IN_SECONDS));

        $insert = array(
            'user_id'        => $owner['user_id'],
            'guest_token'    => $owner['guest_token'],
            'access_token'   => $access_token,
            'order_number'   => $order_number,
            'status'         => 'awaiting_payment',
            'payment_method' => 'bank_transfer',
            'total'          => (float) $summary['total'],
            'discount'       => (float) $summary['discount'],
            'currency'       => DLab_Settings::currency_code(),
            'spots'          => $spots,
            'contact_data'   => wp_json_encode($contact),
            'created_at'     => current_time('mysql'),
            'expires_at'     => $expires_at,
        );

        $ok = $wpdb->insert(
            $wpdb->prefix . 'dlab_orders',
            $insert,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%s', '%s', '%s')
        );

        if ($ok === false) {
            return new WP_Error('dlab_db', __('Rezervaci se nepodařilo vytvořit.', 'design-lab'));
        }

        $order_id    = (int) $wpdb->insert_id;
        $items_table = $wpdb->prefix . 'dlab_order_items';
        $attendees   = isset($contact['attendees']) && is_array($contact['attendees']) ? $contact['attendees'] : array();

        foreach ($items as $index => $item) {
            $line      = isset($summary['lines'][$index]) ? $summary['lines'][$index] : array();
            $spot_type = $item->line_meta['spot_type'] ?? DLab_Capacity::SPOT_REGULAR;
            $resolved  = DLab_Capacity::can_reserve((int) $item->object_id, $spots);
            if (is_wp_error($resolved)) {
                self::cancel_order($order_id);
                return $resolved;
            }
            $spot_type = $resolved['spot_type'];

            $wpdb->insert(
                $items_table,
                array(
                    'order_id'    => $order_id,
                    'object_id'   => (int) $item->object_id,
                    'object_type' => $item->object_type,
                    'qty'         => $spots,
                    'unit_price'  => isset($line['unit']) ? (float) $line['unit'] : 0,
                    'line_total'  => isset($line['line_total']) ? (float) $line['line_total'] : 0,
                    'line_meta'   => wp_json_encode($item->line_meta),
                ),
                array('%d', '%d', '%s', '%d', '%f', '%f', '%s')
            );

            DLab_Capacity::create_holds_from_line(
                $order_id,
                (int) $wpdb->insert_id,
                (int) $item->object_id,
                $item->object_type,
                $owner['user_id'],
                $spots,
                $spot_type,
                $attendees
            );
        }

        $basket->clear($owner);

        return $order_id;
    }

    public static function cancel_order($order_id) {
        DLab_Capacity::release_order_spots($order_id);
        self::update_order_status($order_id, 'cancelled');
    }

    public static function update_order_status($order_id, $status) {
        global $wpdb;

        $order_id = (int) $order_id;
        $data     = array(
            'status'     => $status,
            'updated_at' => current_time('mysql'),
        );
        $format = array('%s', '%s');

        if ($status === 'paid') {
            $data['paid_at'] = current_time('mysql');
            $format[]        = '%s';
            DLab_Capacity::confirm_order_spots($order_id);
        }

        if (in_array($status, array('cancelled', 'expired', 'failed'), true)) {
            DLab_Capacity::release_order_spots($order_id);
        }

        return $wpdb->update(
            $wpdb->prefix . 'dlab_orders',
            $data,
            array('id' => $order_id),
            $format,
            array('%d')
        );
    }

    public static function mark_paid($order_id) {
        $order = self::get_order($order_id);
        if (!$order || in_array($order->status, array('paid', 'cancelled', 'expired', 'failed'), true)) {
            return false;
        }
        self::update_order_status($order_id, 'paid');
        DLab_Emails::send_payment_confirmed_email($order_id);
        return true;
    }

    public static function get_order($order_id) {
        global $wpdb;

        $order_id = (int) $order_id;
        if (!$order_id || !DLab_DB::table_exists('dlab_orders')) {
            return null;
        }

        $orders_table = $wpdb->prefix . 'dlab_orders';
        $items_table  = $wpdb->prefix . 'dlab_order_items';

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $orders_table WHERE id = %d",
            $order_id
        ));

        if (!$order) {
            return null;
        }

        $order->contact_data = json_decode($order->contact_data, true);
        if (!is_array($order->contact_data)) {
            $order->contact_data = array();
        }

        $order->items = $wpdb->get_results($wpdb->prepare(
            "SELECT oi.*, p.post_title
             FROM $items_table oi
             LEFT JOIN {$wpdb->posts} p ON oi.object_id = p.ID
             WHERE oi.order_id = %d",
            $order_id
        ));

        if (!is_array($order->items)) {
            $order->items = array();
        }

        foreach ($order->items as &$item) {
            $item->line_meta = json_decode($item->line_meta, true);
            if (!is_array($item->line_meta)) {
                $item->line_meta = array();
            }
            $item->permalink = get_permalink($item->object_id);
            $item->schedule  = DLab_Workshop::get_schedule_summary($item->object_id);
        }

        return $order;
    }

    public static function contact_from_order($order) {
        $data = is_object($order) && isset($order->contact_data) && is_array($order->contact_data)
            ? $order->contact_data
            : array();

        $email = isset($data['email']) ? (string) $data['email'] : '';
        $name  = isset($data['name']) ? (string) $data['name'] : '';

        if (($email === '' || $name === '') && !empty($order->user_id)) {
            $user = get_userdata((int) $order->user_id);
            if ($user) {
                if ($email === '') {
                    $email = (string) $user->user_email;
                }
                if ($name === '') {
                    $name = (string) $user->display_name;
                }
            }
        }

        return array(
            'name'      => $name,
            'email'     => $email,
            'phone'     => isset($data['phone']) ? (string) $data['phone'] : '',
            'attendees' => isset($data['attendees']) && is_array($data['attendees']) ? $data['attendees'] : array(),
        );
    }

    public static function variable_symbol($order) {
        return DLab_Settings::variable_symbol($order->order_number);
    }

    public static function can_view_order($order, $token = '') {
        if (!$order) {
            return false;
        }
        if (current_user_can('manage_options')) {
            return true;
        }
        if (is_user_logged_in() && (int) $order->user_id === get_current_user_id() && (int) $order->user_id > 0) {
            return true;
        }
        $token = (string) $token;
        if ($token !== '' && !empty($order->access_token) && hash_equals((string) $order->access_token, $token)) {
            return true;
        }
        return false;
    }

    public static function payment_url($order) {
        if (!$order) {
            return '';
        }
        return add_query_arg(
            array(
                'order'  => (int) $order->id,
                'token'  => $order->access_token,
                'method' => 'bank_transfer',
            ),
            DLab_Settings::checkout_page_url()
        );
    }

    public static function status_label($status) {
        $labels = array(
            'pending'          => __('Čeká', 'design-lab'),
            'awaiting_payment' => __('Čeká na platbu', 'design-lab'),
            'paid'             => __('Zaplaceno', 'design-lab'),
            'cancelled'        => __('Zrušeno', 'design-lab'),
            'expired'          => __('Vypršelo', 'design-lab'),
            'failed'           => __('Neúspěšné', 'design-lab'),
        );
        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    private function render_transfer_info($order) {
        $bank         = DLab_Settings::bank();
        $account_name = $bank['account_name'];
        $iban         = $bank['iban'];
        $bic          = $bank['bic'];
        $account_full = $bank['account_full'];
        $vs           = self::variable_symbol($order);
        $qr_message   = __('Místo držíme. Jakmile platbu přijmeme, pošleme potvrzení.', 'design-lab');
        $copy_payload = $this->build_copy_payload($order, $account_name, $account_full, $iban, $bic, $vs);

        ob_start();
        include DLAB_PLUGIN_DIR . 'public/partials/bank-transfer-info.php';
        return ob_get_clean();
    }

    private function build_copy_payload($order, $account_name, $account_full, $iban, $bic, $vs) {
        $lines = array(
            sprintf(__('Částka: %s', 'design-lab'), DLab_Workshop::format_price($order->total)),
        );
        if ($account_name !== '') {
            $lines[] = sprintf(__('Příjemce: %s', 'design-lab'), $account_name);
        }
        if ($account_full !== '') {
            $lines[] = sprintf(__('Účet: %s', 'design-lab'), $account_full);
        }
        if ($iban !== '') {
            $lines[] = 'IBAN: ' . $iban;
        }
        if ($bic !== '') {
            $lines[] = 'SWIFT: ' . $bic;
        }
        if ($vs !== '') {
            $lines[] = sprintf(__('Variabilní symbol: %s', 'design-lab'), $vs);
        }
        return implode("\n", $lines);
    }
}
