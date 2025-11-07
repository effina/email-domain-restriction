<?php
/**
 * WooCommerce Integration
 *
 * Handles WooCommerce registration validation and features.
 *
 * @package Email_Domain_Restriction
 * @subpackage Integrations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_WooCommerce_Integration
 *
 * Integrates with WooCommerce for registration validation.
 */
class EDR_WooCommerce_Integration
{
    /**
     * WooCommerce detected flag
     *
     * @var bool
     */
    private $woocommerce_active = false;

    /**
     * Initialize WooCommerce integration
     */
    public function init()
    {
        // Only load if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        $this->woocommerce_active = true;

        // Validation hooks
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_registration']);
        add_action('woocommerce_register_post', [$this, 'validate_my_account_registration'], 10, 3);

        // Logging hooks
        add_action('woocommerce_created_customer', [$this, 'log_woocommerce_registration'], 10, 3);

        // Meta tracking hooks
        add_action('woocommerce_created_customer', [$this, 'add_customer_meta'], 20, 3);

        // Role assignment hooks (PRO)
        if (edr_is_pro_active()) {
            add_action('woocommerce_created_customer', [$this, 'assign_customer_role'], 30, 1);
        }

        // Admin hooks
        add_action('admin_notices', [$this, 'show_woocommerce_integration_notice']);
        add_action('admin_init', [$this, 'dismiss_woocommerce_notice']);

        // Rate limiting hooks (PRO)
        if (edr_is_pro_active() && class_exists('EDR_Rate_Limiter')) {
            add_action('woocommerce_created_customer', [$this, 'record_rate_limit'], 5, 3);
        }
    }

    /**
     * Validate email domain during checkout registration
     */
    public function validate_checkout_registration()
    {
        // Check if account is being created
        if (is_user_logged_in() || empty($_POST['createaccount'])) {
            return;
        }

        $email = isset($_POST['billing_email']) ? sanitize_email($_POST['billing_email']) : '';

        if (empty($email)) {
            return;
        }

        $domain = substr(strrchr($email, '@'), 1);

        // Check rate limiting (PRO)
        if (edr_is_pro_active() && class_exists('EDR_Rate_Limiter')) {
            $rate_limiter = new EDR_Rate_Limiter();

            if ($rate_limiter->is_domain_rate_limited($domain)) {
                wc_add_notice(
                    __('Too many registration attempts from this domain. Please try again later.', 'email-domain-restriction'),
                    'error'
                );
                return;
            }

            if ($rate_limiter->is_ip_rate_limited($this->get_client_ip())) {
                wc_add_notice(
                    __('Too many registration attempts. Please try again later.', 'email-domain-restriction'),
                    'error'
                );
                return;
            }
        }

        // Validate email domain
        $validation = EDR_Domain_Validator::validate_email($email);

        if (is_wp_error($validation)) {
            // Log blocked attempt
            $this->log_blocked_attempt($email, 'woocommerce-checkout');

            // Display custom error message
            $error_message = get_option('edr_woocommerce_error_message');
            if (empty($error_message)) {
                $error_message = $validation->get_error_message();
            }

            wc_add_notice($error_message, 'error');
        }
    }

    /**
     * Validate email domain on My Account registration
     *
     * @param string $username Username
     * @param string $email Email address
     * @param WP_Error $validation_error Validation errors
     */
    public function validate_my_account_registration($username, $email, $validation_error)
    {
        $domain = substr(strrchr($email, '@'), 1);

        // Check rate limiting (PRO)
        if (edr_is_pro_active() && class_exists('EDR_Rate_Limiter')) {
            $rate_limiter = new EDR_Rate_Limiter();

            if ($rate_limiter->is_domain_rate_limited($domain)) {
                $validation_error->add(
                    'registration_error_rate_limit',
                    __('Too many registration attempts from this domain. Please try again later.', 'email-domain-restriction')
                );
                return;
            }

            if ($rate_limiter->is_ip_rate_limited($this->get_client_ip())) {
                $validation_error->add(
                    'registration_error_rate_limit',
                    __('Too many registration attempts. Please try again later.', 'email-domain-restriction')
                );
                return;
            }
        }

        // Validate email domain
        $validation = EDR_Domain_Validator::validate_email($email);

        if (is_wp_error($validation)) {
            // Log blocked attempt
            $this->log_blocked_attempt($email, 'woocommerce-myaccount');

            // Add error with custom message if available
            $error_message = get_option('edr_woocommerce_error_message');
            if (empty($error_message)) {
                $error_message = $validation->get_error_message();
            }

            $validation_error->add(
                'registration_error_invalid_domain',
                $error_message
            );
        }
    }

