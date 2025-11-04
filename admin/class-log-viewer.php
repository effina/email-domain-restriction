<?php
/**
 * Registration log viewer.
 *
 * @package Email_Domain_Restriction
 */

/**
 * Log viewer class.
 */
class EDR_Log_Viewer
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'handle_actions']);
    }

    /**
     * Handle log actions.
     */
    public function handle_actions()
    {
        // Handle log export
        if (isset($_GET['action']) && $_GET['action'] === 'edr_export_log' && check_admin_referer('edr_export_log', 'nonce')) {
            $this->export_log();
        }

        // Handle clear old logs
        if (isset($_POST['edr_clear_logs']) && check_admin_referer('edr_clear_logs')) {
            $this->clear_old_logs();
        }
    }

    /**
     * Render log viewer page.
     */
    public function render()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'email-domain-restriction'));
        }

        // Get filter parameters
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $domain = isset($_GET['domain']) ? sanitize_text_field($_GET['domain']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 25;
        $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;

        // Build query args
        $args = [
            'status' => $status,
            'domain' => $domain,
            'limit' => $per_page,
            'offset' => ($paged - 1) * $per_page,
        ];

        if (!empty($date_from)) {
            $args['date_from'] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $args['date_to'] = $date_to . ' 23:59:59';
        }

        // Get attempts
        $attempts = EDR_Attempt_Logger::get_attempts($args);
        $total = EDR_Attempt_Logger::get_attempts_count($args);
        $total_pages = ceil($total / $per_page);

        include EDR_PLUGIN_DIR . 'admin/views/log-viewer.php';
    }

    /**
     * Export log to CSV.
     */
    private function export_log()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get filter parameters (same as render)
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $domain = isset($_GET['domain']) ? sanitize_text_field($_GET['domain']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

        $args = [
            'status' => $status,
            'domain' => $domain,
        ];

        if (!empty($date_from)) {
            $args['date_from'] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $args['date_to'] = $date_to . ' 23:59:59';
        }

        $csv_content = EDR_Attempt_Logger::export_log_csv($args);
        $filename = 'edr-registration-log-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv_content;
        exit;
    }

    /**
     * Clear old logs.
     */
    private function clear_old_logs()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = get_option('edr_settings', []);
        $retention_days = isset($settings['log_retention_days']) ? (int) $settings['log_retention_days'] : 30;

        $deleted = EDR_Attempt_Logger::cleanup_old_logs($retention_days);

        if ($deleted !== false) {
            add_settings_error(
                'edr_messages',
                'edr_message',
                sprintf(__('Successfully deleted %d old log entries.', 'email-domain-restriction'), $deleted),
                'success'
            );
        } else {
            add_settings_error(
                'edr_messages',
                'edr_message',
                __('Failed to clear old logs.', 'email-domain-restriction'),
                'error'
            );
        }
    }
}
