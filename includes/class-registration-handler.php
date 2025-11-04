<?php
/**
 * Registration validation handler.
 *
 * @package Email_Domain_Restriction
 */

/**
 * Registration handler class.
 */
class EDR_Registration_Handler
{
    /**
     * Initialize registration hooks.
     */
    public function init()
    {
        // Hook into registration validation
        add_filter('registration_errors', [$this, 'validate_registration'], 10, 3);

        // Double-check on user registration (backup validation)
        add_action('user_register', [$this, 'log_successful_registration'], 10, 1);
    }

    /**
     * Validate email domain during registration.
     *
     * @param WP_Error $errors Registration errors.
     * @param string   $sanitized_user_login Sanitized username.
     * @param string   $user_email User email.
     * @return WP_Error Modified errors object.
     */
    public function validate_registration($errors, $sanitized_user_login, $user_email)
    {
        // Skip if email is empty (let WordPress handle that)
        if (empty($user_email)) {
            return $errors;
        }

        // Extract domain
        $domain = EDR_Domain_Validator::extract_domain($user_email);

        if ($domain === false) {
            $errors->add(
                'invalid_email_domain',
                __('Invalid email address.', 'email-domain-restriction')
            );
            $this->log_attempt($user_email, '', 'blocked');
            return $errors;
        }

        // Validate domain
        $validation = EDR_Domain_Validator::validate_email($user_email);

        if (is_wp_error($validation)) {
            // Domain not whitelisted - block registration
            $errors->add(
                'domain_not_whitelisted',
                $validation->get_error_message()
            );

            // Log the blocked attempt
            $this->log_attempt($user_email, $domain, 'blocked');
        } else {
            // Domain is whitelisted - log successful attempt
            // Note: Full logging happens in log_successful_registration()
            // This is just for tracking in registration_errors filter
        }

        return $errors;
    }

    /**
     * Log successful registration after user is created.
     *
     * @param int $user_id User ID.
     */
    public function log_successful_registration($user_id)
    {
        $user = get_userdata($user_id);

        if (!$user || empty($user->user_email)) {
            return;
        }

        $domain = EDR_Domain_Validator::extract_domain($user->user_email);

        if ($domain !== false) {
            $this->log_attempt($user->user_email, $domain, 'allowed');
        }
    }

    /**
     * Log registration attempt.
     *
     * @param string $email Email address.
     * @param string $domain Domain.
     * @param string $status Status (allowed/blocked).
     */
    private function log_attempt($email, $domain, $status)
    {
        EDR_Attempt_Logger::log_attempt($email, $domain, $status);
    }
}
