<?php
/**
 * License Page View
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro_Admin_Views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$license_info = EDR_License_Manager::get_license_info();
$activation_limit = EDR_License_Manager::get_activation_limit();
$freemius = EDR_Pro_Features::get_freemius();
?>

<div class="wrap">
    <h1><?php esc_html_e('License Management', 'email-domain-restriction'); ?></h1>

    <div class="edr-license-page">
        <?php if ($license_info && $license_info['status'] === 'active'): ?>
            <!-- Active License -->
            <div class="notice notice-success inline">
                <p>
                    <strong><?php esc_html_e('License Active', 'email-domain-restriction'); ?></strong><br>
                    <?php echo esc_html(EDR_License_Manager::get_license_status_message()); ?>
                </p>
            </div>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Plan', 'email-domain-restriction'); ?>
                        </th>
                        <td>
                            <?php echo esc_html($license_info['plan']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('License Key', 'email-domain-restriction'); ?>
                        </th>
                        <td>
                            <code><?php echo esc_html(substr($license_info['key'], 0, 20) . '...'); ?></code>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Status', 'email-domain-restriction'); ?>
                        </th>
                        <td>
                            <?php if ($license_info['is_expired']): ?>
                                <span class="edr-license-status expired"><?php esc_html_e('Expired', 'email-domain-restriction'); ?></span>
                            <?php else: ?>
                                <span class="edr-license-status active"><?php esc_html_e('Active', 'email-domain-restriction'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Activated', 'email-domain-restriction'); ?>
                        </th>
                        <td>
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($license_info['activated']))); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Expires', 'email-domain-restriction'); ?>
                        </th>
                        <td>
                            <?php
                            if ($license_info['expires']) {
                                echo esc_html(date_i18n(get_option('date_format'), strtotime($license_info['expires'])));
                            } else {
                                esc_html_e('Never (Lifetime)', 'email-domain-restriction');
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Activations', 'email-domain-restriction'); ?>
                        </th>
                        <td>
                            <?php
                            printf(
                                esc_html__('%d of %d sites activated', 'email-domain-restriction'),
                                $activation_limit['current'],
                                $activation_limit['max']
                            );
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <?php if ($freemius): ?>
                    <a href="<?php echo esc_url($freemius->get_account_url()); ?>" class="button button-primary">
                        <?php esc_html_e('Manage License', 'email-domain-restriction'); ?>
                    </a>
                    <?php if ($license_info['is_expired']): ?>
                        <a href="<?php echo esc_url(EDR_License_Manager::get_renewal_url()); ?>" class="button button-secondary">
                            <?php esc_html_e('Renew License', 'email-domain-restriction'); ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </p>

        <?php else: ?>
            <!-- No Active License -->
            <div class="notice notice-warning inline">
                <p>
                    <strong><?php esc_html_e('No Active License', 'email-domain-restriction'); ?></strong><br>
                    <?php esc_html_e('You need an active license to access PRO features.', 'email-domain-restriction'); ?>
                </p>
            </div>

            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=email-domain-restriction-pricing')); ?>" class="button button-primary button-hero">
                    <?php esc_html_e('Upgrade to PRO', 'email-domain-restriction'); ?>
                </a>
            </p>

            <h2><?php esc_html_e('PRO Features', 'email-domain-restriction'); ?></h2>
            <ul class="edr-pro-features-list">
                <?php foreach (EDR_Pro_Features::get_pro_features() as $feature => $label): ?>
                    <li>
                        <span class="dashicons dashicons-yes"></span>
                        <?php echo esc_html($label); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<style>
.edr-license-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-weight: 600;
    font-size: 12px;
}

.edr-license-status.active {
    background: #46b450;
    color: #fff;
}

.edr-license-status.expired {
    background: #dc3232;
    color: #fff;
}

.edr-pro-features-list {
    list-style: none;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 10px;
}

.edr-pro-features-list li {
    padding: 10px;
    background: #f0f0f1;
    border-radius: 4px;
}

.edr-pro-features-list .dashicons {
    color: #46b450;
    margin-right: 5px;
}
</style>
