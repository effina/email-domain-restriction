<?php
/**
 * Role Mapping Admin Page
 *
 * Handles role mapping administration interface.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro_Admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Role_Mapping_Page
 *
 * Manages role mapping admin page.
 */
class EDR_Role_Mapping_Page
{
    /**
     * Role manager instance
     *
     * @var EDR_Role_Manager
     */
    private $role_manager;

    /**
     * Initialize role mapping page
     */
    public function init()
    {
        if (!edr_is_pro_active()) {
            return;
        }

        $this->role_manager = new EDR_Role_Manager();

        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // Handle form submissions
        add_action('admin_init', [$this, 'handle_form_submissions']);

        // AJAX handlers
        add_action('wp_ajax_edr_delete_role_mapping', [$this, 'ajax_delete_mapping']);
        add_action('wp_ajax_edr_bulk_delete_mappings', [$this, 'ajax_bulk_delete']);
        add_action('wp_ajax_edr_test_role_assignment', [$this, 'ajax_test_role_assignment']);
        add_action('wp_ajax_edr_export_mappings', [$this, 'ajax_export_mappings']);

        // Enqueue scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'email-domain-restriction',
            __('Role Mappings', 'email-domain-restriction'),
            __('Role Mappings', 'email-domain-restriction'),
            'manage_options',
            'edr-role-mappings',
            [$this, 'render_page']
        );
    }

    /**
     * Enqueue scripts and styles
     *
     * @param string $hook Page hook
     */
    public function enqueue_scripts($hook)
    {
        if (strpos($hook, 'edr-role-mappings') === false) {
            return;
        }

        wp_enqueue_script(
            'edr-role-mappings',
            EDR_PLUGIN_URL . 'admin/js/role-mappings.js',
            ['jquery'],
            EDR_VERSION,
            true
        );

        wp_localize_script('edr-role-mappings', 'edrRoleMappings', [
            'nonce'              => wp_create_nonce('edr_role_mappings'),
            'confirmDelete'      => __('Are you sure you want to delete this role mapping?', 'email-domain-restriction'),
            'confirmBulkDelete'  => __('Are you sure you want to delete the selected role mappings?', 'email-domain-restriction'),
            'testingRole'        => __('Testing role assignment...', 'email-domain-restriction'),
            'exporting'          => __('Exporting role mappings...', 'email-domain-restriction'),
        ]);

        wp_enqueue_style(
            'edr-role-mappings',
            EDR_PLUGIN_URL . 'admin/css/role-mappings.css',
            [],
            EDR_VERSION
        );
    }

    /**
     * Handle form submissions
     */
    public function handle_form_submissions()
    {
        // Add mapping
        if (isset($_POST['edr_add_role_mapping'])) {
            $this->handle_add_mapping();
        }

        // Update mapping
        if (isset($_POST['edr_update_role_mapping'])) {
            $this->handle_update_mapping();
        }

        // Import mappings
        if (isset($_POST['edr_import_mappings'])) {
            $this->handle_import_mappings();
        }
    }

    /**
     * Handle add mapping form
     */
    private function handle_add_mapping()
    {
        if (!isset($_POST['edr_role_mapping_nonce']) || !wp_verify_nonce($_POST['edr_role_mapping_nonce'], 'edr_add_role_mapping')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $domain = sanitize_text_field($_POST['domain']);
        $role = sanitize_text_field($_POST['role']);
        $priority = absint($_POST['priority'] ?? 10);

        $result = $this->role_manager->add_role_mapping($domain, $role, $priority);

        if (is_wp_error($result)) {
            add_settings_error(
                'edr_role_mappings',
                'add_mapping_error',
                $result->get_error_message(),
                'error'
            );
        } else {
            add_settings_error(
                'edr_role_mappings',
                'add_mapping_success',
                __('Role mapping added successfully.', 'email-domain-restriction'),
                'success'
            );
        }
    }

    /**
     * Handle update mapping form
     */
    private function handle_update_mapping()
    {
        if (!isset($_POST['edr_role_mapping_nonce']) || !wp_verify_nonce($_POST['edr_role_mapping_nonce'], 'edr_update_role_mapping')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $mapping_id = absint($_POST['mapping_id']);
        $domain = sanitize_text_field($_POST['domain']);
        $role = sanitize_text_field($_POST['role']);
        $priority = absint($_POST['priority'] ?? 10);

        $result = $this->role_manager->update_role_mapping($mapping_id, $domain, $role, $priority);

        if (is_wp_error($result)) {
            add_settings_error(
                'edr_role_mappings',
                'update_mapping_error',
                $result->get_error_message(),
                'error'
            );
        } else {
            add_settings_error(
                'edr_role_mappings',
                'update_mapping_success',
                __('Role mapping updated successfully.', 'email-domain-restriction'),
                'success'
            );
        }
    }

    /**
     * Handle import mappings form
     */
    private function handle_import_mappings()
    {
        if (!isset($_POST['edr_import_nonce']) || !wp_verify_nonce($_POST['edr_import_nonce'], 'edr_import_mappings')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            add_settings_error(
                'edr_role_mappings',
                'import_error',
                __('Failed to upload file.', 'email-domain-restriction'),
                'error'
            );
            return;
        }

        $json = file_get_contents($_FILES['import_file']['tmp_name']);
        $replace_existing = isset($_POST['replace_existing']);

        $result = $this->role_manager->import_role_mappings($json, $replace_existing);

        if ($result['success'] > 0) {
            add_settings_error(
                'edr_role_mappings',
                'import_success',
                sprintf(
                    __('Successfully imported %d role mapping(s).', 'email-domain-restriction'),
                    $result['success']
                ),
                'success'
            );
        }

        if (!empty($result['errors'])) {
            $error_messages = array_map(function ($error) {
                return $error['message'];
            }, $result['errors']);

            add_settings_error(
                'edr_role_mappings',
                'import_errors',
                sprintf(
                    __('Import completed with %d error(s): %s', 'email-domain-restriction'),
                    count($result['errors']),
                    implode(', ', array_slice($error_messages, 0, 3))
                ),
                'warning'
            );
        }
    }

    /**
     * AJAX: Delete role mapping
     */
    public function ajax_delete_mapping()
    {
        check_ajax_referer('edr_role_mappings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'email-domain-restriction')]);
        }

        $mapping_id = absint($_POST['mapping_id']);

        if ($this->role_manager->remove_role_mapping($mapping_id)) {
            wp_send_json_success(['message' => __('Role mapping deleted.', 'email-domain-restriction')]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete role mapping.', 'email-domain-restriction')]);
        }
    }

    /**
     * AJAX: Bulk delete mappings
     */
    public function ajax_bulk_delete()
    {
        check_ajax_referer('edr_role_mappings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'email-domain-restriction')]);
        }

        $mapping_ids = isset($_POST['mapping_ids']) ? array_map('absint', $_POST['mapping_ids']) : [];

        if (empty($mapping_ids)) {
            wp_send_json_error(['message' => __('No mappings selected.', 'email-domain-restriction')]);
        }

        $deleted = $this->role_manager->bulk_delete_role_mappings($mapping_ids);

        wp_send_json_success([
            'message' => sprintf(
                __('Deleted %d role mapping(s).', 'email-domain-restriction'),
                $deleted
            ),
        ]);
    }

    /**
     * AJAX: Test role assignment
     */
    public function ajax_test_role_assignment()
    {
        check_ajax_referer('edr_role_mappings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'email-domain-restriction')]);
        }

        $email = sanitize_email($_POST['email']);

        if (!is_email($email)) {
            wp_send_json_error(['message' => __('Invalid email address.', 'email-domain-restriction')]);
        }

        $result = $this->role_manager->test_role_assignment($email);

        // Get role name
        if ($result['assigned_role']) {
            $role_obj = get_role($result['assigned_role']);
            $wp_roles = wp_roles();
            $result['assigned_role_name'] = isset($wp_roles->role_names[$result['assigned_role']])
                ? $wp_roles->role_names[$result['assigned_role']]
                : $result['assigned_role'];
        } else {
            $result['assigned_role_name'] = __('None (will use default)', 'email-domain-restriction');
        }

        // Get role names for matching patterns
        foreach ($result['matching_patterns'] as &$pattern) {
            $wp_roles = wp_roles();
            $pattern['role_name'] = isset($wp_roles->role_names[$pattern['role']])
                ? $wp_roles->role_names[$pattern['role']]
                : $pattern['role'];
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Export mappings
     */
    public function ajax_export_mappings()
    {
        check_ajax_referer('edr_role_mappings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'email-domain-restriction')]);
        }

        $json = $this->role_manager->export_role_mappings();

        wp_send_json_success([
            'json'     => $json,
            'filename' => 'edr-role-mappings-' . date('Y-m-d-His') . '.json',
        ]);
    }

    /**
     * Render admin page
     */
    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'email-domain-restriction'));
        }

        // Get current action
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $mapping_id = isset($_GET['mapping_id']) ? absint($_GET['mapping_id']) : 0;

        // Load view
        $view_file = EDR_PLUGIN_DIR . 'admin/pro/views/role-mapping-page.php';

        if (file_exists($view_file)) {
            include $view_file;
        }
    }

    /**
     * Get all WordPress roles
     *
     * @return array
     */
    public function get_available_roles()
    {
        $wp_roles = wp_roles();
        return $wp_roles->role_names;
    }
}
