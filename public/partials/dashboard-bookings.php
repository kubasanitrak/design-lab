<?php
/**
 * @var array  $bookings
 * @var bool   $has_bookings
 * @var string $display_name
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="dlab-dashboard__panel" data-panel="bookings" aria-hidden="true">
    <?php
    $subheader_back = 'overview';
    include DLAB_PLUGIN_DIR . 'public/partials/dashboard-subheader.php';
    ?>

    <h2 class="dlab-dashboard__title"><?php esc_html_e('Moje rezervace', 'design-lab'); ?></h2>

    <?php if (empty($has_bookings)) : ?>
        <p class="dlab-dashboard__empty"><?php esc_html_e('Zatím nemáte žádné rezervace.', 'design-lab'); ?></p>
    <?php else : ?>
        <div class="dlab-dashboard__booking-list">
            <?php foreach ($bookings as $booking) : ?>
                <button
                    type="button"
                    class="dlab-dashboard__booking-row"
                    data-dlab-dashboard-go="<?php echo esc_attr($booking['hash']); ?>"
                >
                    <span class="dlab-dashboard__booking-row-main">
                        <span class="dlab-dashboard__booking-row-title"><?php echo esc_html($booking['title']); ?></span>
                        <?php if ($booking['schedule'] !== '') : ?>
                            <span class="dlab-dashboard__booking-row-meta minor"><?php echo esc_html($booking['schedule']); ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="dlab-dashboard__booking-row-status minor"><?php echo esc_html($booking['status_label']); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
