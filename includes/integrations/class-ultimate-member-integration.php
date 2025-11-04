<?php
/**
 * Ultimate Member Integration
 *
 * Provides compatibility with Ultimate Member plugin for domain validation
 * and registration logging.
 *
 * @package Email_Domain_Restriction
 */

/**
 * Ultimate Member integration class.
 */
class EDR_Ultimate_Member_Integration
{
    /**
     * Check if Ultimate Member is active.
     *
     * @return bool True if UM is active, false otherwise.
     */
    public static function is_active()
    {
        return class_exists('UM');
    }

    /**
     * Initialize integration.
     */
    public function init()
    {
        // Only initialize if UM is active
        if (!self::is_active()) {
            return;
        }

        // Email domain validation during UM registration
        add_action('um_submit_form_errors_hook__registration', [$this, 'validate_email_domain'], 10, 1);

        // Log successful registrations
        add_action('um_registration_complete', [$this, 'log_registration'], 10, 3);

        // Handle email verification conflicts
        add_filter('edr_skip_email_verification', [$this, 'skip_verification_for_um'], 10, 2);

        // Add admin notice if both email verification systems are active
        add_action('admin_notices', [$this, 'email_verification_notice']);
    }

    /**
     * Validate email domain during UM registration.
     *
     * @param array $args Form arguments containing user data.
     */
    public function validate_email_domain($args)
    {
        // Get email from form submission
        $email = isset($args['user_email']) ? sanitize_email($args['user_email']) : '';

        if (empty($email)) {
            return;
        }

        // Use existing validation logic
        $validation = EDR_Domain_Validator::validate_email($email);

        if (is_wp_error($validation)) {
            // Add error to UM form
            if (function_exists('UM') && UM()->form()) {
                UM()->form()->add_error('user_email', $validation->get_error_message());
            }

            // Log the blocked attempt
            $domain = EDR_Domain_Validator::extract_domain($email);
            if ($domain !== false) {
                EDR_Attempt_Logger::log_attempt($email, $domain, 'blocked');
            }
        }
    }

    /**
     * Log successful UM registration.
     *
     * @param int   $user_id User ID.
     * @param array $submitted_data Submitted form data.
     * @param array $form_data Form configuration data.
     */
    public function log_registration($user_id, $submitted_data, $form_data)
    {
        // Get email from submitted data
        $email = isset($submitted_data['user_email']) ? sanitize_email($submitted_data['user_email']) : '';

        if (empty($email)) {
            // Try to get from user object
            $user = get_userdata($user_id);
            if ($user && !empty($user->user_email)) {
                $email = $user->user_email;
            }
        }

        if (empty($email)) {
            return;
        }

        // Log successful registration
        $domain = EDR_Domain_Validator::extract_domain($email);
        if ($domain !== false) {
            EDR_Attempt_Logger::log_attempt($email, $domain, 'allowed');
        }

        // Store metadata to identify UM registrations
        update_user_meta($user_id, '_edr_registered_via_um', true);
        update_user_meta($user_id, '_edr_registration_form_id', isset($form_data['ID']) ? $form_data['ID'] : 0);
    }

    /**
     * Skip our email verification for UM registrations if UM handles it.
     *
     * @param bool $skip Whether to skip verification.
     * @param int  $user_id User ID.
     * @return bool Modified skip value.
     */
    public function skip_verification_for_um($skip, $user_id)
    {
        // Check if user was registered via UM
        $registered_via_um = get_user_meta($user_id, '_edr_registered_via_um', true);

        if ($registered_via_um) {
            // Check if UM has email activation enabled
            if ($this->um_has_email_activation()) {
                return true; // Skip our verification, let UM handle it
            }
        }

        return $skip;
    }

    /**
     * Check if UM has email activation enabled.
     *
     * @return bool True if email activation is enabled.
     */
    private function um_has_email_activation()
    {
        if (!function_exists('UM')) {
            return false;
        }

        // Check global email activation setting
        $email_activate = UM()->options()->get('register_email_activate');

        return !empty($email_activate);
    }

    /**
     * Display admin notice if both email verification systems are active.
     */
    public function email_verification_notice()
    {
        // Only show on our plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'email-domain-restriction') === false) {
            return;
        }

        // Check if both systems are active
        $settings = get_option('edr_settings', []);
        $edr_verification = isset($settings['email_verification_enabled']) ? $settings['email_verification_enabled'] : true;

        if ($edr_verification && $this->um_has_email_activation()) {
            ?>
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Email Domain Restriction:', 'email-domain-restriction'); ?></strong>
                    <?php _e('Ultimate Member email activation is enabled. Users registering through UM forms will use UM\'s email verification system instead of this plugin\'s verification.', 'email-domain-restriction'); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Get integration status for admin display.
     *
     * @return array Status information.
     */
    public static function get_status()
    {
        if (!self::is_active()) {
            return [
                'active' => false,
                'name' => 'Ultimate Member',
                'status' => __('Not Detected', 'email-domain-restriction'),
            ];
        }

        $um_has_email = false;
        if (function_exists('UM')) {
            $um_has_email = !empty(UM()->options()->get('register_email_activate'));
        }

        return [
            'active' => true,
            'name' => 'Ultimate Member',
            'status' => __('Active', 'email-domain-restriction'),
            'email_verification' => $um_has_email ? __('Enabled', 'email-domain-restriction') : __('Disabled', 'email-domain-restriction'),
        ];
    }
}
