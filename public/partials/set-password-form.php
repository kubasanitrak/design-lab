<?php
/**
 * Set password after e-mail verification.
 *
 * @var int    $uid
 * @var string $key
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div data-theme="DD-beige" class="section section-content section-full-width pad-B-4 dlab-auth">
    <div class="inner-content">
        <div class="wp-block-group single-col single-col--narrow">
            <div class="wp-block-group__inner-container is-layout-constrained">
                <h1 class="wp-block-heading has-text-align-center"><strong><?php esc_html_e('Nastavení hesla', 'design-lab'); ?></strong></h1>
                <form class="dlab-auth-form dlab-auth-form--set-password" method="post" action="">
                    <?php wp_nonce_field(DLab_Auth::ACTION_SET_PASSWORD); ?>
                    <input type="hidden" name="dlab_auth_action" value="<?php echo esc_attr(DLab_Auth::ACTION_SET_PASSWORD); ?>">
                    <input type="hidden" name="dlab_user_id" value="<?php echo esc_attr((string) $uid); ?>">
                    <input type="hidden" name="dlab_password_key" value="<?php echo esc_attr($key); ?>">

                    <div class="dlab-checkout__field">
                        <label for="dlab_password"><?php esc_html_e('Heslo', 'design-lab'); ?></label>
                        <input type="password" name="dlab_password" id="dlab_password" required minlength="8" autocomplete="new-password">
                    </div>

                    <div class="dlab-checkout__field">
                        <label for="dlab_password_confirm"><?php esc_html_e('Heslo znovu', 'design-lab'); ?></label>
                        <input type="password" name="dlab_password_confirm" id="dlab_password_confirm" required minlength="8" autocomplete="new-password">
                    </div>

                    <p class="dlab-checkout__hint minor"><?php esc_html_e('Minimálně 8 znaků.', 'design-lab'); ?></p>

                    <div class="wp-block-buttons is-layout-flex dlab-checkout__submit">
                        <button type="submit" class="wp-block-button__link has-dd-white-color has-dd-black-background-color has-text-color has-background btn dlab-btn">
                            <?php esc_html_e('Uložit heslo', 'design-lab'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
