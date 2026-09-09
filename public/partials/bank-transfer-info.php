<?php
/**
 * Bank transfer recap + QR after reservation.
 *
 * @var object $order
 * @var string $account_name
 * @var string $iban
 * @var string $bic
 * @var string $vs
 * @var string $account_full
 * @var string $copy_payload
 * @var string $qr_message
 */

if (!defined('ABSPATH')) {
    exit;
}

$listing_url = DLab_Settings::listing_page_url();
?>
<div data-theme="DD-beige" class="section scroll-trigger section-content section-content--dilna section-full-width pad-B-4 dlab-bank-transfer" id="dlab-bank-transfer">
    <div class="inner-content">
        <div class="wp-block-group single-col single-col--narrow">
            <div class="wp-block-group__inner-container is-layout-constrained wp-block-group-is-layout-constrained">
                <h1 class="wp-block-heading has-text-align-center"><strong><?php esc_html_e('Platební instrukce', 'design-lab'); ?></strong></h1>
                <?php if (in_array($order->status, array('cancelled', 'expired', 'failed'), true)) : ?>
                    <p class="dlab-checkout__notice is-error">
                        <?php echo esc_html(sprintf(
                            /* translators: %s: status label */
                            __('Tato rezervace už není aktivní (%s).', 'design-lab'),
                            DLab_Checkout::status_label($order->status)
                        )); ?>
                    </p>
                <?php elseif ($order->status === 'paid') : ?>
                    <p class="dlab-bank-transfer__hold">
                        <?php esc_html_e('Platba byla přijata. Rezervace je potvrzena.', 'design-lab'); ?>
                    </p>
                <?php else : ?>
                    <p class="dlab-bank-transfer__hold">
                        <?php esc_html_e('Místo držíme. Jakmile platbu přijmeme, pošleme potvrzení.', 'design-lab'); ?>
                    </p>
                <?php endif; ?>

                <p><strong><?php esc_html_e('Číslo rezervace:', 'design-lab'); ?></strong> <?php echo esc_html($order->order_number); ?></p>
                <p><strong><?php esc_html_e('Částka:', 'design-lab'); ?></strong> <?php echo esc_html(DLab_Workshop::format_price($order->total)); ?></p>

                <?php if (!empty($order->items)) : ?>
                    <ul class="dlab-pass__list dlab-checkout__list">
                        <?php foreach ($order->items as $item) : ?>
                            <li class="dlab-pass-line">
                                <div class="dlab-pass-line__header">
                                    <div>
                                        <h3 class="dlab-pass-line__title"><strong><?php echo esc_html($item->post_title); ?></strong></h3>
                                        <?php if (!empty($item->schedule)) : ?>
                                            <p class="dlab-pass-line__schedule"><?php echo esc_html($item->schedule); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dlab-pass-line__price">
                                        <strong><?php echo esc_html(DLab_Workshop::format_price($item->line_total)); ?></strong>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!in_array($order->status, array('cancelled', 'expired', 'failed'), true)) : ?>
                <div class="dlab-bank-transfer__layout">
                    <div class="dlab-bank-transfer__details">
                        <table class="dlab-bank-transfer__table">
                            <?php if ($account_name) : ?>
                                <tr><th><?php esc_html_e('Příjemce', 'design-lab'); ?></th><td><?php echo esc_html($account_name); ?></td></tr>
                            <?php endif; ?>
                            <?php if ($account_full) : ?>
                                <tr><th><?php esc_html_e('Účet', 'design-lab'); ?></th><td><?php echo esc_html($account_full); ?></td></tr>
                            <?php endif; ?>
                            <?php if ($iban) : ?>
                                <tr><th>IBAN</th><td><?php echo esc_html($iban); ?></td></tr>
                            <?php endif; ?>
                            <?php if ($bic) : ?>
                                <tr><th>SWIFT</th><td><?php echo esc_html($bic); ?></td></tr>
                            <?php endif; ?>
                            <tr>
                                <th><?php esc_html_e('Variabilní symbol', 'design-lab'); ?></th>
                                <td><?php echo esc_html($vs); ?></td>
                            </tr>
                        </table>

                        <?php if ($order->status !== 'paid') : ?>
                            <p class="dlab-bank-transfer__actions">
                                <button
                                    type="button"
                                    class="btn dlab-btn dlab-copy-payment"
                                    data-copy="<?php echo esc_attr($copy_payload); ?>"
                                    data-copied="<?php echo esc_attr__('Zkopírováno', 'design-lab'); ?>"
                                >
                                    <?php esc_html_e('Zkopírovat platební údaje', 'design-lab'); ?>
                                </button>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if ($order->status !== 'paid') : ?>
                    <div class="dlab-bank-transfer__qr">
                        <p class="dlab-bank-transfer__qr-label">
                            <?php
                            printf(
                                /* translators: %s: formatted amount */
                                esc_html__('Zaplatit %s', 'design-lab'),
                                esc_html(DLab_Workshop::format_price($order->total))
                            );
                            ?>
                        </p>
                        <?php
                        $qr = new DLab_QR();
                        echo $qr->render_qr_html($order->total, $vs, 220, $qr_message); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($order->status !== 'paid' && !empty($order->expires_at)) : ?>
                    <p class="dlab-bank-transfer__expires">
                        <strong><?php esc_html_e('Uhraďte do:', 'design-lab'); ?></strong>
                        <?php echo esc_html(date_i18n('j. n. Y H:i', strtotime($order->expires_at))); ?>
                    </p>
                <?php endif; ?>

                <?php if ($order->status !== 'paid') : ?>
                <p class="dlab-bank-transfer__note">
                    <?php esc_html_e('Po přijetí platby potvrdíme rezervaci a pošleme e-mail. Do té doby je místo rezervované.', 'design-lab'); ?>
                </p>
                <?php endif; ?>
                <?php endif; ?>

                <div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex">
                    <a class="wp-block-button__link has-dd-white-color has-dd-black-background-color has-text-color has-background btn dlab-btn" href="<?php echo esc_url($listing_url); ?>">
                        <?php esc_html_e('Zpět na workshopy', 'design-lab'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
