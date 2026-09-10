<?php
/**
 * @var array  $booking_context
 * @var string $display_name
 */

if (!defined('ABSPATH')) {
    exit;
}

$booking       = $booking_context;
$can_cancel    = !empty($booking['cancellation']['can_cancel']);
$can_reschedule = !empty($booking['cancellation']['can_reschedule']);
?>
<section
    class="dlab-dashboard__panel"
    data-panel="<?php echo esc_attr($booking['hash']); ?>"
    data-order-id="<?php echo esc_attr((string) $booking['order_id']); ?>"
    data-item-id="<?php echo esc_attr((string) $booking['item_id']); ?>"
    aria-hidden="true"
>
    <?php
    $subheader_back = 'bookings';
    include DLAB_PLUGIN_DIR . 'public/partials/dashboard-subheader.php';
    ?>

    <?php if ($booking['schedule'] !== '') : ?>
        <p class="dlab-dashboard__detail-schedule caps"><?php echo esc_html($booking['schedule']); ?></p>
    <?php endif; ?>

    <h2 class="dlab-dashboard__detail-title"><?php echo esc_html($booking['title']); ?></h2>

    <dl class="dlab-dashboard__detail-list">
        <div class="dlab-dashboard__detail-row">
            <dt class="caps"><?php esc_html_e('Stav', 'design-lab'); ?></dt>
            <dd><?php echo esc_html($booking['status_label']); ?></dd>
        </div>
        <div class="dlab-dashboard__detail-row">
            <dt class="caps"><?php esc_html_e('Číslo rezervace', 'design-lab'); ?></dt>
            <dd><?php echo esc_html($booking['order_number']); ?></dd>
        </div>
        <div class="dlab-dashboard__detail-row">
            <dt class="caps"><?php esc_html_e('Počet míst', 'design-lab'); ?></dt>
            <dd><?php echo esc_html((string) $booking['spots']); ?></dd>
        </div>
        <div class="dlab-dashboard__detail-row">
            <dt class="caps"><?php esc_html_e('Cena položky', 'design-lab'); ?></dt>
            <dd><?php echo esc_html($booking['line_total']); ?></dd>
        </div>
    </dl>

    <?php if (!empty($booking['cancellation']['message'])) : ?>
        <p class="dlab-dashboard__notice"><?php echo esc_html($booking['cancellation']['message']); ?></p>
    <?php endif; ?>

    <div class="dlab-dashboard__detail-actions">
        <?php if (!empty($booking['payment_url'])) : ?>
            <a class="btn dlab-btn" href="<?php echo esc_url($booking['payment_url']); ?>">
                <?php esc_html_e('Platební instrukce', 'design-lab'); ?>
            </a>
        <?php endif; ?>

        <?php if ($can_cancel) : ?>
            <button type="button" class="btn dlab-btn dlab-btn--ghost" data-dlab-dashboard-cancel>
                <?php esc_html_e('Zrušit rezervaci', 'design-lab'); ?>
            </button>
        <?php endif; ?>

        <?php if ($can_reschedule) : ?>
            <button type="button" class="btn dlab-btn dlab-btn--ghost" data-dlab-dashboard-reschedule>
                <?php esc_html_e('Přesunout workshop', 'design-lab'); ?>
            </button>
            <div class="dlab-dashboard__reschedule" data-dlab-reschedule-box hidden>
                <label class="dlab-checkout__field" for="dlab-reschedule-<?php echo esc_attr((string) $booking['item_id']); ?>">
                    <span><?php esc_html_e('Nový workshop', 'design-lab'); ?></span>
                    <select id="dlab-reschedule-<?php echo esc_attr((string) $booking['item_id']); ?>" data-dlab-reschedule-select>
                        <option value=""><?php esc_html_e('Načítám…', 'design-lab'); ?></option>
                    </select>
                </label>
                <button type="button" class="btn dlab-btn" data-dlab-reschedule-confirm disabled>
                    <?php esc_html_e('Potvrdit přesun', 'design-lab'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</section>
