<?php
/**
 * Dashboard template.
 *
 * @package Email_Domain_Restriction
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

?>

<div class="wrap">
    <h1><?php _e('Email Domain Restriction - Dashboard', 'email-domain-restriction'); ?></h1>

    <!-- Date Range Selector -->
    <div class="edr-dashboard-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="email-domain-restriction">
            <label for="date_range"><?php _e('Date Range:', 'email-domain-restriction'); ?></label>
            <select name="date_range" id="date_range" onchange="this.form.submit()">
                <option value="7" <?php selected($date_range, '7'); ?>><?php _e('Last 7 Days', 'email-domain-restriction'); ?></option>
                <option value="30" <?php selected($date_range, '30'); ?>><?php _e('Last 30 Days', 'email-domain-restriction'); ?></option>
                <option value="90" <?php selected($date_range, '90'); ?>><?php _e('Last 90 Days', 'email-domain-restriction'); ?></option>
                <option value="all" <?php selected($date_range, 'all'); ?>><?php _e('All Time', 'email-domain-restriction'); ?></option>
            </select>
            <button type="submit" class="button"><?php _e('Refresh Stats', 'email-domain-restriction'); ?></button>
        </form>
    </div>

    <!-- Quick Stats Cards -->
    <div class="edr-stats-cards">
        <div class="edr-stat-card">
            <div class="edr-stat-icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="edr-stat-content">
                <div class="edr-stat-value"><?php echo number_format_i18n($stats['total']); ?></div>
                <div class="edr-stat-label"><?php _e('Total Attempts', 'email-domain-restriction'); ?></div>
            </div>
        </div>

        <div class="edr-stat-card edr-stat-success">
            <div class="edr-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="edr-stat-content">
                <div class="edr-stat-value"><?php echo number_format_i18n($stats['allowed']); ?></div>
                <div class="edr-stat-label"><?php _e('Allowed', 'email-domain-restriction'); ?></div>
            </div>
        </div>

        <div class="edr-stat-card edr-stat-danger">
            <div class="edr-stat-icon">
                <span class="dashicons dashicons-dismiss"></span>
            </div>
            <div class="edr-stat-content">
                <div class="edr-stat-value"><?php echo number_format_i18n($stats['blocked']); ?></div>
                <div class="edr-stat-label"><?php _e('Blocked', 'email-domain-restriction'); ?></div>
            </div>
        </div>

        <div class="edr-stat-card edr-stat-info">
            <div class="edr-stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="edr-stat-content">
                <div class="edr-stat-value"><?php echo number_format($stats['success_rate'], 1); ?>%</div>
                <div class="edr-stat-label"><?php _e('Success Rate', 'email-domain-restriction'); ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="edr-dashboard-row">
        <div class="edr-chart-container edr-chart-large">
            <h2><?php _e('Registration Attempts Over Time', 'email-domain-restriction'); ?></h2>
            <canvas id="edrAttemptsChart"></canvas>
        </div>

        <div class="edr-chart-container edr-chart-small">
            <h2><?php _e('Allowed vs Blocked', 'email-domain-restriction'); ?></h2>
            <canvas id="edrPieChart"></canvas>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="edr-dashboard-row">
        <div class="edr-chart-container">
            <h2><?php _e('Top 10 Attempted Domains', 'email-domain-restriction'); ?></h2>
            <canvas id="edrTopDomainsChart"></canvas>
        </div>
    </div>

    <!-- Charts Row 3 -->
    <div class="edr-dashboard-row">
        <div class="edr-chart-container">
            <h2><?php _e('Attempts by Day of Week', 'email-domain-restriction'); ?></h2>
            <canvas id="edrWeekdayChart"></canvas>
        </div>

        <div class="edr-chart-container">
            <h2><?php _e('Attempts by Hour of Day', 'email-domain-restriction'); ?></h2>
            <canvas id="edrHourChart"></canvas>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="edr-recent-activity">
        <h2><?php _e('Recent Activity', 'email-domain-restriction'); ?></h2>
        <?php if (empty($stats['recent_attempts'])) : ?>
            <p><?php _e('No recent registration attempts.', 'email-domain-restriction'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Time', 'email-domain-restriction'); ?></th>
                        <th><?php _e('Email', 'email-domain-restriction'); ?></th>
                        <th><?php _e('Domain', 'email-domain-restriction'); ?></th>
                        <th><?php _e('Status', 'email-domain-restriction'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['recent_attempts'] as $attempt) : ?>
                        <tr>
                            <td><?php echo esc_html(human_time_diff(strtotime($attempt['attempted_at']), current_time('timestamp')) . ' ago'); ?></td>
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
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Pass data to JavaScript -->
<script type="text/javascript">
    var edrChartData = {
        attemptsOverTime: <?php echo json_encode($stats['attempts_by_date']); ?>,
        allowedCount: <?php echo json_encode($stats['allowed']); ?>,
        blockedCount: <?php echo json_encode($stats['blocked']); ?>,
        topDomainsAllowed: <?php echo json_encode($stats['top_domains_allowed']); ?>,
        topDomainsBlocked: <?php echo json_encode($stats['top_domains_blocked']); ?>,
        attemptsByWeekday: <?php echo json_encode($stats['attempts_by_weekday']); ?>,
        attemptsByHour: <?php echo json_encode($stats['attempts_by_hour']); ?>
    };
</script>
