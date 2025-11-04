<?php
/**
 * Settings page template.
 *
 * @package Email_Domain_Restriction
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Determine which tab we're on
$current_page = isset($_GET['page']) ? $_GET['page'] : '';
$is_domains_tab = $current_page === 'edr-domains';
$is_settings_tab = $current_page === 'edr-settings';

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('edr_messages'); ?>

    <?php if ($is_domains_tab) : ?>
        <!-- Domain Whitelist Tab -->
        <div class="edr-domains-section">
            <h2><?php _e('Add Domain', 'email-domain-restriction'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('edr_add_domain'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="domain"><?php _e('Domain', 'email-domain-restriction'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="domain" id="domain" class="regular-text"
                                   placeholder="example.com or *.example.com">
                            <p class="description">
                                <?php _e('Enter a domain to whitelist. Use *.example.com to allow all subdomains.', 'email-domain-restriction'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Add Domain', 'email-domain-restriction'), 'primary', 'edr_add_domain'); ?>
            </form>

            <hr>

            <h2><?php _e('Whitelisted Domains', 'email-domain-restriction'); ?></h2>
            <?php if (empty($domains)) : ?>
                <p><?php _e('No domains in whitelist yet. Add your first domain above.', 'email-domain-restriction'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Domain', 'email-domain-restriction'); ?></th>
                            <th><?php _e('Type', 'email-domain-restriction'); ?></th>
                            <th><?php _e('Actions', 'email-domain-restriction'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($domains as $domain) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($domain); ?></strong></td>
                                <td>
                                    <?php
                                    echo strpos($domain, '*') !== false
                                        ? '<span class="dashicons dashicons-star-filled"></span> ' . __('Wildcard', 'email-domain-restriction')
                                        : '<span class="dashicons dashicons-yes"></span> ' . __('Exact', 'email-domain-restriction');
                                    ?>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('edr_remove_domain'); ?>
                                        <input type="hidden" name="domain" value="<?php echo esc_attr($domain); ?>">
                                        <button type="submit" name="edr_remove_domain" class="button button-small button-link-delete"
                                                onclick="return confirm('<?php echo esc_js(__('Are you sure you want to remove this domain?', 'email-domain-restriction')); ?>');">
                                            <?php _e('Remove', 'email-domain-restriction'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="description">
                    <?php printf(__('Total domains: %d', 'email-domain-restriction'), count($domains)); ?>
                </p>
            <?php endif; ?>

            <hr>

            <h2><?php _e('Bulk Import/Export', 'email-domain-restriction'); ?></h2>

            <h3><?php _e('Import Domains', 'email-domain-restriction'); ?></h3>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('edr_bulk_import'); ?>
                <p>
                    <input type="file" name="csv_file" accept=".csv,.txt">
                </p>
                <p class="description">
                    <?php _e('Upload a CSV file with one domain per line. Maximum file size: 1MB.', 'email-domain-restriction'); ?>
                </p>
                <?php submit_button(__('Import Domains', 'email-domain-restriction'), 'secondary', 'edr_bulk_import'); ?>
            </form>

            <h3><?php _e('Export Domains', 'email-domain-restriction'); ?></h3>
            <p>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?action=edr_export_domains'), 'edr_export_domains', 'nonce'); ?>"
                   class="button button-secondary">
                    <?php _e('Download CSV', 'email-domain-restriction'); ?>
                </a>
            </p>
            <p class="description">
                <?php _e('Export all whitelisted domains to a CSV file.', 'email-domain-restriction'); ?>
            </p>
        </div>

    <?php elseif ($is_settings_tab) : ?>
        <!-- Settings Tab -->
        <form method="post" action="">
            <?php wp_nonce_field('edr_update_settings'); ?>

            <h2><?php _e('General Settings', 'email-domain-restriction'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="log_retention_days"><?php _e('Log Retention Period', 'email-domain-restriction'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="log_retention_days" id="log_retention_days"
                               value="<?php echo esc_attr($settings['log_retention_days'] ?? 30); ?>"
                               min="1" max="365" class="small-text">
                        <?php _e('days', 'email-domain-restriction'); ?>
                        <p class="description">
                            <?php _e('How long to keep registration attempt logs before automatic cleanup.', 'email-domain-restriction'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Email Verification', 'email-domain-restriction'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="email_verification_enabled" value="1"
                                   <?php checked($settings['email_verification_enabled'] ?? true); ?>>
                            <?php _e('Enable email verification for new users', 'email-domain-restriction'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Require users to verify their email address before they can log in. (Single-site only)', 'email-domain-restriction'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="verification_token_expiry_hours"><?php _e('Verification Link Expiry', 'email-domain-restriction'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="verification_token_expiry_hours" id="verification_token_expiry_hours"
                               value="<?php echo esc_attr($settings['verification_token_expiry_hours'] ?? 48); ?>"
                               min="1" max="168" class="small-text">
                        <?php _e('hours', 'email-domain-restriction'); ?>
                        <p class="description">
                            <?php _e('How long verification links remain valid.', 'email-domain-restriction'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="blocked_domain_message"><?php _e('Blocked Domain Message', 'email-domain-restriction'); ?></label>
                    </th>
                    <td>
                        <textarea name="blocked_domain_message" id="blocked_domain_message"
                                  rows="3" class="large-text"><?php echo esc_textarea($settings['blocked_domain_message'] ?? ''); ?></textarea>
                        <p class="description">
                            <?php _e('Custom error message shown when registration is blocked. Leave empty for default message.', 'email-domain-restriction'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="default_user_role"><?php _e('Default User Role', 'email-domain-restriction'); ?></label>
                    </th>
                    <td>
                        <select name="default_user_role" id="default_user_role">
                            <?php
                            $roles = get_editable_roles();
                            $default_role = $settings['default_user_role'] ?? 'subscriber';
                            foreach ($roles as $role => $details) {
                                printf(
                                    '<option value="%s" %s>%s</option>',
                                    esc_attr($role),
                                    selected($default_role, $role, false),
                                    esc_html(translate_user_role($details['name']))
                                );
                            }
                            ?>
                        </select>
                        <p class="description">
                            <?php _e('Role assigned to new users after verification.', 'email-domain-restriction'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save Settings', 'email-domain-restriction'), 'primary', 'edr_update_settings'); ?>
        </form>

    <?php endif; ?>
</div>
