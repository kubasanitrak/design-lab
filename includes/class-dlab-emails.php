<?php
/**
 * Transactional e-mails for reservations.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Emails {

    public static function send_order_placed_email($order_id) {
        $order = DLab_Checkout::get_order($order_id);
        if (!$order) {
            return false;
        }

        $contact = DLab_Checkout::contact_from_order($order);
        if ($contact['email'] === '' || !is_email($contact['email'])) {
            return false;
        }

        $blog    = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $subject = sprintf(
            /* translators: 1: site name, 2: order number */
            __('[%1$s] Potvrzení rezervace %2$s', 'design-lab'),
            $blog,
            $order->order_number
        );

        $lines = array();
        foreach ($order->items as $item) {
            $lines[] = sprintf('- %s: %s', $item->post_title, DLab_Workshop::format_price($item->line_total));
        }

        $payment_url = DLab_Checkout::payment_url($order);
        $expires     = '';
        if (!empty($order->expires_at)) {
            $expires = "\n" . sprintf(
                /* translators: %s: datetime */
                __('Platbu prosím uhraďte do: %s', 'design-lab'),
                date_i18n('j. n. Y H:i', strtotime($order->expires_at))
            ) . "\n";
        }

        $body = sprintf(
            /* translators: 1: name, 2: order number, 3: item list, 4: total, 5: expiry block, 6: payment url note */
            __("Dobrý den %1\$s,\n\nvaše rezervace %2\$s byla přijata.\n\n%3\$s\n\nCelkem: %4\$s%5\$s\n\n%6\$s\n", 'design-lab'),
            $contact['name'] !== '' ? $contact['name'] : $contact['email'],
            $order->order_number,
            implode("\n", $lines),
            DLab_Workshop::format_price($order->total),
            $expires,
            $payment_url
                ? sprintf(__('Platební instrukce a QR kód: %s', 'design-lab'), $payment_url)
                : __('Platební instrukce najdete na webu.', 'design-lab')
        );

        self::mail($contact['email'], $subject, $body);
        self::notify_admin_new_order($order_id);

        return true;
    }

    public static function send_payment_confirmed_email($order_id) {
        $order = DLab_Checkout::get_order($order_id);
        if (!$order) {
            return false;
        }

        $contact = DLab_Checkout::contact_from_order($order);
        if ($contact['email'] === '' || !is_email($contact['email'])) {
            return false;
        }

        $blog    = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $subject = sprintf(
            /* translators: 1: site name, 2: order number */
            __('[%1$s] Platba přijata – %2$s', 'design-lab'),
            $blog,
            $order->order_number
        );
        $body = sprintf(
            /* translators: 1: name, 2: order number */
            __("Dobrý den %1\$s,\n\nplatba za rezervaci %2\$s byla přijata. Rezervace je potvrzena.\n", 'design-lab'),
            $contact['name'] !== '' ? $contact['name'] : $contact['email'],
            $order->order_number
        );

        return self::mail($contact['email'], $subject, $body);
    }

    public static function send_expiry_notification($order_id) {
        $order = DLab_Checkout::get_order($order_id);
        if (!$order || $order->status !== 'awaiting_payment') {
            return false;
        }

        $contact = DLab_Checkout::contact_from_order($order);
        if ($contact['email'] === '' || !is_email($contact['email'])) {
            return false;
        }

        $blog        = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $subject     = sprintf(
            /* translators: 1: site name, 2: order number */
            __('[%1$s] Platba za rezervaci %2$s brzy vyprší', 'design-lab'),
            $blog,
            $order->order_number
        );
        $payment_url = DLab_Checkout::payment_url($order);

        $body = sprintf(
            /* translators: 1: name, 2: order number, 3: amount, 4: expiry datetime, 5: payment url */
            __("Dobrý den %1\$s,\n\npřipomínáme platbu rezervace %2\$s (částka %3\$s).\nLhůta platby vyprší: %4\$s\n\n%5\$s\n", 'design-lab'),
            $contact['name'] !== '' ? $contact['name'] : $contact['email'],
            $order->order_number,
            DLab_Workshop::format_price($order->total),
            !empty($order->expires_at) ? date_i18n('j. n. Y H:i', strtotime($order->expires_at)) : '—',
            $payment_url ? __('Instrukce k platbě: ', 'design-lab') . $payment_url : ''
        );

        return self::mail($contact['email'], $subject, $body);
    }

    private static function notify_admin_new_order($order_id) {
        if (!DLab_Settings::admin_notification_enabled()) {
            return false;
        }

        $email = DLab_Settings::admin_notification_email();
        if ($email === '' || !is_email($email)) {
            return false;
        }

        $order = DLab_Checkout::get_order($order_id);
        if (!$order) {
            return false;
        }

        $blog    = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $subject = sprintf(
            /* translators: 1: site name, 2: order number */
            __('[%1$s] Nová rezervace %2$s', 'design-lab'),
            $blog,
            $order->order_number
        );
        $body = sprintf(
            /* translators: 1: order number, 2: amount, 3: admin url */
            __("Nová rezervace %1\$s — %2\$s\n\nSpráva: %3\$s\n", 'design-lab'),
            $order->order_number,
            DLab_Workshop::format_price($order->total),
            admin_url('admin.php?page=dlab-orders')
        );

        return self::mail($email, $subject, $body);
    }

    private static function mail($to, $subject, $body) {
        $is_html = DLab_Settings::email_template_type() === 'html';
        $headers = array(
            'Content-Type: ' . ($is_html ? 'text/html' : 'text/plain') . '; charset=UTF-8',
        );

        $from_name  = DLab_Settings::email_sender_name();
        $from_email = DLab_Settings::email_sender_email();
        if ($from_email) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }

        if ($is_html) {
            $body = '<p>' . nl2br(esc_html($body)) . '</p>';
            $body = preg_replace_callback(
                '#https?://[^\s<]+#',
                function ($m) {
                    $url = esc_url($m[0]);
                    return '<a href="' . $url . '">' . esc_html($m[0]) . '</a>';
                },
                $body
            );
        }

        return wp_mail($to, $subject, $body, $headers);
    }
}
