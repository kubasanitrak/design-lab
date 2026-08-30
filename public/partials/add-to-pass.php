<?php
/**
 * @var int    $post_id
 * @var string $class
 */

if (!defined('ABSPATH')) {
    exit;
}

$extra_class = !empty($class) ? ' ' . sanitize_html_class($class) : '';
$open        = DLab_Workshop::is_booking_open($post_id);
$occupancy   = DLab_Workshop::get_occupancy($post_id);
$full        = ($occupancy['status'] === 'full');
$in_pass     = DLab_Basket::is_current_in_pass($post_id);
$class       = 'dlab-btn--large';
?>
<div class="dlab-pass-cta<?php echo esc_attr($extra_class); ?>">
    <?php if (!$open && !$in_pass) : ?>
        <p class="dlab-pass-cta__hint"><?php esc_html_e('Rezervace není momentálně dostupná.', 'design-lab'); ?></p>
    <?php elseif ($full && !$in_pass) : ?>
        <p class="dlab-pass-cta__hint"><?php esc_html_e('Termín je obsazený.', 'design-lab'); ?></p>
    <?php else : ?>
        <?php
        include DLAB_PLUGIN_DIR . 'public/partials/pass-action.php';
        ?>
        <?php if (!$in_pass && $occupancy['status'] === 'alternate') : ?>
            <p class="dlab-pass-cta__hint"><?php esc_html_e('Přihláška na seznam náhradníků.', 'design-lab'); ?></p>
        <?php endif; ?>
    <?php endif; ?>
</div>
