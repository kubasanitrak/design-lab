<?php
/**
 * Plugin Name: Design Lab
 * Plugin URI: https://github.com/kubasanitrak/design-lab
 * Description: Sobotní Design Lab — workshopy, pass a rezervace pro WordPress.
 * Version: 0.1.0
 * Author: kubasanitrak
 * Author URI: https://github.com/kubasanitrak
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: design-lab
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DLAB_VERSION', '0.1.0');

/** Save ACF field group exports into plugin `acf-json/` (set false to disable). */
if (!defined('DLAB_ACF_SAVE_JSON')) {
    define('DLAB_ACF_SAVE_JSON', true);
}
define('DLAB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DLAB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DLAB_PLUGIN_BASENAME', plugin_basename(__FILE__));

$dlab_autoload = DLAB_PLUGIN_DIR . 'vendor/autoload.php';
if (is_readable($dlab_autoload)) {
    require_once $dlab_autoload;
}

/**
 * GitHub release updates (Plugin Update Checker).
 */
require_once DLAB_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$dlab_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/kubasanitrak/design-lab/',
    __FILE__,
    'design-lab'
);
$dlab_update_checker->getVcsApi()->enableReleaseAssets('/design-lab\.zip($|[?&#])/i');

/**
 * Activation / deactivation.
 */
function dlab_activate() {
    require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-activator.php';
    DLab_Activator::activate();
}
register_activation_hook(__FILE__, 'dlab_activate');

function dlab_deactivate() {
    require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-deactivator.php';
    DLab_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'dlab_deactivate');

require_once DLAB_PLUGIN_DIR . 'includes/class-dlab-loader.php';

/**
 * Initialize the plugin.
 */
function dlab_init() {
    $loader = new DLab_Loader();
    $loader->run();
}
add_action('plugins_loaded', 'dlab_init');

/**
 * Load translations. Default strings in code are Czech; English installs use en_US.mo.
 */
function dlab_load_textdomain() {
    load_plugin_textdomain(
        'design-lab',
        false,
        dirname(DLAB_PLUGIN_BASENAME) . '/languages'
    );
}
add_action('init', 'dlab_load_textdomain');
