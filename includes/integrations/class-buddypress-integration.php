<?php
/**
 * BuddyPress Integration
 *
 * Handles BuddyPress registration validation and features.
 *
 * @package Email_Domain_Restriction
 * @subpackage Integrations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_BuddyPress_Integration
 *
 * Integrates with BuddyPress for registration validation.
 */
class EDR_BuddyPress_Integration
{
    /**
     * BuddyPress detected flag
     *
     * @var bool
     */
    private $buddypress_active = false;

    /**
     * Initialize BuddyPress integration
     */
    public function init()
    {
        // Only load if BuddyPress is active
        if (!class_exists('BuddyPress')) {
            return;
        }

        $this->buddypress_active = true;

        // Validation hooks
        add_action('bp_signup_validate', [$this, 'validate_registration']);

        // Logging hooks
        add_action('bp_core_signup_user', [$this, 'log_buddypress_registration'], 10, 5);

        // Meta tracking hooks
        add_action('bp_core_activated_user', [$this, 'add_member_meta'], 10, 3);

        // Member type assignment (PRO)
        if (edr_is_pro_active()) {
            add_action('bp_core_activated_user', [$this, 'assign_member_type'], 20, 3);
        }

        // Group join validation (PRO)
        if (edr_is_pro_active()) {
            add_action('groups_join_group', [$this, 'validate_group_join'], 10, 2);
        }

        // Admin hooks
        add_action('admin_notices', [$this, 'show_buddypress_integration_notice']);
        add_action('admin_init', [$this, 'dismiss_buddypress_notice']);

        // Rate limiting hooks (PRO)
        if (edr_is_pro_active() && class_exists('EDR_Rate_Limiter')) {
            add_action('bp_core_signup_user', [$this, 'record_rate_limit'], 5, 5);
        }
    }

    /**
     * Validate email domain during BuddyPress registration
     */
    public function validate_registration()
    {
        global $bp;

        $email = isset($_POST['signup_email']) ? sanitize_email($_POST['signup_email']) : '';

        if (empty($email)) {
            return;
        }

        $domain = substr(strrchr($email, '@'), 1);

        // Check rate limiting (PRO)
        if (edr_is_pro_active() && class_exists('EDR_Rate_Limiter')) {
            $rate_limiter = new EDR_Rate_Limiter();

            if ($rate_limiter->is_domain_rate_limited($domain)) {
                $bp->signup->errors['signup_email'] = __(
                    'Too many registration attempts from this domain. Please try again later.',
                    'email-domain-restriction'
                );
                return;
            }

            if ($rate_limiter->is_ip_rate_limited($this->get_client_ip())) {
                $bp->signup->errors['signup_email'] = __(
                    'Too many registration attempts. Please try again later.',
                    'email-domain-restriction'
                );
                return;
            }
        }

        // Validate email domain
        $validation = EDR_Domain_Validator::validate_email($email);

        if (is_wp_error($validation)) {
            // Log blocked attempt
            $this->log_blocked_attempt($email, 'buddypress');

            // Add error with custom message if available
            $error_message = get_option('edr_buddypress_error_message');
            if (empty($error_message)) {
                $error_message = $validation->get_error_message();
            }

            $bp->signup->errors['signup_email'] = $error_message;
        }
    }

