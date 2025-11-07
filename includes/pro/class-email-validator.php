<?php
/**
 * Email Validator
 *
 * Integrates with email validation services (ZeroBounce, Kickbox, etc).
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Email_Validator
 *
 * Validates emails using external API services.
 */
class EDR_Email_Validator
{
    /**
     * Supported validation services
     */
    const SERVICES = [
        'zerobounce' => 'ZeroBounce',
        'kickbox'    => 'Kickbox',
        'hunter'     => 'Hunter.io',
        'neverbounce' => 'NeverBounce',
    ];

    /**
     * Cache group
     */
    const CACHE_GROUP = 'edr_email_validation';

    /**
     * Initialize email validator
     */
    public function init()
    {
        // Add validation to registration process
        add_filter('registration_errors', [$this, 'validate_email_on_registration'], 15, 3);
    }

    /**
     * Validate email on registration
     *
     * @param WP_Error $errors Errors object
     * @param string $sanitized_user_login Username
     * @param string $user_email Email
     * @return WP_Error
     */
    public function validate_email_on_registration($errors, $sanitized_user_login, $user_email)
    {
        if (!edr_is_pro_active()) {
            return $errors;
        }

        // Check if validation is enabled
        if (!get_option('edr_enable_email_validation', false)) {
            return $errors;
        }

        // Skip if email already has errors
        if ($errors->get_error_message('email')) {
            return $errors;
        }

        // Validate email
        $result = $this->validate_email($user_email);

        if (is_wp_error($result)) {
            $errors->add('email', $result->get_error_message());
        } elseif (!$result['valid']) {
            $message = get_option('edr_invalid_email_message', '');
            if (empty($message)) {
                $message = sprintf(
                    __('The email address %s appears to be invalid or risky. Reason: %s', 'email-domain-restriction'),
                    $user_email,
                    $result['reason']
                );
            }
            $errors->add('email', $message);
        }

        return $errors;
    }

    /**
     * Validate email address
     *
     * @param string $email Email address
     * @return array|WP_Error Validation result or error
     */
    public function validate_email($email)
    {
        // Check cache first
        $cache_key = md5($email);
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);

        if ($cached !== false) {
            return $cached;
        }

        // Get service configuration
        $service = get_option('edr_validation_service', '');

        if (empty($service)) {
            return new WP_Error('no_service', __('No validation service configured.', 'email-domain-restriction'));
        }

        // Validate using selected service
        $result = match ($service) {
            'zerobounce'  => $this->validate_zerobounce($email),
            'kickbox'     => $this->validate_kickbox($email),
            'hunter'      => $this->validate_hunter($email),
            'neverbounce' => $this->validate_neverbounce($email),
            default       => new WP_Error('invalid_service', __('Invalid validation service.', 'email-domain-restriction')),
        };

        // Cache result for 24 hours
        if (!is_wp_error($result)) {
            wp_cache_set($cache_key, $result, self::CACHE_GROUP, DAY_IN_SECONDS);
        }

