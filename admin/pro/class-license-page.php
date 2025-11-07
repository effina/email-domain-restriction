<?php
/**
 * License Page
 *
 * Handles license activation and management page.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro_Admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_License_Page
 *
 * Manages license activation page in admin.
 */
class EDR_License_Page
{
    /**
     * Initialize license page
     */
    public function init()
    {
        // Add license submenu page
        add_action('admin_menu', [$this, 'add_license_page'], 25);
    }

    /**
     * Add license page to admin menu
     */
    public function add_license_page()
    {
        if (EDR_Pro_Features::is_pro_active()) {
            add_submenu_page(
                'email-domain-restriction',
                __('License', 'email-domain-restriction'),
                __('License', 'email-domain-restriction'),
                'manage_options',
                'edr-license',
                [$this, 'render_license_page']
            );
        }
    }

    /**
     * Render license page
     */
    public function render_license_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        include EDR_PLUGIN_DIR . 'admin/pro/views/license-page.php';
    }
}
