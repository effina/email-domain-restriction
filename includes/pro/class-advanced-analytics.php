<?php
/**
 * Advanced Analytics
 *
 * Handles advanced analytics and reporting features.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Advanced_Analytics
 *
 * Provides advanced analytics and reporting capabilities.
 */
class EDR_Advanced_Analytics
{
    /**
     * Initialize advanced analytics
     */
    public function init()
    {
        // Schedule cleanup of old data
        add_action('edr_daily_cleanup', [$this, 'cleanup_old_data']);

        if (!wp_next_scheduled('edr_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'edr_daily_cleanup');
        }
    }

    /**
     * Get comprehensive dashboard statistics
     *
     * @param int $days Number of days to analyze
     * @return array
     */
    public function get_dashboard_stats($days = 30)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_registration_attempts';
        $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));

        // Get overall statistics
        $overall = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_attempts,
                SUM(CASE WHEN status = 'allowed' THEN 1 ELSE 0 END) as total_allowed,
                SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as total_blocked,
                COUNT(DISTINCT email) as unique_emails,
                COUNT(DISTINCT ip_address) as unique_ips
            FROM $table
            WHERE created_at >= %s",
            $cutoff
        ), ARRAY_A);

        // Get by source breakdown
        $by_source = $wpdb->get_results($wpdb->prepare(
            "SELECT
                source,
                COUNT(*) as attempts,
                SUM(CASE WHEN status = 'allowed' THEN 1 ELSE 0 END) as allowed,
                SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked
            FROM $table
            WHERE created_at >= %s
            GROUP BY source",
            $cutoff
        ), ARRAY_A);

        // Calculate conversion rate
        $conversion_rate = $overall['total_attempts'] > 0
            ? round(($overall['total_allowed'] / $overall['total_attempts']) * 100, 2)
            : 0;

        return [
            'overall'          => $overall,
            'by_source'        => $by_source,
            'conversion_rate'  => $conversion_rate,
            'date_range'       => $days,
        ];
    }

    /**
     * Get time-series data for charts
     *
     * @param int $days Number of days
     * @param string $interval Interval (day, week, month)
     * @return array
     */
    public function get_time_series_data($days = 30, $interval = 'day')
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_registration_attempts';
        $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));

        // Determine date format based on interval
        $date_format = match ($interval) {
            'hour'  => '%Y-%m-%d %H:00:00',
            'day'   => '%Y-%m-%d',
            'week'  => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE_FORMAT(created_at, %s) as period,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'allowed' THEN 1 ELSE 0 END) as allowed,
                SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked
            FROM $table
            WHERE created_at >= %s
            GROUP BY period
            ORDER BY period ASC",
            $date_format,
            $cutoff
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Get registration attempts by source
     *
     * @param int $days Number of days to analyze
     * @return array
     */
    public function get_attempts_by_source($days = 30)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_registration_attempts';
        $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                source,
                COUNT(*) as count,
                SUM(CASE WHEN status = 'allowed' THEN 1 ELSE 0 END) as allowed,
                SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked
            FROM $table
            WHERE created_at >= %s
            GROUP BY source
            ORDER BY count DESC",
            $cutoff
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Get top domains by registration attempts
     *
     * @param int $limit Number of results
     * @param int $days Number of days
     * @return array
     */
    public function get_top_domains($limit = 10, $days = 30)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_registration_attempts';
        $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));

        // Extract domain from email
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                SUBSTRING_INDEX(email, '@', -1) as domain,
                COUNT(*) as attempts,
                SUM(CASE WHEN status = 'allowed' THEN 1 ELSE 0 END) as allowed,
                SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked
            FROM $table
            WHERE created_at >= %s
            GROUP BY domain
            ORDER BY attempts DESC
            LIMIT %d",
            $cutoff,
            $limit
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Get geographic distribution of registration attempts
     *
     * @param int $days Number of days to analyze
     * @return array
     */
    public function get_geographic_distribution($days = 30)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_registration_attempts';
        $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                country_code,
                COUNT(*) as count,
                SUM(CASE WHEN status = 'allowed' THEN 1 ELSE 0 END) as allowed,
                SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked
            FROM $table
            WHERE created_at >= %s
            AND country_code IS NOT NULL
            GROUP BY country_code
            ORDER BY count DESC",
            $cutoff
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Get conversion funnel data
     *
     * @param int $days Number of days to analyze
     * @return array
     */
    public function get_conversion_funnel($days = 30)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_registration_attempts';
        $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));

        // Total attempts
        $attempts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE created_at >= %s",
            $cutoff
        ));

        // Allowed registrations
        $allowed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE created_at >= %s AND status = 'allowed'",
            $cutoff
        ));

        // Count users created in this period
        $users_created = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT u.ID)
            FROM {$wpdb->users} u
            INNER JOIN $table r ON u.user_email = r.email
            WHERE r.created_at >= %s
            AND r.status = 'allowed'",
            $cutoff
        ));

        // Count active users (logged in within last 7 days)
        $active_cutoff = date('Y-m-d H:i:s', strtotime('-7 days'));
        $active_users = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT u.ID)
            FROM {$wpdb->users} u
            INNER JOIN $table r ON u.user_email = r.email
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE r.created_at >= %s
            AND r.status = 'allowed'
            AND um.meta_key = 'last_login'
            AND um.meta_value >= %s",
            $cutoff,
            $active_cutoff
        ));

        return [
            'attempts'      => (int) $attempts,
            'allowed'       => (int) $allowed,
            'users_created' => (int) $users_created,
            'active_users'  => (int) $active_users,
        ];
    }

    /**
     * Get blocked attempts with reasons
     *
     * @param int $days Number of days
     * @param int $limit Number of results
     * @return array
     */
    public function get_blocked_attempts($days = 30, $limit = 100)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_registration_attempts';
        $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                email,
                ip_address,
                source,
                blocked_reason,
                country_code,
                created_at
            FROM $table
            WHERE created_at >= %s
            AND status = 'blocked'
            ORDER BY created_at DESC
            LIMIT %d",
            $cutoff,
            $limit
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Get rate limit statistics
     *
     * @param int $days Number of days
     * @return array
     */
    public function get_rate_limit_stats($days = 30)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_rate_limits';
        $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return [];
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                identifier_type,
                COUNT(DISTINCT identifier) as unique_identifiers,
                SUM(attempt_count) as total_attempts,
                COUNT(*) as rate_limit_events
            FROM $table
            WHERE window_start >= %s
            GROUP BY identifier_type",
            $cutoff
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Export analytics data to CSV
     *
     * @param array $filters Filters to apply
     * @return string CSV content
     */
    public function export_to_csv($filters = [])
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_registration_attempts';

        // Build query with filters
        $where = ['1=1'];
        $values = [];

        if (!empty($filters['start_date'])) {
            $where[] = 'created_at >= %s';
            $values[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'created_at <= %s';
            $values[] = $filters['end_date'];
        }

        if (!empty($filters['source'])) {
            $where[] = 'source = %s';
            $values[] = $filters['source'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $values[] = $filters['status'];
        }

        $where_clause = implode(' AND ', $where);
        $query = "SELECT * FROM $table WHERE $where_clause ORDER BY created_at DESC";

        if (!empty($values)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $values), ARRAY_A);
        } else {
            $results = $wpdb->get_results($query, ARRAY_A);
        }

        if (empty($results)) {
            return '';
        }

        // Build CSV
        $csv = [];

        // Header row
        $csv[] = implode(',', array_keys($results[0]));

        // Data rows
        foreach ($results as $row) {
            $csv[] = implode(',', array_map(function ($value) {
                // Escape values
                return '"' . str_replace('"', '""', $value ?? '') . '"';
            }, $row));
        }

        return implode("\n", $csv);
    }

    /**
     * Generate PDF report
     *
     * @param array $filters Filters to apply
     * @return string|WP_Error PDF file path or error
     */
    public function generate_pdf_report($filters = [])
    {
        // Check if required library exists
        if (!class_exists('TCPDF')) {
            return new WP_Error('missing_library', __('PDF library not available.', 'email-domain-restriction'));
        }

        $days = $filters['days'] ?? 30;
        $stats = $this->get_dashboard_stats($days);
        $time_series = $this->get_time_series_data($days);
        $top_domains = $this->get_top_domains(10, $days);

        // Create PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Email Domain Restriction PRO');
        $pdf->SetAuthor(get_bloginfo('name'));
        $pdf->SetTitle('Registration Analytics Report');
        $pdf->SetSubject('Email Domain Restriction Analytics');

        // Add a page
        $pdf->AddPage();

        // Title
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 15, 'Registration Analytics Report', 0, 1, 'C');

        // Date range
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, sprintf('Report Period: Last %d Days', $days), 0, 1, 'C');
        $pdf->Ln(10);

        // Overall statistics
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Overall Statistics', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);

        $overall = $stats['overall'];
        $pdf->Cell(0, 8, 'Total Attempts: ' . $overall['total_attempts'], 0, 1);
        $pdf->Cell(0, 8, 'Allowed: ' . $overall['total_allowed'], 0, 1);
        $pdf->Cell(0, 8, 'Blocked: ' . $overall['total_blocked'], 0, 1);
        $pdf->Cell(0, 8, 'Conversion Rate: ' . $stats['conversion_rate'] . '%', 0, 1);
        $pdf->Ln(10);

        // Top domains table
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Top Domains', 0, 1, 'L');

        $html = '<table border="1" cellpadding="5">';
        $html .= '<tr><th>Domain</th><th>Attempts</th><th>Allowed</th><th>Blocked</th></tr>';

        foreach ($top_domains as $domain) {
            $html .= sprintf(
                '<tr><td>%s</td><td>%d</td><td>%d</td><td>%d</td></tr>',
                esc_html($domain['domain']),
                $domain['attempts'],
                $domain['allowed'],
                $domain['blocked']
            );
        }

        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        // Save to temp file
        $upload_dir = wp_upload_dir();
        $filename = 'edr-report-' . date('Y-m-d-His') . '.pdf';
        $filepath = $upload_dir['basedir'] . '/edr-reports/' . $filename;

        // Create directory if it doesn't exist
        wp_mkdir_p(dirname($filepath));

        $pdf->Output($filepath, 'F');

        return $filepath;
    }

    /**
     * Schedule email report
     *
     * @param string $frequency Frequency (daily, weekly, monthly)
     * @param string $email Recipient email
     * @return bool
     */
    public function schedule_email_report($frequency, $email)
    {
        $hook = 'edr_send_scheduled_report';

        // Clear existing schedules for this email
        wp_clear_scheduled_hook($hook, [$email]);

        // Schedule new event
        $timestamp = match ($frequency) {
            'daily'   => strtotime('tomorrow 9:00'),
            'weekly'  => strtotime('next Monday 9:00'),
            'monthly' => strtotime('first day of next month 9:00'),
            default   => false,
        };

        if ($timestamp) {
            return wp_schedule_event($timestamp, $frequency, $hook, [$email]);
        }

        return false;
    }

    /**
     * Send scheduled email report
     *
     * @param string $recipient Recipient email
     */
    public function send_scheduled_report($recipient)
    {
        $stats = $this->get_dashboard_stats(30);

        $subject = sprintf(
            __('[%s] Registration Analytics Report', 'email-domain-restriction'),
            get_bloginfo('name')
        );

        $message = $this->format_email_report($stats);

        wp_mail($recipient, $subject, $message);
    }

    /**
     * Format email report content
     *
     * @param array $stats Statistics data
     * @return string
     */
    private function format_email_report($stats)
    {
        $overall = $stats['overall'];

        $message = sprintf(
            __('Registration Analytics Report for %s', 'email-domain-restriction'),
            get_bloginfo('name')
        ) . "\n\n";

        $message .= __('Overall Statistics (Last 30 Days):', 'email-domain-restriction') . "\n";
        $message .= '-------------------------------------------' . "\n";
        $message .= sprintf(__('Total Attempts: %d', 'email-domain-restriction'), $overall['total_attempts']) . "\n";
        $message .= sprintf(__('Allowed: %d', 'email-domain-restriction'), $overall['total_allowed']) . "\n";
        $message .= sprintf(__('Blocked: %d', 'email-domain-restriction'), $overall['total_blocked']) . "\n";
        $message .= sprintf(__('Conversion Rate: %s%%', 'email-domain-restriction'), $stats['conversion_rate']) . "\n\n";

        $message .= __('By Source:', 'email-domain-restriction') . "\n";
        $message .= '-------------------------------------------' . "\n";

        foreach ($stats['by_source'] as $source) {
            $message .= sprintf(
                "%s: %d attempts (%d allowed, %d blocked)\n",
                $source['source'],
                $source['attempts'],
                $source['allowed'],
                $source['blocked']
            );
        }

        $message .= "\n" . __('View detailed analytics:', 'email-domain-restriction') . "\n";
        $message .= admin_url('admin.php?page=edr-analytics');

        return $message;
    }

    /**
     * Cleanup old data based on retention policy
     */
    public function cleanup_old_data()
    {
        $retention_days = get_option('edr_data_retention_days', 365);

        if ($retention_days <= 0) {
            return; // Retention disabled
        }

        global $wpdb;

        $table = $wpdb->prefix . 'edr_registration_attempts';
        $cutoff = date('Y-m-d H:i:s', strtotime("-$retention_days days"));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE created_at < %s",
            $cutoff
        ));
    }

    /**
     * Get custom report data
     *
     * @param array $config Report configuration
     * @return array
     */
    public function get_custom_report_data($config)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_registration_attempts';

        // Build query based on configuration
        $select = [];
        $groupby = [];

        foreach ($config['metrics'] as $metric) {
            $select[] = match ($metric) {
                'count'         => 'COUNT(*) as count',
                'allowed'       => "SUM(CASE WHEN status = 'allowed' THEN 1 ELSE 0 END) as allowed",
                'blocked'       => "SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked",
                'unique_emails' => 'COUNT(DISTINCT email) as unique_emails',
                'unique_ips'    => 'COUNT(DISTINCT ip_address) as unique_ips',
                default         => 'COUNT(*) as count',
            };
        }

        foreach ($config['dimensions'] as $dimension) {
            $groupby[] = $dimension;
        }

        $select_clause = implode(', ', array_merge($groupby, $select));
        $groupby_clause = !empty($groupby) ? 'GROUP BY ' . implode(', ', $groupby) : '';

        $query = "SELECT $select_clause FROM $table";

        // Apply filters
        $where = [];
        $values = [];

        if (!empty($config['filters']['start_date'])) {
            $where[] = 'created_at >= %s';
            $values[] = $config['filters']['start_date'];
        }

        if (!empty($config['filters']['end_date'])) {
            $where[] = 'created_at <= %s';
            $values[] = $config['filters']['end_date'];
        }

        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }

        if ($groupby_clause) {
            $query .= ' ' . $groupby_clause;
        }

        if (!empty($values)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $values), ARRAY_A);
        } else {
            $results = $wpdb->get_results($query, ARRAY_A);
        }

        return $results ?: [];
    }
}
