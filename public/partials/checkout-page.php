<?php
/**
 * Checkout form — recap, contact, attendees, confirm reservation.
 *
 * @var array  $summary
 * @var array  $contact
 * @var int    $terms_page
 * @var int    $gdpr_page
 * @var string $login_url
 * @var bool   $is_logged_in
 */

if (!defined('ABSPATH')) {
    exit;
}

$items        = $summary['items'] ?? array();
$spots        = (int) ($summary['spots'] ?? 1);
$pass_applied = !empty($summary['pass_applied']);
$pass_url     = DLab_Settings::pass_page_url();
$is_logged_in = !empty($is_logged_in);
$login_url    = isset($login_url) ? $login_url : DLab_Settings::login_url(DLab_Settings::checkout_page_url());
?>
<div data-theme="DD-beige" class="section scroll-trigger section-content section-content--dilna section-full-width pad-B-4 dlab-checkout" id="dlab-checkout" data-dlab-checkout>
    <div class="inner-content">
        <div class="wp-block-group single-col single-col--narrow">
            <div class="wp-block-group__inner-container is-layout-constrained wp-block-group-is-layout-constrained">
                <h1 class="wp-block-heading has-text-align-center dlab-checkout__title"><strong><?php esc_html_e('Rezervace', 'design-lab'); ?></strong></h1>
                <p class="dlab-checkout__notice" data-dlab-checkout-notice hidden></p>

                <?php if (!$is_logged_in) : ?>
                    <div class="dlab-checkout__auth">
                        <p class="dlab-checkout__auth-lead">
                            <?php esc_html_e('Rezervace vyžaduje účet. Přihlaste se, nebo pokračujte registrací — účet vytvoříme při potvrzení rezervace.', 'design-lab'); ?>
                        </p>
                        <p class="dlab-checkout__auth-actions">
                            <a class="btn dlab-btn dlab-btn--ghost" href="<?php echo esc_url($login_url); ?>">
                                <?php esc_html_e('Přihlásit se', 'design-lab'); ?>
                            </a>
                        </p>
                        <p class="dlab-checkout__section-label caps"><?php esc_html_e('Nová registrace', 'design-lab'); ?></p>
                    </div>
                <?php endif; ?>

                <form id="dlab-checkout-form" class="dlab-checkout-form">
                    <div class="dlab-checkout__recap">
                        <p class="dlab-checkout__section-label caps"><?php esc_html_e('Váš pass', 'design-lab'); ?></p>
                        <ul class="dlab-pass__list dlab-checkout__list">
                            <?php foreach ($items as $item) :
                                $line_total = isset($item->line_total) ? (float) $item->line_total : 0;
                                $spot_type  = $item->line_meta['spot_type'] ?? DLab_Capacity::SPOT_REGULAR;
                                ?>
                                <li class="dlab-pass-line">
                                    <div class="dlab-pass-line__header">
                                        <div>
                                            <h3 class="dlab-pass-line__title">
                                                <strong><a href="<?php echo esc_url($item->permalink); ?>"><?php echo esc_html($item->post_title); ?></a></strong>
                                            </h3>
                                            <?php if (!empty($item->schedule)) : ?>
                                                <p class="dlab-pass-line__schedule"><?php echo esc_html($item->schedule); ?></p>
                                            <?php endif; ?>
                                            <?php if ($spot_type === DLab_Capacity::SPOT_ALTERNATE) : ?>
                                                <p class="dlab-pass-line__waitlist"><?php esc_html_e('Náhradník / čekací listina', 'design-lab'); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="dlab-pass-line__price">
                                            <strong><?php echo esc_html(DLab_Workshop::format_price($line_total)); ?></strong>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <p class="dlab-pass__row">
                            <span><?php esc_html_e('Počet účastníků', 'design-lab'); ?></span>
                            <span><?php echo esc_html((string) $spots); ?></span>
                        </p>
                        <?php if ($pass_applied && (float) $summary['discount'] > 0) : ?>
                            <p class="dlab-pass__row dlab-pass__row--discount">
                                <span><?php esc_html_e('Design Lab pass', 'design-lab'); ?></span>
                                <span>−<?php echo esc_html($summary['discount_formatted']); ?></span>
                            </p>
                        <?php endif; ?>
                        <p class="dlab-pass__row dlab-pass__row--total">
                            <span class="h4 wp-block-heading"><strong><?php esc_html_e('Celkem', 'design-lab'); ?></strong></span>
                            <strong><?php echo esc_html($summary['total_formatted']); ?></strong>
                        </p>
                        <p class="dlab-checkout__edit">
                            <a class="textlink textlink-underline" href="<?php echo esc_url($pass_url); ?>">
                                <?php esc_html_e('Upravit pass', 'design-lab'); ?>
                            </a>
                        </p>
                    </div>

                    <fieldset class="dlab-checkout__section">
                        <legend class="dlab-checkout__section-label caps">
                            <?php echo $is_logged_in ? esc_html__('Kontakt', 'design-lab') : esc_html__('Registrační údaje', 'design-lab'); ?>
                        </legend>
                        <div class="dlab-checkout__field">
                            <label for="dlab-contact-name"><?php esc_html_e('Jméno a příjmení', 'design-lab'); ?></label>
                            <input type="text" id="dlab-contact-name" name="contact_name" value="<?php echo esc_attr($contact['name']); ?>" required autocomplete="name">
                        </div>
                        <div class="dlab-checkout__field">
                            <label for="dlab-contact-email"><?php esc_html_e('E-mail', 'design-lab'); ?></label>
                            <input type="email" id="dlab-contact-email" name="contact_email" value="<?php echo esc_attr($contact['email']); ?>" required autocomplete="email" <?php disabled($is_logged_in); ?>>
                            <?php if ($is_logged_in) : ?>
                                <input type="hidden" name="contact_email" value="<?php echo esc_attr($contact['email']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="dlab-checkout__field">
                            <label for="dlab-contact-phone"><?php esc_html_e('Telefon', 'design-lab'); ?></label>
                            <input type="tel" id="dlab-contact-phone" name="contact_phone" value="<?php echo esc_attr($contact['phone']); ?>" required autocomplete="tel">
                        </div>
                        <?php if (!$is_logged_in) : ?>
                            <p class="dlab-checkout__hint minor"><?php esc_html_e('Heslo si nastavíte po ověření e-mailu.', 'design-lab'); ?></p>
                        <?php endif; ?>
                    </fieldset>

                    <fieldset class="dlab-checkout__section">
                        <legend class="dlab-checkout__section-label caps"><?php esc_html_e('Účastníci', 'design-lab'); ?></legend>
                        <p class="dlab-checkout__hint minor"><?php esc_html_e('Stejní účastníci platí pro všechny workshopy v rezervaci.', 'design-lab'); ?></p>
                        <?php for ($i = 0; $i < $spots; $i++) : ?>
                            <div class="dlab-checkout__field">
                                <label for="dlab-attendee-<?php echo esc_attr((string) $i); ?>">
                                    <?php
                                    printf(
                                        /* translators: %d: attendee index */
                                        esc_html__('Účastník %d', 'design-lab'),
                                        $i + 1
                                    );
                                    ?>
                                </label>
                                <input type="text" id="dlab-attendee-<?php echo esc_attr((string) $i); ?>" name="attendees[]" required>
                            </div>
                        <?php endfor; ?>
                    </fieldset>

                    <fieldset class="dlab-checkout__section">
                        <legend class="dlab-checkout__section-label caps"><?php esc_html_e('Platba', 'design-lab'); ?></legend>
                        <p class="dlab-checkout__hint"><?php esc_html_e('Bankovní převod — po odeslání rezervace uvidíte platební údaje a QR kód.', 'design-lab'); ?></p>
                    </fieldset>

                    <div class="dlab-checkout__consents">
                        <?php if ($terms_page) : ?>
                            <label class="dlab-checkbox">
                                <input type="checkbox" name="agree_terms" value="1" required>
                                <span>
                                    <?php
                                    printf(
                                        wp_kses(
                                            /* translators: %s: terms URL */
                                            __('Souhlasím s <a class="textlink textlink-underline" href="%s" target="_blank" rel="noopener">obchodními podmínkami</a>', 'design-lab'),
                                            array(
                                                'a' => array(
                                                    'class'  => array(),
                                                    'href'   => array(),
                                                    'target' => array(),
                                                    'rel'    => array(),
                                                ),
                                            )
                                        ),
                                        esc_url(get_permalink($terms_page))
                                    );
                                    ?>
                                </span>
                            </label>
                        <?php endif; ?>
                        <?php if ($gdpr_page) : ?>
                            <label class="dlab-checkbox">
                                <input type="checkbox" name="agree_gdpr" value="1" required>
                                <span>
                                    <?php
                                    printf(
                                        wp_kses(
                                            /* translators: %s: GDPR URL */
                                            __('Souhlasím se <a class="textlink textlink-underline" href="%s" target="_blank" rel="noopener">zpracováním osobních údajů</a>', 'design-lab'),
                                            array(
                                                'a' => array(
                                                    'class'  => array(),
                                                    'href'   => array(),
                                                    'target' => array(),
                                                    'rel'    => array(),
                                                ),
                                            )
                                        ),
                                        esc_url(get_permalink($gdpr_page))
                                    );
                                    ?>
                                </span>
                            </label>
                        <?php endif; ?>
                    </div>

                    <div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex dlab-checkout__submit">
                        <button type="submit" class="wp-block-button__link has-dd-white-color has-dd-black-background-color has-text-color has-background btn dlab-btn" id="dlab-checkout-submit">
                            <?php echo $is_logged_in
                                ? esc_html__('Potvrdit rezervaci', 'design-lab')
                                : esc_html__('Registrovat a potvrdit rezervaci', 'design-lab'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
