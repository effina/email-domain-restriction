<?php
/**
 * PRO Settings
 *
 * Handles PRO-specific settings and configuration.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro_Admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Pro_Settings
 *
 * Manages PRO settings and configuration.
 */
class EDR_Pro_Settings
{
    /**
     * Initialize PRO settings
     */
    public function init()
    {
        // Register PRO settings
        add_action('admin_init', [$this, 'register_pro_settings']);

        // Load role mapping page
        require_once EDR_PLUGIN_DIR . 'admin/pro/class-role-mapping-page.php';
        $role_mapping_page = new EDR_Role_Mapping_Page();
        $role_mapping_page->init();

        // Load analytics page
        require_once EDR_PLUGIN_DIR . 'admin/pro/class-analytics-page.php';
        $analytics_page = new EDR_Analytics_Page();
        $analytics_page->init();

        // Load email validation settings
        require_once EDR_PLUGIN_DIR . 'admin/pro/class-email-validation-settings.php';
        $email_validation = new EDR_Email_Validation_Settings();
        $email_validation->init();

        // Load geolocation service
        require_once EDR_PLUGIN_DIR . 'includes/pro/class-geolocation.php';
        $geolocation = new EDR_Geolocation();
        $geolocation->init();

        // Load webhook manager
        require_once EDR_PLUGIN_DIR . 'includes/pro/class-webhook-manager.php';
        $webhooks = new EDR_Webhook_Manager();
        $webhooks->init();

        // Load WooCommerce settings if available
        if (class_exists('WooCommerce')) {
            require_once EDR_PLUGIN_DIR . 'admin/pro/class-woocommerce-settings.php';
            $woo_settings = new EDR_WooCommerce_Settings();
            $woo_settings->init();
        }

        // Load BuddyPress settings if available
        if (class_exists('BuddyPress')) {
            require_once EDR_PLUGIN_DIR . 'admin/pro/class-buddypress-settings.php';
            $bp_settings = new EDR_BuddyPress_Settings();
            $bp_settings->init();
        }
    }

    /**
     * Register PRO settings
     */
    public function register_pro_settings()
    {
        // Rate limiting settings
        register_setting('edr_pro_settings', 'edr_domain_rate_limit');
        register_setting('edr_pro_settings', 'edr_ip_rate_limit');
        register_setting('edr_pro_settings', 'edr_rate_limit_window');

        // Geolocation settings
        register_setting('edr_pro_settings', 'edr_enable_geolocation');
        register_setting('edr_pro_settings', 'edr_allowed_countries');
        register_setting('edr_pro_settings', 'edr_blocked_countries');

        // Notification settings
        register_setting('edr_pro_settings', 'edr_enable_slack_notifications');
        register_setting('edr_pro_settings', 'edr_slack_webhook_url');
        register_setting('edr_pro_settings', 'edr_enable_email_notifications');
        register_setting('edr_pro_settings', 'edr_notification_email');

        // Advanced verification settings
        register_setting('edr_pro_settings', 'edr_enable_smtp_verification');
        register_setting('edr_pro_settings', 'edr_enable_disposable_email_check');
    }

    /**
     * Render PRO settings section
     */
    public function render_pro_settings_section()
    {
        include EDR_PLUGIN_DIR . 'admin/pro/views/pro-settings.php';
    }

    /**
     * Render rate limiting settings
     */
    public function render_rate_limiting_settings()
    {
        $domain_limit = get_option('edr_domain_rate_limit', 10);
        $ip_limit = get_option('edr_ip_rate_limit', 5);
        $window = get_option('edr_rate_limit_window', 3600);

        ?>
        <tr>
            <th scope="row">
                <?php esc_html_e('Domain Rate Limit', 'email-domain-restriction'); ?>
            </th>
            <td>
                <input type="number" name="edr_domain_rate_limit" value="<?php echo esc_attr($domain_limit); ?>" min="1" max="100" />
                <p class="description">
                    <?php esc_html_e('Maximum registration attempts per domain within the time window.', 'email-domain-restriction'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <?php esc_html_e('IP Rate Limit', 'email-domain-restriction'); ?>
            </th>
            <td>
                <input type="number" name="edr_ip_rate_limit" value="<?php echo esc_attr($ip_limit); ?>" min="1" max="100" />
                <p class="description">
                    <?php esc_html_e('Maximum registration attempts per IP address within the time window.', 'email-domain-restriction'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <?php esc_html_e('Time Window (seconds)', 'email-domain-restriction'); ?>
            </th>
            <td>
                <input type="number" name="edr_rate_limit_window" value="<?php echo esc_attr($window); ?>" min="60" max="86400" />
                <p class="description">
                    <?php esc_html_e('Time window for rate limiting (default: 3600 = 1 hour).', 'email-domain-restriction'); ?>
                </p>
            </td>
        </tr>
        <?php
    }
}