    /**
     * Log WooCommerce registration attempt
     *
     * @param int $customer_id Customer ID
     * @param array $new_customer_data Customer data
     * @param string $password_generated Whether password was generated
     */
    public function log_woocommerce_registration($customer_id, $new_customer_data, $password_generated)
    {
        $user = get_userdata($customer_id);

        if (!$user) {
            return;
        }

        $email = $user->user_email;
        $domain = substr(strrchr($email, '@'), 1);

        // Determine registration source
        $source = 'woocommerce-myaccount';
        if (did_action('woocommerce_checkout_process')) {
            $source = 'woocommerce-checkout';
        }

        // Log the registration
        if (class_exists('EDR_Attempt_Logger')) {
            $log_data = [
                'email'     => $email,
                'domain'    => $domain,
                'status'    => 'allowed',
                'source'    => $source,
                'user_id'   => $customer_id,
            ];

            // Add geolocation if PRO is active
            if (edr_is_pro_active()) {
                $geo_data = $this->get_geolocation_data();
                if ($geo_data) {
                    $log_data = array_merge($log_data, $geo_data);
                }
            }

            EDR_Attempt_Logger::log_attempt($log_data);
        }
    }

    /**
     * Log blocked registration attempt
     *
     * @param string $email Email address
     * @param string $source Registration source
     */
    private function log_blocked_attempt($email, $source)
    {
        if (!class_exists('EDR_Attempt_Logger')) {
            return;
        }

        $domain = substr(strrchr($email, '@'), 1);

        $log_data = [
            'email'     => $email,
            'domain'    => $domain,
            'status'    => 'blocked',
            'source'    => $source,
            'user_id'   => null,
        ];

        // Add geolocation if PRO is active
        if (edr_is_pro_active()) {
            $geo_data = $this->get_geolocation_data();
            if ($geo_data) {
                $log_data = array_merge($log_data, $geo_data);
            }
        }

        EDR_Attempt_Logger::log_attempt($log_data);
    }

    /**
     * Add WooCommerce-specific customer meta
     *
     * @param int $customer_id Customer ID
     * @param array $new_customer_data Customer data
     * @param string $password_generated Whether password was generated
     */
    public function add_customer_meta($customer_id, $new_customer_data, $password_generated)
    {
        // Mark as WooCommerce registration
        update_user_meta($customer_id, '_edr_registered_via_woocommerce', 'yes');

        // Store registration source
        $source = did_action('woocommerce_checkout_process') ? 'checkout' : 'my-account';
        update_user_meta($customer_id, '_edr_registration_source', $source);

        // Store order ID if registered during checkout
        if ($source === 'checkout' && !empty(WC()->session)) {
            $order_id = WC()->session->get('order_awaiting_payment');
            if ($order_id) {
                update_user_meta($customer_id, '_edr_order_id_at_registration', $order_id);
            }
        }

        // Store verification date
        update_user_meta($customer_id, '_edr_domain_verified_date', current_time('mysql'));

        // Store email domain
        $user = get_userdata($customer_id);
        if ($user) {
            $domain = substr(strrchr($user->user_email, '@'), 1);
            update_user_meta($customer_id, '_edr_email_domain', $domain);
        }
    }

    /**
     * Assign customer role based on domain (PRO)
     *
     * @param int $customer_id Customer ID
     */
    public function assign_customer_role($customer_id)
    {
        if (!edr_is_pro_active() || !class_exists('EDR_Role_Manager')) {
            return;
        }

        $role_manager = new EDR_Role_Manager();
        $role_manager->assign_role_by_domain($customer_id);
    }

