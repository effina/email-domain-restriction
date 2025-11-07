<?php
/**
 * PRO Activator
 *
 * Handles PRO database table creation and activation tasks.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Pro_Activator
 *
 * Handles PRO plugin activation and database setup.
 */
class EDR_Pro_Activator
{
    /**
     * Create PRO database tables
     */
    public static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Domain groups table
        $groups_table = $wpdb->prefix . 'edr_domain_groups';
        $groups_sql = "CREATE TABLE $groups_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY name (name)
        ) $charset_collate;";

        // Domain group members table
        $members_table = $wpdb->prefix . 'edr_domain_group_members';
        $members_sql = "CREATE TABLE $members_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            group_id bigint(20) NOT NULL,
            domain_id bigint(20) NOT NULL,
            added_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY group_id (group_id),
            KEY domain_id (domain_id)
        ) $charset_collate;";

        // Role mappings table
        $role_mappings_table = $wpdb->prefix . 'edr_role_mappings';
        $role_mappings_sql = "CREATE TABLE $role_mappings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            domain varchar(255) NOT NULL,
            role varchar(50) NOT NULL,
            priority int(11) DEFAULT 10,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY domain (domain),
            KEY priority (priority)
        ) $charset_collate;";

        // Rate limits table
        $rate_limits_table = $wpdb->prefix . 'edr_rate_limits';
        $rate_limits_sql = "CREATE TABLE $rate_limits_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            identifier varchar(255) NOT NULL,
            identifier_type varchar(20) NOT NULL,
            attempt_count int(11) DEFAULT 0,
            window_start datetime NOT NULL,
            blocked_until datetime,
            PRIMARY KEY  (id),
            KEY identifier (identifier, identifier_type),
            KEY window_start (window_start)
        ) $charset_collate;";

        // Webhooks table
        $webhooks_table = $wpdb->prefix . 'edr_webhooks';
        $webhooks_sql = "CREATE TABLE $webhooks_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url varchar(500) NOT NULL,
            events varchar(255) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            secret_key varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Audit log table
        $audit_log_table = $wpdb->prefix . 'edr_audit_log';
        $audit_log_sql = "CREATE TABLE $audit_log_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            action varchar(50) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20),
            old_value text,
            new_value text,
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";

        // BuddyPress member type mappings table
        $bp_member_type_table = $wpdb->prefix . 'edr_bp_member_type_mappings';
        $bp_member_type_sql = "CREATE TABLE $bp_member_type_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            domain varchar(255) NOT NULL,
            member_type varchar(50) NOT NULL,
            priority int(11) DEFAULT 10,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY domain (domain),
            KEY priority (priority)
        ) $charset_collate;";

        // Execute table creation
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($groups_sql);
        dbDelta($members_sql);
        dbDelta($role_mappings_sql);
        dbDelta($rate_limits_sql);
        dbDelta($webhooks_sql);
        dbDelta($audit_log_sql);
        dbDelta($bp_member_type_sql);

        // Update existing registration attempts table with PRO fields
        self::update_registration_attempts_table();

        // Set PRO tables version
        update_option('edr_pro_db_version', '1.0.0');
    }

    /**
     * Update registration attempts table with PRO fields
     */
    private static function update_registration_attempts_table()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_registration_attempts';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return;
        }

        // Add source column if it doesn't exist
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'source'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN source varchar(50) DEFAULT 'wordpress' AFTER status");
        }

        // Add country_code column if it doesn't exist
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'country_code'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN country_code varchar(2) AFTER user_agent");
        }

        // Add latitude column if it doesn't exist
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'latitude'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN latitude decimal(10,8) AFTER country_code");
        }

        // Add longitude column if it doesn't exist
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'longitude'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN longitude decimal(11,8) AFTER latitude");
        }

        // Add blocked_reason column if it doesn't exist
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'blocked_reason'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN blocked_reason varchar(255) AFTER longitude");
        }

        // Add user_role column if it doesn't exist
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'user_role'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN user_role varchar(50) AFTER blocked_reason");
        }
    }

    /**
     * Check if PRO tables exist
     *
     * @return bool
     */
    public static function tables_exist()
    {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'edr_domain_groups',
            $wpdb->prefix . 'edr_role_mappings',
            $wpdb->prefix . 'edr_rate_limits',
        ];

        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                return false;
            }
        }

        return true;
    }

    /**
     * Run PRO activation tasks
     */
    public static function activate()
    {
        // Create PRO tables if they don't exist
        if (!self::tables_exist()) {
            self::create_tables();
        }

        // Set activation flag
        update_option('edr_pro_activated', true);
        update_option('edr_pro_activation_date', current_time('mysql'));
    }
}
