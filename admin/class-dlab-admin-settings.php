<?php
/**
 * Settings screen.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Admin_Settings {

    const PAGE_SLUG = 'dlab-settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 20);
    }

    public function register_menu() {
        add_submenu_page(
            DLab_Admin::MENU_SLUG,
            __('Nastavení', 'design-lab'),
            __('Nastavení', 'design-lab'),
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_page')
        );
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap dlab-admin-wrap">
            <h1><?php esc_html_e('Design Lab — nastavení', 'design-lab'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('dlab_settings'); ?>
                <h2><?php esc_html_e('Pass', 'design-lab'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="dlab_pass_min_workshops"><?php esc_html_e('Pass — minimum workshopů', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <input type="number" min="2" step="1" class="small-text" id="dlab_pass_min_workshops" name="<?php echo esc_attr(DLab_Settings::OPT_PASS_MIN_WORKSHOPS); ?>" value="<?php echo esc_attr((string) DLab_Settings::pass_min_workshops()); ?>">
                            <p class="description"><?php esc_html_e('Sleva Design Lab pass se uplatní od tohoto počtu různých workshopů v košíku (výchozí 2).', 'design-lab'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Pass — počet účastníků', 'design-lab'); ?></th>
                        <td>
                            <label>
                                <input type="hidden" name="<?php echo esc_attr(DLab_Settings::OPT_PASS_SHARED_HEADCOUNT); ?>" value="0">
                                <input type="checkbox" name="<?php echo esc_attr(DLab_Settings::OPT_PASS_SHARED_HEADCOUNT); ?>" value="1" <?php checked(DLab_Settings::pass_shared_headcount()); ?>>
                                <?php esc_html_e('Jeden počet účastníků pro celý pass (ne zvlášť u každého workshopu).', 'design-lab'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="dlab_currency_symbol"><?php esc_html_e('Měna', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <input type="text" class="small-text" id="dlab_currency_code" name="<?php echo esc_attr(DLab_Settings::OPT_CURRENCY_CODE); ?>" value="<?php echo esc_attr(DLab_Settings::currency_code()); ?>">
                            <input type="text" class="small-text" id="dlab_currency_symbol" name="<?php echo esc_attr(DLab_Settings::OPT_CURRENCY_SYMBOL); ?>" value="<?php echo esc_attr(DLab_Settings::currency_symbol()); ?>">
                            <select name="<?php echo esc_attr(DLab_Settings::OPT_CURRENCY_POSITION); ?>">
                                <option value="after" <?php selected(DLab_Settings::currency_position(), 'after'); ?>><?php esc_html_e('za částkou (Kč)', 'design-lab'); ?></option>
                                <option value="before" <?php selected(DLab_Settings::currency_position(), 'before'); ?>><?php esc_html_e('před částkou', 'design-lab'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="dlab_listing_page"><?php esc_html_e('Stránka výpisu', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_pages(array(
                                'name'              => DLab_Settings::OPT_LISTING_PAGE,
                                'id'                => 'dlab_listing_page',
                                'selected'          => DLab_Settings::listing_page_id(),
                                'show_option_none'  => __('— vybrat —', 'design-lab'),
                                'option_none_value' => '0',
                            ));
                            ?>
                            <p class="description"><?php esc_html_e('Stránka se shortcode [dlab_workshops_list], obvykle /design-lab/.', 'design-lab'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="dlab_pass_page"><?php esc_html_e('Stránka passu', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_pages(array(
                                'name'              => DLab_Settings::OPT_PASS_PAGE,
                                'id'                => 'dlab_pass_page',
                                'selected'          => DLab_Settings::pass_page_id(),
                                'show_option_none'  => __('— vybrat —', 'design-lab'),
                                'option_none_value' => '0',
                            ));
                            ?>
                            <p class="description"><?php esc_html_e('Stránka se shortcode [dlab_pass], obvykle /pass/.', 'design-lab'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Členský účet — rezervace', 'design-lab'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="dlab_reservation_expiry_hours"><?php esc_html_e('Platnost rezervace (hodin)', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <input type="number" min="1" step="1" class="small-text" id="dlab_reservation_expiry_hours" name="<?php echo esc_attr(DLab_Settings::OPT_RESERVATION_EXPIRY_HOURS); ?>" value="<?php echo esc_attr((string) DLab_Settings::reservation_expiry_hours()); ?>">
                            <p class="description"><?php esc_html_e('Jak dlouho držíme rezervaci (čekání na platbu). Výchozí 72 hodin.', 'design-lab'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="dlab_cancel_hours_before_start"><?php esc_html_e('Zrušení rezervace (hodiny před začátkem)', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <input type="number" min="0" step="1" class="small-text" id="dlab_cancel_hours_before_start" name="<?php echo esc_attr(DLab_Settings::OPT_CANCEL_HOURS_BEFORE_START); ?>" value="<?php echo esc_attr((string) DLab_Settings::cancel_hours_before_start()); ?>">
                            <p class="description"><?php esc_html_e('Do této lhůty může uživatel rezervaci zrušit. Výchozí 24 hodin před začátkem workshopu.', 'design-lab'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Platba — bankovní účet', 'design-lab'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Údaje pro QR platbu (číslo účtu + kód banky) a rekapitulaci platby (IBAN, SWIFT).', 'design-lab'); ?>
                </p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="dlab_bank_account_name"><?php esc_html_e('Název účtu', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" id="dlab_bank_account_name" name="<?php echo esc_attr(DLab_Settings::OPT_BANK_ACCOUNT_NAME); ?>" value="<?php echo esc_attr((string) get_option(DLab_Settings::OPT_BANK_ACCOUNT_NAME, '')); ?>">
                            <p class="description"><?php esc_html_e('Příjemce na platební rekapitulaci.', 'design-lab'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="dlab_bank_account_number"><?php esc_html_e('Číslo účtu', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" id="dlab_bank_account_number" name="<?php echo esc_attr(DLab_Settings::OPT_BANK_ACCOUNT_NUMBER); ?>" value="<?php echo esc_attr((string) get_option(DLab_Settings::OPT_BANK_ACCOUNT_NUMBER, '')); ?>">
                            <p class="description"><?php esc_html_e('Bez kódu banky, včetně předčíslí (např. 19-1234567890).', 'design-lab'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="dlab_bank_code"><?php esc_html_e('Kód banky', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <input type="text" class="small-text" id="dlab_bank_code" name="<?php echo esc_attr(DLab_Settings::OPT_BANK_CODE); ?>" value="<?php echo esc_attr((string) get_option(DLab_Settings::OPT_BANK_CODE, '')); ?>" maxlength="4" inputmode="numeric">
                            <p class="description"><?php esc_html_e('Čtyřmístný kód (např. 3030). Nutný pro QR kód.', 'design-lab'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="dlab_bank_iban">IBAN</label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" id="dlab_bank_iban" name="<?php echo esc_attr(DLab_Settings::OPT_BANK_IBAN); ?>" value="<?php echo esc_attr((string) get_option(DLab_Settings::OPT_BANK_IBAN, '')); ?>" autocomplete="off">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="dlab_bank_bic"><?php esc_html_e('SWIFT (BIC)', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" id="dlab_bank_bic" name="<?php echo esc_attr(DLab_Settings::OPT_BANK_BIC); ?>" value="<?php echo esc_attr((string) get_option(DLab_Settings::OPT_BANK_BIC, '')); ?>" maxlength="11" autocomplete="off">
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Fakturace', 'design-lab'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="dlab_invoice_sequence_start"><?php esc_html_e('Číselná řada faktur', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <input type="number" min="1" step="1" class="small-text" id="dlab_invoice_sequence_start" name="<?php echo esc_attr(DLab_Settings::OPT_INVOICE_SEQUENCE_START); ?>" value="<?php echo esc_attr((string) DLab_Settings::invoice_sequence_start()); ?>">
                            <p class="description">
                                <?php esc_html_e('Čísla dokladů mají formát RR-##### (např. 26-00001). Toto je další pořadové číslo pro nové faktury a variabilní symbol QR platby.', 'design-lab'); ?>
                            </p>
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: next invoice number preview */
                                    esc_html__('Další číslo: %s', 'design-lab'),
                                    esc_html(DLab_Settings::peek_next_invoice_number())
                                );
                                $current = get_option(DLab_Settings::OPT_INVOICE_SEQUENCE_CURRENT, false);
                                if ($current !== false) {
                                    echo '<br>' . esc_html(sprintf(
                                        /* translators: %d: current sequence counter */
                                        __('Aktuální počítadlo: %d', 'design-lab'),
                                        (int) $current
                                    ));
                                }
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('E-maily', 'design-lab'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="dlab_email_sender_name"><?php esc_html_e('Odesílatel', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" id="dlab_email_sender_name" name="<?php echo esc_attr(DLab_Settings::OPT_EMAIL_SENDER_NAME); ?>" value="<?php echo esc_attr(DLab_Settings::email_sender_name()); ?>">
                            <p class="description"><?php esc_html_e('Jméno zobrazené v odchozích e-mailech.', 'design-lab'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="dlab_email_sender_email"><?php esc_html_e('E-mail odesílatele', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <input type="email" class="regular-text" id="dlab_email_sender_email" name="<?php echo esc_attr(DLab_Settings::OPT_EMAIL_SENDER_EMAIL); ?>" value="<?php echo esc_attr(DLab_Settings::email_sender_email()); ?>">
                            <p class="description"><?php esc_html_e('Adresa, ze které se e-maily odesílají (From).', 'design-lab'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="dlab_email_template_type"><?php esc_html_e('Formát e-mailů', 'design-lab'); ?></label>
                        </th>
                        <td>
                            <select id="dlab_email_template_type" name="<?php echo esc_attr(DLab_Settings::OPT_EMAIL_TEMPLATE_TYPE); ?>">
                                <option value="html" <?php selected(DLab_Settings::email_template_type(), 'html'); ?>><?php esc_html_e('HTML', 'design-lab'); ?></option>
                                <option value="plain" <?php selected(DLab_Settings::email_template_type(), 'plain'); ?>><?php esc_html_e('Prostý text', 'design-lab'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Notifikace admin', 'design-lab'); ?></th>
                        <td>
                            <input type="hidden" name="<?php echo esc_attr(DLab_Settings::OPT_ADMIN_NOTIFICATION_ENABLED); ?>" value="0">
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(DLab_Settings::OPT_ADMIN_NOTIFICATION_ENABLED); ?>" value="1" <?php checked(DLab_Settings::admin_notification_enabled()); ?>>
                                <?php esc_html_e('Posílat e-mail při nové objednávce', 'design-lab'); ?>
                            </label>
                            <p>
                                <label for="dlab_admin_notification_email" class="screen-reader-text"><?php esc_html_e('E-mail pro notifikace admin', 'design-lab'); ?></label>
                                <input type="email" class="regular-text" id="dlab_admin_notification_email" name="<?php echo esc_attr(DLab_Settings::OPT_ADMIN_NOTIFICATION_EMAIL); ?>" value="<?php echo esc_attr(DLab_Settings::admin_notification_email()); ?>">
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
