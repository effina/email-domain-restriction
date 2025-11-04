<?php
/**
 * Log viewer template.
 *
 * @package Email_Domain_Restriction
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('edr_messages'); ?>

    <!-- Filters -->
    <div class="edr-log-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="edr-log">

            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="status">
                        <option value=""><?php _e('All Statuses', 'email-domain-restriction'); ?></option>
                        <option value="allowed" <?php selected($status, 'allowed'); ?>>
                            <?php _e('Allowed', 'email-domain-restriction'); ?>
                        </option>
                        <option value="blocked" <?php selected($status, 'blocked'); ?>>
                            <?php _e('Blocked', 'email-domain-restriction'); ?>
                        </option>
                    </select>

                    <input type="text" name="domain" value="<?php echo esc_attr($domain); ?>"
                           placeholder="<?php esc_attr_e('Search domain...', 'email-domain-restriction'); ?>">

                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>"
                           placeholder="<?php esc_attr_e('From date', 'email-domain-restriction'); ?>">

                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>"
                           placeholder="<?php esc_attr_e('To date', 'email-domain-restriction'); ?>">

                    <button type="submit" class="button">
                        <?php _e('Filter', 'email-domain-restriction'); ?>
                    </button>

                    <?php if ($status || $domain || $date_from || $date_to) : ?>
                        <a href="<?php echo admin_url('admin.php?page=edr-log'); ?>" class="button">
                            <?php _e('Clear Filters', 'email-domain-restriction'); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="alignright actions">
                    <select name="per_page">
                        <option value="25" <?php selected($per_page, 25); ?>>25</option>
                        <option value="50" <?php selected($per_page, 50); ?>>50</option>
                        <option value="100" <?php selected($per_page, 100); ?>>100</option>
                    </select>
                    <label><?php _e('per page', 'email-domain-restriction'); ?></label>

                    <?php
                    $export_url = wp_nonce_url(
                        add_query_arg(
                            array_filter([
                                'action' => 'edr_export_log',
                                'status' => $status,
                                'domain' => $domain,
                                'date_from' => $date_from,
                                'date_to' => $date_to,
                            ]),
                            admin_url('admin.php')
                        ),
                        'edr_export_log',
                        'nonce'
                    );
                    ?>
                    <a href="<?php echo esc_url($export_url); ?>" class="button">
                        <?php _e('Export CSV', 'email-domain-restriction'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Log Table -->
    <?php if (empty($attempts)) : ?>
        <p><?php _e('No registration attempts found.', 'email-domain-restriction'); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 160px;"><?php _e('Date/Time', 'email-domain-restriction'); ?></th>
                    <th><?php _e('Email', 'email-domain-restriction'); ?></th>
                    <th><?php _e('Domain', 'email-domain-restriction'); ?></th>
                    <th style="width: 100px;"><?php _e('Status', 'email-domain-restriction'); ?></th>
                    <th style="width: 120px;"><?php _e('IP Address', 'email-domain-restriction'); ?></th>
                    <th><?php _e('User Agent', 'email-domain-restriction'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attempts as $attempt) : ?>
                    <tr>
                        <td><?php echo esc_html($attempt['attempted_at']); ?></td>
                        <td><?php echo esc_html($attempt['email']); ?></td>
                        <td><strong><?php echo esc_html($attempt['domain']); ?></strong></td>
                        <td>
                            <?php if ($attempt['status'] === 'allowed') : ?>
                                <span class="edr-status edr-status-allowed">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php _e('Allowed', 'email-domain-restriction'); ?>
                                </span>
                            <?php else : ?>
                                <span class="edr-status edr-status-blocked">
                                    <span class="dashicons dashicons-dismiss"></span>
                                    <?php _e('Blocked', 'email-domain-restriction'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($attempt['ip_address']); ?></td>
                        <td>
                            <span class="edr-user-agent" title="<?php echo esc_attr($attempt['user_agent']); ?>">
                                <?php echo esc_html(substr($attempt['user_agent'], 0, 50)); ?>
                                <?php if (strlen($attempt['user_agent']) > 50) : ?>...<?php endif; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(__('%s items', 'email-domain-restriction'), number_format_i18n($total)); ?>
                    </span>
                    <?php
                    $page_links = paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $paged,
                        'add_args' => array_filter([
                            'status' => $status,
                            'domain' => $domain,
                            'date_from' => $date_from,
                            'date_to' => $date_to,
                            'per_page' => $per_page,
                        ]),
                    ]);
                    echo $page_links;
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Clear Logs -->
    <hr>
    <h2><?php _e('Maintenance', 'email-domain-restriction'); ?></h2>
    <form method="post" action="">
        <?php wp_nonce_field('edr_clear_logs'); ?>
        <?php
        $settings = get_option('edr_settings', []);
        $retention_days = $settings['log_retention_days'] ?? 30;
        ?>
        <p>
            <?php printf(__('Clear log entries older than %d days.', 'email-domain-restriction'), $retention_days); ?>
        </p>
        <button type="submit" name="edr_clear_logs" class="button button-secondary"
                onclick="return confirm('<?php echo esc_js(__('Are you sure? This cannot be undone.', 'email-domain-restriction')); ?>');">
            <?php _e('Clear Old Logs', 'email-domain-restriction'); ?>
        </button>
    </form>
</div>
