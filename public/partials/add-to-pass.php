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
?>
<div class="dlab-pass-cta<?php echo esc_attr($extra_class); ?>">
    <?php if (!$open) : ?>
        <p class="dlab-pass-cta__hint"><?php esc_html_e('Rezervace není momentálně dostupná.', 'design-lab'); ?></p>
    <?php elseif ($full) : ?>
        <p class="dlab-pass-cta__hint"><?php esc_html_e('Termín je obsazený.', 'design-lab'); ?></p>
    <?php else : ?>
        <button type="button" class="btn dlab-btn dlab-btn--pass dlab-btn--large" data-dlab-add="<?php echo esc_attr((string) $post_id); ?>">
            <?php esc_html_e('Přidat do passu', 'design-lab'); ?>
        </button>
        <?php if ($occupancy['status'] === 'alternate') : ?>
            <p class="dlab-pass-cta__hint"><?php esc_html_e('Přihláška na seznam náhradníků.', 'design-lab'); ?></p>
        <?php endif; ?>
    <?php endif; ?>
</div>
