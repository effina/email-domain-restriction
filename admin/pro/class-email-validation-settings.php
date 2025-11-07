<?php
/**
 * Email Validation Settings
 *
 * Handles email validation service settings in admin.
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro_Admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EDR_Email_Validation_Settings
 *
 * Manages email validation settings.
 */
class EDR_Email_Validation_Settings
{
    /**
     * Email validator instance
     *
     * @var EDR_Email_Validator
     */
    private $validator;

    /**
     * Initialize email validation settings
     */
    public function init()
    {
        if (!edr_is_pro_active()) {
            return;
        }

        require_once EDR_PLUGIN_DIR . 'includes/pro/class-email-validator.php';
        $this->validator = new EDR_Email_Validator();
        $this->validator->init();

        // Register settings
        add_action('admin_init', [$this, 'register_settings']);

        // Add settings section to PRO settings page
        add_action('edr_pro_settings_sections', [$this, 'add_settings_section']);

        // AJAX handlers
        add_action('wp_ajax_edr_test_validation_api', [$this, 'ajax_test_api']);
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('edr_pro_settings', 'edr_enable_email_validation');
        register_setting('edr_pro_settings', 'edr_validation_service');
        register_setting('edr_pro_settings', 'edr_zerobounce_api_key');
        register_setting('edr_pro_settings', 'edr_kickbox_api_key');
        register_setting('edr_pro_settings', 'edr_hunter_api_key');
        register_setting('edr_pro_settings', 'edr_neverbounce_api_key');
        register_setting('edr_pro_settings', 'edr_invalid_email_message');
        register_setting('edr_pro_settings', 'edr_validation_reject_disposable');
        register_setting('edr_pro_settings', 'edr_validation_reject_free');
    }

