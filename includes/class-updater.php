<?php
/**
 * Plugin update checker.
 *
 * Integrates with Plugin Update Checker library to provide
 * automatic updates from custom server.
 *
 * @package Email_Domain_Restriction
 */

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Updater class.
 */
class EDR_Updater
{
    /**
     * Update checker instance.
     *
     * @var object
     */
    private $update_checker;

    /**
     * Initialize update checker.
     */
    public function init()
    {
        // Load Plugin Update Checker library
        if (!class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
            require_once EDR_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';
        }

        // Build update checker
        $this->update_checker = PucFactory::buildUpdateChecker(
            'https://codeeffina.com/wordpress/plugins/email-domain-restriction/metadata/info.json',
            EDR_PLUGIN_DIR . 'email-domain-restriction.php',
            'email-domain-restriction'
        );

        // Check for updates every 12 hours
        $this->update_checker->checkPeriod = 12;

        // Optional: Add custom query parameters for tracking
        // $this->update_checker->addQueryArgFilter([$this, 'add_query_args']);
    }

    /**
     * Add custom query parameters to update check request.
     *
     * @param array $query_args Current query arguments.
     * @return array Modified query arguments.
     */
    public function add_query_args($query_args)
    {
        // Add site URL for tracking (optional)
        $query_args['site_url'] = home_url();

        return $query_args;
    }

    /**
     * Get update checker instance.
     *
     * @return object Update checker instance.
     */
    public function get_update_checker()
    {
        return $this->update_checker;
    }
}
