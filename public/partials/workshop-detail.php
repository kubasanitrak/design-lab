<?php
/**
 * Workshop detail — layout aligned with theme dilna singles
 * (`section-content--dilna` + `custom-columns`).
 *
 * @var int  $post_id
 * @var bool $show_gallery
 * @var bool $show_program
 * @var bool $show_attendees
 * @var bool $show_services
 */

if (!defined('ABSPATH')) {
    exit;
}

$post = get_post($post_id);
if (!$post) {
    return;
}

$schedule    = DLab_Workshop::get_schedule_summary($post_id);
$date_label  = DLab_Workshop::format_date(DLab_Workshop::get_workshop_date($post_id));
$time_from   = DLab_Workshop::format_time(DLab_Workshop::get_time_from($post_id));
$time_to     = DLab_Workshop::format_time(DLab_Workshop::get_time_to($post_id));
$price       = DLab_Workshop::get_price_label($post_id);
$pass_price  = DLab_Workshop::get_pass_price_label($post_id);
$img_id      = DLab_Workshop::get_tile_image_id($post_id);
$age         = DLab_Workshop::get_age_label($post_id);
$occupancy   = DLab_Workshop::get_occupancy($post_id);
$synopsis    = function_exists('get_field') ? get_field('synopsis', $post_id) : '';
$place_text  = function_exists('get_field') ? get_field('place_text', $post_id) : '';
$place_photo = function_exists('get_field') ? get_field('place_photo', $post_id) : null;
$place_url   = function_exists('get_field') ? get_field('place_map_url', $post_id) : '';
$prereq      = function_exists('get_field') ? get_field('prerequisites', $post_id) : '';
$listing_url = DLab_Settings::listing_page_url();
$instructors = DLab_Workshop::get_instructor_ids($post_id);

