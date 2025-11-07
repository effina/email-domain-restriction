<?php
/**
 * License Manager
 *
 * Handles license operations and validation.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_License_Manager
 *
 * Manages license activation, deactivation, and validation.
 */
class EDR_License_Manager
{
    /**
     * License status transient key
     */
    const LICENSE_STATUS_TRANSIENT = 'edr_license_status';

    /**
     * License check interval (12 hours)
     */
    const LICENSE_CHECK_INTERVAL = 43200;

    /**
     * Get license information
     *
     * @return array|null
     */
    public static function get_license_info()
    {
        $freemius = EDR_Pro_Features::get_freemius();

        if ($freemius === null) {
            return null;
        }

        $license = $freemius->_get_license();

        if (!$license) {
            return null;
        }

        return [
            'key'        => $license->secret_key,
            'plan'       => $freemius->get_plan_name(),
            'status'     => $license->is_active() ? 'active' : 'inactive',
            'activated'  => $license->activated_local,
            'expires'    => $license->expiration,
            'is_expired' => $license->is_expired(),
            'quota'      => $license->quota,
            'activated_sites' => $license->activated,
        ];
    }

    /**
     * Check if license is valid
     *
     * @return bool
     */
    public static function is_license_valid()
    {
        // Check transient first
        $cached_status = get_transient(self::LICENSE_STATUS_TRANSIENT);
        if ($cached_status !== false) {
            return $cached_status === 'valid';
        }

        $freemius = EDR_Pro_Features::get_freemius();

        if ($freemius === null) {
            return false;
        }

        $is_valid = $freemius->is_premium() && $freemius->is_plan('pro', true);

        // Cache the result
        set_transient(self::LICENSE_STATUS_TRANSIENT, $is_valid ? 'valid' : 'invalid', self::LICENSE_CHECK_INTERVAL);

        return $is_valid;
    }

    /**
     * Get license status message
     *
     * @return string
     */
    public static function get_license_status_message()
    {
        $license_info = self::get_license_info();

        if ($license_info === null) {
            return __('No license activated', 'email-domain-restriction');
        }

        if ($license_info['status'] !== 'active') {
            return __('License is inactive', 'email-domain-restriction');
        }

        if ($license_info['is_expired']) {
            return __('License has expired', 'email-domain-restriction');
        }

        $expires = date_i18n(get_option('date_format'), strtotime($license_info['expires']));

        return sprintf(
            __('License active - Expires on %s', 'email-domain-restriction'),
            $expires
        );
    }

    /**
     * Clear license cache
     */
    public static function clear_license_cache()
    {
        delete_transient(self::LICENSE_STATUS_TRANSIENT);
    }

    /**
     * Get activation limit status
     *
     * @return array
     */
    public static function get_activation_limit()
    {
        $license_info = self::get_license_info();

        if ($license_info === null) {
            return [
                'current' => 0,
                'max'     => 0,
                'remaining' => 0,
            ];
        }

        $quota = $license_info['quota'];
        $activated = $license_info['activated_sites'];

        return [
            'current'   => $activated,
            'max'       => $quota,
            'remaining' => max(0, $quota - $activated),
        ];
    }

    /**
     * Handle license activation webhook
     */
    public static function handle_activation_webhook()
    {
        // Clear cache when license is activated
        self::clear_license_cache();

        // Log activation
        if (class_exists('EDR_Attempt_Logger')) {
            error_log('EDR PRO: License activated');
        }
    }

    /**
     * Handle license deactivation webhook
     */
    public static function handle_deactivation_webhook()
    {
        // Clear cache when license is deactivated
        self::clear_license_cache();

        // Log deactivation
        if (class_exists('EDR_Attempt_Logger')) {
            error_log('EDR PRO: License deactivated');
        }
    }

    /**
     * Get license type
     *
     * @return string
     */
    public static function get_license_type()
    {
        $license_info = self::get_license_info();

        if ($license_info === null) {
            return 'free';
        }

        return strtolower($license_info['plan']);
    }

    /**
     * Check if feature is available in current license
     *
     * @param string $feature Feature key
     * @return bool
     */
    public static function has_feature($feature)
    {
        if (!self::is_license_valid()) {
            return false;
        }

        return EDR_Pro_Features::has_feature($feature);
    }

    /**
     * Get license renewal URL
     *
     * @return string
     */
    public static function get_renewal_url()
    {
        $freemius = EDR_Pro_Features::get_freemius();

        if ($freemius === null) {
            return admin_url('admin.php?page=email-domain-restriction-pricing');
        }

        return $freemius->get_upgrade_url();
    }

    /**
     * Get license upgrade URL
     *
     * @return string
     */
    public static function get_upgrade_url()
    {
        $freemius = EDR_Pro_Features::get_freemius();

        if ($freemius === null) {
            return admin_url('admin.php?page=email-domain-restriction-pricing');
        }

        return $freemius->get_upgrade_url();
    }
}
