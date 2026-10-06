<?php
/**
 * Admin orders list and payment actions.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Admin_Orders {

    const PAGE_SLUG = 'dlab-orders';

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 11);
        add_action('admin_init', array($this, 'handle_actions'));
    }

    public static function actionable_statuses() {
        return array('pending', 'awaiting_payment');
    }

    public function register_menu() {
        add_submenu_page(
            DLab_Admin::MENU_SLUG,
            __('Rezervace', 'design-lab'),
            __('Rezervace', 'design-lab'),
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_page')
        );
    }

    public function handle_actions() {
        if (isset($_POST['dlab_set_status'], $_POST['order_id'], $_POST['status'], $_POST['_wpnonce'])) {
            $this->handle_set_status();
            return;
        }

        if (!isset($_GET['dlab_action'], $_GET['order_id'], $_GET['_wpnonce'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'dlab_admin_order')) {
            return;
        }

        $order_id = (int) $_GET['order_id'];
        $order    = DLab_Checkout::get_order($order_id);
        if (!$order) {
            return;
        }

        $action   = sanitize_key(wp_unslash($_GET['dlab_action']));
        $redirect = admin_url('admin.php?page=' . self::PAGE_SLUG);

        switch ($action) {
            case 'confirm_payment':
                if (in_array($order->status, self::actionable_statuses(), true)) {
                    DLab_Checkout::mark_paid($order_id);
                    $redirect = add_query_arg('dlab_msg', 'payment_confirmed', $redirect);
                }
                break;
            case 'cancel_order':
                if (in_array($order->status, self::actionable_statuses(), true)) {
                    DLab_Checkout::cancel_order($order_id);
                    $redirect = add_query_arg('dlab_msg', 'order_cancelled', $redirect);
                }
                break;
        }

        wp_safe_redirect($redirect);
        exit;
    }

    private function handle_set_status() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $order_id = (int) $_POST['order_id'];
        $nonce    = sanitize_text_field(wp_unslash($_POST['_wpnonce']));
        if (!wp_verify_nonce($nonce, 'dlab_admin_set_status_' . $order_id)) {
            wp_safe_redirect(add_query_arg('dlab_msg', 'status_error', admin_url('admin.php?page=' . self::PAGE_SLUG)));
            exit;
        }

        $status = sanitize_key(wp_unslash($_POST['status']));
        $result = DLab_Checkout::admin_set_status($order_id, $status);

        $redirect = admin_url('admin.php?page=' . self::PAGE_SLUG);
        $filter   = isset($_POST['status_filter']) ? sanitize_key(wp_unslash($_POST['status_filter'])) : '';
        $paged    = isset($_POST['paged']) ? max(1, (int) $_POST['paged']) : 1;
        if ($filter !== '') {
            $redirect = add_query_arg('status', $filter, $redirect);
        }
        if ($paged > 1) {
            $redirect = add_query_arg('paged', $paged, $redirect);
        }

        if ($result === 'unchanged') {
            $redirect = add_query_arg('dlab_msg', 'status_unchanged', $redirect);
        } elseif (is_wp_error($result)) {
            $redirect = add_query_arg('dlab_msg', 'status_error', $redirect);
        } elseif ($status === 'paid') {
            $redirect = add_query_arg('dlab_msg', 'payment_confirmed', $redirect);
        } elseif ($status === 'cancelled') {
            $redirect = add_query_arg('dlab_msg', 'order_cancelled', $redirect);
        } else {
            $redirect = add_query_arg('dlab_msg', 'status_updated', $redirect);
        }

        wp_safe_redirect($redirect);
        exit;
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;

        $status_filter = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
        $paged         = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $per_page      = 20;
        $orders        = array();
        $total_pages   = 1;

        if (DLab_DB::table_exists('dlab_orders')) {
            $table  = $wpdb->prefix . 'dlab_orders';
            $where  = '1=1';
            $params = array();
            if ($status_filter === 'awaiting_payment') {
                $where .= " AND status IN ('awaiting_payment', 'pending')";
            } elseif ($status_filter !== '') {
                $where   .= ' AND status = %s';
                $params[] = $status_filter;
            }

            $count_sql = "SELECT COUNT(*) FROM $table WHERE $where";
            $total     = (int) ($params
                ? $wpdb->get_var($wpdb->prepare($count_sql, ...$params))
                : $wpdb->get_var($count_sql));
            $total_pages = max(1, (int) ceil($total / $per_page));
            $offset      = ($paged - 1) * $per_page;

            $sql      = "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
            $params[] = $per_page;
            $params[] = $offset;
            $orders   = $wpdb->get_results($wpdb->prepare($sql, ...$params));
            if (!is_array($orders)) {
                $orders = array();
            }
        }

        include DLAB_PLUGIN_DIR . 'admin/partials/orders-page.php';
    }
}
