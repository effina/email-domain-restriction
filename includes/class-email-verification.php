<?php
/**
 * Email verification handler.
 *
 * @package Email_Domain_Restriction
 */

/**
 * Email verification class.
 */
class EDR_Email_Verification
{
    /**
     * Initialize email verification hooks.
     */
    public function init()
    {
        $settings = get_option('edr_settings', []);
        $verification_enabled = isset($settings['email_verification_enabled'])
            ? $settings['email_verification_enabled']
            : true;

        // Only enable for single-site WordPress (multisite has native verification)
        if (!is_multisite() && $verification_enabled) {
            add_action('user_register', [$this, 'send_verification_email'], 20, 1);
            add_filter('wp_authenticate_user', [$this, 'block_unverified_login'], 10, 2);
            add_action('wp_ajax_nopriv_edr_verify_email', [$this, 'handle_verification']);
            add_action('wp_ajax_edr_verify_email', [$this, 'handle_verification']);
        }
    }

    /**
     * Send verification email to new user.
     *
     * @param int $user_id User ID.
     */
    public function send_verification_email($user_id)
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return;
        }

        // Allow integrations to skip verification for specific users
        $skip_verification = apply_filters('edr_skip_email_verification', false, $user_id);
        if ($skip_verification) {
            // Mark as verified if skipping
            update_user_meta($user_id, 'edr_email_verified', 'yes');
            return;
        }

        // Generate verification token
        $token = $this->generate_token($user_id);

        // Store token and expiry in user meta
        $settings = get_option('edr_settings', []);
        $expiry_hours = isset($settings['verification_token_expiry_hours'])
            ? (int) $settings['verification_token_expiry_hours']
            : 48;

        update_user_meta($user_id, 'edr_verification_token', $token);
        update_user_meta($user_id, 'edr_verification_expiry', time() + ($expiry_hours * HOUR_IN_SECONDS));
        update_user_meta($user_id, 'edr_email_verified', 'no');

        // Set user role to pending
        $user->set_role('subscriber'); // Default role but not verified yet

        // Build verification URL
        $verification_url = add_query_arg(
            [
                'action' => 'edr_verify_email',
                'token' => $token,
                'user_id' => $user_id,
            ],
            admin_url('admin-ajax.php')
        );

        // Email subject and message
        $site_name = get_bloginfo('name');
        $subject = sprintf(
            __('[%s] Verify Your Email Address', 'email-domain-restriction'),
            $site_name
        );

        $message = sprintf(
            __('Welcome to %1$s!

Please verify your email address by clicking the link below:

%2$s

This link will expire in %3$d hours.

If you did not create an account, please ignore this email.

Thank you!', 'email-domain-restriction'),
            $site_name,
            $verification_url,
            $expiry_hours
        );

        // Send email
        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Block login for unverified users.
     *
     * @param WP_User|WP_Error $user User object or error.
     * @param string           $password Password.
     * @return WP_User|WP_Error Modified user or error.
     */
    public function block_unverified_login($user, $password)
    {
        if (is_wp_error($user)) {
            return $user;
        }

        $verified = get_user_meta($user->ID, 'edr_email_verified', true);

        if ($verified === 'no') {
            return new WP_Error(
                'email_not_verified',
                __(
                    '<strong>Error:</strong> Please verify your email address before logging in. Check your inbox for the verification link.',
                    'email-domain-restriction'
                )
            );
        }

        return $user;
    }

    /**
     * Handle email verification request.
     */
    public function handle_verification()
    {
        // Get parameters
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

        if (empty($token) || empty($user_id)) {
            wp_die(
                __('Invalid verification link.', 'email-domain-restriction'),
                __('Verification Error', 'email-domain-restriction'),
                ['response' => 400]
            );
        }

        // Verify token
        $result = $this->verify_token($user_id, $token);

        if (is_wp_error($result)) {
            wp_die(
                $result->get_error_message(),
                __('Verification Error', 'email-domain-restriction'),
                ['response' => 400]
            );
        }

        // Success - redirect to login
        $login_url = wp_login_url();
        $redirect_url = add_query_arg('verified', '1', $login_url);

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Verify token and activate user.
     *
     * @param int    $user_id User ID.
     * @param string $token Token to verify.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    private function verify_token($user_id, $token)
    {
        $stored_token = get_user_meta($user_id, 'edr_verification_token', true);
        $expiry = get_user_meta($user_id, 'edr_verification_expiry', true);

        // Check token match
        if (empty($stored_token) || !hash_equals($stored_token, $token)) {
            return new WP_Error(
                'invalid_token',
                __('Invalid or expired verification link.', 'email-domain-restriction')
            );
        }

        // Check expiry
        if (empty($expiry) || time() > (int) $expiry) {
            return new WP_Error(
                'expired_token',
                __('This verification link has expired. Please contact support for assistance.', 'email-domain-restriction')
            );
        }

        // Mark as verified
        update_user_meta($user_id, 'edr_email_verified', 'yes');

        // Clean up verification data
        delete_user_meta($user_id, 'edr_verification_token');
        delete_user_meta($user_id, 'edr_verification_expiry');

        return true;
    }

    /**
     * Generate verification token.
     *
     * @param int $user_id User ID.
     * @return string Token.
     */
    private function generate_token($user_id)
    {
        $user = get_userdata($user_id);
        $data = $user_id . $user->user_email . time() . wp_generate_password(20, false);

        return hash('sha256', $data);
    }

    /**
     * Check if user email is verified.
     *
     * @param int $user_id User ID.
     * @return bool True if verified, false otherwise.
     */
    public static function is_email_verified($user_id)
    {
        // Multisite always considered verified (uses native system)
        if (is_multisite()) {
            return true;
        }

        $verified = get_user_meta($user_id, 'edr_email_verified', true);

        // If meta doesn't exist, consider verified (for existing users)
        if (empty($verified)) {
            return true;
        }

        return $verified === 'yes';
    }
}
