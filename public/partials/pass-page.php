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
<div class="dlab-pass" id="dlab-pass" data-dlab-pass>
    <h1 class="dlab-pass__title"><?php esc_html_e('Design Lab pass', 'design-lab'); ?></h1>
    <p class="dlab-pass__notice" data-dlab-pass-notice hidden></p>
    <div class="dlab-pass__body" data-dlab-pass-body>
        <?php include DLAB_PLUGIN_DIR . 'public/partials/pass-lines.php'; ?>
    </div>
    <?php if (!empty($summary['items'])) : ?>
        <p class="dlab-pass__continue">
            <a class="btn dlab-btn" href="<?php echo esc_url($listing_url); ?>">
                <?php esc_html_e('Přidat další workshop', 'design-lab'); ?>
            </a>
        </p>
    <?php endif; ?>
</div>
