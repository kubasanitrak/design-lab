<?php
/**
 * Pass recap / editor (Phase 2). Checkout lands in Phase 4.
 *
 * @var array $summary
 */

if (!defined('ABSPATH')) {
    exit;
}

$listing_url = DLab_Settings::listing_page_url();
?>
<!-- <div data-theme="DD-beige" class="section scroll-trigger section-content section-content--dilna section-full-width pad-B-4 dlab-pass" id="dlab-pass" data-dlab-pass> -->
<div data-theme="DD-beige" class="section scroll-trigger section-content scroll-trigger--cols section-content--dilna section-full-width pad-B-4 dlab-pass" id="dlab-pass" data-dlab-pass>
    <div class="inner-content">
        <div class="wp-block-group single-col single-col--narrow">
            <div class="wp-block-group__inner-container is-layout-constrained wp-block-group-is-layout-constrained">
                <h1 class="wp-block-heading has-text-align-center dlab-pass__title"><strong><?php esc_html_e('Design Lab pass', 'design-lab'); ?></strong></h1>
                <p class="dlab-pass__notice" data-dlab-pass-notice hidden></p>
                <div class="dlab-pass__body" data-dlab-pass-body>
                    <?php include DLAB_PLUGIN_DIR . 'public/partials/pass-lines.php'; ?>
                </div>
                <?php if (!empty($summary['items'])) : ?>
                    <div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex dlab-pass__continue">
                        <a class="wp-block-button__link has-dd-white-color has-dd-black-background-color has-text-color has-background has-link-color btn dlab-btn" href="<?php echo esc_url($listing_url); ?>">
                            <?php esc_html_e('Přidat další workshop', 'design-lab'); ?>
                        </a></div>
                    </div>
                    
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

