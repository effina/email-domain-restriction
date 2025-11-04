<?php
/**
 * Domain whitelist management.
 *
 * @package Email_Domain_Restriction
 */

/**
 * Domain manager class.
 */
class EDR_Domain_Manager
{
    /**
     * Get all whitelisted domains.
     *
     * @return array Array of domain strings.
     */
    public static function get_domains()
    {
        $domains = get_option('edr_whitelisted_domains', []);
        return is_array($domains) ? $domains : [];
    }

    /**
     * Add a domain to the whitelist.
     *
     * @param string $domain Domain to add.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function add_domain($domain)
    {
        // Sanitize and validate
        $domain = sanitize_text_field($domain);
        $domain = strtolower(trim($domain));

        // Validate domain format
        $validation = self::validate_domain_format($domain);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Get current domains
        $domains = self::get_domains();

        // Check for duplicates
        if (in_array($domain, $domains, true)) {
            return new WP_Error(
                'duplicate_domain',
                __('This domain is already in the whitelist.', 'email-domain-restriction')
            );
        }

        // Add domain
        $domains[] = $domain;
        update_option('edr_whitelisted_domains', $domains);

        return true;
    }

    /**
     * Remove a domain from the whitelist.
     *
     * @param string $domain Domain to remove.
     * @return bool True on success, false on failure.
     */
    public static function remove_domain($domain)
    {
        $domain = sanitize_text_field($domain);
        $domain = strtolower(trim($domain));

        $domains = self::get_domains();
        $key = array_search($domain, $domains, true);

        if ($key !== false) {
            unset($domains[$key]);
            $domains = array_values($domains); // Re-index array
            update_option('edr_whitelisted_domains', $domains);
            return true;
        }

        return false;
    }

    /**
     * Bulk import domains from CSV data.
     *
     * @param string $csv_data CSV content with one domain per line.
     * @return array Results array with 'added', 'skipped', and 'errors' counts.
     */
    public static function bulk_import($csv_data)
    {
        $results = [
            'added' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        // Parse CSV data
        $lines = explode("\n", $csv_data);

        foreach ($lines as $line_number => $line) {
            $domain = trim($line);

            // Skip empty lines
            if (empty($domain)) {
                continue;
            }

            // Try to add domain
            $result = self::add_domain($domain);

            if ($result === true) {
                $results['added']++;
            } elseif (is_wp_error($result) && $result->get_error_code() === 'duplicate_domain') {
                $results['skipped']++;
            } else {
                $results['errors'][] = sprintf(
                    __('Line %d: %s - %s', 'email-domain-restriction'),
                    $line_number + 1,
                    $domain,
                    is_wp_error($result) ? $result->get_error_message() : __('Unknown error', 'email-domain-restriction')
                );
            }
        }

        return $results;
    }

    /**
     * Generate CSV export of all domains.
     *
     * @return string CSV content.
     */
    public static function bulk_export()
    {
        $domains = self::get_domains();
        return implode("\n", $domains);
    }

    /**
     * Validate domain format.
     *
     * @param string $domain Domain to validate.
     * @return bool|WP_Error True if valid, WP_Error if invalid.
     */
    private static function validate_domain_format($domain)
    {
        if (empty($domain)) {
            return new WP_Error(
                'empty_domain',
                __('Domain cannot be empty.', 'email-domain-restriction')
            );
        }

        // Allow wildcards (*.example.com)
        $pattern = '/^(\*\.)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i';

        if (!preg_match($pattern, $domain)) {
            return new WP_Error(
                'invalid_domain',
                __('Invalid domain format. Use format like "example.com" or "*.example.com"', 'email-domain-restriction')
            );
        }

        return true;
    }

    /**
     * Get domain count.
     *
     * @return int Number of whitelisted domains.
     */
    public static function get_domain_count()
    {
        return count(self::get_domains());
    }
}
