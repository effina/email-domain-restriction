<?php
/**
 * Settings page handler.
 *
 * @package Email_Domain_Restriction
 */

/**
 * Settings page class.
 */
class EDR_Settings_Page
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'handle_form_submissions']);
    }

    /**
     * Handle form submissions.
     */
    public function handle_form_submissions()
    {
        // Handle domain add
        if (isset($_POST['edr_add_domain']) && check_admin_referer('edr_add_domain')) {
            $this->handle_add_domain();
        }

        // Handle domain remove
        if (isset($_POST['edr_remove_domain']) && check_admin_referer('edr_remove_domain')) {
            $this->handle_remove_domain();
        }

        // Handle bulk import
        if (isset($_POST['edr_bulk_import']) && check_admin_referer('edr_bulk_import')) {
            $this->handle_bulk_import();
        }

        // Handle bulk export
        if (isset($_GET['action']) && $_GET['action'] === 'edr_export_domains' && check_admin_referer('edr_export_domains', 'nonce')) {
            $this->handle_bulk_export();
        }

        // Handle settings update
        if (isset($_POST['edr_update_settings']) && check_admin_referer('edr_update_settings')) {
            $this->handle_settings_update();
        }
    }

    /**
     * Render domains tab.
     */
    public function render_domains_tab()
    {
        $domains = EDR_Domain_Manager::get_domains();
        include EDR_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Render settings tab.
     */
    public function render_settings_tab()
    {
        $settings = get_option('edr_settings', []);
        include EDR_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Handle add domain.
     */
    private function handle_add_domain()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $domain = isset($_POST['domain']) ? sanitize_text_field($_POST['domain']) : '';

        if (empty($domain)) {
            add_settings_error(
                'edr_messages',
                'edr_message',
                __('Domain cannot be empty.', 'email-domain-restriction'),
                'error'
            );
            return;
        }

        $result = EDR_Domain_Manager::add_domain($domain);

        if (is_wp_error($result)) {
            add_settings_error(
                'edr_messages',
                'edr_message',
                $result->get_error_message(),
                'error'
            );
        } else {
            add_settings_error(
                'edr_messages',
                'edr_message',
                sprintf(__('Domain "%s" added successfully.', 'email-domain-restriction'), esc_html($domain)),
                'success'
            );
        }
    }

    /**
     * Handle remove domain.
     */
    private function handle_remove_domain()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $domain = isset($_POST['domain']) ? sanitize_text_field($_POST['domain']) : '';

        if (EDR_Domain_Manager::remove_domain($domain)) {
            add_settings_error(
                'edr_messages',
                'edr_message',
                sprintf(__('Domain "%s" removed successfully.', 'email-domain-restriction'), esc_html($domain)),
                'success'
            );
        } else {
            add_settings_error(
                'edr_messages',
                'edr_message',
                __('Failed to remove domain.', 'email-domain-restriction'),
                'error'
            );
        }
    }

    /**
     * Handle bulk import.
     */
    private function handle_bulk_import()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            add_settings_error(
                'edr_messages',
                'edr_message',
                __('Please select a CSV file to import.', 'email-domain-restriction'),
                'error'
            );
            return;
        }

        // Validate file type
        $file_type = wp_check_filetype($_FILES['csv_file']['name']);
        if (!in_array($file_type['ext'], ['csv', 'txt'], true)) {
            add_settings_error(
                'edr_messages',
                'edr_message',
                __('Invalid file type. Please upload a CSV file.', 'email-domain-restriction'),
                'error'
            );
            return;
        }

        // Validate file size (1MB max)
        if ($_FILES['csv_file']['size'] > 1048576) {
            add_settings_error(
                'edr_messages',
                'edr_message',
                __('File size exceeds 1MB limit.', 'email-domain-restriction'),
                'error'
            );
            return;
        }

        // Read file
        $csv_data = file_get_contents($_FILES['csv_file']['tmp_name']);

        if ($csv_data === false) {
            add_settings_error(
                'edr_messages',
                'edr_message',
                __('Failed to read CSV file.', 'email-domain-restriction'),
                'error'
            );
            return;
        }

        // Import domains
        $results = EDR_Domain_Manager::bulk_import($csv_data);

        $message = sprintf(
            __('Import complete: %d added, %d skipped, %d errors.', 'email-domain-restriction'),
            $results['added'],
            $results['skipped'],
            count($results['errors'])
        );

        if (!empty($results['errors'])) {
            $message .= '<br><br>' . __('Errors:', 'email-domain-restriction') . '<br>';
            $message .= implode('<br>', array_slice($results['errors'], 0, 10));
            if (count($results['errors']) > 10) {
                $message .= '<br>' . sprintf(__('... and %d more errors', 'email-domain-restriction'), count($results['errors']) - 10);
            }
        }

        add_settings_error(
            'edr_messages',
            'edr_message',
            $message,
            !empty($results['errors']) ? 'warning' : 'success'
        );
    }

    /**
     * Handle bulk export.
     */
    private function handle_bulk_export()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $csv_content = EDR_Domain_Manager::bulk_export();
        $filename = 'edr-domains-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv_content;
        exit;
    }

    /**
     * Handle settings update.
     */
    private function handle_settings_update()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = [
            'log_retention_days' => isset($_POST['log_retention_days'])
                ? absint($_POST['log_retention_days'])
                : 30,
            'email_verification_enabled' => isset($_POST['email_verification_enabled']),
            'verification_token_expiry_hours' => isset($_POST['verification_token_expiry_hours'])
                ? absint($_POST['verification_token_expiry_hours'])
                : 48,
            'blocked_domain_message' => isset($_POST['blocked_domain_message'])
                ? sanitize_textarea_field($_POST['blocked_domain_message'])
                : '',
            'default_user_role' => isset($_POST['default_user_role'])
                ? sanitize_text_field($_POST['default_user_role'])
                : 'subscriber',
        ];

        update_option('edr_settings', $settings);

        add_settings_error(
            'edr_messages',
            'edr_message',
            __('Settings updated successfully.', 'email-domain-restriction'),
            'success'
        );
    }
}
