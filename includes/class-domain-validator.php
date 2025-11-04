<?php
/**
 * Domain validation and matching logic.
 *
 * @package Email_Domain_Restriction
 */

/**
 * Domain validator class.
 */
class EDR_Domain_Validator
{
    /**
     * Extract domain from email address.
     *
     * @param string $email Email address.
     * @return string|false Domain or false on failure.
     */
    public static function extract_domain($email)
    {
        $email = sanitize_email($email);

        if (!is_email($email)) {
            return false;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }

        return strtolower(trim($parts[1]));
    }

    /**
     * Check if domain is whitelisted.
     *
     * Supports exact matches and wildcard patterns (*.example.com).
     *
     * @param string $domain Domain to check.
     * @return bool True if whitelisted, false otherwise.
     */
    public static function is_whitelisted($domain)
    {
        $domain = strtolower(trim($domain));
        $whitelisted_domains = EDR_Domain_Manager::get_domains();

        foreach ($whitelisted_domains as $whitelisted) {
            // Exact match
            if ($domain === $whitelisted) {
                return true;
            }

            // Wildcard match
            if (strpos($whitelisted, '*') !== false) {
                if (self::matches_wildcard($domain, $whitelisted)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if domain matches wildcard pattern.
     *
     * @param string $domain Domain to check.
     * @param string $pattern Wildcard pattern (e.g., *.example.com).
     * @return bool True if matches, false otherwise.
     */
    private static function matches_wildcard($domain, $pattern)
    {
        // Convert wildcard pattern to regex
        $regex = self::convert_wildcard_to_regex($pattern);

        return (bool) preg_match($regex, $domain);
    }

    /**
     * Convert wildcard domain pattern to regex.
     *
     * @param string $pattern Wildcard pattern (e.g., *.example.com).
     * @return string Regex pattern.
     */
    private static function convert_wildcard_to_regex($pattern)
    {
        // Escape special regex characters except *
        $pattern = preg_quote($pattern, '/');

        // Convert * to regex pattern
        // \* matches literal asterisk (from preg_quote)
        // Replace with regex pattern that matches any subdomain
        $pattern = str_replace('\*', '[a-z0-9]+([\-\.][a-z0-9]+)*', $pattern);

        // Add anchors for exact match
        return '/^' . $pattern . '$/i';
    }

    /**
     * Validate email and check domain.
     *
     * @param string $email Email address to validate.
     * @return bool|WP_Error True if valid and whitelisted, WP_Error otherwise.
     */
    public static function validate_email($email)
    {
        // Validate email format
        $email = sanitize_email($email);

        if (!is_email($email)) {
            return new WP_Error(
                'invalid_email',
                __('Invalid email address format.', 'email-domain-restriction')
            );
        }

        // Extract domain
        $domain = self::extract_domain($email);

        if ($domain === false) {
            return new WP_Error(
                'invalid_domain',
                __('Could not extract domain from email address.', 'email-domain-restriction')
            );
        }

        // Check if whitelisted
        if (!self::is_whitelisted($domain)) {
            $settings = get_option('edr_settings', []);
            $message = isset($settings['blocked_domain_message'])
                ? $settings['blocked_domain_message']
                : __(
                    'Registration is restricted to approved email domains only.',
                    'email-domain-restriction'
                );

            return new WP_Error(
                'domain_not_whitelisted',
                $message
            );
        }

        return true;
    }
}
