<?php
/**
 * Transactional e-mails for reservations.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Emails {

    public static function send_verification_email($user_id, $token) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $url  = DLab_Auth::get_verification_url($user_id, $token);
        $blog = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);

        $subject = sprintf(
            /* translators: %s: site name */
            __('[%s] Ověření registrace – Design Lab', 'design-lab'),
            $blog
        );

        $body = sprintf(
            /* translators: 1: display name, 2: site name, 3: verification url */
            __("Dobrý den %1\$s,\n\npro dokončení registrace na %2\$s (Design Lab) klikněte na odkaz:\n\n%3\$s\n\nPo ověření si nastavíte heslo.\n\nPokud jste se neregistrovali, tento e-mail ignorujte.\n", 'design-lab'),
            $user->display_name,
            $blog,
            $url
        );

        return self::mail($user->user_email, $subject, $body);
    }

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

        $is_html = DLab_Settings::email_template_type() === 'html';
        $qr_png  = '';
        $embed   = null;

        if ($is_html) {
            $qr_png = self::fetch_order_qr_png($order);
            $body   = self::order_confirmation_html($order, $contact, $qr_png !== '');
            if ($qr_png !== '') {
                $embed = static function ($phpmailer) use ($qr_png) {
                    $phpmailer->addStringEmbeddedImage($qr_png, 'dlab-qr', 'qr-platba.png', 'base64', 'image/png');
                };
                add_action('phpmailer_init', $embed);
            }
            self::mail($contact['email'], $subject, $body, true);
            if ($embed) {
                remove_action('phpmailer_init', $embed);
            }
        } else {
            self::mail($contact['email'], $subject, self::order_confirmation_plain($order, $contact));
        }

        self::notify_admin_new_order($order_id);

        return true;
    }

    private static function order_confirmation_plain($order, array $contact) {
        $lines = array();
        foreach ($order->items as $item) {
            $lines[] = sprintf('- %s: %s', $item->post_title, DLab_Workshop::format_price($item->line_total));
        }

        $name        = $contact['name'] !== '' ? $contact['name'] : $contact['email'];
        $payment_url = DLab_Checkout::payment_url($order);
        $expires     = '';
        if (!empty($order->expires_at)) {
            $expires = "\n" . sprintf(
                /* translators: %s: datetime */
                __('Platbu prosím uhraďte do: %s', 'design-lab'),
                date_i18n('j. n. Y H:i', strtotime($order->expires_at))
            ) . "\n";
        }

        $bank_lines = array();
        foreach (self::payment_rows($order) as $row) {
            $bank_lines[] = $row['label'] . ': ' . $row['value'];
        }

        $qr = new DLab_QR();
        $qr_url = $qr->generate_order_qr($order, 280);
        if ($qr_url) {
            $bank_lines[] = __('QR platba', 'design-lab') . ': ' . $qr_url;
        }

        return sprintf(
            /* translators: 1: name, 2: order number, 3: item list, 4: total, 5: expiry block, 6: bank details, 7: payment url note */
            __("Dobrý den %1\$s,\n\nvaše rezervace %2\$s byla přijata.\n\n%3\$s\n\nCelkem: %4\$s%5\$s\n\n%6\$s\n\n%7\$s\n", 'design-lab'),
            $name,
            $order->order_number,
            implode("\n", $lines),
            DLab_Workshop::format_price($order->total),
            $expires,
            implode("\n", $bank_lines),
            $payment_url
                ? sprintf(__('Platební instrukce: %s', 'design-lab'), $payment_url)
                : __('Platební instrukce najdete na webu.', 'design-lab')
        );
    }

    private static function order_confirmation_html($order, array $contact, $has_embedded_qr) {
        $name = $contact['name'] !== '' ? $contact['name'] : $contact['email'];
        $rows = '';
        foreach ($order->items as $item) {
            $rows .= '<tr><td style="padding:8px 12px 8px 0;border-bottom:1px solid #e6e6e6;">'
                . esc_html($item->post_title)
                . '</td><td style="padding:8px 0;border-bottom:1px solid #e6e6e6;text-align:right;white-space:nowrap;">'
                . esc_html(DLab_Workshop::format_price($item->line_total))
                . '</td></tr>';
        }

        $bank = '';
        foreach (self::payment_rows($order) as $row) {
            $bank .= '<tr><td style="padding:6px 16px 6px 0;color:#555;">'
                . esc_html($row['label'])
                . '</td><td style="padding:6px 0;"><strong>'
                . esc_html($row['value'])
                . '</strong></td></tr>';
        }

        $expires = '';
        if (!empty($order->expires_at)) {
            $expires = '<p style="margin:16px 0 0;">'
                . esc_html(sprintf(
                    /* translators: %s: datetime */
                    __('Platbu prosím uhraďte do: %s', 'design-lab'),
                    date_i18n('j. n. Y H:i', strtotime($order->expires_at))
                ))
                . '</p>';
        }

        $qr_html = '';
        $qr      = new DLab_QR();
        $qr_url  = $qr->generate_order_qr($order, 280);
        if ($has_embedded_qr) {
            $qr_html = '<p style="margin:20px 0 8px;"><strong>' . esc_html__('QR platba', 'design-lab') . '</strong></p>'
                . '<img src="cid:dlab-qr" width="220" height="220" alt="' . esc_attr__('QR platba', 'design-lab') . '" style="display:block;border:0;">';
        } elseif ($qr_url) {
            $qr_html = '<p style="margin:20px 0 8px;"><strong>' . esc_html__('QR platba', 'design-lab') . '</strong></p>'
                . '<img src="' . esc_url($qr_url) . '" width="220" height="220" alt="' . esc_attr__('QR platba', 'design-lab') . '" style="display:block;border:0;">';
        }

        $payment_url = DLab_Checkout::payment_url($order);
        $link        = '';
        if ($payment_url) {
            $link = '<p style="margin:20px 0 0;"><a href="' . esc_url($payment_url) . '">'
                . esc_html__('Otevřít platební instrukce', 'design-lab')
                . '</a></p>';
        }

        $intro = sprintf(
            /* translators: 1: name, 2: order number */
            __('Dobrý den %1$s, vaše rezervace %2$s byla přijata.', 'design-lab'),
            $name,
            $order->order_number
        );

        return '<div style="font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:1.5;color:#111;">'
            . '<p style="margin:0 0 16px;">' . esc_html($intro) . '</p>'
            . '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 16px;">'
            . $rows
            . '<tr><td style="padding:12px 12px 0 0;"><strong>' . esc_html__('Celkem', 'design-lab') . '</strong></td>'
            . '<td style="padding:12px 0 0;text-align:right;"><strong>' . esc_html(DLab_Workshop::format_price($order->total)) . '</strong></td></tr>'
            . '</table>'
            . '<p style="margin:20px 0 8px;"><strong>' . esc_html__('Platební údaje', 'design-lab') . '</strong></p>'
            . '<table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">' . $bank . '</table>'
            . $expires
            . $qr_html
            . $link
            . '</div>';
    }

    /**
     * @return array<int, array{label:string,value:string}>
     */
    private static function payment_rows($order) {
        $bank = DLab_Settings::bank();
        $rows = array();

        if ($bank['account_name'] !== '') {
            $rows[] = array(
                'label' => __('Příjemce', 'design-lab'),
                'value' => $bank['account_name'],
            );
        }
        if ($bank['account_full'] !== '') {
            $rows[] = array(
                'label' => __('Účet', 'design-lab'),
                'value' => $bank['account_full'],
            );
        }
        if ($bank['iban'] !== '') {
            $rows[] = array(
                'label' => 'IBAN',
                'value' => $bank['iban'],
            );
        }
        if ($bank['bic'] !== '') {
            $rows[] = array(
                'label' => 'SWIFT',
                'value' => $bank['bic'],
            );
        }

        $rows[] = array(
            'label' => __('Variabilní symbol', 'design-lab'),
            'value' => DLab_Checkout::variable_symbol($order),
        );
        $rows[] = array(
            'label' => __('Částka', 'design-lab'),
            'value' => DLab_Workshop::format_price($order->total),
        );

        return $rows;
    }

    private static function fetch_order_qr_png($order) {
        $qr  = new DLab_QR();
        $url = $qr->generate_order_qr($order, 280);
        if (!$url) {
            return '';
        }

        $response = wp_remote_get($url, array('timeout' => 10));
        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return '';
        }

        $body = wp_remote_retrieve_body($response);
        if (!is_string($body) || strlen($body) < 80) {
            return '';
        }

        return $body;
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

    private static function mail($to, $subject, $body, $raw_html = false) {
        $is_html = $raw_html || DLab_Settings::email_template_type() === 'html';
        $headers = array(
            'Content-Type: ' . ($is_html ? 'text/html' : 'text/plain') . '; charset=UTF-8',
        );

        $from_name  = DLab_Settings::email_sender_name();
        $from_email = DLab_Settings::email_sender_email();
        if ($from_email) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }

        if ($is_html && !$raw_html) {
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
