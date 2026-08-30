<?php
/**
 * @var WP_Query $query
 * @var string   $title
 * @var bool     $show_filters
 * @var string   $filter_action
 * @var array    $preset_atts
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="dlab-listing dlab-listing--grid section section--dilny-grid">
    <?php if (!empty($title)) : ?>
        <header class="dlab-listing__header">
            <h2 class="dlab-listing__title strong"><?php echo esc_html($title); ?></h2>
        </header>
    <?php endif; ?>

    <?php if (!empty($show_filters)) : ?>
        <?php
        $filter_action = $filter_action ?: get_permalink();
        include DLAB_PLUGIN_DIR . 'public/partials/filters-pills.php';
        ?>
    <?php endif; ?>

    <?php if ($query->have_posts()) : ?>
        <div class="list-grid list-grid--dilny">
            <?php
            while ($query->have_posts()) :
                $query->the_post();
                $post_id = get_the_ID();
                include DLAB_PLUGIN_DIR . 'public/partials/workshop-card.php';
            endwhile;
            ?>
        </div>
        <?php
        $pagination = paginate_links(array(
            'total'   => $query->max_num_pages,
            'current' => max(1, (int) get_query_var('paged')),
            'type'    => 'list',
        ));
        if ($pagination) {
            echo '<nav class="dlab-pagination">' . wp_kses_post($pagination) . '</nav>';
        }
        ?>
    <?php else : ?>
        <p class="dlab-empty"><?php esc_html_e('Žádné workshopy nevyhovují filtru.', 'design-lab'); ?></p>
    <?php endif; ?>
</div>
