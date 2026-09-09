<?php
/**
 * @var array  $orders
 * @var string $status_filter
 * @var int    $paged
 * @var int    $total_pages
 * @var array  $actionable_statuses
 */

if (!defined('ABSPATH')) {
    exit;
}

$statuses = array(
    ''                 => __('Vše', 'design-lab'),
    'awaiting_payment' => __('Čeká na platbu', 'design-lab'),
    'paid'             => __('Zaplaceno', 'design-lab'),
    'cancelled'        => __('Zrušeno', 'design-lab'),
    'expired'          => __('Vypršelo', 'design-lab'),
);
?>
<div class="wrap dlab-admin-wrap">
    <h1><?php esc_html_e('Rezervace', 'design-lab'); ?></h1>

    <?php if (!empty($_GET['dlab_msg'])) : ?>
        <?php if ($_GET['dlab_msg'] === 'payment_confirmed') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Platba byla potvrzena.', 'design-lab'); ?></p></div>
        <?php elseif ($_GET['dlab_msg'] === 'order_cancelled') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Rezervace byla zrušena.', 'design-lab'); ?></p></div>
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
                            <?php if (in_array($row->status, $actionable_statuses, true)) : ?>
                                <a class="button button-small button-primary"
                                   href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=dlab-orders&dlab_action=confirm_payment&order_id=' . (int) $row->id), 'dlab_admin_order')); ?>"
                                   onclick="return confirm('<?php echo esc_js(__('Potvrdit přijetí platby?', 'design-lab')); ?>');">
                                    <?php esc_html_e('Potvrdit platbu', 'design-lab'); ?>
                                </a>
                                <a class="button button-small"
                                   href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=dlab-orders&dlab_action=cancel_order&order_id=' . (int) $row->id), 'dlab_admin_order')); ?>"
                                   onclick="return confirm('<?php echo esc_js(__('Zrušit rezervaci?', 'design-lab')); ?>');">
                                    <?php esc_html_e('Zrušit', 'design-lab'); ?>
                                </a>
                            <?php endif; ?>
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
