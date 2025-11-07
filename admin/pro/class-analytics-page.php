<?php
/**
 * Advanced Analytics Admin Page
 *
 * Handles advanced analytics administration interface.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro_Admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Analytics_Page
 *
 * Manages advanced analytics admin page.
 */
class EDR_Analytics_Page
{
    /**
     * Analytics instance
     *
     * @var EDR_Advanced_Analytics
     */
    private $analytics;

    /**
     * Initialize analytics page
     */
    public function init()
    {
        if (!edr_is_pro_active()) {
            return;
        }

        $this->analytics = new EDR_Advanced_Analytics();
        $this->analytics->init();

        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // AJAX handlers
        add_action('wp_ajax_edr_get_chart_data', [$this, 'ajax_get_chart_data']);
        add_action('wp_ajax_edr_export_csv', [$this, 'ajax_export_csv']);
        add_action('wp_ajax_edr_export_pdf', [$this, 'ajax_export_pdf']);
        add_action('wp_ajax_edr_schedule_report', [$this, 'ajax_schedule_report']);
        add_action('wp_ajax_edr_get_custom_report', [$this, 'ajax_get_custom_report']);

        // Schedule handler
        add_action('edr_send_scheduled_report', [$this->analytics, 'send_scheduled_report']);

        // Enqueue scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('edr_analytics_settings', 'edr_data_retention_days');
        register_setting('edr_analytics_settings', 'edr_scheduled_report_frequency');
        register_setting('edr_analytics_settings', 'edr_scheduled_report_email');
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'email-domain-restriction',
            __('Analytics', 'email-domain-restriction'),
            __('Analytics', 'email-domain-restriction'),
            'manage_options',
            'edr-analytics',
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
        if (strpos($hook, 'edr-analytics') === false) {
            return;
        }

        // Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );

        // Custom analytics script
        wp_enqueue_script(
            'edr-analytics',
            EDR_PLUGIN_URL . 'admin/js/analytics.js',
            ['jquery', 'chartjs'],
            EDR_VERSION,
            true
        );

        wp_localize_script('edr-analytics', 'edrAnalytics', [
            'nonce'          => wp_create_nonce('edr_analytics'),
            'loadingChart'   => __('Loading chart data...', 'email-domain-restriction'),
            'exportingCSV'   => __('Exporting CSV...', 'email-domain-restriction'),
            'exportingPDF'   => __('Generating PDF...', 'email-domain-restriction'),
            'schedulingReport' => __('Scheduling report...', 'email-domain-restriction'),
        ]);

        wp_enqueue_style(
            'edr-analytics',
            EDR_PLUGIN_URL . 'admin/css/analytics.css',
            [],
            EDR_VERSION
        );
    }

