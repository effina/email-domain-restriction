<?php
/**
 * Fired during plugin activation and deactivation.
 *
 * @package Email_Domain_Restriction
 */

/**
 * Plugin activator class.
 */
class EDR_Activator
{
    /**
     * Activate the plugin.
     *
     * Creates database table and initializes default options.
     */
    public static function activate()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'edr_registration_attempts';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL,
            domain varchar(100) NOT NULL,
            status varchar(20) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent varchar(255) DEFAULT '',
            attempted_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY domain (domain),
            KEY status (status),
            KEY attempted_at (attempted_at),
            KEY status_date (status, attempted_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Initialize default options
        self::initialize_options();

        // Store plugin version
        update_option('edr_plugin_version', EDR_VERSION);

        // Activate PRO features if available
        if (file_exists(dirname(__FILE__) . '/pro/class-pro-activator.php')) {
            require_once dirname(__FILE__) . '/pro/class-pro-activator.php';
            EDR_Pro_Activator::activate();
        }
    }

    /**
     * Deactivate the plugin.
     *
     * Note: Does not delete data. Use uninstall.php for cleanup.
     */
    public static function deactivate()
    {
        // Clear all cached statistics
        self::clear_all_caches();
    }

    /**
     * Initialize default plugin options.
     */
    private static function initialize_options()
    {
        // Initialize whitelisted domains if not exists
        if (get_option('edr_whitelisted_domains') === false) {
            update_option('edr_whitelisted_domains', []);
        }

        // Initialize settings if not exists
        if (get_option('edr_settings') === false) {
            $default_settings = [
                'log_retention_days' => 30,
                'email_verification_enabled' => true,
                'verification_token_expiry_hours' => 48,
                'blocked_domain_message' => __(
                    'Registration is restricted to approved email domains only. Please use an authorized email address.',
                    'email-domain-restriction'
                ),
                'default_user_role' => 'subscriber',
            ];
            update_option('edr_settings', $default_settings);
        }
    }

    /**
     * Clear all cached statistics.
     */
    private static function clear_all_caches()
    {
        global $wpdb;

        // Delete all transients starting with 'edr_stats_'
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_edr_stats_%'
            OR option_name LIKE '_transient_timeout_edr_stats_%'"
        );
    }
}
