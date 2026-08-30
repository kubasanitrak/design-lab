<?php
/**
 * Add-to-pass button or “already in pass” link.
 *
 * @var int    $post_id
 * @var bool   $open
 * @var bool   $full
 * @var string $class Extra classes on the button/link (e.g. dlab-btn--large).
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id     = (int) $post_id;
$open        = !empty($open);
$full        = !empty($full);
$class       = isset($class) ? trim((string) $class) : '';
$in_pass     = DLab_Basket::is_current_in_pass($post_id);
$pass_url    = DLab_Settings::pass_page_url();
$btn_classes = trim('btn dlab-btn dlab-btn--pass ' . $class);
?>
<span
    class="dlab-pass-action"
    data-dlab-pass-action="<?php echo esc_attr((string) $post_id); ?>"
    data-dlab-open="<?php echo $open ? '1' : '0'; ?>"
    data-dlab-full="<?php echo $full ? '1' : '0'; ?>"
    data-dlab-class="<?php echo esc_attr($class); ?>"
>
    <?php if ($in_pass) : ?>
        <a class="<?php echo esc_attr($btn_classes); ?> is-in-pass" href="<?php echo esc_url($pass_url); ?>">
            <?php esc_html_e('V passu', 'design-lab'); ?>
        </a>
    <?php else : ?>
        <button
            type="button"
            class="<?php echo esc_attr($btn_classes); ?>"
            data-dlab-add="<?php echo esc_attr((string) $post_id); ?>"
            <?php echo (!$open || $full) ? ' disabled' : ''; ?>
        >
            <?php esc_html_e('Přidat do passu', 'design-lab'); ?>
        </button>
    <?php endif; ?>
</span>
