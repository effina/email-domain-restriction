<?php
/**
 * PRO Features Manager
 *
 * Handles PRO feature initialization and feature gating.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Pro_Features
 *
 * Manages PRO features and integration.
 */
class EDR_Pro_Features
{
    /**
     * Freemius instance
     *
     * @var Freemius
     */
    private static $freemius;

    /**
     * PRO features enabled flag
     *
     * @var bool
     */
    private static $is_pro_active = false;

    /**
     * Initialize PRO features
     */
    public static function init()
    {
        // Initialize Freemius
        self::init_freemius();

        // Check if PRO is active
        self::$is_pro_active = self::check_pro_status();

        // Load PRO components if active
        if (self::$is_pro_active) {
            self::load_pro_components();
        }

        // Add PRO admin hooks
        add_action('admin_menu', [__CLASS__, 'add_pro_menu_items'], 20);
        add_action('admin_notices', [__CLASS__, 'show_upgrade_notice']);
    }

    /**
     * Initialize Freemius SDK
     */
    private static function init_freemius()
    {
        global $edr_freemius;

        if (!isset($edr_freemius)) {
            // Include Freemius SDK
            require_once EDR_PLUGIN_DIR . 'vendor/freemius/start.php';

            $edr_freemius = fs_dynamic_init([
                'id'                  => '21633',
                'slug'                => 'email-domain-restriction',
                'premium_slug'        => 'email-domain-restriction-pro',
                'type'                => 'plugin',
                'public_key'          => 'pk_03e8185f96740ac7102635d05644c',
                'is_premium'          => true,
                'has_premium_version' => true,
                'has_paid_plans'      => true,
                'has_addons'          => false,
                'menu'                => [
                    'slug'       => 'email-domain-restriction',
                    'first-path' => 'admin.php?page=email-domain-restriction',
                    'support'    => false,
                ],
            ]);
        }

        self::$freemius = $edr_freemius;

        return self::$freemius;
    }

    /**
     * Check if PRO version is active
     *
     * @return bool
     */
    private static function check_pro_status()
    {
        if (self::$freemius === null) {
            return false;
        }

        // Check if license is valid
        return self::$freemius->is_premium() && self::$freemius->is_plan('pro', true);
    }

    /**
     * Load PRO components
     */
    private static function load_pro_components()
    {
        // Load PRO classes
        require_once EDR_PLUGIN_DIR . 'includes/pro/class-license-manager.php';
        require_once EDR_PLUGIN_DIR . 'includes/pro/class-role-manager.php';
        require_once EDR_PLUGIN_DIR . 'includes/pro/class-rate-limiter.php';
        require_once EDR_PLUGIN_DIR . 'includes/pro/class-advanced-analytics.php';
        require_once EDR_PLUGIN_DIR . 'includes/pro/class-domain-groups.php';

        // Load integrations
        require_once EDR_PLUGIN_DIR . 'includes/integrations/class-woocommerce-integration.php';
        require_once EDR_PLUGIN_DIR . 'includes/integrations/class-buddypress-integration.php';

        // Initialize PRO features
        $role_manager = new EDR_Role_Manager();
        $role_manager->init();

        $rate_limiter = new EDR_Rate_Limiter();
        $rate_limiter->init();

        // Initialize integrations if plugins are active
        if (class_exists('WooCommerce')) {
            $woocommerce_integration = new EDR_WooCommerce_Integration();
            $woocommerce_integration->init();
        }

        if (class_exists('BuddyPress')) {
            $buddypress_integration = new EDR_BuddyPress_Integration();
            $buddypress_integration->init();
        }

        // Load PRO admin components
        if (is_admin()) {
            require_once EDR_PLUGIN_DIR . 'admin/pro/class-pro-dashboard.php';
            require_once EDR_PLUGIN_DIR . 'admin/pro/class-pro-settings.php';
            require_once EDR_PLUGIN_DIR . 'admin/pro/class-license-page.php';

            $pro_dashboard = new EDR_Pro_Dashboard();
            $pro_dashboard->init();

            $pro_settings = new EDR_Pro_Settings();
            $pro_settings->init();
        }
    }

