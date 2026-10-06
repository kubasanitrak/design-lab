<?php
/**
 * @var array  $orders
 * @var string $status_filter
 * @var int    $paged
 * @var int    $total_pages
 */

if (!defined('ABSPATH')) {
    exit;
}

$statuses = array(
    ''                 => __('Vše', 'design-lab'),
    'awaiting_payment' => __('Pending', 'design-lab'),
    'paid'             => __('Zaplaceno', 'design-lab'),
    'cancelled'        => __('Zrušeno', 'design-lab'),
    'expired'          => __('Vypršelo', 'design-lab'),
);
$status_options = DLab_Checkout::admin_status_options();
?>
<div class="wrap dlab-admin-wrap">
    <h1><?php esc_html_e('Rezervace', 'design-lab'); ?></h1>

    <?php if (!empty($_GET['dlab_msg'])) : ?>
        <?php if ($_GET['dlab_msg'] === 'payment_confirmed') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Platba byla potvrzena. Potvrzení rezervace bylo odesláno zákazníkovi i na e-mail Design Labu.', 'design-lab'); ?></p></div>
        <?php elseif ($_GET['dlab_msg'] === 'order_cancelled') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Rezervace byla zrušena.', 'design-lab'); ?></p></div>
        <?php elseif ($_GET['dlab_msg'] === 'status_unchanged') : ?>
            <div class="notice notice-info is-dismissible"><p><?php esc_html_e('Stav rezervace už je nastavený.', 'design-lab'); ?></p></div>
        <?php elseif ($_GET['dlab_msg'] === 'status_updated') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Stav rezervace byl změněn.', 'design-lab'); ?></p></div>
        <?php elseif ($_GET['dlab_msg'] === 'status_error') : ?>
            <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Stav rezervace se nepodařilo změnit.', 'design-lab'); ?></p></div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="get" class="dlab-orders-filter" style="margin:1em 0;">
        <input type="hidden" name="page" value="dlab-orders">
        <select name="status" onchange="this.form.submit()">
            <?php foreach ($statuses as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($status_filter, $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Rezervace', 'design-lab'); ?></th>
                <th><?php esc_html_e('Zákazník', 'design-lab'); ?></th>
                <th><?php esc_html_e('Částka', 'design-lab'); ?></th>
                <th><?php esc_html_e('Stav', 'design-lab'); ?></th>
                <th><?php esc_html_e('Datum', 'design-lab'); ?></th>
                <th><?php esc_html_e('Akce', 'design-lab'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)) : ?>
                <tr><td colspan="6"><?php esc_html_e('Žádné rezervace.', 'design-lab'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($orders as $row) :
                    $contact = json_decode($row->contact_data, true);
                    if (!is_array($contact)) {
                        $contact = array();
                    }
                    $name  = isset($contact['name']) ? $contact['name'] : '';
                    $email = isset($contact['email']) ? $contact['email'] : '';
                    $pay_url = add_query_arg(
                        array(
                            'order'  => (int) $row->id,
                            'token'  => $row->access_token,
                            'method' => 'bank_transfer',
                        ),
                        DLab_Settings::checkout_page_url()
                    );
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($row->order_number); ?></strong>
                            <br><small>VS <?php echo esc_html(DLab_Settings::variable_symbol($row->order_number)); ?></small>
                        </td>
                        <td>
                            <?php echo esc_html($name !== '' ? $name : '—'); ?><br>
                            <small><?php echo esc_html($email); ?></small>
                        </td>
                        <td><?php echo esc_html(DLab_Workshop::format_price($row->total)); ?></td>
                        <td>
                            <span class="dlab-status dlab-status--<?php echo esc_attr($row->status); ?>">
                                <?php echo esc_html(DLab_Checkout::status_label($row->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date_i18n('j. n. Y H:i', strtotime($row->created_at))); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url($pay_url); ?>" target="_blank" rel="noopener">
                                <?php esc_html_e('Platba', 'design-lab'); ?>
                            </a>
                            <?php
                            $current_status = $row->status === 'pending' ? 'awaiting_payment' : $row->status;
                            ?>
                            <form method="post" class="dlab-status-form">
                                <?php wp_nonce_field('dlab_admin_set_status_' . (int) $row->id); ?>
                                <input type="hidden" name="dlab_set_status" value="1">
                                <input type="hidden" name="order_id" value="<?php echo esc_attr((string) (int) $row->id); ?>">
                                <input type="hidden" name="status_filter" value="<?php echo esc_attr($status_filter); ?>">
                                <input type="hidden" name="paged" value="<?php echo esc_attr((string) $paged); ?>">
                                <label class="screen-reader-text" for="dlab-status-<?php echo esc_attr((string) (int) $row->id); ?>">
                                    <?php esc_html_e('Stav rezervace', 'design-lab'); ?>
                                </label>
                                <select id="dlab-status-<?php echo esc_attr((string) (int) $row->id); ?>" name="status">
                                    <?php foreach ($status_options as $value => $label) : ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($current_status, $value); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="button button-small"
                                    onclick="return confirm('<?php echo esc_js(__('Nastavit stav rezervace? U stavu Zaplaceno se odešle potvrzení e-mailem.', 'design-lab')); ?>');">
                                    <?php esc_html_e('Nastavit stav', 'design-lab'); ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1) : ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                echo wp_kses_post(paginate_links(array(
                    'base'      => add_query_arg('paged', '%#%'),
                    'format'    => '',
                    'current'   => $paged,
                    'total'     => $total_pages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                )));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>
