<?php
/**
 * One-off and pass prices. Expects $price and $pass_price in scope.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<?php if (!empty($price)) : ?>
    <h6><?php esc_html_e('Cena za jednorázový workshop', 'design-lab'); ?></h6>
    <h3 class="dlab-detail__price"><?php echo esc_html($price); ?></h3>
<?php endif; ?>
<?php if (!empty($pass_price)) : ?>
    <h6><?php esc_html_e('Cena v rámci Design Lab passu', 'design-lab'); ?></h6>
    <h3 class="dlab-detail__price">
        <?php
        echo esc_html(sprintf(
            /* translators: %s: formatted pass unit price */
            __('od %s', 'design-lab'),
            $pass_price
        ));
        ?>
    </h3>
<?php endif; ?>