        return $result;
    }

    /**
     * Validate email with ZeroBounce
     *
     * @param string $email Email address
     * @return array|WP_Error
     */
    private function validate_zerobounce($email)
    {
        $api_key = get_option('edr_zerobounce_api_key', '');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('ZeroBounce API key not configured.', 'email-domain-restriction'));
        }

        $url = add_query_arg([
            'api_key' => $api_key,
            'email'   => urlencode($email),
        ], 'https://api.zerobounce.net/v2/validate');

        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['status'])) {
            return new WP_Error('invalid_response', __('Invalid API response.', 'email-domain-restriction'));
        }

        // ZeroBounce statuses: valid, invalid, catch-all, unknown, spamtrap, abuse, do_not_mail
        $valid = in_array($body['status'], ['valid', 'catch-all']);

        return [
            'valid'      => $valid,
            'status'     => $body['status'],
            'sub_status' => $body['sub_status'] ?? '',
            'reason'     => $this->format_zerobounce_reason($body),
            'score'      => $body['score'] ?? 0,
            'free'       => $body['free_email'] ?? false,
            'disposable' => in_array($body['status'], ['disposable', 'do_not_mail']),
            'service'    => 'zerobounce',
        ];
    }

    /**
     * Validate email with Kickbox
     *
     * @param string $email Email address
     * @return array|WP_Error
     */
    private function validate_kickbox($email)
    {
        $api_key = get_option('edr_kickbox_api_key', '');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('Kickbox API key not configured.', 'email-domain-restriction'));
        }

        $url = add_query_arg([
            'email' => urlencode($email),
        ], 'https://api.kickbox.com/v2/verify');

        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['result'])) {
            return new WP_Error('invalid_response', __('Invalid API response.', 'email-domain-restriction'));
        }

        // Kickbox results: deliverable, undeliverable, risky, unknown
        $valid = $body['result'] === 'deliverable';

        return [
            'valid'      => $valid,
            'status'     => $body['result'],
            'sub_status' => $body['reason'] ?? '',
            'reason'     => $this->format_kickbox_reason($body),
            'score'      => $body['sendex'] ?? 0,
            'free'       => $body['free'] ?? false,
            'disposable' => $body['disposable'] ?? false,
            'service'    => 'kickbox',
        ];
    }

    /**
     * Validate email with Hunter.io
     *
     * @param string $email Email address
     * @return array|WP_Error
     */
    private function validate_hunter($email)
    {
        $api_key = get_option('edr_hunter_api_key', '');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('Hunter.io API key not configured.', 'email-domain-restriction'));
        }

        $url = add_query_arg([
            'email'   => urlencode($email),
            'api_key' => $api_key,
        ], 'https://api.hunter.io/v2/email-verifier');

        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['data']['status'])) {
            return new WP_Error('invalid_response', __('Invalid API response.', 'email-domain-restriction'));
        }

        $data = $body['data'];

        // Hunter statuses: valid, invalid, accept_all, webmail, disposable, unknown
        $valid = in_array($data['status'], ['valid', 'accept_all', 'webmail']);

        return [
            'valid'      => $valid,
            'status'     => $data['status'],
            'sub_status' => '',
            'reason'     => $this->format_hunter_reason($data),
            'score'      => $data['score'] ?? 0,
            'free'       => $data['webmail'] ?? false,
            'disposable' => $data['disposable'] ?? false,
            'service'    => 'hunter',
        ];
    }

    /**
     * Validate email with NeverBounce
     *
     * @param string $email Email address
     * @return array|WP_Error
     */
    private function validate_neverbounce($email)
    {
        $api_key = get_option('edr_neverbounce_api_key', '');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('NeverBounce API key not configured.', 'email-domain-restriction'));
        }

        $response = wp_remote_get('https://api.neverbounce.com/v4/single/check', [
            'timeout' => 10,
            'body'    => [
                'key'   => $api_key,
                'email' => $email,
            ],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['result'])) {
            return new WP_Error('invalid_response', __('Invalid API response.', 'email-domain-restriction'));
        }

        // NeverBounce results: valid, invalid, disposable, catchall, unknown
        $valid = in_array($body['result'], ['valid', 'catchall']);

        return [
            'valid'      => $valid,
            'status'     => $body['result'],
            'sub_status' => '',
            'reason'     => $this->format_neverbounce_reason($body),
            'score'      => 0,
            'free'       => false,
            'disposable' => $body['result'] === 'disposable',
            'service'    => 'neverbounce',
        ];
    }

    /**
     * Format ZeroBounce reason
     *
     * @param array $data API response data
     * @return string
     */
    private function format_zerobounce_reason($data)
    {
        $reasons = [
            'valid'       => __('Valid email address', 'email-domain-restriction'),
            'invalid'     => __('Invalid email address', 'email-domain-restriction'),
            'catch-all'   => __('Domain accepts all emails', 'email-domain-restriction'),
            'unknown'     => __('Unable to verify', 'email-domain-restriction'),
            'spamtrap'    => __('Known spam trap', 'email-domain-restriction'),
            'abuse'       => __('Known abusive email', 'email-domain-restriction'),
            'do_not_mail' => __('Do not mail address', 'email-domain-restriction'),
        ];

        return $reasons[$data['status']] ?? $data['status'];
    }

    /**
     * Format Kickbox reason
     *
     * @param array $data API response data
     * @return string
     */
    private function format_kickbox_reason($data)
    {
        $reasons = [
            'deliverable'   => __('Deliverable email', 'email-domain-restriction'),
            'undeliverable' => __('Undeliverable email', 'email-domain-restriction'),
            'risky'         => __('Risky email address', 'email-domain-restriction'),
            'unknown'       => __('Unable to verify', 'email-domain-restriction'),
        ];

        $reason = $reasons[$data['result']] ?? $data['result'];

        if (!empty($data['reason'])) {
            $reason .= ' (' . $data['reason'] . ')';
        }

        return $reason;
    }

    /**
     * Format Hunter reason
     *
     * @param array $data API response data
     * @return string
     */
    private function format_hunter_reason($data)
    {
        $reasons = [
            'valid'      => __('Valid email address', 'email-domain-restriction'),
            'invalid'    => __('Invalid email address', 'email-domain-restriction'),
            'accept_all' => __('Domain accepts all emails', 'email-domain-restriction'),
            'webmail'    => __('Webmail address', 'email-domain-restriction'),
            'disposable' => __('Disposable email address', 'email-domain-restriction'),
            'unknown'    => __('Unable to verify', 'email-domain-restriction'),
        ];

        return $reasons[$data['status']] ?? $data['status'];
    }

    /**
     * Format NeverBounce reason
     *
     * @param array $data API response data
     * @return string
     */
    private function format_neverbounce_reason($data)
    {
        $reasons = [
            'valid'      => __('Valid email address', 'email-domain-restriction'),
            'invalid'    => __('Invalid email address', 'email-domain-restriction'),
            'disposable' => __('Disposable email address', 'email-domain-restriction'),
            'catchall'   => __('Domain accepts all emails', 'email-domain-restriction'),
            'unknown'    => __('Unable to verify', 'email-domain-restriction'),
        ];

        return $reasons[$data['result']] ?? $data['result'];
    }

    /**
     * Test API connection
     *
     * @param string $service Service name
     * @param string $api_key API key
     * @return array|WP_Error
     */
    public function test_api_connection($service, $api_key)
    {
        $test_email = 'test@example.com';

        // Temporarily set API key
        $old_service = get_option('edr_validation_service');
        $old_key = get_option("edr_{$service}_api_key");

        update_option('edr_validation_service', $service);
        update_option("edr_{$service}_api_key", $api_key);

        // Test validation
        $result = $this->validate_email($test_email);

        // Restore old settings
        if ($old_service) {
            update_option('edr_validation_service', $old_service);
        }
        if ($old_key) {
            update_option("edr_{$service}_api_key", $old_key);
        }

        if (is_wp_error($result)) {
            return $result;
        }

        return [
            'success' => true,
            'message' => __('API connection successful!', 'email-domain-restriction'),
            'result'  => $result,
        ];
    }

    /**
     * Get API usage statistics
     *
     * @param string $service Service name
     * @return array|WP_Error
     */
    public function get_api_usage($service)
    {
        // This would vary by service - placeholder implementation
        $api_key = get_option("edr_{$service}_api_key", '');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('API key not configured.', 'email-domain-restriction'));
        }

        // Each service has different endpoints for usage stats
        // This is a simplified version
        return [
            'service'   => $service,
            'remaining' => 'N/A',
            'used'      => 'N/A',
            'limit'     => 'N/A',
        ];
    }

    /**
     * Clear validation cache
     *
     * @param string $email Optional specific email to clear
     */
    public function clear_cache($email = null)
    {
        if ($email) {
            $cache_key = md5($email);
            wp_cache_delete($cache_key, self::CACHE_GROUP);
        } else {
            wp_cache_flush();
        }
    }
}
