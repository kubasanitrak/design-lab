<?php
/**
 * @var int $workshop_count
 * @var int $awaiting
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap dlab-admin-wrap">
    <h1><?php esc_html_e('Design Lab', 'design-lab'); ?></h1>

    <div class="dlab-admin-cards">
        <div class="dlab-admin-card">
            <strong><?php esc_html_e('Publikované workshopy', 'design-lab'); ?></strong>
            <p class="dlab-admin-card__value"><?php echo (int) $workshop_count; ?></p>
        </div>
        <div class="dlab-admin-card">
            <strong><?php esc_html_e('Čeká na platbu', 'design-lab'); ?></strong>
            <p class="dlab-admin-card__value"><?php echo (int) $awaiting; ?></p>
        </div>
    </div>

    <p>
        <a class="button button-primary" href="<?php echo esc_url(admin_url('edit.php?post_type=' . DLab_Post_Types::POST_TYPE_WORKSHOP)); ?>">
            <?php esc_html_e('Workshopy', 'design-lab'); ?>
        </a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=dlab-orders')); ?>">
            <?php esc_html_e('Rezervace', 'design-lab'); ?>
        </a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=dlab-settings')); ?>">
            <?php esc_html_e('Nastavení', 'design-lab'); ?>
        </a>
    </p>

    <p class="description">
        <?php esc_html_e('Rezervace, členský účet a bankovní platba (QR) jsou aktivní. Faktury přijdou v dalších fázích.', 'design-lab'); ?>
    </p>
</div>
