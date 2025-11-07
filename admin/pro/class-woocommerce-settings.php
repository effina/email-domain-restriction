<?php
/**
 * WooCommerce Settings
 *
 * Handles WooCommerce-specific settings in admin.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro_Admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_WooCommerce_Settings
 *
 * Manages WooCommerce-specific settings.
 */
class EDR_WooCommerce_Settings
{
    /**
     * Initialize WooCommerce settings
     */
    public function init()
    {
        // Only load if WooCommerce is active and PRO is enabled
        if (!class_exists('WooCommerce') || !edr_is_pro_active()) {
            return;
        }

        // Register settings
        add_action('admin_init', [$this, 'register_settings']);

        // Add settings section to PRO settings page
        add_action('edr_pro_settings_sections', [$this, 'add_settings_section']);
    }

    /**
     * Register WooCommerce-specific settings
     */
    public function register_settings()
    {
        // Custom error message for WooCommerce
        register_setting('edr_pro_settings', 'edr_woocommerce_error_message');

        // Enable/disable checkout validation
        register_setting('edr_pro_settings', 'edr_woocommerce_validate_checkout');

        // Enable/disable My Account validation
        register_setting('edr_pro_settings', 'edr_woocommerce_validate_myaccount');

        // Track WooCommerce metadata
        register_setting('edr_pro_settings', 'edr_woocommerce_track_metadata');

        // B2B mode settings
        register_setting('edr_pro_settings', 'edr_woocommerce_b2b_mode');
        register_setting('edr_pro_settings', 'edr_woocommerce_b2b_domains');
    }

    /**
     * Add WooCommerce settings section
     */
    public function add_settings_section()
    {
        ?>
        <h2><?php esc_html_e('WooCommerce Integration', 'email-domain-restriction'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <?php $this->render_validation_settings(); ?>
                <?php $this->render_error_message_setting(); ?>
                <?php $this->render_metadata_setting(); ?>
                <?php $this->render_b2b_settings(); ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render validation settings
     */
    private function render_validation_settings()
    {
        $validate_checkout = get_option('edr_woocommerce_validate_checkout', true);
        $validate_myaccount = get_option('edr_woocommerce_validate_myaccount', true);

        ?>
        <tr>
            <th scope="row">
                <?php esc_html_e('Validation Settings', 'email-domain-restriction'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="edr_woocommerce_validate_checkout" value="1" <?php checked($validate_checkout, true); ?> />
                        <?php esc_html_e('Validate email domains during checkout registration', 'email-domain-restriction'); ?>
                    </label>
                    <br>
                    <label>
                        <input type="checkbox" name="edr_woocommerce_validate_myaccount" value="1" <?php checked($validate_myaccount, true); ?> />
                        <?php esc_html_e('Validate email domains on My Account registration', 'email-domain-restriction'); ?>
                    </label>
                </fieldset>
                <p class="description">
                    <?php esc_html_e('Control where email domain validation is applied in WooCommerce.', 'email-domain-restriction'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render error message setting
     */
    private function render_error_message_setting()
    {
        $error_message = get_option('edr_woocommerce_error_message', '');

        ?>
        <tr>
            <th scope="row">
                <label for="edr_woocommerce_error_message">
                    <?php esc_html_e('Custom Error Message', 'email-domain-restriction'); ?>
                </label>
            </th>
            <td>
                <textarea
                    name="edr_woocommerce_error_message"
                    id="edr_woocommerce_error_message"
                    rows="3"
                    class="large-text"
                    placeholder="<?php esc_attr_e('Leave empty to use default message', 'email-domain-restriction'); ?>"
                ><?php echo esc_textarea($error_message); ?></textarea>
                <p class="description">
                    <?php esc_html_e('Custom error message shown to WooCommerce customers when their email domain is not whitelisted. Leave empty to use the default message.', 'email-domain-restriction'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render metadata tracking setting
     */
    private function render_metadata_setting()
    {
        $track_metadata = get_option('edr_woocommerce_track_metadata', true);

        ?>
        <tr>
            <th scope="row">
                <?php esc_html_e('Customer Metadata', 'email-domain-restriction'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="edr_woocommerce_track_metadata" value="1" <?php checked($track_metadata, true); ?> />
                    <?php esc_html_e('Track WooCommerce registration metadata (source, order ID, domain, etc.)', 'email-domain-restriction'); ?>
                </label>
                <p class="description">
                    <?php esc_html_e('Store additional metadata about WooCommerce registrations for analytics and reporting.', 'email-domain-restriction'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render B2B mode settings
     */
    private function render_b2b_settings()
    {
        $b2b_mode = get_option('edr_woocommerce_b2b_mode', false);
        $b2b_domains = get_option('edr_woocommerce_b2b_domains', '');

        ?>
        <tr>
            <th scope="row">
                <?php esc_html_e('B2B Mode', 'email-domain-restriction'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="edr_woocommerce_b2b_mode" value="1" <?php checked($b2b_mode, true); ?> />
                    <?php esc_html_e('Enable B2B/Wholesale mode', 'email-domain-restriction'); ?>
                </label>
                <p class="description">
                    <?php esc_html_e('Restrict checkout to specific corporate domains for B2B and wholesale customers.', 'email-domain-restriction'); ?>
                </p>
            </td>
        </tr>
        <?php if ($b2b_mode): ?>
        <tr>
            <th scope="row">
                <label for="edr_woocommerce_b2b_domains">
                    <?php esc_html_e('B2B Domains', 'email-domain-restriction'); ?>
                </label>
            </th>
            <td>
                <textarea
                    name="edr_woocommerce_b2b_domains"
                    id="edr_woocommerce_b2b_domains"
                    rows="5"
                    class="large-text"
                    placeholder="company1.com&#10;company2.com&#10;*.partnernetwork.com"
                ><?php echo esc_textarea($b2b_domains); ?></textarea>
                <p class="description">
                    <?php esc_html_e('List of approved corporate domains for B2B checkout (one per line). Supports wildcards like *.example.com', 'email-domain-restriction'); ?>
                </p>
            </td>
        </tr>
        <?php endif; ?>
        <?php
    }

    /**
     * Get WooCommerce-specific statistics for dashboard
     *
     * @return array
     */
    public function get_dashboard_stats()
    {
        if (!class_exists('EDR_WooCommerce_Integration')) {
            return [];
        }

        $integration = new EDR_WooCommerce_Integration();
        return $integration->get_woocommerce_stats(30);
    }
}
