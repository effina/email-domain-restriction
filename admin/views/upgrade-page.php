<?php
/**
 * Upgrade Page View
 *
 * @package Email_Domain_Restriction
 * @subpackage Admin_Views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$freemius = EDR_Pro_Features::get_freemius();
$upgrade_url = $freemius ? $freemius->get_upgrade_url() : 'https://codeeffina.com/wordpress/plugins/email-domain-restriction/pricing';
?>

<div class="wrap edr-upgrade-page">
    <h1><?php esc_html_e('Upgrade to Email Domain Restriction PRO', 'email-domain-restriction'); ?></h1>

    <div class="edr-upgrade-hero">
        <h2><?php esc_html_e('Unlock Premium Features', 'email-domain-restriction'); ?></h2>
        <p class="edr-hero-description">
            <?php esc_html_e('Take your email domain restrictions to the next level with WooCommerce integration, BuddyPress support, advanced analytics, and more!', 'email-domain-restriction'); ?>
        </p>
        <p>
            <a href="<?php echo esc_url($upgrade_url); ?>" class="button button-primary button-hero">
                <?php esc_html_e('Upgrade Now', 'email-domain-restriction'); ?>
            </a>
        </p>
    </div>

    <div class="edr-features-grid">
        <h2><?php esc_html_e('PRO Features', 'email-domain-restriction'); ?></h2>

        <div class="edr-feature-cards">
            <?php
            $features = [
                [
                    'title' => __('WooCommerce Integration', 'email-domain-restriction'),
                    'description' => __('Validate email domains during checkout and My Account registration. Perfect for B2B stores and wholesale sites.', 'email-domain-restriction'),
                    'icon' => 'cart',
                ],
                [
                    'title' => __('BuddyPress Integration', 'email-domain-restriction'),
                    'description' => __('Control who can join your BuddyPress community. Group-level domain restrictions and member type assignment.', 'email-domain-restriction'),
                    'icon' => 'groups',
                ],
                [
                    'title' => __('Role-Based Restrictions', 'email-domain-restriction'),
                    'description' => __('Automatically assign user roles based on email domain. Map @company.com to Wholesale Customer role.', 'email-domain-restriction'),
                    'icon' => 'admin-users',
                ],
                [
                    'title' => __('Advanced Analytics', 'email-domain-restriction'),
                    'description' => __('Geographic distribution, conversion funnels, PDF exports, and scheduled email reports.', 'email-domain-restriction'),
                    'icon' => 'chart-area',
                ],
                [
                    'title' => __('Rate Limiting & Anti-Abuse', 'email-domain-restriction'),
                    'description' => __('Protect against spam with domain and IP-based rate limiting. Disposable email detection.', 'email-domain-restriction'),
                    'icon' => 'shield',
                ],
                [
                    'title' => __('Geolocation Restrictions', 'email-domain-restriction'),
                    'description' => __('Combine domain restrictions with country-based access control. VPN and proxy detection.', 'email-domain-restriction'),
                    'icon' => 'location',
                ],
                [
                    'title' => __('Domain Groups', 'email-domain-restriction'),
                    'description' => __('Organize domains into groups. Temporary access with start and end dates. Conditional rules.', 'email-domain-restriction'),
                    'icon' => 'category',
                ],
                [
                    'title' => __('Webhooks & API', 'email-domain-restriction'),
                    'description' => __('Integrate with external systems. REST API for domain management. Slack and Discord notifications.', 'email-domain-restriction'),
                    'icon' => 'rest-api',
                ],
                [
                    'title' => __('Advanced Verification', 'email-domain-restriction'),
                    'description' => __('SMTP validation, custom email templates, SMS verification option via Twilio integration.', 'email-domain-restriction'),
                    'icon' => 'yes-alt',
                ],
                [
                    'title' => __('Multi-Network Support', 'email-domain-restriction'),
                    'description' => __('Full multisite support with network-level controls and site-specific overrides.', 'email-domain-restriction'),
                    'icon' => 'networking',
                ],
                [
                    'title' => __('GDPR Compliance', 'email-domain-restriction'),
                    'description' => __('Automatic PII data purging, consent tracking, audit logging, and compliance reporting.', 'email-domain-restriction'),
                    'icon' => 'privacy',
                ],
                [
                    'title' => __('Priority Support', 'email-domain-restriction'),
                    'description' => __('Get help when you need it. Email and phone support with faster response times.', 'email-domain-restriction'),
                    'icon' => 'sos',
                ],
            ];

            foreach ($features as $feature): ?>
                <div class="edr-feature-card">
                    <span class="dashicons dashicons-<?php echo esc_attr($feature['icon']); ?>"></span>
                    <h3><?php echo esc_html($feature['title']); ?></h3>
                    <p><?php echo esc_html($feature['description']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="edr-pricing-section">
        <h2><?php esc_html_e('Simple, Transparent Pricing', 'email-domain-restriction'); ?></h2>

        <div class="edr-pricing-cards">
            <div class="edr-pricing-card">
                <h3><?php esc_html_e('Single Site', 'email-domain-restriction'); ?></h3>
                <div class="edr-price">
                    <span class="edr-currency">$</span>
                    <span class="edr-amount">79</span>
                    <span class="edr-period">/year</span>
                </div>
                <ul>
                    <li>1 website</li>
                    <li>All PRO features</li>
                    <li>1 year of updates</li>
                    <li>Priority email support</li>
                </ul>
                <a href="<?php echo esc_url($upgrade_url); ?>" class="button button-primary">
                    <?php esc_html_e('Get Started', 'email-domain-restriction'); ?>
                </a>
            </div>

            <div class="edr-pricing-card edr-popular">
                <div class="edr-popular-badge"><?php esc_html_e('Most Popular', 'email-domain-restriction'); ?></div>
                <h3><?php esc_html_e('5-Site License', 'email-domain-restriction'); ?></h3>
                <div class="edr-price">
                    <span class="edr-currency">$</span>
                    <span class="edr-amount">149</span>
                    <span class="edr-period">/year</span>
                </div>
                <ul>
                    <li>Up to 5 websites</li>
                    <li>All PRO features</li>
                    <li>1 year of updates</li>
                    <li>Priority email support</li>
                </ul>
                <a href="<?php echo esc_url($upgrade_url); ?>" class="button button-primary">
                    <?php esc_html_e('Get Started', 'email-domain-restriction'); ?>
                </a>
            </div>

            <div class="edr-pricing-card">
                <h3><?php esc_html_e('Unlimited', 'email-domain-restriction'); ?></h3>
                <div class="edr-price">
                    <span class="edr-currency">$</span>
                    <span class="edr-amount">299</span>
                    <span class="edr-period">/year</span>
                </div>
                <ul>
                    <li>Unlimited websites</li>
                    <li>All PRO features</li>
                    <li>1 year of updates</li>
                    <li>Priority email + phone support</li>
                </ul>
                <a href="<?php echo esc_url($upgrade_url); ?>" class="button button-primary">
                    <?php esc_html_e('Get Started', 'email-domain-restriction'); ?>
                </a>
            </div>
        </div>

        <p class="edr-money-back-guarantee">
            <?php esc_html_e('14-day money-back guarantee. No questions asked.', 'email-domain-restriction'); ?>
        </p>
    </div>
</div>

<style>
.edr-upgrade-page {
    max-width: 1200px;
}

.edr-upgrade-hero {
    background: linear-gradient(135deg, #0073aa 0%, #005177 100%);
    color: white;
    padding: 40px;
    border-radius: 8px;
    text-align: center;
    margin: 20px 0 40px;
}

.edr-upgrade-hero h2 {
    color: white;
    font-size: 32px;
    margin-bottom: 15px;
}

.edr-hero-description {
    font-size: 18px;
    margin-bottom: 30px;
    opacity: 0.95;
}

.edr-feature-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.edr-feature-card {
    background: white;
    padding: 25px;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.edr-feature-card .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #0073aa;
    margin-bottom: 15px;
}

.edr-feature-card h3 {
    margin: 0 0 10px;
    font-size: 18px;
}

.edr-feature-card p {
    color: #666;
    line-height: 1.6;
}

.edr-pricing-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.edr-pricing-card {
    background: white;
    padding: 30px;
    border: 2px solid #ddd;
    border-radius: 8px;
    text-align: center;
    position: relative;
}

.edr-pricing-card.edr-popular {
    border-color: #0073aa;
    box-shadow: 0 4px 16px rgba(0, 115, 170, 0.2);
}

.edr-popular-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: #0073aa;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.edr-price {
    margin: 20px 0;
    font-size: 48px;
    font-weight: 700;
    color: #0073aa;
}

.edr-currency {
    font-size: 24px;
    vertical-align: super;
}

.edr-period {
    font-size: 16px;
    color: #666;
    font-weight: 400;
}

.edr-pricing-card ul {
    list-style: none;
    padding: 0;
    margin: 20px 0;
    text-align: left;
}

.edr-pricing-card li {
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f1;
}

.edr-money-back-guarantee {
    text-align: center;
    color: #666;
    font-style: italic;
    margin-top: 30px;
}
</style>
