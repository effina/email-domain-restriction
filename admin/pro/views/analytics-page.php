<?php
/**
 * Advanced Analytics Page View
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro_Admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$page_obj = new EDR_Analytics_Page();
$date_ranges = $page_obj->get_date_ranges();
$sources = $page_obj->get_available_sources();
$retention_days = get_option('edr_data_retention_days', 365);
$scheduled_frequency = get_option('edr_scheduled_report_frequency', '');
$scheduled_email = get_option('edr_scheduled_report_email', '');
?>

<div class="wrap edr-analytics-page">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Advanced Analytics', 'email-domain-restriction'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Date Range Selector -->
    <div class="edr-analytics-header">
        <div class="edr-date-selector">
            <label for="edr-date-range"><?php esc_html_e('Date Range:', 'email-domain-restriction'); ?></label>
            <select id="edr-date-range">
                <?php foreach ($date_ranges as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($days, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button" id="edr-refresh-data">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Refresh', 'email-domain-restriction'); ?>
            </button>
        </div>

        <div class="edr-export-controls">
            <button type="button" class="button" id="edr-export-csv-btn">
                <span class="dashicons dashicons-media-spreadsheet"></span>
                <?php esc_html_e('Export CSV', 'email-domain-restriction'); ?>
            </button>
            <button type="button" class="button" id="edr-export-pdf-btn">
                <span class="dashicons dashicons-pdf"></span>
                <?php esc_html_e('Export PDF', 'email-domain-restriction'); ?>
            </button>
            <button type="button" class="button" id="edr-settings-btn">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e('Settings', 'email-domain-restriction'); ?>
            </button>
        </div>
    </div>

    <!-- Overview Statistics -->
    <div class="edr-stats-grid">
        <div class="edr-stat-card">
            <div class="edr-stat-icon edr-stat-icon-total">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="edr-stat-content">
                <div class="edr-stat-value"><?php echo number_format($stats['overall']['total_attempts']); ?></div>
                <div class="edr-stat-label"><?php esc_html_e('Total Attempts', 'email-domain-restriction'); ?></div>
            </div>
        </div>

        <div class="edr-stat-card">
            <div class="edr-stat-icon edr-stat-icon-allowed">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="edr-stat-content">
                <div class="edr-stat-value"><?php echo number_format($stats['overall']['total_allowed']); ?></div>
                <div class="edr-stat-label"><?php esc_html_e('Allowed', 'email-domain-restriction'); ?></div>
            </div>
        </div>

        <div class="edr-stat-card">
            <div class="edr-stat-icon edr-stat-icon-blocked">
                <span class="dashicons dashicons-dismiss"></span>
            </div>
            <div class="edr-stat-content">
                <div class="edr-stat-value"><?php echo number_format($stats['overall']['total_blocked']); ?></div>
                <div class="edr-stat-label"><?php esc_html_e('Blocked', 'email-domain-restriction'); ?></div>
            </div>
        </div>

        <div class="edr-stat-card">
            <div class="edr-stat-icon edr-stat-icon-conversion">
                <span class="dashicons dashicons-performance"></span>
            </div>
            <div class="edr-stat-content">
                <div class="edr-stat-value"><?php echo $stats['conversion_rate']; ?>%</div>
                <div class="edr-stat-label"><?php esc_html_e('Conversion Rate', 'email-domain-restriction'); ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="edr-charts-row">
        <div class="edr-chart-container edr-chart-large">
            <h2><?php esc_html_e('Registration Trends', 'email-domain-restriction'); ?></h2>
            <canvas id="edr-time-series-chart"></canvas>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="edr-charts-row">
        <div class="edr-chart-container edr-chart-medium">
            <h2><?php esc_html_e('By Source', 'email-domain-restriction'); ?></h2>
            <canvas id="edr-source-chart"></canvas>
        </div>

        <div class="edr-chart-container edr-chart-medium">
            <h2><?php esc_html_e('Conversion Funnel', 'email-domain-restriction'); ?></h2>
            <div class="edr-funnel-chart">
                <div class="edr-funnel-stage">
                    <div class="edr-funnel-bar" style="width: 100%;">
                        <span class="edr-funnel-label"><?php esc_html_e('Attempts', 'email-domain-restriction'); ?></span>
                        <span class="edr-funnel-value"><?php echo number_format($funnel['attempts']); ?></span>
                    </div>
                </div>
                <div class="edr-funnel-stage">
                    <?php $allowed_pct = $funnel['attempts'] > 0 ? ($funnel['allowed'] / $funnel['attempts']) * 100 : 0; ?>
                    <div class="edr-funnel-bar" style="width: <?php echo $allowed_pct; ?>%;">
                        <span class="edr-funnel-label"><?php esc_html_e('Allowed', 'email-domain-restriction'); ?></span>
                        <span class="edr-funnel-value"><?php echo number_format($funnel['allowed']); ?></span>
                    </div>
                </div>
                <div class="edr-funnel-stage">
                    <?php $created_pct = $funnel['attempts'] > 0 ? ($funnel['users_created'] / $funnel['attempts']) * 100 : 0; ?>
                    <div class="edr-funnel-bar" style="width: <?php echo $created_pct; ?>%;">
                        <span class="edr-funnel-label"><?php esc_html_e('Users Created', 'email-domain-restriction'); ?></span>
                        <span class="edr-funnel-value"><?php echo number_format($funnel['users_created']); ?></span>
                    </div>
                </div>
                <div class="edr-funnel-stage">
                    <?php $active_pct = $funnel['attempts'] > 0 ? ($funnel['active_users'] / $funnel['attempts']) * 100 : 0; ?>
                    <div class="edr-funnel-bar" style="width: <?php echo $active_pct; ?>%;">
                        <span class="edr-funnel-label"><?php esc_html_e('Active Users', 'email-domain-restriction'); ?></span>
                        <span class="edr-funnel-value"><?php echo number_format($funnel['active_users']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 3 -->
    <div class="edr-charts-row">
        <div class="edr-chart-container edr-chart-medium">
            <h2><?php esc_html_e('Top Domains', 'email-domain-restriction'); ?></h2>
            <canvas id="edr-domains-chart"></canvas>
        </div>

        <div class="edr-chart-container edr-chart-medium">
            <h2><?php esc_html_e('Geographic Distribution', 'email-domain-restriction'); ?></h2>
            <canvas id="edr-geographic-chart"></canvas>
        </div>
    </div>

    <!-- Recent Blocked Attempts -->
    <div class="edr-chart-container">
        <h2><?php esc_html_e('Recent Blocked Attempts', 'email-domain-restriction'); ?></h2>
        <?php if (empty($blocked)): ?>
            <p class="edr-no-data"><?php esc_html_e('No blocked attempts in this period.', 'email-domain-restriction'); ?></p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Email', 'email-domain-restriction'); ?></th>
                        <th><?php esc_html_e('IP Address', 'email-domain-restriction'); ?></th>
                        <th><?php esc_html_e('Source', 'email-domain-restriction'); ?></th>
                        <th><?php esc_html_e('Reason', 'email-domain-restriction'); ?></th>
                        <th><?php esc_html_e('Country', 'email-domain-restriction'); ?></th>
                        <th><?php esc_html_e('Date', 'email-domain-restriction'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blocked as $attempt): ?>
                        <tr>
                            <td><?php echo esc_html($attempt['email']); ?></td>
                            <td><code><?php echo esc_html($attempt['ip_address']); ?></code></td>
                            <td>
                                <?php
                                $source_label = isset($sources[$attempt['source']]) ? $sources[$attempt['source']] : $attempt['source'];
                                echo esc_html($source_label);
                                ?>
                            </td>
                            <td><?php echo esc_html($attempt['blocked_reason'] ?: __('Domain not whitelisted', 'email-domain-restriction')); ?></td>
                            <td><?php echo esc_html($attempt['country_code'] ?: '—'); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($attempt['created_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Settings Modal -->
<div id="edr-settings-modal" class="edr-modal" style="display:none;">
    <div class="edr-modal-content">
        <span class="edr-modal-close">&times;</span>
        <h2><?php esc_html_e('Analytics Settings', 'email-domain-restriction'); ?></h2>

        <form id="edr-analytics-settings-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="edr_data_retention_days">
                            <?php esc_html_e('Data Retention', 'email-domain-restriction'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="edr_data_retention_days" id="edr_data_retention_days"
                               value="<?php echo esc_attr($retention_days); ?>" min="0" max="3650" class="small-text">
                        <p class="description">
                            <?php esc_html_e('Number of days to keep analytics data. Set to 0 to keep forever.', 'email-domain-restriction'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="edr_scheduled_frequency">
                            <?php esc_html_e('Scheduled Reports', 'email-domain-restriction'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="edr_scheduled_frequency" id="edr_scheduled_frequency">
                            <option value="never" <?php selected($scheduled_frequency, ''); ?>><?php esc_html_e('Never', 'email-domain-restriction'); ?></option>
                            <option value="daily" <?php selected($scheduled_frequency, 'daily'); ?>><?php esc_html_e('Daily', 'email-domain-restriction'); ?></option>
                            <option value="weekly" <?php selected($scheduled_frequency, 'weekly'); ?>><?php esc_html_e('Weekly', 'email-domain-restriction'); ?></option>
                            <option value="monthly" <?php selected($scheduled_frequency, 'monthly'); ?>><?php esc_html_e('Monthly', 'email-domain-restriction'); ?></option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('How often to send analytics reports via email.', 'email-domain-restriction'); ?>
                        </p>
                    </td>
                </tr>
                <tr id="edr-report-email-row">
                    <th scope="row">
                        <label for="edr_scheduled_email">
                            <?php esc_html_e('Report Email', 'email-domain-restriction'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="email" name="edr_scheduled_email" id="edr_scheduled_email"
                               value="<?php echo esc_attr($scheduled_email); ?>" class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Email address to send scheduled reports to.', 'email-domain-restriction'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Save Settings', 'email-domain-restriction'); ?>
                </button>
                <button type="button" class="button edr-modal-cancel">
                    <?php esc_html_e('Cancel', 'email-domain-restriction'); ?>
                </button>
            </p>
        </form>
    </div>
</div>

<!-- Export CSV Modal -->
<div id="edr-export-csv-modal" class="edr-modal" style="display:none;">
    <div class="edr-modal-content">
        <span class="edr-modal-close">&times;</span>
        <h2><?php esc_html_e('Export CSV', 'email-domain-restriction'); ?></h2>

        <form id="edr-export-csv-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="csv_start_date"><?php esc_html_e('Start Date', 'email-domain-restriction'); ?></label>
                    </th>
                    <td>
                        <input type="date" name="start_date" id="csv_start_date">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="csv_end_date"><?php esc_html_e('End Date', 'email-domain-restriction'); ?></label>
                    </th>
                    <td>
                        <input type="date" name="end_date" id="csv_end_date">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="csv_source"><?php esc_html_e('Source', 'email-domain-restriction'); ?></label>
                    </th>
                    <td>
                        <select name="source" id="csv_source">
                            <option value=""><?php esc_html_e('All Sources', 'email-domain-restriction'); ?></option>
                            <?php foreach ($sources as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="csv_status"><?php esc_html_e('Status', 'email-domain-restriction'); ?></label>
                    </th>
                    <td>
                        <select name="status" id="csv_status">
                            <option value=""><?php esc_html_e('All Statuses', 'email-domain-restriction'); ?></option>
                            <option value="allowed"><?php esc_html_e('Allowed', 'email-domain-restriction'); ?></option>
                            <option value="blocked"><?php esc_html_e('Blocked', 'email-domain-restriction'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Export CSV', 'email-domain-restriction'); ?>
                </button>
                <button type="button" class="button edr-modal-cancel">
                    <?php esc_html_e('Cancel', 'email-domain-restriction'); ?>
                </button>
            </p>
        </form>
    </div>
</div>
