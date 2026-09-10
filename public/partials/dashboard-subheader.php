<?php
/**
 * @var string $display_name
 * @var string $subheader_back
 */

if (!defined('ABSPATH')) {
    exit;
}

$subheader_back = isset($subheader_back) ? $subheader_back : 'overview';
?>
<header class="dlab-dashboard__subheader">
    <button type="button" class="dlab-dashboard__back textlink textlink-underline" data-dlab-dashboard-go="<?php echo esc_attr($subheader_back); ?>">
        <?php esc_html_e('← Zpět', 'design-lab'); ?>
    </button>
    <p class="dlab-dashboard__name dlab-dashboard__name--small" data-dlab-dashboard-name><?php echo esc_html($display_name); ?></p>
</header>
