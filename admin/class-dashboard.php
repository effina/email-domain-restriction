<?php
/**
 * Dashboard with statistics and analytics.
 *
 * @package Email_Domain_Restriction
 */

/**
 * Dashboard class.
 */
class EDR_Dashboard
{
    /**
     * Render dashboard.
     */
    public function render()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'email-domain-restriction'));
        }

        // Get date range from request
        $date_range = isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '7';

        // Calculate date boundaries
        $date_ranges = $this->get_date_boundaries($date_range);

        // Get statistics
        $stats = [
            'total' => $this->get_total_attempts($date_ranges),
            'allowed' => $this->get_allowed_count($date_ranges),
            'blocked' => $this->get_blocked_count($date_ranges),
            'success_rate' => $this->get_success_rate($date_ranges),
            'attempts_by_date' => $this->get_attempts_by_date($date_ranges),
            'top_domains_allowed' => $this->get_top_domains(10, 'allowed', $date_ranges),
            'top_domains_blocked' => $this->get_top_domains(10, 'blocked', $date_ranges),
            'attempts_by_weekday' => $this->get_attempts_by_weekday($date_ranges),
            'attempts_by_hour' => $this->get_attempts_by_hour($date_ranges),
            'recent_attempts' => $this->get_recent_attempts(10),
        ];

        include EDR_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Get date boundaries for query.
     *
     * @param string $range Range identifier (7, 30, 90, or custom).
     * @return array Array with 'from' and 'to' dates.
     */
    private function get_date_boundaries($range)
    {
        $to = current_time('mysql');

        switch ($range) {
            case '7':
                $from = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case '30':
                $from = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
            case '90':
                $from = date('Y-m-d H:i:s', strtotime('-90 days'));
                break;
            case 'all':
                $from = '2000-01-01 00:00:00';
                break;
            default:
                $from = date('Y-m-d H:i:s', strtotime('-7 days'));
        }

        return [
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Get total registration attempts.
     *
     * @param array $date_range Date range array.
     * @return int Total count.
     */
    private function get_total_attempts($date_range)
    {
        return $this->get_cached_stats('total_' . md5(serialize($date_range)), function() use ($date_range) {
            return EDR_Attempt_Logger::get_attempts_count([
                'date_from' => $date_range['from'],
                'date_to' => $date_range['to'],
            ]);
        });
    }

    /**
     * Get allowed registration count.
     *
     * @param array $date_range Date range array.
     * @return int Allowed count.
     */
    private function get_allowed_count($date_range)
    {
        return $this->get_cached_stats('allowed_' . md5(serialize($date_range)), function() use ($date_range) {
            return EDR_Attempt_Logger::get_attempts_count([
                'status' => 'allowed',
                'date_from' => $date_range['from'],
                'date_to' => $date_range['to'],
            ]);
        });
    }

    /**
     * Get blocked registration count.
     *
     * @param array $date_range Date range array.
     * @return int Blocked count.
     */
    private function get_blocked_count($date_range)
    {
        return $this->get_cached_stats('blocked_' . md5(serialize($date_range)), function() use ($date_range) {
            return EDR_Attempt_Logger::get_attempts_count([
                'status' => 'blocked',
                'date_from' => $date_range['from'],
                'date_to' => $date_range['to'],
            ]);
        });
    }

    /**
     * Get success rate percentage.
     *
     * @param array $date_range Date range array.
     * @return float Success rate percentage.
     */
    private function get_success_rate($date_range)
    {
        $total = $this->get_total_attempts($date_range);
        if ($total == 0) {
            return 0;
        }

        $allowed = $this->get_allowed_count($date_range);
        return round(($allowed / $total) * 100, 1);
    }

    /**
     * Get attempts grouped by date.
     *
     * @param array $date_range Date range array.
     * @return array Array of date => counts.
     */
    private function get_attempts_by_date($date_range)
    {
        return $this->get_cached_stats('by_date_' . md5(serialize($date_range)), function() use ($date_range) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'edr_registration_attempts';

            $query = $wpdb->prepare(
                "SELECT DATE(attempted_at) as date,
                        SUM(CASE WHEN status = 'allowed' THEN 1 ELSE 0 END) as allowed,
                        SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked
                FROM {$table_name}
                WHERE attempted_at >= %s AND attempted_at <= %s
                GROUP BY DATE(attempted_at)
                ORDER BY date ASC",
                $date_range['from'],
                $date_range['to']
            );

            return $wpdb->get_results($query, ARRAY_A);
        });
    }

    /**
     * Get top domains by attempt count.
     *
     * @param int    $limit Limit results.
     * @param string $status Status filter (allowed/blocked).
     * @param array  $date_range Date range array.
     * @return array Array of domain => count.
     */
    private function get_top_domains($limit, $status, $date_range)
    {
        return $this->get_cached_stats("top_{$status}_" . md5(serialize($date_range)), function() use ($limit, $status, $date_range) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'edr_registration_attempts';

            $query = $wpdb->prepare(
                "SELECT domain, COUNT(*) as count
                FROM {$table_name}
                WHERE status = %s
                AND attempted_at >= %s AND attempted_at <= %s
                GROUP BY domain
                ORDER BY count DESC
                LIMIT %d",
                $status,
                $date_range['from'],
                $date_range['to'],
                $limit
            );

            return $wpdb->get_results($query, ARRAY_A);
        });
    }

    /**
     * Get attempts by hour of day.
     *
     * @param array $date_range Date range array.
     * @return array Array of hour => count.
     */
    private function get_attempts_by_hour($date_range)
    {
        return $this->get_cached_stats('by_hour_' . md5(serialize($date_range)), function() use ($date_range) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'edr_registration_attempts';

            $query = $wpdb->prepare(
                "SELECT HOUR(attempted_at) as hour, COUNT(*) as count
                FROM {$table_name}
                WHERE attempted_at >= %s AND attempted_at <= %s
                GROUP BY HOUR(attempted_at)
                ORDER BY hour ASC",
                $date_range['from'],
                $date_range['to']
            );

            $results = $wpdb->get_results($query, ARRAY_A);

            // Fill in missing hours with 0
            $hourly_data = array_fill(0, 24, 0);
            foreach ($results as $row) {
                $hourly_data[(int)$row['hour']] = (int)$row['count'];
            }

            return $hourly_data;
        });
    }

    /**
     * Get attempts by day of week.
     *
     * @param array $date_range Date range array.
     * @return array Array of weekday => count.
     */
    private function get_attempts_by_weekday($date_range)
    {
        return $this->get_cached_stats('by_weekday_' . md5(serialize($date_range)), function() use ($date_range) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'edr_registration_attempts';

            $query = $wpdb->prepare(
                "SELECT DAYOFWEEK(attempted_at) as day, COUNT(*) as count
                FROM {$table_name}
                WHERE attempted_at >= %s AND attempted_at <= %s
                GROUP BY DAYOFWEEK(attempted_at)
                ORDER BY day ASC",
                $date_range['from'],
                $date_range['to']
            );

            $results = $wpdb->get_results($query, ARRAY_A);

            // Convert to day names (1=Sunday, 7=Saturday)
            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $weekday_data = [];

            // Initialize all days
            foreach ($days as $day) {
                $weekday_data[$day] = 0;
            }

            // Fill in actual data
            foreach ($results as $row) {
                $day_index = (int)$row['day'] - 1; // Convert to 0-indexed
                $weekday_data[$days[$day_index]] = (int)$row['count'];
            }

            return $weekday_data;
        });
    }

    /**
     * Get recent registration attempts.
     *
     * @param int $limit Limit results.
     * @return array Array of recent attempts.
     */
    private function get_recent_attempts($limit)
    {
        return EDR_Attempt_Logger::get_attempts([
            'limit' => $limit,
            'orderby' => 'attempted_at',
            'order' => 'DESC',
        ]);
    }

    /**
     * Get cached statistics with fallback to callback.
     *
     * @param string   $cache_key Cache key.
     * @param callable $callback Callback to get fresh data.
     * @return mixed Cached or fresh data.
     */
    private function get_cached_stats($cache_key, $callback)
    {
        $cache_key = 'edr_stats_' . $cache_key;

        // Try to get from cache
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Get fresh data
        $data = $callback();

        // Cache for 1 hour
        set_transient($cache_key, $data, HOUR_IN_SECONDS);

        return $data;
    }

    /**
     * Clear all dashboard caches.
     */
    public static function clear_cache()
    {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_edr_stats_%'
            OR option_name LIKE '_transient_timeout_edr_stats_%'"
        );
    }
}
