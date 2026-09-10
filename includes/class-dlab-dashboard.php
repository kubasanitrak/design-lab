<?php
/**
 * Member dashboard — account settings + bookings (cancel / reschedule).
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Dashboard {

    public function __construct() {
        add_shortcode('dlab_dashboard', array($this, 'render'));
        add_action('wp_ajax_dlab_dashboard_save_profile', array($this, 'ajax_save_profile'));
        add_action('wp_ajax_dlab_dashboard_cancel_order', array($this, 'ajax_cancel_order'));
        add_action('wp_ajax_dlab_dashboard_reschedule_options', array($this, 'ajax_reschedule_options'));
        add_action('wp_ajax_dlab_dashboard_reschedule', array($this, 'ajax_reschedule'));
        add_filter('dlab_enqueue_public_assets', array($this, 'enqueue_assets_flag'));
    }

    public function enqueue_assets_flag($load) {
        return $load || $this->current_page_has_dashboard_shortcode();
    }

    private function current_page_has_dashboard_shortcode() {
        $post = get_post();
        if (!$post || empty($post->post_content)) {
            return false;
        }
        return has_shortcode($post->post_content, 'dlab_dashboard');
    }

    public function render() {
        if (!is_user_logged_in()) {
            $login = DLab_Settings::login_url(DLab_Settings::dashboard_page_url());
            return '<p class="dlab-notice">' .
                esc_html__('Přihlaste se pro zobrazení účtu.', 'design-lab') .
                ' <a class="textlink textlink-underline" href="' . esc_url($login) . '">' . esc_html__('Přihlásit se', 'design-lab') . '</a>' .
                '</p>';
        }

        $user_id = get_current_user_id();
        $user    = wp_get_current_user();
        $profile = self::get_user_profile($user_id);
        $bookings = self::get_user_booking_items($user_id);

        $dashboard_url = DLab_Settings::dashboard_page_url();

        $context = array(
            'profile'       => $profile,
            'bookings'      => $bookings,
            'logout_url'    => wp_logout_url(home_url('/')),
            'password_url'  => wp_lostpassword_url($dashboard_url),
            'user_email'    => $user->user_email,
            'dashboard_url' => $dashboard_url,
        );

        ob_start();
        extract($context, EXTR_SKIP); // phpcs:ignore WordPress.PHP.DontExtract
        include DLAB_PLUGIN_DIR . 'public/partials/dashboard-page.php';
        return ob_get_clean();
    }

    public static function get_user_profile($user_id) {
        $first = get_user_meta($user_id, DLab_Auth::META_FIRST_NAME, true);
        $last  = get_user_meta($user_id, DLab_Auth::META_LAST_NAME, true);
        if ($first === '' && $last === '') {
            $user  = get_userdata($user_id);
            $first = $user ? (string) $user->first_name : '';
            $last  = $user ? (string) $user->last_name : '';
        }

        return array(
            'first_name' => $first,
            'last_name'  => $last,
            'full_name'  => trim($first . ' ' . $last),
            'phone'      => (string) get_user_meta($user_id, DLab_Auth::META_PHONE, true),
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function get_user_booking_items($user_id) {
        $orders = self::get_user_orders($user_id);
        $items  = array();

        foreach ($orders as $order) {
            if (in_array($order->status, array('cancelled', 'expired', 'failed'), true)) {
                continue;
            }

            $full = DLab_Checkout::get_order((int) $order->id);
            if (!$full || empty($full->items)) {
                continue;
            }

            foreach ($full->items as $line) {
                $built = self::build_booking_item($full, $line);
                if ($built) {
                    $items[] = $built;
                }
            }
        }

        return $items;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function build_booking_item($order, $line) {
        $object_id = (int) $line->object_id;
        $spots     = max(1, (int) $order->spots);

        return array(
            'order_id'     => (int) $order->id,
            'item_id'      => (int) $line->id,
            'order_number' => $order->order_number,
            'status'       => $order->status,
            'status_label' => DLab_Checkout::status_label($order->status),
            'object_id'    => $object_id,
            'title'        => $line->post_title ?: get_the_title($object_id),
            'schedule'     => DLab_Workshop::get_schedule_summary($object_id),
            'spots'        => $spots,
            'line_total'   => DLab_Workshop::format_price((float) $line->line_total),
            'payment_url'  => $order->status === 'awaiting_payment' ? DLab_Checkout::payment_url($order) : '',
            'cancellation' => self::get_cancellation_state($order, $object_id),
            'hash'         => self::booking_hash((int) $order->id, (int) $line->id),
        );
    }

    public static function booking_hash($order_id, $item_id) {
        return 'booking/' . (int) $order_id . '/' . (int) $item_id;
    }

    /**
     * @return array{action:string,message:string,can_cancel:bool,can_reschedule:bool}
     */
    public static function get_cancellation_state($order, $object_id) {
        $terminal = array('cancelled', 'expired', 'failed');
        if (in_array($order->status, $terminal, true)) {
            return array(
                'action'         => 'none',
                'message'        => '',
                'can_cancel'     => false,
                'can_reschedule' => false,
            );
        }

        $start_ts = DLab_Workshop::get_start_timestamp($object_id);
        if ($start_ts && $start_ts <= time()) {
            return array(
                'action'         => 'none',
                'message'        => __('Workshop již začal — rezervaci nelze zrušit ani přesunout.', 'design-lab'),
                'can_cancel'     => false,
                'can_reschedule' => false,
            );
        }

        $hours         = DLab_Settings::cancel_hours_before_start();
        $can_cancel    = true;
        $can_reschedule = true;

        if ($start_ts && $hours > 0) {
            $seconds_until = $start_ts - time();
            if ($seconds_until <= ($hours * HOUR_IN_SECONDS)) {
                $can_cancel = false;
            }
        }

        if ($can_cancel) {
            return array(
                'action'         => 'cancel',
                'message'        => '',
                'can_cancel'     => true,
                'can_reschedule' => $can_reschedule,
            );
        }

        return array(
            'action'         => 'reschedule',
            'message'        => sprintf(
                /* translators: %d: hours before start */
                __('Lhůta pro zrušení (%d h před začátkem) již uplynula. Rezervaci můžete přesunout na jiný workshop.', 'design-lab'),
                $hours
            ),
            'can_cancel'     => false,
            'can_reschedule' => true,
        );
    }

    public static function get_user_orders($user_id) {
        global $wpdb;

        if (!DLab_DB::table_exists('dlab_orders')) {
            return array();
        }

        $table = $wpdb->prefix . 'dlab_orders';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT 50",
            $user_id
        ));
    }

    public function ajax_save_profile() {
        check_ajax_referer('dlab_public', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Přihlaste se.', 'design-lab')));
        }

        $user_id = get_current_user_id();
        $first   = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '';
        $last    = isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '';
        $phone   = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $email   = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

        if ($first === '' || $last === '') {
            wp_send_json_error(array('message' => __('Vyplňte jméno a příjmení.', 'design-lab')));
        }
        if ($phone === '') {
            wp_send_json_error(array('message' => __('Vyplňte telefon.', 'design-lab')));
        }
        if ($email === '' || !is_email($email)) {
            wp_send_json_error(array('message' => __('Zadejte platný e-mail.', 'design-lab')));
        }

        $existing = email_exists($email);
        if ($existing && (int) $existing !== $user_id) {
            wp_send_json_error(array('message' => __('Tento e-mail už používá jiný účet.', 'design-lab')));
        }

        $updated = wp_update_user(array(
            'ID'           => $user_id,
            'user_email'   => $email,
            'first_name'   => $first,
            'last_name'    => $last,
            'display_name' => trim($first . ' ' . $last),
        ));

        if (is_wp_error($updated)) {
            wp_send_json_error(array('message' => $updated->get_error_message()));
        }

        update_user_meta($user_id, DLab_Auth::META_FIRST_NAME, $first);
        update_user_meta($user_id, DLab_Auth::META_LAST_NAME, $last);
        update_user_meta($user_id, DLab_Auth::META_PHONE, $phone);

        wp_send_json_success(array(
            'message'   => __('Nastavení bylo uloženo.', 'design-lab'),
            'full_name' => trim($first . ' ' . $last),
            'email'     => $email,
        ));
    }

    public function ajax_cancel_order() {
        check_ajax_referer('dlab_public', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Přihlaste se.', 'design-lab')));
        }

        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        if (!$order_id) {
            wp_send_json_error(array('message' => __('Neplatná rezervace.', 'design-lab')));
        }

        global $wpdb;
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dlab_orders WHERE id = %d AND user_id = %d",
            $order_id,
            get_current_user_id()
        ));

        if (!$order) {
            wp_send_json_error(array('message' => __('Rezervace nebyla nalezena.', 'design-lab')));
        }

        if (in_array($order->status, array('cancelled', 'expired', 'failed'), true)) {
            wp_send_json_error(array('message' => __('Tuto rezervaci již nelze zrušit.', 'design-lab')));
        }

        $full = DLab_Checkout::get_order($order_id);
        if (!$full || empty($full->items)) {
            wp_send_json_error(array('message' => __('Rezervace nebyla nalezena.', 'design-lab')));
        }

        $can_cancel_any = false;
        foreach ($full->items as $line) {
            $state = self::get_cancellation_state($order, (int) $line->object_id);
            if (!empty($state['can_cancel'])) {
                $can_cancel_any = true;
                break;
            }
        }

        if (!$can_cancel_any) {
            wp_send_json_error(array('message' => __('Lhůta pro zrušení již uplynula.', 'design-lab')));
        }

        DLab_Checkout::cancel_order($order_id);

        wp_send_json_success(array(
            'message' => __('Rezervace byla zrušena.', 'design-lab'),
        ));
    }

    public function ajax_reschedule_options() {
        check_ajax_referer('dlab_public', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Přihlaste se.', 'design-lab')));
        }

        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $item_id  = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;

        if (!$this->user_owns_order_item($order_id, $item_id)) {
            wp_send_json_error(array('message' => __('Rezervace nebyla nalezena.', 'design-lab')));
        }

        $order = DLab_Checkout::get_order($order_id);
        $line  = null;
        foreach ($order->items as $candidate) {
            if ((int) $candidate->id === $item_id) {
                $line = $candidate;
                break;
            }
        }
        if (!$line) {
            wp_send_json_error(array('message' => __('Položka rezervace nebyla nalezena.', 'design-lab')));
        }

        $state = self::get_cancellation_state($order, (int) $line->object_id);
        if (empty($state['can_reschedule'])) {
            wp_send_json_error(array('message' => __('Tuto položku nelze přesunout.', 'design-lab')));
        }

        wp_send_json_success(array(
            'options' => DLab_Checkout::get_reschedule_options($order_id, $item_id),
        ));
    }

    public function ajax_reschedule() {
        check_ajax_referer('dlab_public', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Přihlaste se.', 'design-lab')));
        }

        $order_id    = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $item_id     = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
        $workshop_id = isset($_POST['workshop_id']) ? (int) $_POST['workshop_id'] : 0;

        if (!$workshop_id || !$this->user_owns_order_item($order_id, $item_id)) {
            wp_send_json_error(array('message' => __('Neplatný požadavek.', 'design-lab')));
        }

        $order = DLab_Checkout::get_order($order_id);
        $line  = null;
        foreach ($order->items as $candidate) {
            if ((int) $candidate->id === $item_id) {
                $line = $candidate;
                break;
            }
        }
        if (!$line) {
            wp_send_json_error(array('message' => __('Položka rezervace nebyla nalezena.', 'design-lab')));
        }

        $state = self::get_cancellation_state($order, (int) $line->object_id);
        if (empty($state['can_reschedule'])) {
            wp_send_json_error(array('message' => __('Tuto položku nelze přesunout.', 'design-lab')));
        }

        $result = DLab_Checkout::reschedule_order_item($order_id, $item_id, $workshop_id);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        $updated = DLab_Checkout::get_order($order_id);
        $msg     = __('Workshop byl přesunut.', 'design-lab');
        if ($updated && $order->status === 'paid' && (float) $updated->total !== (float) $order->total) {
            $msg .= ' ' . sprintf(
                /* translators: %s: new total */
                __('Nová cena rezervace: %s. Případný doplatek / přeplatek řešte s námi.', 'design-lab'),
                DLab_Workshop::format_price($updated->total)
            );
        }

        wp_send_json_success(array('message' => $msg));
    }

    private function user_owns_order_item($order_id, $item_id) {
        global $wpdb;

        $order_id = (int) $order_id;
        $item_id  = (int) $item_id;
        if (!$order_id || !$item_id) {
            return false;
        }

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dlab_orders WHERE id = %d AND user_id = %d",
            $order_id,
            get_current_user_id()
        ));
        if (!$order) {
            return false;
        }

        $item = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dlab_order_items WHERE id = %d AND order_id = %d",
            $item_id,
            $order_id
        ));

        return (bool) $item;
    }
}
