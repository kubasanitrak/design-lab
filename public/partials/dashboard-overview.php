<?php
/**
 * @var string $display_name
 * @var string $logout_url
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="dlab-dashboard__panel is-active" data-panel="overview" aria-hidden="false">
    <header class="dlab-dashboard__profile">
        <p class="dlab-dashboard__name" data-dlab-dashboard-name><?php echo esc_html($display_name); ?></p>
    </header>

    <nav class="dlab-dashboard__nav" aria-label="<?php esc_attr_e('Účet', 'design-lab'); ?>">
        <button type="button" class="dlab-dashboard__nav-item" data-dlab-dashboard-go="bookings">
            <span><?php esc_html_e('Moje rezervace', 'design-lab'); ?></span>
        </button>
        <button type="button" class="dlab-dashboard__nav-item" data-dlab-dashboard-go="settings">
            <span><?php esc_html_e('Nastavení účtu', 'design-lab'); ?></span>
        </button>
    </nav>

    <footer class="dlab-dashboard__footer">
        <a class="btn dlab-btn dlab-btn--ghost" href="<?php echo esc_url($logout_url); ?>">
            <?php esc_html_e('Odhlásit se', 'design-lab'); ?>
        </a>
    </footer>
</section>
