<?php
/**
 * Pass recap / editor. Rezervovat continues to checkout.
 *
 * @var array $summary
 */

if (!defined('ABSPATH')) {
    exit;
}

$listing_url  = DLab_Settings::listing_page_url();
$checkout_url = DLab_Settings::checkout_page_url();
?>
<div data-theme="DD-beige" class="section scroll-trigger section-content section-full-width">
    <div class="inner-content">
        <h1 class="wp-block-heading has-text-align-center dlab-pass__title"><strong><?php esc_html_e('Design Lab pass', 'design-lab'); ?></strong></h1>
    </div>
</div>
<div data-theme="DD-beige" class="section scroll-trigger section-content scroll-trigger--cols section-content--dilna section-full-width pad-B-4 dlab-pass" id="dlab-pass" data-dlab-pass>
    <div class="inner-content">
        <div class="wp-block-group single-col single-col--narrow">
            <div class="wp-block-group__inner-container is-layout-constrained wp-block-group-is-layout-constrained">
                <p class="dlab-pass__notice" data-dlab-pass-notice hidden></p>
                <div class="dlab-pass__body" data-dlab-pass-body>
                    <?php include DLAB_PLUGIN_DIR . 'public/partials/pass-lines.php'; ?>
                </div>
                <?php if (!empty($summary['items'])) : ?>
                    <div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex dlab-pass__continue">
                        <a class="wp-block-button__link has-dd-black-color has-dd-white-background-color has-text-color has-background has-link-color btn dlab-btn dlab-btn--ghost" href="<?php echo esc_url($listing_url); ?>">&larr;&ThinSpace;<?php esc_html_e('Přidat další workshop', 'design-lab'); ?> </a>
                        <a class="wp-block-button__link has-dd-white-color has-dd-black-background-color has-text-color has-background has-link-color btn dlab-btn" href="<?php echo esc_url($checkout_url); ?>">
                            <?php esc_html_e('Rezervovat', 'design-lab'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
