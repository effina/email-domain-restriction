<?php
/**
 * PRO Dashboard
 *
 * Handles PRO dashboard features and advanced analytics display.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro_Admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Pro_Dashboard
 *
 * Manages PRO dashboard features.
 */
class EDR_Pro_Dashboard
{
    /**
     * Initialize PRO dashboard
     */
    public function init()
    {
        // Add PRO dashboard widgets
        add_action('admin_init', [$this, 'register_pro_dashboard_widgets']);

        // Enqueue PRO dashboard assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_pro_dashboard_assets']);
    }

    /**
     * Register PRO dashboard widgets
     */
    public function register_pro_dashboard_widgets()
    {
        // WooCommerce widget
        if (class_exists('WooCommerce')) {
            add_action('edr_dashboard_widgets', [$this, 'render_woocommerce_widget']);
        }

        // BuddyPress widget
        if (class_exists('BuddyPress')) {
            add_action('edr_dashboard_widgets', [$this, 'render_buddypress_widget']);
        }
    }

    /**
     * Render WooCommerce dashboard widget
     */
    public function render_woocommerce_widget()
    {
        if (!class_exists('EDR_WooCommerce_Integration')) {
            return;
        }

        $integration = new EDR_WooCommerce_Integration();
        $stats = $integration->get_woocommerce_stats(30);

        if (empty($stats)) {
            return;
        }

        ?>
        <div class="edr-dashboard-widget edr-woocommerce-widget">
            <h3>
                <span class="dashicons dashicons-cart"></span>
                <?php esc_html_e('WooCommerce Registrations (Last 30 Days)', 'email-domain-restriction'); ?>
            </h3>
            <div class="edr-widget-stats">
                <div class="edr-stat-box">
                    <div class="edr-stat-value"><?php echo esc_html($stats['checkout_registrations'] ?? 0); ?></div>
                    <div class="edr-stat-label"><?php esc_html_e('Checkout', 'email-domain-restriction'); ?></div>
                </div>
                <div class="edr-stat-box">
                    <div class="edr-stat-value"><?php echo esc_html($stats['myaccount_registrations'] ?? 0); ?></div>
                    <div class="edr-stat-label"><?php esc_html_e('My Account', 'email-domain-restriction'); ?></div>
                </div>
                <div class="edr-stat-box">
                    <div class="edr-stat-value"><?php echo esc_html($stats['total_allowed'] ?? 0); ?></div>
                    <div class="edr-stat-label"><?php esc_html_e('Allowed', 'email-domain-restriction'); ?></div>
                </div>
                <div class="edr-stat-box">
                    <div class="edr-stat-value"><?php echo esc_html($stats['total_blocked'] ?? 0); ?></div>
                    <div class="edr-stat-label"><?php esc_html_e('Blocked', 'email-domain-restriction'); ?></div>
                </div>
            </div>
        </div>
        <style>
        .edr-dashboard-widget {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .edr-dashboard-widget h3 {
            margin: 0 0 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .edr-dashboard-widget .dashicons {
            color: #0073aa;
        }
        .edr-widget-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }
        .edr-stat-box {
            text-align: center;
            padding: 15px;
            background: #f0f0f1;
            border-radius: 4px;
        }
        .edr-stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #0073aa;
            line-height: 1;
        }
        .edr-stat-label {
            margin-top: 8px;
            font-size: 13px;
            color: #666;
        }
        </style>
        <?php
    }

    /**
     * Render BuddyPress dashboard widget
     */
    public function render_buddypress_widget()
    {
        if (!class_exists('EDR_BuddyPress_Integration')) {
            return;
        }

        $integration = new EDR_BuddyPress_Integration();
        $stats = $integration->get_buddypress_stats(30);

        if (empty($stats)) {
            return;
        }

        ?>
        <div class="edr-dashboard-widget edr-buddypress-widget">
            <h3>
                <span class="dashicons dashicons-groups"></span>
                <?php esc_html_e('BuddyPress Registrations (Last 30 Days)', 'email-domain-restriction'); ?>
            </h3>
            <div class="edr-widget-stats">
                <div class="edr-stat-box">
                    <div class="edr-stat-value"><?php echo esc_html($stats['standard_registrations'] ?? 0); ?></div>
                    <div class="edr-stat-label"><?php esc_html_e('Standard', 'email-domain-restriction'); ?></div>
                </div>
                <div class="edr-stat-box">
                    <div class="edr-stat-value"><?php echo esc_html($stats['invitation_registrations'] ?? 0); ?></div>
                    <div class="edr-stat-label"><?php esc_html_e('Invitations', 'email-domain-restriction'); ?></div>
                </div>
                <div class="edr-stat-box">
                    <div class="edr-stat-value"><?php echo esc_html($stats['total_allowed'] ?? 0); ?></div>
                    <div class="edr-stat-label"><?php esc_html_e('Allowed', 'email-domain-restriction'); ?></div>
                </div>
                <div class="edr-stat-box">
                    <div class="edr-stat-value"><?php echo esc_html($stats['total_blocked'] ?? 0); ?></div>
                    <div class="edr-stat-label"><?php esc_html_e('Blocked', 'email-domain-restriction'); ?></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue PRO dashboard assets
     *
     * @param string $hook Page hook
     */
    public function enqueue_pro_dashboard_assets($hook)
    {
        // Only load on plugin pages
        if (strpos($hook, 'email-domain-restriction') === false) {
            return;
        }

        // Enqueue PRO-specific scripts and styles
        // Placeholder for future implementation
    }

    /**
     * Render advanced analytics section
     */
    public function render_advanced_analytics()
    {
        // Placeholder for future implementation
        include EDR_PLUGIN_DIR . 'admin/pro/views/advanced-analytics.php';
    }

    /**
     * Render geographic distribution chart
     */
    public function render_geographic_chart()
    {
        // Placeholder for future implementation
    }

    /**
     * Render conversion funnel
     */
    public function render_conversion_funnel()
    {
        // Placeholder for future implementation
    }
}
