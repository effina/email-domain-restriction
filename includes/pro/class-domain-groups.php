<?php
/**
 * Domain Groups
 *
 * Handles domain grouping and organization.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Domain_Groups
 *
 * Manages domain groups and organization.
 */
class EDR_Domain_Groups
{
    /**
     * Initialize domain groups
     */
    public function init()
    {
        // Placeholder for future implementation
    }

    /**
     * Create domain group
     *
     * @param string $name Group name
     * @param string $description Group description
     * @return int|false Group ID or false on failure
     */
    public function create_group($name, $description = '')
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_domain_groups';

        $result = $wpdb->insert(
            $table,
            [
                'name'        => sanitize_text_field($name),
                'description' => sanitize_textarea_field($description),
                'created_at'  => current_time('mysql'),
            ],
            ['%s', '%s', '%s']
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get all domain groups
     *
     * @return array
     */
    public function get_all_groups()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_domain_groups';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return [];
        }

        $groups = $wpdb->get_results(
            "SELECT * FROM $table ORDER BY name ASC",
            ARRAY_A
        );

        return $groups ?: [];
    }

    /**
     * Add domain to group
     *
     * @param int $group_id Group ID
     * @param int $domain_id Domain ID
     * @return bool
     */
    public function add_domain_to_group($group_id, $domain_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_domain_group_members';

        $result = $wpdb->insert(
            $table,
            [
                'group_id'  => absint($group_id),
                'domain_id' => absint($domain_id),
                'added_at'  => current_time('mysql'),
            ],
            ['%d', '%d', '%s']
        );

        return $result !== false;
    }

    /**
     * Remove domain from group
     *
     * @param int $group_id Group ID
     * @param int $domain_id Domain ID
     * @return bool
     */
    public function remove_domain_from_group($group_id, $domain_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_domain_group_members';

        $result = $wpdb->delete(
            $table,
            [
                'group_id'  => absint($group_id),
                'domain_id' => absint($domain_id),
            ],
            ['%d', '%d']
        );

        return $result !== false;
    }

    /**
     * Get domains in group
     *
     * @param int $group_id Group ID
     * @return array
     */
    public function get_group_domains($group_id)
    {
        global $wpdb;

        $members_table = $wpdb->prefix . 'edr_domain_group_members';
        $domains_table = $wpdb->prefix . 'edr_whitelisted_domains';

        // Check if tables exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$members_table'") !== $members_table) {
            return [];
        }

        $domains = $wpdb->get_results($wpdb->prepare(
            "SELECT d.* FROM $domains_table d
            INNER JOIN $members_table m ON d.id = m.domain_id
            WHERE m.group_id = %d
            ORDER BY d.domain ASC",
            $group_id
        ), ARRAY_A);

        return $domains ?: [];
    }
}