    /**
     * Log BuddyPress registration attempt
     *
     * @param int|bool $user User ID or false
     * @param string $user_login Username
     * @param string $user_password Password
     * @param string $user_email Email address
     * @param array $usermeta User metadata
     */
    public function log_buddypress_registration($user, $user_login, $user_password, $user_email, $usermeta)
    {
        $domain = substr(strrchr($user_email, '@'), 1);

        // Determine registration source
        $source = 'buddypress';
        if (!empty($usermeta['field_1'])) { // Example: check if invited
            $source = 'buddypress-invitation';
        }

        // Log the registration
        if (class_exists('EDR_Attempt_Logger')) {
            $log_data = [
                'email'     => $user_email,
                'domain'    => $domain,
                'status'    => 'allowed',
                'source'    => $source,
                'user_id'   => is_int($user) ? $user : null,
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
     * Add BuddyPress-specific member meta
     *
     * @param int $user_id User ID
     * @param string $key Activation key
     * @param array $user User data
     */
    public function add_member_meta($user_id, $key, $user)
    {
        // Mark as BuddyPress registration
        update_user_meta($user_id, '_edr_registered_via_buddypress', 'yes');

        // Store BuddyPress activation key
        update_user_meta($user_id, '_edr_bp_activation_key', $key);

        // Store member type if set
        $member_type = bp_get_member_type($user_id);
        if ($member_type) {
            update_user_meta($user_id, '_edr_bp_member_type', $member_type);
        }

        // Store verification date
        update_user_meta($user_id, '_edr_domain_verified_date', current_time('mysql'));

        // Store email domain
        $user_data = get_userdata($user_id);
        if ($user_data) {
            $domain = substr(strrchr($user_data->user_email, '@'), 1);
            update_user_meta($user_id, '_edr_email_domain', $domain);
        }

        // Check if invited by another user
        $invited_by = get_user_meta($user_id, 'invited_by', true);
        if ($invited_by) {
            update_user_meta($user_id, '_edr_bp_invited_by', $invited_by);
        }
    }

    /**
     * Assign member type based on domain (PRO)
     *
     * @param int $user_id User ID
     * @param string $key Activation key
     * @param array $user User data
     */
    public function assign_member_type($user_id, $key, $user)
    {
        if (!edr_is_pro_active()) {
            return;
        }

        // Get member type mapping for user's domain
        $user_data = get_userdata($user_id);
        if (!$user_data) {
            return;
        }

        $domain = substr(strrchr($user_data->user_email, '@'), 1);
        $member_type = $this->get_member_type_for_domain($domain);

        if ($member_type && function_exists('bp_set_member_type')) {
            bp_set_member_type($user_id, $member_type);
        }

        // Also assign role if configured
        if (class_exists('EDR_Role_Manager')) {
            $role_manager = new EDR_Role_Manager();
            $role_manager->assign_role_by_domain($user_id);
        }
    }

    /**
     * Get member type for domain (PRO)
     *
     * @param string $domain Domain name
     * @return string|null
     */
    private function get_member_type_for_domain($domain)
    {
        if (!edr_is_pro_active()) {
            return null;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'edr_bp_member_type_mappings';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return null;
        }

        $member_type = $wpdb->get_var($wpdb->prepare(
            "SELECT member_type FROM $table WHERE domain = %s ORDER BY priority DESC LIMIT 1",
            $domain
        ));

        return $member_type;
    }

    /**
     * Validate group join by domain (PRO)
     *
     * @param int $group_id Group ID
     * @param int $user_id User ID
     * @return bool
     */
    public function validate_group_join($group_id, $user_id)
    {
        if (!edr_is_pro_active()) {
            return true;
        }

        // Get group domain restrictions
        $allowed_domains = groups_get_groupmeta($group_id, 'edr_allowed_domains', true);

        if (empty($allowed_domains)) {
            return true; // No restrictions on this group
        }

        // Get user's email domain
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $user_domain = substr(strrchr($user->user_email, '@'), 1);

        // Check if user's domain is in allowed list
        $domains = array_map('trim', explode("\n", $allowed_domains));

        foreach ($domains as $allowed_domain) {
            if (EDR_Domain_Validator::matches_domain($user_domain, $allowed_domain)) {
                return true;
            }
        }

        // Domain not allowed, prevent group join
        bp_core_add_message(
            __('You cannot join this group. Your email domain is not authorized.', 'email-domain-restriction'),
            'error'
        );

        return false;
    }

    /**
     * Record rate limit attempt (PRO)
     *
     * @param int|bool $user User ID or false
     * @param string $user_login Username
     * @param string $user_password Password
     * @param string $user_email Email address
     * @param array $usermeta User metadata
     */
    public function record_rate_limit($user, $user_login, $user_password, $user_email, $usermeta)
    {
        if (!edr_is_pro_active() || !class_exists('EDR_Rate_Limiter')) {
            return;
        }

        $domain = substr(strrchr($user_email, '@'), 1);
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
     * Show BuddyPress integration notice
     */
    public function show_buddypress_integration_notice()
    {
        // Only show on plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'email-domain-restriction') === false) {
            return;
        }

        // Check if user dismissed notice
        $dismissed = get_user_meta(get_current_user_id(), 'edr_buddypress_notice_dismissed', true);
        if ($dismissed) {
            return;
        }

        ?>
        <div class="notice notice-success is-dismissible edr-buddypress-notice" data-notice="buddypress">
            <p>
                <strong><?php esc_html_e('BuddyPress Integration Active', 'email-domain-restriction'); ?></strong><br>
                <?php esc_html_e('Email Domain Restriction is now validating BuddyPress registrations.', 'email-domain-restriction'); ?>
            </p>
        </div>
        <script>
        jQuery(document).on('click', '.edr-buddypress-notice .notice-dismiss', function() {
            jQuery.post(ajaxurl, {
                action: 'edr_dismiss_integration_notice',
                notice: 'buddypress',
                nonce: '<?php echo wp_create_nonce('edr_dismiss_notice'); ?>'
            });
        });
        </script>
        <?php
    }

    /**
     * Handle dismissal of BuddyPress notice
     */
    public function dismiss_buddypress_notice()
    {
        if (!isset($_POST['action']) || $_POST['action'] !== 'edr_dismiss_integration_notice') {
            return;
        }

        if (!isset($_POST['notice']) || $_POST['notice'] !== 'buddypress') {
            return;
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'edr_dismiss_notice')) {
            return;
        }

        update_user_meta(get_current_user_id(), 'edr_buddypress_notice_dismissed', true);
    }

    /**
     * Check if BuddyPress is active
     *
     * @return bool
     */
    public function is_buddypress_active()
    {
        return $this->buddypress_active;
    }

    /**
     * Get BuddyPress registration statistics (PRO)
     *
     * @param int $days Number of days
     * @return array
     */
    public function get_buddypress_stats($days = 30)
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
                SUM(CASE WHEN source = 'buddypress' THEN 1 ELSE 0 END) as standard_registrations,
                SUM(CASE WHEN source = 'buddypress-invitation' THEN 1 ELSE 0 END) as invitation_registrations,
                SUM(CASE WHEN source LIKE 'buddypress%%' AND status = 'allowed' THEN 1 ELSE 0 END) as total_allowed,
                SUM(CASE WHEN source LIKE 'buddypress%%' AND status = 'blocked' THEN 1 ELSE 0 END) as total_blocked
            FROM $table
            WHERE created_at >= %s
            AND source LIKE 'buddypress%%'",
            $cutoff
        ), ARRAY_A);

