<?php
/**
 * Grid item — matches theme `list-grid list-grid--dilny`.
 *
 * @var int $post_id
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id    = isset($post_id) ? (int) $post_id : get_the_ID();
$permalink  = get_permalink($post_id);
$title      = get_the_title($post_id);
$date       = DLab_Workshop::get_card_date($post_id);
$age        = DLab_Workshop::get_age_label($post_id);
$occupancy  = DLab_Workshop::get_occupancy($post_id);
$img_id     = DLab_Workshop::get_tile_image_id($post_id);
$open       = DLab_Workshop::is_booking_open($post_id);
$full       = ($occupancy['status'] === 'full');
$class      = '';
?>
<div class="grid-item dlab-card" data-post-id="<?php echo esc_attr((string) $post_id); ?>" data-occupancy="<?php echo esc_attr($occupancy['status']); ?>">
    <div class="grid-item--img_container">
        <?php if ($img_id) : ?>
            <?php echo wp_get_attachment_image($img_id, 'large', false, array(
                'class' => 'grid-item--img',
                'alt'   => $title,
            )); ?>
        <?php endif; ?>
    </div>
    <div class="grid-item--label">
        <?php if ($date) : ?>
            <time class="dlab-card__date minor" datetime="<?php echo esc_attr(DLab_Workshop::get_workshop_date($post_id)); ?>">
                <?php echo esc_html($date); ?>
            </time>
        <?php endif; ?>
        <h4 class="grid-item--title strong"><?php echo esc_html($title); ?></h4>
        <div class="dlab-card__meta">
            <?php if ($age) : ?>
                <span class="dlab-card__age"><?php echo esc_html($age); ?></span>
            <?php endif; ?>
            <span class="dlab-card__occupancy dlab-card__occupancy--<?php echo esc_attr($occupancy['status']); ?>">
                <?php echo esc_html($occupancy['label']); ?>
            </span>
        </div>
        <div class="dlab-card__actions">
            <a class="dlab-card__explore minor" href="<?php echo esc_url($permalink); ?>">
                <?php esc_html_e('Prozkoumat', 'design-lab'); ?>
            </a>
            <?php include DLAB_PLUGIN_DIR . 'public/partials/pass-action.php'; ?>
        </div>
    </div>
</div>