    /**
     * AJAX: Get chart data
     */
    public function ajax_get_chart_data()
    {
        check_ajax_referer('edr_analytics', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'email-domain-restriction')]);
        }

        $type = sanitize_text_field($_POST['chart_type']);
        $days = absint($_POST['days'] ?? 30);
        $interval = sanitize_text_field($_POST['interval'] ?? 'day');

        $data = match ($type) {
            'time_series'   => $this->analytics->get_time_series_data($days, $interval),
            'by_source'     => $this->analytics->get_attempts_by_source($days),
            'top_domains'   => $this->analytics->get_top_domains(10, $days),
            'geographic'    => $this->analytics->get_geographic_distribution($days),
            'funnel'        => $this->analytics->get_conversion_funnel($days),
            'rate_limits'   => $this->analytics->get_rate_limit_stats($days),
            default         => [],
        };

        wp_send_json_success(['data' => $data]);
    }

    /**
     * AJAX: Export CSV
     */
    public function ajax_export_csv()
    {
        check_ajax_referer('edr_analytics', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'email-domain-restriction')]);
        }

        $filters = [
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date'   => sanitize_text_field($_POST['end_date'] ?? ''),
            'source'     => sanitize_text_field($_POST['source'] ?? ''),
            'status'     => sanitize_text_field($_POST['status'] ?? ''),
        ];

        $csv = $this->analytics->export_to_csv($filters);

        if (empty($csv)) {
            wp_send_json_error(['message' => __('No data to export.', 'email-domain-restriction')]);
        }

        wp_send_json_success([
            'csv'      => $csv,
            'filename' => 'edr-analytics-' . date('Y-m-d-His') . '.csv',
        ]);
    }

    /**
     * AJAX: Export PDF
     */
    public function ajax_export_pdf()
    {
        check_ajax_referer('edr_analytics', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'email-domain-restriction')]);
        }

        $filters = [
            'days' => absint($_POST['days'] ?? 30),
        ];

        $result = $this->analytics->generate_pdf_report($filters);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        $upload_dir = wp_upload_dir();
        $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $result);

        wp_send_json_success([
            'url' => $file_url,
            'message' => __('PDF report generated successfully.', 'email-domain-restriction'),
        ]);
    }

    /**
     * AJAX: Schedule report
     */
    public function ajax_schedule_report()
    {
        check_ajax_referer('edr_analytics', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'email-domain-restriction')]);
        }

        $frequency = sanitize_text_field($_POST['frequency']);
        $email = sanitize_email($_POST['email']);

        if (!is_email($email)) {
            wp_send_json_error(['message' => __('Invalid email address.', 'email-domain-restriction')]);
        }

        if (!in_array($frequency, ['daily', 'weekly', 'monthly', 'never'])) {
            wp_send_json_error(['message' => __('Invalid frequency.', 'email-domain-restriction')]);
        }

        if ($frequency === 'never') {
            wp_clear_scheduled_hook('edr_send_scheduled_report', [$email]);
            update_option('edr_scheduled_report_frequency', '');
            update_option('edr_scheduled_report_email', '');

            wp_send_json_success(['message' => __('Scheduled reports cancelled.', 'email-domain-restriction')]);
        }

        $result = $this->analytics->schedule_email_report($frequency, $email);

        if ($result) {
            update_option('edr_scheduled_report_frequency', $frequency);
            update_option('edr_scheduled_report_email', $email);

            wp_send_json_success([
                'message' => sprintf(
                    __('Report scheduled %s to %s', 'email-domain-restriction'),
                    $frequency,
                    $email
                ),
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to schedule report.', 'email-domain-restriction')]);
        }
    }

    /**
     * AJAX: Get custom report
     */
    public function ajax_get_custom_report()
    {
        check_ajax_referer('edr_analytics', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'email-domain-restriction')]);
        }

        $config = [
            'metrics'    => isset($_POST['metrics']) ? array_map('sanitize_text_field', $_POST['metrics']) : [],
            'dimensions' => isset($_POST['dimensions']) ? array_map('sanitize_text_field', $_POST['dimensions']) : [],
            'filters'    => [
                'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
                'end_date'   => sanitize_text_field($_POST['end_date'] ?? ''),
            ],
        ];

        $data = $this->analytics->get_custom_report_data($config);

        wp_send_json_success(['data' => $data]);
    }

    /**
     * Render admin page
     */
    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'email-domain-restriction'));
        }

        $days = isset($_GET['days']) ? absint($_GET['days']) : 30;
        $stats = $this->analytics->get_dashboard_stats($days);
        $funnel = $this->analytics->get_conversion_funnel($days);
        $blocked = $this->analytics->get_blocked_attempts($days, 10);

        // Load view
        $view_file = EDR_PLUGIN_DIR . 'admin/pro/views/analytics-page.php';

        if (file_exists($view_file)) {
            include $view_file;
        }
    }

    /**
     * Get available date ranges
     *
     * @return array
     */
    public function get_date_ranges()
    {
        return [
            7   => __('Last 7 Days', 'email-domain-restriction'),
            30  => __('Last 30 Days', 'email-domain-restriction'),
            90  => __('Last 90 Days', 'email-domain-restriction'),
            180 => __('Last 6 Months', 'email-domain-restriction'),
            365 => __('Last Year', 'email-domain-restriction'),
        ];
    }

    /**
     * Get available sources
     *
     * @return array
     */
    public function get_available_sources()
    {
        return [
            'wordpress'              => __('WordPress', 'email-domain-restriction'),
            'woocommerce-checkout'   => __('WooCommerce Checkout', 'email-domain-restriction'),
            'woocommerce-myaccount'  => __('WooCommerce My Account', 'email-domain-restriction'),
            'buddypress'             => __('BuddyPress', 'email-domain-restriction'),
            'buddypress-invitation'  => __('BuddyPress Invitation', 'email-domain-restriction'),
            'ultimate-member'        => __('Ultimate Member', 'email-domain-restriction'),
        ];
    }
}
