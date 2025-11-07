<?php
/**
 * BuddyPress Settings
 *
 * Handles BuddyPress-specific settings in admin.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro_Admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_BuddyPress_Settings
 *
 * Manages BuddyPress-specific settings.
 */
class EDR_BuddyPress_Settings
{
    /**
     * Initialize BuddyPress settings
     */
    public function init()
    {
        // Only load if BuddyPress is active and PRO is enabled
        if (!class_exists('BuddyPress') || !edr_is_pro_active()) {
            return;
        }

        // Register settings
        add_action('admin_init', [$this, 'register_settings']);

        // Add settings section to PRO settings page
        add_action('edr_pro_settings_sections', [$this, 'add_settings_section']);

        // Add group settings metabox
        add_action('bp_after_group_settings_admin', [$this, 'render_group_domain_settings']);
        add_action('groups_group_after_save', [$this, 'save_group_domain_settings']);
    }

    /**
     * Register BuddyPress-specific settings
     */
    public function register_settings()
    {
        // Custom error message for BuddyPress
        register_setting('edr_pro_settings', 'edr_buddypress_error_message');

        // Enable/disable registration validation
        register_setting('edr_pro_settings', 'edr_buddypress_validate_registration');

        // Track BuddyPress metadata
        register_setting('edr_pro_settings', 'edr_buddypress_track_metadata');

        // Group restrictions
        register_setting('edr_pro_settings', 'edr_buddypress_enable_group_restrictions');
    }

    /**
     * Add BuddyPress settings section
     */
    public function add_settings_section()
    {
        ?>
        <h2><?php esc_html_e('BuddyPress Integration', 'email-domain-restriction'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <?php $this->render_validation_settings(); ?>
                <?php $this->render_error_message_setting(); ?>
                <?php $this->render_metadata_setting(); ?>
                <?php $this->render_group_restrictions_setting(); ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render validation settings
     */
    private function render_validation_settings()
    {
        $validate_registration = get_option('edr_buddypress_validate_registration', true);

        ?>
        <tr>
            <th scope="row">
                <?php esc_html_e('Validation Settings', 'email-domain-restriction'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="edr_buddypress_validate_registration" value="1" <?php checked($validate_registration, true); ?> />
                    <?php esc_html_e('Validate email domains during BuddyPress registration', 'email-domain-restriction'); ?>
                </label>
                <p class="description">
                    <?php esc_html_e('Control whether email domain validation is applied to BuddyPress registrations.', 'email-domain-restriction'); ?>
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
        $error_message = get_option('edr_buddypress_error_message', '');

        ?>
        <tr>
            <th scope="row">
                <label for="edr_buddypress_error_message">
                    <?php esc_html_e('Custom Error Message', 'email-domain-restriction'); ?>
                </label>
            </th>
            <td>
                <textarea
                    name="edr_buddypress_error_message"
                    id="edr_buddypress_error_message"
                    rows="3"
                    class="large-text"
                    placeholder="<?php esc_attr_e('Leave empty to use default message', 'email-domain-restriction'); ?>"
                ><?php echo esc_textarea($error_message); ?></textarea>
                <p class="description">
                    <?php esc_html_e('Custom error message shown when an email domain is not whitelisted. Leave empty to use the default message.', 'email-domain-restriction'); ?>
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
        $track_metadata = get_option('edr_buddypress_track_metadata', true);

        ?>
        <tr>
            <th scope="row">
                <?php esc_html_e('Member Metadata', 'email-domain-restriction'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="edr_buddypress_track_metadata" value="1" <?php checked($track_metadata, true); ?> />
                    <?php esc_html_e('Track BuddyPress registration metadata (member type, invitation, etc.)', 'email-domain-restriction'); ?>
                </label>
                <p class="description">
                    <?php esc_html_e('Store additional metadata about BuddyPress registrations for analytics and reporting.', 'email-domain-restriction'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render group restrictions setting
     */
    private function render_group_restrictions_setting()
    {
        $enable_group_restrictions = get_option('edr_buddypress_enable_group_restrictions', false);

        ?>
        <tr>
            <th scope="row">
                <?php esc_html_e('Group Restrictions', 'email-domain-restriction'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="edr_buddypress_enable_group_restrictions" value="1" <?php checked($enable_group_restrictions, true); ?> />
                    <?php esc_html_e('Enable group-level domain restrictions', 'email-domain-restriction'); ?>
                </label>
                <p class="description">
                    <?php esc_html_e('Allow group admins to restrict group membership to specific email domains.', 'email-domain-restriction'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render group domain settings in group admin
     *
     * @param int $group_id Group ID
     */
    public function render_group_domain_settings($group_id = null)
    {
        // Check if group restrictions are enabled
        if (!get_option('edr_buddypress_enable_group_restrictions', false)) {
            return;
        }

        if (!$group_id) {
            $group_id = bp_get_current_group_id();
        }

        $integration = new EDR_BuddyPress_Integration();
        $allowed_domains = $integration->get_group_domain_restrictions($group_id);
        $domains_string = implode("\n", $allowed_domains);

        ?>
        <div class="edr-group-domain-settings">
            <h4><?php esc_html_e('Email Domain Restrictions', 'email-domain-restriction'); ?></h4>
            <p class="description">
                <?php esc_html_e('Restrict group membership to specific email domains. One domain per line. Supports wildcards (*.example.com)', 'email-domain-restriction'); ?>
            </p>
            <textarea
                name="edr_group_allowed_domains"
                rows="5"
                style="width: 100%; max-width: 500px;"
                placeholder="example.com&#10;*.company.com"
            ><?php echo esc_textarea($domains_string); ?></textarea>
            <?php wp_nonce_field('edr_save_group_domains', 'edr_group_domains_nonce'); ?>
        </div>
        <?php
    }

    /**
     * Save group domain settings
     *
     * @param int $group_id Group ID
     */
    public function save_group_domain_settings($group_id)
    {
        // Check if group restrictions are enabled
        if (!get_option('edr_buddypress_enable_group_restrictions', false)) {
            return;
        }

        // Verify nonce
        if (!isset($_POST['edr_group_domains_nonce']) || !wp_verify_nonce($_POST['edr_group_domains_nonce'], 'edr_save_group_domains')) {
            return;
        }

        // Check user permissions
        if (!bp_is_item_admin() && !bp_current_user_can('bp_moderate')) {
            return;
        }

        // Get submitted domains
        $domains_string = isset($_POST['edr_group_allowed_domains']) ? sanitize_textarea_field($_POST['edr_group_allowed_domains']) : '';

        if (empty($domains_string)) {
            // Remove restrictions
            groups_delete_groupmeta($group_id, 'edr_allowed_domains');
        } else {
            // Save domains
            $domains = array_map('trim', explode("\n", $domains_string));
            $integration = new EDR_BuddyPress_Integration();
            $integration->set_group_domain_restrictions($group_id, $domains);
        }
    }

    /**
     * Get BuddyPress-specific statistics for dashboard
     *
     * @return array
     */
    public function get_dashboard_stats()
    {
        if (!class_exists('EDR_BuddyPress_Integration')) {
            return [];
        }

        $integration = new EDR_BuddyPress_Integration();
        return $integration->get_buddypress_stats(30);
    }
}
