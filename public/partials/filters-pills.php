<?php
/**
 * Pill-style GET filters (věk, obor).
 *
 * @var string $filter_action
 */

if (!defined('ABSPATH')) {
    exit;
}

$filter_action = !empty($filter_action) ? $filter_action : get_permalink();
$pills         = DLab_Query::get_filter_pills();
$base_url      = DLab_Query::get_filter_base_url($filter_action);
$reset_url     = DLab_Query::get_filter_reset_url($base_url);
$has_active    = (bool) array_filter($pills, function ($pill) {
    return !empty($pill['active']);
});
?>
<div class="dlab-filters dlab-filters--pills">
    <div class="dlab-filters__head">
        <?php if ($has_active) : ?>
            <a class="dlab-filters__reset caps" href="<?php echo esc_url($reset_url); ?>">
                <?php esc_html_e('Obnovit filtr', 'design-lab'); ?>
            </a>
        <?php endif; ?>
    </div>
    <div class="dlab-filters__pills" role="group" aria-label="<?php esc_attr_e('Filtrace', 'design-lab'); ?>">
        <?php foreach ($pills as $pill) : ?>
            <?php
            $url = DLab_Query::get_filter_toggle_url(
                $base_url,
                $pill['param'],
                $pill['slug'],
                $pill['key']
            );
            $classes = array('dlab-filter-pill', 'caps');
            if (!empty($pill['active'])) {
                $classes[] = 'is-active';
            }
            ?>
            <a
                class="<?php echo esc_attr(implode(' ', $classes)); ?>"
                href="<?php echo esc_url($url); ?>"
                <?php echo !empty($pill['active']) ? ' aria-current="true"' : ''; ?>
            ><?php echo esc_html($pill['label']); ?></a>
        <?php endforeach; ?>
    </div>
</div>
