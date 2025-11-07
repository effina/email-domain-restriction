<?php
/**
 * Geolocation Service
 *
 * Handles IP geolocation and country-based restrictions.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Geolocation
 *
 * Provides geolocation services.
 */
class EDR_Geolocation
{
    /**
     * Cache group
     */
    const CACHE_GROUP = 'edr_geolocation';

    /**
     * Initialize geolocation
     */
    public function init()
    {
        if (!edr_is_pro_active()) {
            return;
        }

        // Add country-based validation
        add_filter('registration_errors', [$this, 'validate_country'], 20, 3);
    }

    /**
     * Validate user's country
     *
     * @param WP_Error $errors Errors object
     * @param string $sanitized_user_login Username
     * @param string $user_email Email
     * @return WP_Error
     */
    public function validate_country($errors, $sanitized_user_login, $user_email)
    {
        if (!get_option('edr_enable_country_restrictions', false)) {
            return $errors;
        }

        $ip = $this->get_user_ip();
        $country_code = $this->get_country_code($ip);

        if (!$country_code) {
            return $errors;
        }

        $mode = get_option('edr_country_restriction_mode', 'allow');
        $countries = array_filter(explode("\n", get_option('edr_restricted_countries', '')));
        $countries = array_map('trim', $countries);

        $is_in_list = in_array($country_code, $countries);

        $blocked = ($mode === 'allow' && !$is_in_list) || ($mode === 'block' && $is_in_list);

        if ($blocked) {
            $message = get_option('edr_country_blocked_message', '');
            if (empty($message)) {
                $message = __('Registrations from your country are not allowed.', 'email-domain-restriction');
            }
            $errors->add('country', $message);
        }

        return $errors;
    }

    /**
     * Get country code from IP address
     *
     * @param string $ip IP address
     * @return string|false Country code or false
     */
    public function get_country_code($ip)
    {
        // Check cache
        $cache_key = 'country_' . md5($ip);
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);

        if ($cached !== false) {
            return $cached;
        }

        // Try ipapi.co (free tier: 1000 requests/day)
        $response = wp_remote_get("https://ipapi.co/{$ip}/country/", [
            'timeout' => 5,
        ]);

        if (!is_wp_error($response)) {
            $country = trim(wp_remote_retrieve_body($response));
            if (strlen($country) === 2) {
                wp_cache_set($cache_key, $country, self::CACHE_GROUP, WEEK_IN_SECONDS);
                return $country;
            }
        }

        return false;
    }

    /**
     * Get full geolocation data
     *
     * @param string $ip IP address
     * @return array|false
     */
    public function get_geolocation_data($ip)
    {
        $response = wp_remote_get("https://ipapi.co/{$ip}/json/", [
            'timeout' => 5,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        return [
            'ip'           => $data['ip'] ?? $ip,
            'country_code' => $data['country_code'] ?? '',
            'country'      => $data['country_name'] ?? '',
            'region'       => $data['region'] ?? '',
            'city'         => $data['city'] ?? '',
            'latitude'     => $data['latitude'] ?? null,
            'longitude'    => $data['longitude'] ?? null,
            'timezone'     => $data['timezone'] ?? '',
        ];
    }

    /**
     * Get user's IP address
     *
     * @return string
     */
    private function get_user_ip()
    {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }

        return $ip;
    }
}