        return $stats[0] ?? [];
    }

    /**
     * Get group domain restrictions (PRO)
     *
     * @param int $group_id Group ID
     * @return array
     */
    public function get_group_domain_restrictions($group_id)
    {
        if (!edr_is_pro_active() || !function_exists('groups_get_groupmeta')) {
            return [];
        }

        $allowed_domains = groups_get_groupmeta($group_id, 'edr_allowed_domains', true);

        if (empty($allowed_domains)) {
            return [];
        }

        return array_map('trim', explode("\n", $allowed_domains));
    }

    /**
     * Set group domain restrictions (PRO)
     *
     * @param int $group_id Group ID
     * @param array $domains Array of allowed domains
     * @return bool
     */
    public function set_group_domain_restrictions($group_id, $domains)
    {
        if (!edr_is_pro_active() || !function_exists('groups_update_groupmeta')) {
            return false;
        }

        $domains_string = implode("\n", array_map('trim', $domains));
        return groups_update_groupmeta($group_id, 'edr_allowed_domains', $domains_string);
    }

    /**
     * Get all member types mapped to domains (PRO)
     *
     * @return array
     */
    public function get_member_type_mappings()
    {
        if (!edr_is_pro_active()) {
            return [];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'edr_bp_member_type_mappings';

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
     * Add member type mapping (PRO)
     *
     * @param string $domain Domain pattern
     * @param string $member_type Member type slug
     * @param int $priority Priority
     * @return bool
     */
    public function add_member_type_mapping($domain, $member_type, $priority = 10)
    {
        if (!edr_is_pro_active()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'edr_bp_member_type_mappings';

        $result = $wpdb->insert(
            $table,
            [
                'domain'      => sanitize_text_field($domain),
                'member_type' => sanitize_text_field($member_type),
                'priority'    => absint($priority),
                'created_at'  => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%s']
        );

        return $result !== false;
    }

    /**
     * Remove member type mapping (PRO)
     *
     * @param int $mapping_id Mapping ID
     * @return bool
     */
    public function remove_member_type_mapping($mapping_id)
    {
        if (!edr_is_pro_active()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'edr_bp_member_type_mappings';

        $result = $wpdb->delete(
            $table,
            ['id' => absint($mapping_id)],
            ['%d']
        );

        return $result !== false;
    }
}

