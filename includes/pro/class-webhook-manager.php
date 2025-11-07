<?php
/**
 * Webhook Manager
 *
 * Handles webhook notifications for events.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Webhook_Manager
 *
 * Manages webhooks for event notifications.
 */
class EDR_Webhook_Manager
{
    /**
     * Initialize webhook manager
     */
    public function init()
    {
        if (!edr_is_pro_active()) {
            return;
        }

        // Hook into registration events
        add_action('edr_registration_allowed', [$this, 'trigger_allowed_webhook'], 10, 2);
        add_action('edr_registration_blocked', [$this, 'trigger_blocked_webhook'], 10, 2);
    }

    /**
     * Trigger webhook for allowed registration
     *
     * @param string $email Email address
     * @param array $data Registration data
     */
    public function trigger_allowed_webhook($email, $data)
    {
        $this->send_webhooks('registration.allowed', [
            'event'      => 'registration.allowed',
            'email'      => $email,
            'domain'     => substr(strrchr($email, '@'), 1),
            'ip_address' => $data['ip_address'] ?? '',
            'source'     => $data['source'] ?? 'wordpress',
            'timestamp'  => current_time('mysql'),
        ]);
    }

    /**
     * Trigger webhook for blocked registration
     *
     * @param string $email Email address
     * @param array $data Registration data
     */
    public function trigger_blocked_webhook($email, $data)
    {
        $this->send_webhooks('registration.blocked', [
            'event'      => 'registration.blocked',
            'email'      => $email,
            'domain'     => substr(strrchr($email, '@'), 1),
            'reason'     => $data['reason'] ?? 'Domain not whitelisted',
            'ip_address' => $data['ip_address'] ?? '',
            'source'     => $data['source'] ?? 'wordpress',
            'timestamp'  => current_time('mysql'),
        ]);
    }

    /**
     * Send webhooks for event
     *
     * @param string $event Event type
     * @param array $payload Payload data
     */
    private function send_webhooks($event, $payload)
    {
        $webhooks = $this->get_active_webhooks($event);

        foreach ($webhooks as $webhook) {
            $this->send_webhook($webhook, $payload);
        }
    }

    /**
     * Send individual webhook
     *
     * @param array $webhook Webhook configuration
     * @param array $payload Payload data
     */
    private function send_webhook($webhook, $payload)
    {
        $signature = $this->generate_signature($payload, $webhook['secret_key']);

        $response = wp_remote_post($webhook['url'], [
            'timeout' => 10,
            'headers' => [
                'Content-Type'     => 'application/json',
                'X-EDR-Signature'  => $signature,
                'X-EDR-Event'      => $payload['event'],
            ],
            'body'    => wp_json_encode($payload),
        ]);

        // Log webhook delivery
        $this->log_webhook_delivery($webhook['id'], $response);
    }

    /**
     * Get active webhooks for event
     *
     * @param string $event Event type
     * @return array
     */
    private function get_active_webhooks($event)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_webhooks';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return [];
        }

        $webhooks = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
            WHERE is_active = 1
            AND (events = 'all' OR events LIKE %s)",
            '%' . $wpdb->esc_like($event) . '%'
        ), ARRAY_A);

        return $webhooks ?: [];
    }

    /**
     * Generate HMAC signature
     *
     * @param array $payload Payload data
     * @param string $secret Secret key
     * @return string
     */
    private function generate_signature($payload, $secret)
    {
        return hash_hmac('sha256', wp_json_encode($payload), $secret);
    }

    /**
     * Log webhook delivery
     *
     * @param int $webhook_id Webhook ID
     * @param array|WP_Error $response Response
     */
    private function log_webhook_delivery($webhook_id, $response)
    {
        $status = is_wp_error($response) ? 'failed' : 'success';
        $response_code = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);

        // Could log to a webhook_logs table if needed
        do_action('edr_webhook_delivered', $webhook_id, $status, $response_code);
    }

    /**
     * Add webhook
     *
     * @param array $data Webhook data
     * @return int|false Webhook ID or false
     */
    public function add_webhook($data)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_webhooks';

        $result = $wpdb->insert(
            $table,
            [
                'name'       => sanitize_text_field($data['name']),
                'url'        => esc_url_raw($data['url']),
                'events'     => sanitize_text_field($data['events']),
                'secret_key' => wp_generate_password(32, false),
                'is_active'  => 1,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s']
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get all webhooks
     *
     * @return array
     */
    public function get_all_webhooks()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_webhooks';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return [];
        }

        $webhooks = $wpdb->get_results(
            "SELECT * FROM $table ORDER BY created_at DESC",
            ARRAY_A
        );

        return $webhooks ?: [];
    }

    /**
     * Delete webhook
     *
     * @param int $webhook_id Webhook ID
     * @return bool
     */
    public function delete_webhook($webhook_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_webhooks';

        $result = $wpdb->delete(
            $table,
            ['id' => absint($webhook_id)],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Toggle webhook status
     *
     * @param int $webhook_id Webhook ID
     * @return bool
     */
    public function toggle_webhook($webhook_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'edr_webhooks';

        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM $table WHERE id = %d",
            $webhook_id
        ));

        if ($current === null) {
            return false;
        }

        $wpdb->update(
            $table,
            ['is_active' => $current ? 0 : 1],
            ['id' => $webhook_id],
            ['%d'],
            ['%d']
        );

        return true;
    }
}
