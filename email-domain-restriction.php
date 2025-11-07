<?php
/**
 * Plugin Name: Email Domain Restriction
 * Plugin URI: https://codeeffina.com/wordpress/plugins/email-domain-restriction
 * Description: Whitelist email domains for user registration with email verification and comprehensive statistics dashboard.
 * Version: 1.0.0
 * Requires PHP: 8.3
 * Author: Erik C. Olson
 * Author URI: https://codeeffina.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: email-domain-restriction
 * Domain Path: /languages
 *
 * @package Email_Domain_Restriction
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('EDR_VERSION', '1.0.0');

/**
 * Plugin directory path.
 */
define('EDR_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('EDR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_email_domain_restriction()
{
    require_once EDR_PLUGIN_DIR . 'includes/class-activator.php';
    EDR_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_email_domain_restriction()
{
    require_once EDR_PLUGIN_DIR . 'includes/class-activator.php';
    EDR_Activator::deactivate();
}

register_activation_hook(__FILE__, 'activate_email_domain_restriction');
register_deactivation_hook(__FILE__, 'deactivate_email_domain_restriction');

/**
 * Load plugin text domain for translations.
 */
function edr_load_textdomain()
{
    load_plugin_textdomain(
        'email-domain-restriction',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
add_action('plugins_loaded', 'edr_load_textdomain');

/**
 * Initialize the plugin.
 */
function run_email_domain_restriction()
{
    // Load dependencies
    require_once EDR_PLUGIN_DIR . 'includes/class-domain-manager.php';
    require_once EDR_PLUGIN_DIR . 'includes/class-domain-validator.php';
    require_once EDR_PLUGIN_DIR . 'includes/class-registration-handler.php';
    require_once EDR_PLUGIN_DIR . 'includes/class-attempt-logger.php';
    require_once EDR_PLUGIN_DIR . 'includes/class-email-verification.php';

    // Initialize components
    $registration_handler = new EDR_Registration_Handler();
    $registration_handler->init();

    $email_verification = new EDR_Email_Verification();
    $email_verification->init();

    // Load integrations
    require_once EDR_PLUGIN_DIR . 'includes/integrations/class-ultimate-member-integration.php';
    $um_integration = new EDR_Ultimate_Member_Integration();
    $um_integration->init();

    // Initialize update checker
    require_once EDR_PLUGIN_DIR . 'includes/class-updater.php';
    $updater = new EDR_Updater();
    $updater->init();

    // Load admin components if in admin area
    if (is_admin()) {
        require_once EDR_PLUGIN_DIR . 'admin/class-admin-menu.php';
        require_once EDR_PLUGIN_DIR . 'admin/class-dashboard.php';
        require_once EDR_PLUGIN_DIR . 'admin/class-settings-page.php';
        require_once EDR_PLUGIN_DIR . 'admin/class-log-viewer.php';

        $admin_menu = new EDR_Admin_Menu();
        $admin_menu->init();
    }

    // Initialize PRO features if available
    if (file_exists(EDR_PLUGIN_DIR . 'includes/pro/class-pro-features.php')) {
        require_once EDR_PLUGIN_DIR . 'includes/pro/class-pro-features.php';
        EDR_Pro_Features::init();
    }
}
add_action('plugins_loaded', 'run_email_domain_restriction');

/**
 * Helper function to check if PRO is active
 *
 * @return bool
 */
function edr_is_pro_active()
{
    return class_exists('EDR_Pro_Features') && EDR_Pro_Features::is_pro_active();
}

/**
 * Get Freemius instance
 *
 * @return Freemius|null
 */
function edr_freemius()
{
    global $edr_freemius;
    return $edr_freemius;
}
