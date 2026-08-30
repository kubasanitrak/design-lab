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
                            <input type="text" class="small-text" id="dlab_currency_code" name="<?php echo esc_attr(DLab_Settings::OPT_CURRENCY_CODE); ?>" value="<?php echo esc_attr((string) get_option(DLab_Settings::OPT_CURRENCY_CODE, 'CZK')); ?>">
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
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