$body = $post->post_content;
$body = preg_replace('/\[dlab_workshop_detail[^\]]*\]/', '', $body);
?>
<article class="dlab-detail section-content--dilna" id="dlab-detail-<?php echo esc_attr((string) $post_id); ?>">

    <div data-theme="DD-beige" class="section section-full-width section-content--dilna section-padded">
        <div class="inner-content">
            <div class="custom-columns">
                <div class="custom-columns--item custom-columns--item_major pad-B-0">
                    <a href="<?php echo esc_url($listing_url); ?>" class="back-to-parent h5">
                        <?php esc_html_e('← Zpět na výpis', 'design-lab'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div data-theme="DD-beige" class="section section-full-width section-content--dilna section-padded">
        <div class="inner-content">
            <div class="custom-columns">
                <div class="custom-columns--item custom-columns--item_major">
                    <?php if ($date_label) : ?>
                        <p class="dlab-detail__date h5"><?php echo esc_html($date_label); ?></p>
                    <?php endif; ?>
                    <h2 class="dlab-detail__title strong"><?php echo esc_html(get_the_title($post_id)); ?></h2>
                    <?php if ($synopsis) : ?>
                        <div class="dlab-detail__synopsis h5"><?php echo wp_kses_post(wpautop($synopsis)); ?></div>
                    <?php endif; ?>
                    <ul class="dlab-detail__facts">
                        <?php if ($time_from || $time_to) : ?>
                            <li><?php echo esc_html(trim($time_from . '–' . $time_to, '–')); ?></li>
                        <?php endif; ?>
                        <?php if ($age) : ?>
                            <li><?php echo esc_html($age); ?></li>
                        <?php endif; ?>
                        <?php if ($place_text) : ?>
                            <li><?php echo esc_html(wp_strip_all_tags($place_text)); ?></li>
                        <?php endif; ?>
                        <li><?php echo esc_html($occupancy['label']); ?></li>
                    </ul>
                    <?php echo DLab_Workshop::render_tags($post_id, array('class' => 'dlab-detail__tags', 'link' => true)); ?>
                </div>
                <div class="custom-columns--item custom-columns--item_minor">
                    <?php if ($img_id) : ?>
                        <figure class="dlab-detail__hero">
                            <?php echo wp_get_attachment_image($img_id, 'large', false, array('class' => 'dlab-detail__hero-img')); ?>
                        </figure>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="section section-full-width section-content--dilna section-padded">
        <div class="inner-content">
            <div class="custom-columns">
                <div class="custom-columns--item custom-columns--item_major">
                    <h5 class="dlab-item--subtitle border-B caps"><?php esc_html_e('O workshopu', 'design-lab'); ?></h5>
                    <div class="dlab-detail__prose">
                        <?php echo apply_filters('the_content', $body); ?>
                    </div>
                    <?php if ($prereq) : ?>
                        <h6 class="caps"><?php esc_html_e('Předpoklady', 'design-lab'); ?></h6>
                        <div class="dlab-detail__prose"><?php echo wp_kses_post(wpautop($prereq)); ?></div>
                    <?php endif; ?>
                </div>
                <div class="custom-columns--item custom-columns--item_minor">
                    <h5 class="dlab-item--subtitle border-B caps"><?php esc_html_e('Detaily', 'design-lab'); ?></h5>
                    <?php if ($schedule) : ?>
                        <p class="dlab-detail__schedule"><?php echo esc_html($schedule); ?></p>
                    <?php endif; ?>

                    <?php if ($place_text || $place_url || $place_photo) : ?>
                        <h6 class="caps"><?php esc_html_e('Místo', 'design-lab'); ?></h6>
                        <?php if ($place_text) : ?>
                            <div class="dlab-detail__prose"><?php echo wp_kses_post(wpautop($place_text)); ?></div>
                        <?php endif; ?>
                        <?php
                        $photo_id = 0;
                        if (is_array($place_photo) && !empty($place_photo['ID'])) {
                            $photo_id = (int) $place_photo['ID'];
                        } elseif (is_numeric($place_photo)) {
                            $photo_id = (int) $place_photo;
                        }
                        if ($photo_id) {
                            echo wp_get_attachment_image($photo_id, 'medium', false, array('class' => 'dlab-detail__place-img'));
                        }
                        ?>
                        <?php if ($place_url) : ?>
                            <p class="dlab-detail__location">
                                <a href="<?php echo esc_url($place_url); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php esc_html_e('Zobrazit na mapě', 'design-lab'); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($show_services) :
                        $services = DLab_Workshop::get_optional_services($post_id);
                        if (!empty($services)) : ?>
                            <h6 class="caps border-T"><?php esc_html_e('Volitelné služby', 'design-lab'); ?></h6>
                            <ul class="dlab-detail__services">
                                <?php foreach ($services as $row) :
                                    if (!is_array($row)) {
                                        continue;
                                    }
                                    $label = isset($row['label']) ? $row['label'] : '';
                                    if ($label === '') {
                                        continue;
                                    }
                                    $addon = isset($row['price_addon']) && $row['price_addon'] !== '' ? (float) $row['price_addon'] : 0;
                                    ?>
                                    <li>
                                        <?php echo esc_html($label); ?>
                                        <?php if ($addon > 0) : ?>
                                            <span class="dlab-detail__service-price">+<?php echo esc_html(DLab_Workshop::format_price($addon)); ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif;
                    endif; ?>

                    <div class="dlab-detail__cta-container">
                        <?php if ($price) : ?>
                            <h6 class="caps"><?php esc_html_e('Cena za osobu', 'design-lab'); ?></h6>
                            <h3 class="dlab-detail__price"><?php echo esc_html($price); ?></h3>
                        <?php endif; ?>
                        <?php if ($pass_price) : ?>
                            <p class="dlab-detail__pass-price">
                                <?php
                                echo esc_html(sprintf(
                                    /* translators: %s: pass unit price */
                                    __('V passu od %s', 'design-lab'),
                                    $pass_price
                                ));
                                ?>
                            </p>
                        <?php endif; ?>
                        <?php include DLAB_PLUGIN_DIR . 'public/partials/add-to-pass.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($show_program && function_exists('have_rows') && have_rows('program_arr', $post_id)) : ?>
        <div class="section section-full-width section-content--dilna section-padded">
            <div class="inner-content">
                <div class="custom-columns">
                    <div class="custom-columns--item custom-columns--item_major">
                        <h5 class="dlab-item--subtitle border-B caps"><?php esc_html_e('Program', 'design-lab'); ?></h5>
                        <?php while (have_rows('program_arr', $post_id)) : the_row(); ?>
                            <?php if ($label = get_sub_field('program_row_label')) : ?>
                                <div class="programtable-header">
                                    <h5 class="programtable-headline caps"><?php echo esc_html($label); ?></h5>
                                </div>
                            <?php endif; ?>
                            <?php if (have_rows('program_row')) : ?>
                                <div class="programtable-content">
                                    <?php while (have_rows('program_row')) : the_row(); ?>
                                        <div class="programtable-row">
                                            <div class="programtable-col">
                                                <p class="plain"><?php echo esc_html((string) get_sub_field('program_row_time')); ?></p>
                                            </div>
                                            <div class="programtable-col">
                                                <p class="plain"><?php echo esc_html((string) get_sub_field('program_row_content')); ?></p>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($instructors)) : ?>
        <div class="section section-full-width section-content--dilna section-padded">
            <div class="inner-content">
                <h5 class="dlab-item--subtitle border-B caps"><?php esc_html_e('O lektorkách', 'design-lab'); ?></h5>
                <div class="list-grid list-grid--lecture dlab-instructors">
                    <?php foreach ($instructors as $inst_id) :
                        $inst = get_post($inst_id);
                        if (!$inst) {
                            continue;
                        }
                        ?>
                        <div class="grid-item">
                            <div class="grid-item--thumb_container">
                                <?php echo get_the_post_thumbnail($inst_id, 'medium', array(
                                    'class' => 'grid-item--img',
                                    'alt'   => get_the_title($inst_id),
                                )); ?>
                            </div>
                            <div class="grid-item--caption">
                                <h4 class="grid-item--title strong"><?php echo esc_html(get_the_title($inst_id)); ?></h4>
                                <div class="h5 grid-item--excerpt">
                                    <?php echo wp_kses_post(wpautop($inst->post_content ? $inst->post_content : get_the_excerpt($inst_id))); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($show_attendees && function_exists('get_field') && get_field('show_attendee_list', $post_id)) : ?>
        <div class="section section-full-width section-content--dilna section-padded">
            <div class="inner-content">
                <div class="custom-columns">
                    <div class="custom-columns--item custom-columns--item_major">
                        <h5 class="dlab-item--subtitle border-B caps"><?php esc_html_e('Účastníci', 'design-lab'); ?></h5>
                        <p class="dlab-detail__muted"><?php esc_html_e('Seznam účastníků bude dostupný po spuštění rezervací.', 'design-lab'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($show_gallery && function_exists('get_field')) :
        $gallery = get_field('gallery', $post_id);
        if (!empty($gallery) && is_array($gallery)) : ?>
            <section class="dlab-detail__gallery section section-padded">
                <?php foreach ($gallery as $image) :
                    $id = is_array($image) ? (int) ($image['ID'] ?? 0) : (int) $image;
                    if (!$id) {
                        continue;
                    }
                    $src = wp_get_attachment_image_src($id, 'medium');
                    $cls = 'lazyload dlab-gallery__img';
                    if ($src && (int) $src[1] < (int) $src[2]) {
                        $cls .= ' is-portrait';
                    }
                    echo wp_get_attachment_image($id, 'medium_large', false, array('class' => $cls, 'loading' => 'lazy'));
                endforeach; ?>
            </section>
        <?php endif;
    endif; ?>

</article>