    /**
     * Record rate limit attempt (PRO)
     *
     * @param int $customer_id Customer ID
     * @param array $new_customer_data Customer data
     * @param string $password_generated Whether password was generated
     */
    public function record_rate_limit($customer_id, $new_customer_data, $password_generated)
    {
        if (!edr_is_pro_active() || !class_exists('EDR_Rate_Limiter')) {
            return;
        }

        $user = get_userdata($customer_id);
        if (!$user) {
            return;
        }

        $domain = substr(strrchr($user->user_email, '@'), 1);
        $rate_limiter = new EDR_Rate_Limiter();

        // Record domain attempt
        $rate_limiter->record_attempt('domain', $domain);

        // Record IP attempt
        $rate_limiter->record_attempt('ip', $this->get_client_ip());
    }

    /**
     * Get geolocation data (PRO)
     *
     * @return array|null
     */
    private function get_geolocation_data()
    {
        if (!edr_is_pro_active()) {
            return null;
        }

        // Placeholder for geolocation implementation
        // Will be implemented in Phase 7
        return null;
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip()
    {
        $ip_address = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip_address = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }

        return $ip_address;
    }

    /**
     * Show WooCommerce integration notice
     */
    public function show_woocommerce_integration_notice()
    {
        // Only show on plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'email-domain-restriction') === false) {
            return;
        }

        // Check if user dismissed notice
        $dismissed = get_user_meta(get_current_user_id(), 'edr_woocommerce_notice_dismissed', true);
        if ($dismissed) {
            return;
        }

        ?>
        <div class="notice notice-success is-dismissible edr-woocommerce-notice" data-notice="woocommerce">
            <p>
                <strong><?php esc_html_e('WooCommerce Integration Active', 'email-domain-restriction'); ?></strong><br>
                <?php esc_html_e('Email Domain Restriction is now validating WooCommerce registrations (checkout and My Account).', 'email-domain-restriction'); ?>
            </p>
        </div>
        <script>
        jQuery(document).on('click', '.edr-woocommerce-notice .notice-dismiss', function() {
            jQuery.post(ajaxurl, {
                action: 'edr_dismiss_integration_notice',
                notice: 'woocommerce',
                nonce: '<?php echo wp_create_nonce('edr_dismiss_notice'); ?>'
            });
        });
        </script>
        <?php
    }

    /**
     * Handle dismissal of WooCommerce notice
     */
    public function dismiss_woocommerce_notice()
    {
        if (!isset($_POST['action']) || $_POST['action'] !== 'edr_dismiss_integration_notice') {
            return;
        }

        if (!isset($_POST['notice']) || $_POST['notice'] !== 'woocommerce') {
            return;
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'edr_dismiss_notice')) {
            return;
        }

        update_user_meta(get_current_user_id(), 'edr_woocommerce_notice_dismissed', true);
    }

    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    public function is_woocommerce_active()
    {
        return $this->woocommerce_active;
    }

    /**
     * Get WooCommerce registration statistics (PRO)
     *
     * @param int $days Number of days
     * @return array
     */
    public function get_woocommerce_stats($days = 30)
    {
        if (!edr_is_pro_active()) {
            return [];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'edr_registration_attempts';
        $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));

        // Get stats by source
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT
                SUM(CASE WHEN source = 'woocommerce-checkout' THEN 1 ELSE 0 END) as checkout_registrations,
                SUM(CASE WHEN source = 'woocommerce-myaccount' THEN 1 ELSE 0 END) as myaccount_registrations,
                SUM(CASE WHEN source LIKE 'woocommerce%%' AND status = 'allowed' THEN 1 ELSE 0 END) as total_allowed,
                SUM(CASE WHEN source LIKE 'woocommerce%%' AND status = 'blocked' THEN 1 ELSE 0 END) as total_blocked
            FROM $table
            WHERE created_at >= %s
            AND source LIKE 'woocommerce%%'",
            $cutoff
        ), ARRAY_A);

        return $stats[0] ?? [];
    }
}

