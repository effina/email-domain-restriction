<?php
/**
 * Role Manager
 *
 * Handles role-based domain restrictions and automatic role assignment.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Role_Manager
 *
 * Manages domain-to-role mappings and automatic role assignment.
 */
class EDR_Role_Manager
{
    /**
     * Initialize role manager
     */
    public function init()
    {
        // Hook into user registration
        add_action('user_register', [$this, 'assign_role_by_domain'], 10, 1);

        // Admin hooks
        if (is_admin()) {
            add_action('admin_init', [$this, 'handle_role_mapping_form']);
        }
    }

    /**
     * Assign role to user based on email domain
     *
     * @param int $user_id User ID
     */
    public function assign_role_by_domain($user_id)
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return;
        }

        $email = $user->user_email;
        $domain = substr(strrchr($email, '@'), 1);

        // Get role mapping for domain
        $role = $this->get_role_for_domain($domain);

        if ($role && get_role($role)) {
            $user->set_role($role);
        }
    }

    /**
     * Get role for domain with wildcard and conflict resolution support
     *
     * @param string $domain Domain name
     * @return string|null
     */
    public function get_role_for_domain($domain)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_role_mappings';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return null;
        }

        // Get all mappings ordered by priority
        $mappings = $wpdb->get_results(
            "SELECT domain, role, priority FROM $table ORDER BY priority DESC",
            ARRAY_A
        );

        if (empty($mappings)) {
            return null;
        }

        $matching_roles = [];

        // Check for exact and wildcard matches
        foreach ($mappings as $mapping) {
            $pattern = $mapping['domain'];

            if ($this->domain_matches($domain, $pattern)) {
                $matching_roles[] = [
                    'role'     => $mapping['role'],
                    'priority' => $mapping['priority'],
                    'pattern'  => $pattern,
                ];
            }
        }

        // Apply conflict resolution
        return $this->resolve_role_conflict($matching_roles);
    }

    /**
     * Check if domain matches pattern (supports wildcards)
     *
     * @param string $domain User's email domain
     * @param string $pattern Domain pattern (may include wildcards)
     * @return bool
     */
    private function domain_matches($domain, $pattern)
    {
        // Exact match
        if ($domain === $pattern) {
            return true;
        }

        // Wildcard match (e.g., *.example.com)
        if (strpos($pattern, '*') !== false) {
            $regex = '/^' . str_replace(['*', '.'], ['.*', '\.'], $pattern) . '$/i';
            return preg_match($regex, $domain) === 1;
        }

        return false;
    }

    /**
     * Resolve conflict when multiple role mappings match
     *
     * @param array $matching_roles Array of matching roles with priorities
     * @return string|null
     */
    private function resolve_role_conflict($matching_roles)
    {
        if (empty($matching_roles)) {
            return null;
        }

        // Single match - no conflict
        if (count($matching_roles) === 1) {
            return $matching_roles[0]['role'];
        }

        // Multiple matches - resolve by priority and specificity
        usort($matching_roles, function ($a, $b) {
            // First, compare priorities (higher is better)
            if ($a['priority'] !== $b['priority']) {
                return $b['priority'] - $a['priority'];
            }

            // If priorities are equal, prefer more specific patterns
            $a_specificity = $this->calculate_pattern_specificity($a['pattern']);
            $b_specificity = $this->calculate_pattern_specificity($b['pattern']);

            return $b_specificity - $a_specificity;
        });

        // Return highest priority/most specific role
        return $matching_roles[0]['role'];
    }

    /**
     * Calculate pattern specificity (more specific = higher score)
     *
     * @param string $pattern Domain pattern
     * @return int Specificity score
     */
    private function calculate_pattern_specificity($pattern)
    {
        // Exact domains (no wildcards) are most specific
        if (strpos($pattern, '*') === false) {
            return 1000;
        }

        // Count the number of specific parts
        // *.example.com is more specific than *.com
        $parts = explode('.', str_replace('*', '', $pattern));
        $specific_parts = array_filter($parts, function ($part) {
            return !empty(trim($part));
        });

        return count($specific_parts) * 10;
    }

    /**
     * Add role mapping with validation
     *
     * @param string $domain Domain pattern
     * @param string $role Role slug
     * @param int $priority Priority (higher = more important)
     * @return bool|WP_Error
     */
    public function add_role_mapping($domain, $role, $priority = 10)
    {
        global $wpdb;

        // Validate inputs
        $validation = $this->validate_role_mapping($domain, $role, $priority);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Check for duplicates
        if ($this->mapping_exists($domain, $role)) {
            return new WP_Error(
                'duplicate_mapping',
                __('A role mapping already exists for this domain and role combination.', 'email-domain-restriction')
            );
        }

        $table = $wpdb->prefix . 'edr_role_mappings';

        $result = $wpdb->insert(
            $table,
            [
                'domain'     => sanitize_text_field($domain),
                'role'       => sanitize_text_field($role),
                'priority'   => absint($priority),
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%s']
        );

        if ($result === false) {
            return new WP_Error('database_error', __('Failed to add role mapping.', 'email-domain-restriction'));
        }

        // Log the action
        do_action('edr_role_mapping_added', $wpdb->insert_id, $domain, $role, $priority);

        return true;
    }

    /**
     * Update role mapping
     *
     * @param int $mapping_id Mapping ID
     * @param string $domain Domain pattern
     * @param string $role Role slug
     * @param int $priority Priority
     * @return bool|WP_Error
     */
    public function update_role_mapping($mapping_id, $domain, $role, $priority = 10)
    {
        global $wpdb;

        // Validate inputs
        $validation = $this->validate_role_mapping($domain, $role, $priority);
        if (is_wp_error($validation)) {
            return $validation;
        }

        $table = $wpdb->prefix . 'edr_role_mappings';

        $result = $wpdb->update(
            $table,
            [
                'domain'   => sanitize_text_field($domain),
                'role'     => sanitize_text_field($role),
                'priority' => absint($priority),
            ],
            ['id' => absint($mapping_id)],
            ['%s', '%s', '%d'],
            ['%d']
        );

        if ($result === false) {
            return new WP_Error('database_error', __('Failed to update role mapping.', 'email-domain-restriction'));
        }

        // Log the action
        do_action('edr_role_mapping_updated', $mapping_id, $domain, $role, $priority);

        return true;
    }

    /**
     * Validate role mapping data
     *
     * @param string $domain Domain pattern
     * @param string $role Role slug
     * @param int $priority Priority
     * @return bool|WP_Error
     */
    private function validate_role_mapping($domain, $role, $priority)
    {
        // Validate domain
        if (empty($domain)) {
            return new WP_Error('empty_domain', __('Domain cannot be empty.', 'email-domain-restriction'));
        }

        // Validate domain format
        $domain_pattern = '/^(\*\.)?[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)*$/';
        if (!preg_match($domain_pattern, $domain)) {
            return new WP_Error('invalid_domain', __('Invalid domain format.', 'email-domain-restriction'));
        }

        // Validate role exists
        if (!get_role($role)) {
            return new WP_Error('invalid_role', __('The specified role does not exist.', 'email-domain-restriction'));
        }

        // Validate priority
        if ($priority < 0 || $priority > 100) {
            return new WP_Error('invalid_priority', __('Priority must be between 0 and 100.', 'email-domain-restriction'));
        }

        return true;
    }

    /**
     * Check if mapping exists
     *
     * @param string $domain Domain pattern
     * @param string $role Role slug
     * @return bool
     */
    private function mapping_exists($domain, $role)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_role_mappings';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE domain = %s AND role = %s",
            $domain,
            $role
        ));

        return $count > 0;
    }

    /**
     * Remove role mapping
     *
     * @param int $mapping_id Mapping ID
     * @return bool
     */
    public function remove_role_mapping($mapping_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_role_mappings';

        // Get mapping data before deletion for logging
        $mapping = $this->get_role_mapping($mapping_id);

        $result = $wpdb->delete(
            $table,
            ['id' => absint($mapping_id)],
            ['%d']
        );

        if ($result !== false && $mapping) {
            do_action('edr_role_mapping_deleted', $mapping_id, $mapping);
        }

        return $result !== false;
    }

    /**
     * Get single role mapping by ID
     *
     * @param int $mapping_id Mapping ID
     * @return array|null
     */
    public function get_role_mapping($mapping_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_role_mappings';

        $mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            absint($mapping_id)
        ), ARRAY_A);

        return $mapping ?: null;
    }

    /**
     * Bulk add role mappings
     *
     * @param array $mappings Array of mappings [['domain' => '', 'role' => '', 'priority' => 10], ...]
     * @return array ['success' => int, 'errors' => array]
     */
    public function bulk_add_role_mappings($mappings)
    {
        $success = 0;
        $errors = [];

        foreach ($mappings as $index => $mapping) {
            $domain = $mapping['domain'] ?? '';
            $role = $mapping['role'] ?? '';
            $priority = $mapping['priority'] ?? 10;

            $result = $this->add_role_mapping($domain, $role, $priority);

            if (is_wp_error($result)) {
                $errors[] = [
                    'index'   => $index,
                    'domain'  => $domain,
                    'role'    => $role,
                    'message' => $result->get_error_message(),
                ];
            } else {
                $success++;
            }
        }

        return [
            'success' => $success,
            'errors'  => $errors,
        ];
    }

    /**
     * Bulk delete role mappings
     *
     * @param array $mapping_ids Array of mapping IDs
     * @return int Number of deleted mappings
     */
    public function bulk_delete_role_mappings($mapping_ids)
    {
        $deleted = 0;

        foreach ($mapping_ids as $mapping_id) {
            if ($this->remove_role_mapping($mapping_id)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Export role mappings to JSON
     *
     * @return string JSON string
     */
    public function export_role_mappings()
    {
        $mappings = $this->get_all_role_mappings();

        // Remove IDs and timestamps for cleaner export
        $export_data = array_map(function ($mapping) {
            return [
                'domain'   => $mapping['domain'],
                'role'     => $mapping['role'],
                'priority' => $mapping['priority'],
            ];
        }, $mappings);

        return wp_json_encode($export_data, JSON_PRETTY_PRINT);
    }

    /**
     * Import role mappings from JSON
     *
     * @param string $json JSON string
     * @param bool $replace_existing Replace existing mappings
     * @return array ['success' => int, 'errors' => array]
     */
    public function import_role_mappings($json, $replace_existing = false)
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => 0,
                'errors'  => [['message' => __('Invalid JSON format.', 'email-domain-restriction')]],
            ];
        }

        if (!is_array($data)) {
            return [
                'success' => 0,
                'errors'  => [['message' => __('Invalid data structure.', 'email-domain-restriction')]],
            ];
        }

        // Optionally clear existing mappings
        if ($replace_existing) {
            global $wpdb;
            $table = $wpdb->prefix . 'edr_role_mappings';
            $wpdb->query("TRUNCATE TABLE $table");
        }

        // Bulk add imported mappings
        return $this->bulk_add_role_mappings($data);
    }

    /**
     * Get role mappings by role
     *
     * @param string $role Role slug
     * @return array
     */
    public function get_mappings_by_role($role)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_role_mappings';

        $mappings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE role = %s ORDER BY priority DESC",
            $role
        ), ARRAY_A);

        return $mappings ?: [];
    }

    /**
     * Test role assignment for a given email
     *
     * @param string $email Email address
     * @return array ['domain' => '', 'assigned_role' => '', 'matching_patterns' => []]
     */
    public function test_role_assignment($email)
    {
        $domain = substr(strrchr($email, '@'), 1);

        global $wpdb;
        $table = $wpdb->prefix . 'edr_role_mappings';

        $mappings = $wpdb->get_results(
            "SELECT domain, role, priority FROM $table ORDER BY priority DESC",
            ARRAY_A
        );

        $matching_patterns = [];

        foreach ($mappings as $mapping) {
            if ($this->domain_matches($domain, $mapping['domain'])) {
                $matching_patterns[] = [
                    'pattern'  => $mapping['domain'],
                    'role'     => $mapping['role'],
                    'priority' => $mapping['priority'],
                ];
            }
        }

        $assigned_role = $this->get_role_for_domain($domain);

        return [
            'domain'            => $domain,
            'assigned_role'     => $assigned_role,
            'matching_patterns' => $matching_patterns,
        ];
    }

    /**
     * Get all role mappings
     *
     * @return array
     */
    public function get_all_role_mappings()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_role_mappings';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return [];
        }

        $mappings = $wpdb->get_results(
            "SELECT * FROM $table ORDER BY priority DESC, domain ASC",
            ARRAY_A
        );

        return $mappings ?: [];
    }

    /**
     * Handle role mapping form submission
     */
    public function handle_role_mapping_form()
    {
        if (!isset($_POST['edr_role_mapping_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['edr_role_mapping_nonce'], 'edr_role_mapping')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        // Add role mapping
        if (isset($_POST['add_role_mapping'])) {
            $domain = sanitize_text_field($_POST['domain']);
            $role = sanitize_text_field($_POST['role']);
            $priority = absint($_POST['priority'] ?? 10);

            if ($this->add_role_mapping($domain, $role, $priority)) {
                add_settings_error(
                    'edr_messages',
                    'edr_message',
                    __('Role mapping added successfully.', 'email-domain-restriction'),
                    'updated'
                );
            } else {
                add_settings_error(
                    'edr_messages',
                    'edr_message',
                    __('Failed to add role mapping.', 'email-domain-restriction'),
                    'error'
                );
            }
        }

        // Remove role mapping
        if (isset($_POST['remove_role_mapping'])) {
            $mapping_id = absint($_POST['mapping_id']);

            if ($this->remove_role_mapping($mapping_id)) {
                add_settings_error(
                    'edr_messages',
                    'edr_message',
                    __('Role mapping removed successfully.', 'email-domain-restriction'),
                    'updated'
                );
            } else {
                add_settings_error(
                    'edr_messages',
                    'edr_message',
                    __('Failed to remove role mapping.', 'email-domain-restriction'),
                    'error'
                );
            }
        }
    }
}
