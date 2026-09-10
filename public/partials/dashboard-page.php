<?php
/**
 * Member dashboard shell with hash-routed panels.
 *
 * @var array  $profile
 * @var array  $bookings
 * @var string $logout_url
 * @var string $password_url
 * @var string $user_email
 * @var string $dashboard_url
 */

if (!defined('ABSPATH')) {
    exit;
}

$display_name = $profile['full_name'] !== '' ? $profile['full_name'] : __('Uživatel', 'design-lab');
$has_bookings = !empty($bookings);
?>
<div data-theme="DD-beige" class="section section-content section-full-width pad-B-4 dlab-dashboard" data-dlab-dashboard>
    <div class="inner-content">
        <div class="wp-block-group single-col single-col--narrow">
            <div class="wp-block-group__inner-container is-layout-constrained">
                <?php include DLAB_PLUGIN_DIR . 'public/partials/dashboard-overview.php'; ?>
                <?php include DLAB_PLUGIN_DIR . 'public/partials/dashboard-bookings.php'; ?>

                <?php foreach ($bookings as $booking) : ?>
                    <?php
                    $booking_context = $booking;
                    include DLAB_PLUGIN_DIR . 'public/partials/dashboard-booking-detail.php';
                    ?>
                <?php endforeach; ?>

                <?php include DLAB_PLUGIN_DIR . 'public/partials/dashboard-settings.php'; ?>
            </div>
        </div>
    </div>
</div>
