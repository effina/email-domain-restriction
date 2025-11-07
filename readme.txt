=== Email Domain Restriction ===
Contributors: codeeffina
Tags: email, domain, whitelist, registration, membership
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 8.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Control WordPress registrations by whitelisting specific email domains. Perfect for membership sites, schools, and B2B platforms.

== Description ==

Email Domain Restriction allows you to control who can register on your WordPress site by restricting registrations to specific email domains. Perfect for membership sites, educational institutions, corporate intranets, and B2B platforms.

**FREE Version Features:**

* ✅ Domain whitelisting (exact and wildcard patterns like *.company.com)
* ✅ WordPress registration validation
* ✅ Ultimate Member registration support
* ✅ Basic analytics dashboard
* ✅ Registration attempt logging
* ✅ Export domain list
* ✅ Custom error messages
* ✅ Admin dashboard with statistics

**PRO Version Features:**

Upgrade to PRO for advanced integrations and analytics:

* 🚀 **WooCommerce Integration** - Validate domains during checkout and My Account registration with B2B mode
* 🚀 **BuddyPress Integration** - Control community access with member type auto-assignment and group restrictions
* 🚀 **Role-Based Domain Mapping** - Automatically assign WordPress roles based on email domain with wildcard support
* 🚀 **Advanced Analytics Dashboard** - Beautiful Chart.js visualizations with time-series, funnels, and geography
* 🚀 **Email Validation APIs** - Real-time validation via ZeroBounce, Kickbox, Hunter.io & NeverBounce
* 🚀 **Rate Limiting** - Prevent spam with configurable domain/IP limits and cooldowns
* 🚀 **Geolocation Restrictions** - Allow or block registrations from specific countries
* 🚀 **Webhook Notifications** - HTTP webhooks with HMAC security for Zapier/Make integration
* 🚀 **CSV/PDF Export** - Export data and generate professional reports with scheduling
* 🚀 **Domain Groups** - Organize domains into groups for easier management
* 🚀 **Audit Logging** - Complete audit trail for compliance and security

[Learn more about PRO features →](https://codeeffina.com/email-domain-restriction-pro)

**Use Cases:**

* **Membership Sites**: Restrict registrations to members with company email addresses
* **Educational Institutions**: Allow only students with .edu email domains
* **Corporate Intranets**: Limit access to employees with corporate email domains
* **B2B Platforms**: Validate business email addresses and block free providers
* **Private Communities**: Control who can join your community by email domain
* **WooCommerce Stores**: Restrict wholesale accounts to verified business domains

== Installation ==

**Automatic Installation:**

1. Log in to your WordPress admin panel
2. Navigate to Plugins → Add New
3. Search for "Email Domain Restriction"
4. Click "Install Now" and then "Activate"

**Manual Installation:**

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to Plugins → Add New → Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin

**Configuration:**

1. Navigate to Email Domain Restriction → Settings
2. Add your whitelisted domains (one per line)
3. Use wildcards for subdomains: `*.company.com`
4. Customize error messages
5. Save changes

== Frequently Asked Questions ==

= What email domains can I whitelist? =

You can whitelist any email domain, including:
* Exact domains: `company.com`
* Wildcard subdomains: `*.company.com`
* Multiple domains: Add one per line

= Does this work with WooCommerce? =

Basic validation works with WooCommerce in the FREE version. PRO version adds dedicated WooCommerce integration with checkout validation, My Account support, B2B mode, and customer metadata tracking.

= Can I restrict registrations by country? =

Yes, the PRO version includes geolocation features that allow you to allow or block registrations from specific countries using IP geolocation.

= Does this work with membership plugins? =

Yes! FREE version includes Ultimate Member support. PRO version adds BuddyPress integration with member types and group restrictions.

= Can I automatically assign user roles based on domain? =

Yes, the PRO version includes advanced role-based domain mapping with wildcard support, priority-based conflict resolution, and import/export capabilities.

= How do I validate email addresses in real-time? =

The PRO version integrates with 4 premium email validation services (ZeroBounce, Kickbox, Hunter.io, NeverBounce) to validate emails, detect disposable addresses, and check reputation scores during registration.

= Can I export registration data? =

Yes! FREE version allows basic domain list export. PRO version includes CSV/PDF export with custom filters, scheduled reports, and automated email delivery.

= Is this GDPR compliant? =

The plugin logs registration attempts for security and analytics. PRO version includes configurable data retention policies (0-3650 days) to help with GDPR compliance.

= Does this prevent spam registrations? =

Yes! The PRO version includes rate limiting (domain + IP based) with configurable limits and cooldown periods to prevent spam and abuse.

= Can I get notifications when registrations are blocked? =

The PRO version includes webhook notifications with HMAC security that can integrate with Zapier, Make, Slack, or custom systems to notify you of registration events.

== Screenshots ==

1. Dashboard - Overview of registration attempts and statistics
2. Domain Whitelist - Manage allowed email domains with wildcard support
3. Registration Attempts Log - View and filter all registration attempts
4. Settings - Configure error messages and validation options
5. Analytics Dashboard (PRO) - Beautiful Chart.js visualizations
6. Role Mapping (PRO) - Automatic role assignment by domain

== Changelog ==

= 1.0.0 =
* Initial release
* Domain whitelisting with wildcard support
* WordPress registration validation
* Ultimate Member integration
* Basic analytics dashboard
* Registration attempt logging
* Export capabilities
* Admin dashboard with statistics

== Upgrade Notice ==

= 1.0.0 =
Initial release of Email Domain Restriction plugin with domain whitelisting, WordPress registration validation, and analytics.

== Support ==

For support, feature requests, and bug reports:

* Documentation: https://codeeffina.com/docs/email-domain-restriction
* Support: https://codeeffina.com/support
* PRO Features: https://codeeffina.com/email-domain-restriction-pro

== Privacy Policy ==

This plugin logs registration attempts including email addresses, IP addresses, and timestamps for security and analytics purposes. This data is stored in your WordPress database and can be managed through the plugin's settings.

PRO version includes configurable data retention policies to comply with data protection regulations like GDPR.
