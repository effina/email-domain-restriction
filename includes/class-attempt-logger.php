<?php
/**
 * Registration attempt logging.
 *
 * @package Email_Domain_Restriction
 */

/**
 * Attempt logger class.
 */
class EDR_Attempt_Logger
{
    /**
     * Log a registration attempt.
     *
     * @param string $email Email address.
     * @param string $domain Domain.
     * @param string $status Status (allowed/blocked).
     * @return int|false Insert ID on success, false on failure.
     */
    public static function log_attempt($email, $domain, $status)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'edr_registration_attempts';

        // Get user information
        $ip_address = self::get_user_ip();
        $user_agent = self::get_user_agent();

        // Insert log entry
        $result = $wpdb->insert(
            $table_name,
            [
                'email' => sanitize_email($email),
                'domain' => sanitize_text_field($domain),
                'status' => sanitize_text_field($status),
                'ip_address' => sanitize_text_field($ip_address),
                'user_agent' => sanitize_text_field($user_agent),
                'attempted_at' => current_time('mysql'),
            ],
            [
                '%s', // email
                '%s', // domain
                '%s', // status
                '%s', // ip_address
                '%s', // user_agent
                '%s', // attempted_at
            ]
        );

        return $result !== false ? $wpdb->insert_id : false;
    }

    /**
     * Get registration attempts with optional filtering.
     *
     * @param array $args Query arguments.
     * @return array Array of attempt records.
     */
    public static function get_attempts($args = [])
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'edr_registration_attempts';

        // Default arguments
        $defaults = [
            'status' => '', // 'allowed', 'blocked', or empty for all
            'domain' => '',
            'date_from' => '',
            'date_to' => '',
            'limit' => 25,
            'offset' => 0,
            'orderby' => 'attempted_at',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);

        // Build WHERE clause
        $where_clauses = ['1=1'];
        $where_values = [];

        if (!empty($args['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        if (!empty($args['domain'])) {
            $where_clauses[] = 'domain LIKE %s';
            $where_values[] = '%' . $wpdb->esc_like($args['domain']) . '%';
        }

        if (!empty($args['date_from'])) {
            $where_clauses[] = 'attempted_at >= %s';
            $where_values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where_clauses[] = 'attempted_at <= %s';
            $where_values[] = $args['date_to'];
        }

        $where = implode(' AND ', $where_clauses);

        // Build ORDER BY clause
        $allowed_orderby = ['attempted_at', 'email', 'domain', 'status', 'ip_address'];
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'attempted_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        // Build query
        $query = "SELECT * FROM {$table_name} WHERE {$where} ORDER BY {$orderby} {$order}";

        // Add limit
        if ($args['limit'] > 0) {
            $query .= $wpdb->prepare(' LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }

        // Prepare query if we have WHERE values
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get total count of attempts.
     *
     * @param array $args Query arguments (same as get_attempts).
     * @return int Total count.
     */
    public static function get_attempts_count($args = [])
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'edr_registration_attempts';

        // Build WHERE clause (same as get_attempts)
        $where_clauses = ['1=1'];
        $where_values = [];

        if (!empty($args['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        if (!empty($args['domain'])) {
            $where_clauses[] = 'domain LIKE %s';
            $where_values[] = '%' . $wpdb->esc_like($args['domain']) . '%';
        }

        if (!empty($args['date_from'])) {
            $where_clauses[] = 'attempted_at >= %s';
            $where_values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where_clauses[] = 'attempted_at <= %s';
            $where_values[] = $args['date_to'];
        }

        $where = implode(' AND ', $where_clauses);

        $query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where}";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return (int) $wpdb->get_var($query);
    }

    /**
     * Delete old log entries.
     *
     * @param int $days Number of days to keep.
     * @return int|false Number of rows deleted or false on failure.
     */
    public static function cleanup_old_logs($days = 30)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'edr_registration_attempts';
        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE attempted_at < %s",
                $date_threshold
            )
        );
    }

    /**
     * Export attempts to CSV.
     *
     * @param array $args Query arguments (same as get_attempts).
     * @return string CSV content.
     */
    public static function export_log_csv($args = [])
    {
        $attempts = self::get_attempts(array_merge($args, ['limit' => 0]));

        // CSV header
        $csv = "Date/Time,Email,Domain,Status,IP Address,User Agent\n";

        // CSV rows
        foreach ($attempts as $attempt) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s"' . "\n",
                $attempt['attempted_at'],
                $attempt['email'],
                $attempt['domain'],
                $attempt['status'],
                $attempt['ip_address'],
                str_replace('"', '""', $attempt['user_agent']) // Escape quotes
            );
        }

        return $csv;
    }

    /**
     * Get user IP address.
     *
     * @return string IP address.
     */
    private static function get_user_ip()
    {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Validate IP
        $ip = filter_var($ip, FILTER_VALIDATE_IP);

        return $ip !== false ? $ip : '0.0.0.0';
    }

    /**
     * Get user agent string.
     *
     * @return string User agent.
     */
    private static function get_user_agent()
    {
        return !empty($_SERVER['HTTP_USER_AGENT'])
            ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255)
            : '';
    }
}