    /**
     * Add email validation settings section
     */
    public function add_settings_section()
    {
        ?>
        <h2><?php esc_html_e('Email Validation', 'email-domain-restriction'); ?></h2>
        <p class="description">
            <?php esc_html_e('Integrate with email validation services to verify email addresses during registration.', 'email-domain-restriction'); ?>
        </p>
        <table class="form-table" role="presentation">
            <tbody>
                <?php $this->render_enable_validation(); ?>
                <?php $this->render_service_selector(); ?>
                <?php $this->render_api_keys(); ?>
                <?php $this->render_validation_rules(); ?>
                <?php $this->render_error_message(); ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render enable validation checkbox
     */
    private function render_enable_validation()
    {
        $enabled = get_option('edr_enable_email_validation', false);

        ?>
        <tr>
            <th scope="row">
                <?php esc_html_e('Email Validation', 'email-domain-restriction'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="edr_enable_email_validation" value="1" <?php checked($enabled, true); ?> />
                    <?php esc_html_e('Enable email validation during registration', 'email-domain-restriction'); ?>
                </label>
                <p class="description">
                    <?php esc_html_e('Validate email addresses using external API services to detect invalid, disposable, or risky emails.', 'email-domain-restriction'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render service selector
     */
    private function render_service_selector()
    {
        $service = get_option('edr_validation_service', '');
        $services = EDR_Email_Validator::SERVICES;

        ?>
        <tr>
            <th scope="row">
                <label for="edr_validation_service">
                    <?php esc_html_e('Validation Service', 'email-domain-restriction'); ?>
                </label>
            </th>
            <td>
                <select name="edr_validation_service" id="edr_validation_service">
                    <option value=""><?php esc_html_e('— Select Service —', 'email-domain-restriction'); ?></option>
                    <?php foreach ($services as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($service, $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php esc_html_e('Choose which email validation service to use.', 'email-domain-restriction'); ?>
                    <br>
                    <strong><?php esc_html_e('Supported Services:', 'email-domain-restriction'); ?></strong>
                    <a href="https://www.zerobounce.net/" target="_blank">ZeroBounce</a> |
                    <a href="https://kickbox.com/" target="_blank">Kickbox</a> |
                    <a href="https://hunter.io/" target="_blank">Hunter.io</a> |
                    <a href="https://neverbounce.com/" target="_blank">NeverBounce</a>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render API keys
     */
    private function render_api_keys()
    {
        $services = EDR_Email_Validator::SERVICES;

        foreach ($services as $key => $label) {
            $api_key = get_option("edr_{$key}_api_key", '');
            $row_class = $key === get_option('edr_validation_service') ? '' : 'hidden';

            ?>
            <tr class="edr-api-key-row edr-api-key-<?php echo esc_attr($key); ?> <?php echo esc_attr($row_class); ?>">
                <th scope="row">
                    <label for="edr_<?php echo esc_attr($key); ?>_api_key">
                        <?php echo esc_html($label); ?> <?php esc_html_e('API Key', 'email-domain-restriction'); ?>
                    </label>
                </th>
                <td>
                    <input type="text"
                           name="edr_<?php echo esc_attr($key); ?>_api_key"
                           id="edr_<?php echo esc_attr($key); ?>_api_key"
                           value="<?php echo esc_attr($api_key); ?>"
                           class="regular-text"
                           placeholder="<?php esc_attr_e('Enter API key', 'email-domain-restriction'); ?>">
                    <button type="button"
                            class="button edr-test-api-btn"
                            data-service="<?php echo esc_attr($key); ?>">
                        <?php esc_html_e('Test Connection', 'email-domain-restriction'); ?>
                    </button>
                    <span class="edr-api-test-result"></span>
                    <p class="description">
                        <?php
                        printf(
                            __('Get your API key from <a href="%s" target="_blank">%s</a>', 'email-domain-restriction'),
                            $this->get_service_url($key),
                            $label
                        );
                        ?>
                    </p>
                </td>
            </tr>
            <?php
        }
    }

    /**
     * Render validation rules
     */
    private function render_validation_rules()
    {
        $reject_disposable = get_option('edr_validation_reject_disposable', true);
        $reject_free = get_option('edr_validation_reject_free', false);

        ?>
        <tr>
            <th scope="row">
                <?php esc_html_e('Validation Rules', 'email-domain-restriction'); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="edr_validation_reject_disposable" value="1" <?php checked($reject_disposable, true); ?> />
                        <?php esc_html_e('Reject disposable email addresses', 'email-domain-restriction'); ?>
                    </label>
                    <br>
                    <label>
                        <input type="checkbox" name="edr_validation_reject_free" value="1" <?php checked($reject_free, true); ?> />
                        <?php esc_html_e('Reject free email addresses (Gmail, Yahoo, etc.)', 'email-domain-restriction'); ?>
                    </label>
                </fieldset>
                <p class="description">
                    <?php esc_html_e('Additional validation rules to apply.', 'email-domain-restriction'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render error message
     */
    private function render_error_message()
    {
        $message = get_option('edr_invalid_email_message', '');

        ?>
        <tr>
            <th scope="row">
                <label for="edr_invalid_email_message">
                    <?php esc_html_e('Invalid Email Message', 'email-domain-restriction'); ?>
                </label>
            </th>
            <td>
                <textarea name="edr_invalid_email_message"
                          id="edr_invalid_email_message"
                          rows="3"
                          class="large-text"
                          placeholder="<?php esc_attr_e('Leave empty to use default message', 'email-domain-restriction'); ?>"
                ><?php echo esc_textarea($message); ?></textarea>
                <p class="description">
                    <?php esc_html_e('Custom error message shown when email validation fails. Leave empty to use the default message with the validation reason.', 'email-domain-restriction'); ?>
                </p>
            </td>
        </tr>

        <style>
            .edr-api-key-row.hidden {
                display: none;
            }
            .edr-api-test-result {
                margin-left: 10px;
                font-weight: 600;
            }
            .edr-api-test-result.success {
                color: #00a32a;
            }
            .edr-api-test-result.error {
                color: #d63638;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Show/hide API key fields based on selected service
            $('#edr_validation_service').on('change', function() {
                const service = $(this).val();
                $('.edr-api-key-row').addClass('hidden');
                if (service) {
                    $('.edr-api-key-' + service).removeClass('hidden');
                }
            });

            // Test API connection
            $('.edr-test-api-btn').on('click', function() {
                const $btn = $(this);
                const $result = $btn.siblings('.edr-api-test-result');
                const service = $btn.data('service');
                const apiKey = $('#edr_' + service + '_api_key').val();

                if (!apiKey) {
                    alert('<?php esc_html_e('Please enter an API key first.', 'email-domain-restriction'); ?>');
                    return;
                }

                $btn.prop('disabled', true).text('<?php esc_html_e('Testing...', 'email-domain-restriction'); ?>');
                $result.text('').removeClass('success error');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'edr_test_validation_api',
                        nonce: '<?php echo wp_create_nonce('edr_test_validation_api'); ?>',
                        service: service,
                        api_key: apiKey
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.addClass('success').text('✓ ' + response.data.message);
                        } else {
                            $result.addClass('error').text('✗ ' + response.data.message);
                        }
                        $btn.prop('disabled', false).text('<?php esc_html_e('Test Connection', 'email-domain-restriction'); ?>');
                    },
                    error: function() {
                        $result.addClass('error').text('✗ <?php esc_html_e('Connection failed', 'email-domain-restriction'); ?>');
                        $btn.prop('disabled', false).text('<?php esc_html_e('Test Connection', 'email-domain-restriction'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Get service URL
     *
     * @param string $service Service key
     * @return string
     */
    private function get_service_url($service)
    {
        $urls = [
            'zerobounce'  => 'https://www.zerobounce.net/',
            'kickbox'     => 'https://kickbox.com/',
            'hunter'      => 'https://hunter.io/',
            'neverbounce' => 'https://neverbounce.com/',
        ];

        return $urls[$service] ?? '#';
    }

    /**
     * AJAX: Test API connection
     */
    public function ajax_test_api()
    {
        check_ajax_referer('edr_test_validation_api', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'email-domain-restriction')]);
        }

        $service = sanitize_text_field($_POST['service']);
        $api_key = sanitize_text_field($_POST['api_key']);

        if (empty($service) || empty($api_key)) {
            wp_send_json_error(['message' => __('Service and API key are required.', 'email-domain-restriction')]);
        }

        $result = $this->validator->test_api_connection($service, $api_key);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }
}