    /**
     * Add PRO menu items
     */
    public static function add_pro_menu_items()
    {
        if (!self::is_pro_active()) {
            // Add upgrade menu item for free users
            add_submenu_page(
                'email-domain-restriction',
                __('Upgrade to PRO', 'email-domain-restriction'),
                '<span style="color:#f18500">' . __('Upgrade to PRO', 'email-domain-restriction') . '</span>',
                'manage_options',
                'edr-upgrade',
                [__CLASS__, 'render_upgrade_page']
            );
        }
    }

    /**
     * Render upgrade page
     */
    public static function render_upgrade_page()
    {
        include EDR_PLUGIN_DIR . 'admin/views/upgrade-page.php';
    }

    /**
     * Show upgrade notice to free users
     */
    public static function show_upgrade_notice()
    {
        // Only show on plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'email-domain-restriction') === false) {
            return;
        }

        // Don't show if PRO is active
        if (self::is_pro_active()) {
            return;
        }

        // Check if user dismissed notice
        $dismissed = get_user_meta(get_current_user_id(), 'edr_upgrade_notice_dismissed', true);
        if ($dismissed) {
            return;
        }

        ?>
        <div class="notice notice-info is-dismissible edr-upgrade-notice">
            <p>
                <strong><?php esc_html_e('Email Domain Restriction PRO', 'email-domain-restriction'); ?></strong>
                <?php esc_html_e('Unlock WooCommerce integration, BuddyPress support, advanced analytics, and more!', 'email-domain-restriction'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=edr-upgrade')); ?>" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e('Learn More', 'email-domain-restriction'); ?>
                </a>
            </p>
        </div>
        <script>
        jQuery(document).on('click', '.edr-upgrade-notice .notice-dismiss', function() {
            jQuery.post(ajaxurl, {
                action: 'edr_dismiss_upgrade_notice',
                nonce: '<?php echo wp_create_nonce('edr_dismiss_upgrade_notice'); ?>'
            });
        });
        </script>
        <?php
    }

    /**
     * Check if PRO is active (public static method)
     *
     * @return bool
     */
    public static function is_pro_active()
    {
        return self::$is_pro_active;
    }

    /**
     * Get Freemius instance
     *
     * @return Freemius|null
     */
    public static function get_freemius()
    {
        return self::$freemius;
    }

    /**
     * Get PRO features list
     *
     * @return array
     */
    public static function get_pro_features()
    {
        return [
            'woocommerce_integration'  => __('WooCommerce Integration', 'email-domain-restriction'),
            'buddypress_integration'   => __('BuddyPress Integration', 'email-domain-restriction'),
            'role_based_restrictions'  => __('Role-Based Domain Restrictions', 'email-domain-restriction'),
            'advanced_analytics'       => __('Advanced Analytics & Reporting', 'email-domain-restriction'),
            'rate_limiting'            => __('Rate Limiting & Anti-Abuse', 'email-domain-restriction'),
            'geolocation'              => __('Geolocation & IP Restrictions', 'email-domain-restriction'),
            'domain_groups'            => __('Domain Groups & Conditional Rules', 'email-domain-restriction'),
            'webhooks_api'             => __('Webhooks & REST API', 'email-domain-restriction'),
            'advanced_verification'    => __('Advanced Email Verification', 'email-domain-restriction'),
            'multinetwork_support'     => __('Multi-Network Support', 'email-domain-restriction'),
            'admin_notifications'      => __('Admin Notifications (Slack, Email)', 'email-domain-restriction'),
            'gdpr_compliance'          => __('GDPR Compliance Tools', 'email-domain-restriction'),
            'audit_logging'            => __('Audit Logging', 'email-domain-restriction'),
            'unlimited_domains'        => __('Unlimited Domains', 'email-domain-restriction'),
            'priority_support'         => __('Priority Support', 'email-domain-restriction'),
        ];
    }

    /**
     * Check if specific PRO feature is available
     *
     * @param string $feature Feature key
     * @return bool
     */
    public static function has_feature($feature)
    {
        if (!self::is_pro_active()) {
            return false;
        }

        $features = self::get_pro_features();
        return isset($features[$feature]);
    }
}
