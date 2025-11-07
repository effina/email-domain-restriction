<?php
/**
 * Rate Limiter
 *
 * Handles rate limiting and anti-abuse protection.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Rate_Limiter
 *
 * Manages rate limiting for registration attempts.
 */
class EDR_Rate_Limiter
{
    /**
     * Initialize rate limiter
     */
    public function init()
    {
        // Hook into registration validation
        add_filter('registration_errors', [$this, 'check_rate_limit'], 5, 3);
    }

    /**
     * Check if rate limit is exceeded
     *
     * @param WP_Error $errors Errors object
     * @param string $sanitized_user_login Sanitized username
     * @param string $user_email User email
     * @return WP_Error
     */
    public function check_rate_limit($errors, $sanitized_user_login, $user_email)
    {
        $domain = substr(strrchr($user_email, '@'), 1);
        $ip_address = $this->get_client_ip();

        // Check domain rate limit
        if ($this->is_domain_rate_limited($domain)) {
            $errors->add(
                'rate_limit_exceeded',
                __('Too many registration attempts from this domain. Please try again later.', 'email-domain-restriction')
            );
        }

        // Check IP rate limit
        if ($this->is_ip_rate_limited($ip_address)) {
            $errors->add(
                'rate_limit_exceeded',
                __('Too many registration attempts from your IP address. Please try again later.', 'email-domain-restriction')
            );
        }

        return $errors;
    }

    /**
     * Check if domain is rate limited
     *
     * @param string $domain Domain name
     * @return bool
     */
    public function is_domain_rate_limited($domain)
    {
        // Get rate limit settings
        $limit = get_option('edr_domain_rate_limit', 10); // Default: 10 attempts per hour
        $window = get_option('edr_rate_limit_window', 3600); // Default: 1 hour

        $count = $this->get_attempt_count('domain', $domain, $window);

        return $count >= $limit;
    }

    /**
     * Check if IP is rate limited
     *
     * @param string $ip_address IP address
     * @return bool
     */
    public function is_ip_rate_limited($ip_address)
    {
        // Get rate limit settings
        $limit = get_option('edr_ip_rate_limit', 5); // Default: 5 attempts per hour
        $window = get_option('edr_rate_limit_window', 3600); // Default: 1 hour

        $count = $this->get_attempt_count('ip', $ip_address, $window);

        return $count >= $limit;
    }

    /**
     * Get attempt count for identifier
     *
     * @param string $type Type (domain or ip)
     * @param string $identifier Identifier value
     * @param int $window Time window in seconds
     * @return int
     */
    private function get_attempt_count($type, $identifier, $window)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_rate_limits';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return 0;
        }

        $window_start = date('Y-m-d H:i:s', time() - $window);

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT attempt_count FROM $table
            WHERE identifier_type = %s
            AND identifier = %s
            AND window_start >= %s
            LIMIT 1",
            $type,
            $identifier,
            $window_start
        ));

        return $count ? absint($count) : 0;
    }

    /**
     * Record registration attempt
     *
     * @param string $type Type (domain or ip)
     * @param string $identifier Identifier value
     */
    public function record_attempt($type, $identifier)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_rate_limits';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return;
        }

        $window = get_option('edr_rate_limit_window', 3600);
        $window_start = date('Y-m-d H:i:s', time() - $window);

        // Check if record exists for this window
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table
            WHERE identifier_type = %s
            AND identifier = %s
            AND window_start >= %s
            LIMIT 1",
            $type,
            $identifier,
            $window_start
        ));

        if ($existing) {
            // Increment count
            $wpdb->update(
                $table,
                ['attempt_count' => $existing->attempt_count + 1],
                ['id' => $existing->id],
                ['%d'],
                ['%d']
            );
        } else {
            // Create new record
            $wpdb->insert(
                $table,
                [
                    'identifier'      => $identifier,
                    'identifier_type' => $type,
                    'attempt_count'   => 1,
                    'window_start'    => current_time('mysql'),
                ],
                ['%s', '%s', '%d', '%s']
            );
        }
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
     * Clean up old rate limit records
     */
    public function cleanup_old_records()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_rate_limits';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return;
        }

        $window = get_option('edr_rate_limit_window', 3600);
        $cutoff = date('Y-m-d H:i:s', time() - ($window * 2));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE window_start < %s",
            $cutoff
        ));
    }
}
