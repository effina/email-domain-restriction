<?php
/**
 * Admin menu setup.
 *
 * @package Email_Domain_Restriction
 */

/**
 * Admin menu class.
 */
class EDR_Admin_Menu
{
    /**
     * Initialize admin menu hooks.
     */
    public function init()
    {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Add admin menu pages.
     */
    public function add_menu_pages()
    {
        // Main menu page (Dashboard)
        add_menu_page(
            __('Email Domain Restriction', 'email-domain-restriction'),
            __('Email Domain Restriction', 'email-domain-restriction'),
            'manage_options',
            'email-domain-restriction',
            [$this, 'render_dashboard_page'],
            'dashicons-shield',
            80
        );

        // Dashboard submenu (duplicate of main menu)
        add_submenu_page(
            'email-domain-restriction',
            __('Dashboard', 'email-domain-restriction'),
            __('Dashboard', 'email-domain-restriction'),
            'manage_options',
            'email-domain-restriction',
            [$this, 'render_dashboard_page']
        );

        // Domain Whitelist submenu
        add_submenu_page(
            'email-domain-restriction',
            __('Domain Whitelist', 'email-domain-restriction'),
            __('Domain Whitelist', 'email-domain-restriction'),
            'manage_options',
            'edr-domains',
            [$this, 'render_domains_page']
        );

        // Registration Log submenu
        add_submenu_page(
            'email-domain-restriction',
            __('Registration Log', 'email-domain-restriction'),
            __('Registration Log', 'email-domain-restriction'),
            'manage_options',
            'edr-log',
            [$this, 'render_log_page']
        );

        // Settings submenu
        add_submenu_page(
            'email-domain-restriction',
            __('Settings', 'email-domain-restriction'),
            __('Settings', 'email-domain-restriction'),
            'manage_options',
            'edr-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Render dashboard page.
     */
    public function render_dashboard_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'email-domain-restriction'));
        }

        $dashboard = new EDR_Dashboard();
        $dashboard->render();
    }

    /**
     * Render domains page.
     */
    public function render_domains_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'email-domain-restriction'));
        }

        $settings_page = new EDR_Settings_Page();
        $settings_page->render_domains_tab();
    }

    /**
     * Render log page.
     */
    public function render_log_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'email-domain-restriction'));
        }

        $log_viewer = new EDR_Log_Viewer();
        $log_viewer->render();
    }

    /**
     * Render settings page.
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'email-domain-restriction'));
        }

        $settings_page = new EDR_Settings_Page();
        $settings_page->render_settings_tab();
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load on our plugin pages
        if (strpos($hook, 'email-domain-restriction') === false && strpos($hook, 'edr-') === false) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'edr-admin-styles',
            EDR_PLUGIN_URL . 'assets/css/admin-styles.css',
            [],
            EDR_VERSION
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'edr-admin-scripts',
            EDR_PLUGIN_URL . 'assets/js/admin-scripts.js',
            ['jquery'],
            EDR_VERSION,
            true
        );

        // Enqueue dashboard assets on dashboard page
        if (strpos($hook, 'toplevel_page_email-domain-restriction') !== false) {
            // Chart.js
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
                [],
                '4.4.0',
                true
            );

            // Dashboard CSS
            wp_enqueue_style(
                'edr-dashboard-styles',
                EDR_PLUGIN_URL . 'assets/css/dashboard.css',
                ['edr-admin-styles'],
                EDR_VERSION
            );

            // Dashboard JS
            wp_enqueue_script(
                'edr-dashboard-scripts',
                EDR_PLUGIN_URL . 'assets/js/dashboard.js',
                ['jquery', 'chartjs'],
                EDR_VERSION,
                true
            );

            // Localize script
            wp_localize_script(
                'edr-dashboard-scripts',
                'edrDashboard',
                [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('edr_dashboard'),
                ]
            );
        }

        // Localize main admin script
        wp_localize_script(
            'edr-admin-scripts',
            'edrAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('edr_admin'),
                'confirmDelete' => __('Are you sure you want to delete this domain?', 'email-domain-restriction'),
                'confirmClearLogs' => __('Are you sure you want to clear old logs? This cannot be undone.', 'email-domain-restriction'),
            ]
        );
    }
}
