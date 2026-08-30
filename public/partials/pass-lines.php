<?php
/**
 * Inner pass lines + totals (also returned via AJAX).
 *
 * @var array $summary
 */

if (!defined('ABSPATH')) {
    exit;
}

$items         = $summary['items'] ?? array();
$spots         = (int) ($summary['spots'] ?? 1);
$pass_applied  = !empty($summary['pass_applied']);
$pass_min      = (int) ($summary['pass_min'] ?? 2);
$count         = (int) ($summary['count'] ?? 0);
$needed        = max(0, $pass_min - $count);
$listing_url   = DLab_Settings::listing_page_url();
?>
<?php if (empty($items)) : ?>
    <div class="dlab-pass__empty">
        <p class="dlab-empty"><?php esc_html_e('Pass je prázdný.', 'design-lab'); ?></p>
        <a class="btn dlab-btn" href="<?php echo esc_url($listing_url); ?>">
            <?php esc_html_e('Vybrat workshopy', 'design-lab'); ?>
        </a>
    </div>
<?php else : ?>
    <div class="dlab-pass__spots">
        <label class="caps h5" for="dlab-pass-spots"><?php esc_html_e('Počet účastníků', 'design-lab'); ?></label>
        <p class="dlab-pass__spots-hint minor"><?php esc_html_e('Stejný počet platí pro všechny workshopy v passu.', 'design-lab'); ?></p>
        <div class="dlab-quantity">
            <input type="number" id="dlab-pass-spots" class="dlab-pass-spots" min="1" max="20" value="<?php echo esc_attr((string) $spots); ?>" inputmode="numeric">
            <div class="dlab-quantity-nav">
                <button type="button" class="dlab-quantity-button dlab-quantity-up" aria-label="<?php esc_attr_e('Přidat účastníka', 'design-lab'); ?>"></button>
                <button type="button" class="dlab-quantity-button dlab-quantity-down" aria-label="<?php esc_attr_e('Odebrat účastníka', 'design-lab'); ?>"></button>
            </div>
        </div>
    </div>

    <ul class="dlab-pass__list">
        <?php foreach ($items as $item) :
            $post_id       = (int) $item->object_id;
            $services_defs = DLab_Pricing::get_optional_services($post_id);
            $selected      = array_fill_keys(
                DLab_Pricing::selected_service_keys($item->line_meta['services'] ?? array()),
                true
            );
            $spot_type     = $item->line_meta['spot_type'] ?? DLab_Capacity::SPOT_REGULAR;
            $line_total    = isset($item->line_total) ? (float) $item->line_total : 0;
            ?>
            <li class="dlab-pass-line" data-post-id="<?php echo esc_attr((string) $post_id); ?>">
                <div class="dlab-pass-line__header">
                    <div>
                        <h2 class="dlab-pass-line__title">
                            <a href="<?php echo esc_url($item->permalink); ?>"><?php echo esc_html($item->post_title); ?></a>
                        </h2>
                        <?php if (!empty($item->schedule)) : ?>
                            <p class="dlab-pass-line__schedule"><?php echo esc_html($item->schedule); ?></p>
                        <?php endif; ?>
                        <?php if ($spot_type === DLab_Capacity::SPOT_ALTERNATE) : ?>
                            <p class="dlab-pass-line__waitlist"><?php esc_html_e('Náhradník / čekací listina', 'design-lab'); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="dlab-pass-line__price">
                        <strong><?php echo esc_html(DLab_Workshop::format_price($line_total)); ?></strong>
                        <button type="button" class="dlab-remove-line" data-dlab-remove="<?php echo esc_attr((string) $post_id); ?>" aria-label="<?php esc_attr_e('Odebrat z passu', 'design-lab'); ?>">&times;</button>
                    </div>
                </div>
                <?php if (!empty($services_defs)) : ?>
                    <fieldset class="dlab-pass-line__services">
                        <legend class="caps"><?php esc_html_e('Volitelné služby', 'design-lab'); ?></legend>
                        <?php foreach ($services_defs as $svc) :
                            if (!is_array($svc)) {
                                continue;
                            }
                            $slug = DLab_Pricing::service_key($svc);
                            if ($slug === '') {
                                continue;
                            }
                            $addon = (float) ($svc['price_addon'] ?? 0);
                            ?>
                            <label class="dlab-checkbox">
                                <input type="checkbox" class="dlab-service-cb" value="<?php echo esc_attr($slug); ?>" data-post-id="<?php echo esc_attr((string) $post_id); ?>" <?php checked(isset($selected[$slug])); ?>>
                                <span><?php echo esc_html($svc['label'] ?? $slug); ?></span>
                                <?php if ($addon > 0) : ?>
                                    <span class="dlab-service-price">+<?php echo esc_html(DLab_Workshop::format_price($addon)); ?></span>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="dlab-pass__totals">
        <?php if ($needed > 0) : ?>
            <p class="dlab-pass__hint">
                <?php
                echo esc_html(sprintf(
                    /* translators: 1: remaining workshops, 2: pass minimum */
                    _n(
                        'Přidejte ještě %1$d workshop pro cenu Design Lab passu (od %2$d).',
                        'Přidejte ještě %1$d workshopy pro cenu Design Lab passu (od %2$d).',
                        $needed,
                        'design-lab'
                    ),
                    $needed,
                    $pass_min
                ));
                ?>
            </p>
        <?php endif; ?>

        <?php if ($pass_applied && (float) $summary['discount'] > 0) : ?>
            <p class="dlab-pass__row">
                <span><?php esc_html_e('Bez passu', 'design-lab'); ?></span>
                <span><?php echo esc_html($summary['list_formatted']); ?></span>
            </p>
            <p class="dlab-pass__row dlab-pass__row--discount">
                <span><?php esc_html_e('Design Lab pass', 'design-lab'); ?></span>
                <span>−<?php echo esc_html($summary['discount_formatted']); ?></span>
            </p>
        <?php elseif ($pass_applied) : ?>
            <p class="dlab-pass__hint"><?php esc_html_e('Cena Design Lab passu je započítána.', 'design-lab'); ?></p>
        <?php endif; ?>

        <p class="dlab-pass__row dlab-pass__row--total">
            <span><?php esc_html_e('Celkem', 'design-lab'); ?></span>
            <strong data-dlab-pass-total><?php echo esc_html($summary['total_formatted']); ?></strong>
        </p>
    </div>
<?php endif; ?>
