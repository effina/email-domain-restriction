<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Email_Domain_Restriction
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete plugin options
delete_option('edr_whitelisted_domains');
delete_option('edr_settings');
delete_option('edr_plugin_version');

// Delete all cached statistics transients
$wpdb->query(
    "DELETE FROM {$wpdb->options}
    WHERE option_name LIKE '_transient_edr_stats_%'
    OR option_name LIKE '_transient_timeout_edr_stats_%'"
);

// Delete user meta for email verification tokens
$wpdb->query(
    "DELETE FROM {$wpdb->usermeta}
    WHERE meta_key LIKE 'edr_verification_%'"
);

// Drop the registration attempts table
$table_name = $wpdb->prefix . 'edr_registration_attempts';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Delete any users with pending_verification role (optional - be careful with this)
// Uncomment if you want to clean up unverified users on uninstall
// $pending_users = get_users(['role' => 'pending_verification']);
// foreach ($pending_users as $user) {
//     wp_delete_user($user->ID);
// }
