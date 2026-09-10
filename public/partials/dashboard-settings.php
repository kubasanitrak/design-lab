<?php
/**
 * @var array  $profile
 * @var string $password_url
 * @var string $user_email
 * @var string $display_name
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="dlab-dashboard__panel" data-panel="settings" aria-hidden="true">
    <?php
    $subheader_back = 'overview';
    include DLAB_PLUGIN_DIR . 'public/partials/dashboard-subheader.php';
    ?>

    <h2 class="dlab-dashboard__title"><?php esc_html_e('Nastavení účtu', 'design-lab'); ?></h2>

    <div class="dlab-dashboard__notice dlab-dashboard__notice--hidden" data-dlab-settings-notice role="status" aria-live="polite"></div>

    <form class="dlab-dashboard__settings-form" data-dlab-dashboard-settings novalidate>
        <fieldset class="dlab-checkout__section">
            <legend class="dlab-checkout__section-label caps"><?php esc_html_e('Kontakt', 'design-lab'); ?></legend>

            <div class="dlab-checkout__field">
                <label for="dlab_dash_first_name"><?php esc_html_e('Jméno', 'design-lab'); ?></label>
                <input type="text" id="dlab_dash_first_name" name="first_name" value="<?php echo esc_attr($profile['first_name']); ?>" autocomplete="given-name" required>
            </div>
            <div class="dlab-checkout__field">
                <label for="dlab_dash_last_name"><?php esc_html_e('Příjmení', 'design-lab'); ?></label>
                <input type="text" id="dlab_dash_last_name" name="last_name" value="<?php echo esc_attr($profile['last_name']); ?>" autocomplete="family-name" required>
            </div>
            <div class="dlab-checkout__field">
                <label for="dlab_dash_email"><?php esc_html_e('E-mail', 'design-lab'); ?></label>
                <input type="email" id="dlab_dash_email" name="email" value="<?php echo esc_attr($user_email); ?>" autocomplete="email" required>
            </div>
            <div class="dlab-checkout__field">
                <label for="dlab_dash_phone"><?php esc_html_e('Telefon', 'design-lab'); ?></label>
                <input type="tel" id="dlab_dash_phone" name="phone" value="<?php echo esc_attr($profile['phone']); ?>" autocomplete="tel" required>
            </div>
        </fieldset>

        <fieldset class="dlab-checkout__section">
            <legend class="dlab-checkout__section-label caps"><?php esc_html_e('Heslo', 'design-lab'); ?></legend>
            <p class="dlab-checkout__hint">
                <a class="textlink textlink-underline" href="<?php echo esc_url($password_url); ?>">
                    <?php esc_html_e('Změnit heslo', 'design-lab'); ?>
                </a>
            </p>
        </fieldset>

        <div class="wp-block-buttons is-layout-flex dlab-checkout__submit">
            <button type="submit" class="wp-block-button__link has-dd-white-color has-dd-black-background-color has-text-color has-background btn dlab-btn">
                <?php esc_html_e('Uložit nastavení', 'design-lab'); ?>
            </button>
        </div>
    </form>
</section>
