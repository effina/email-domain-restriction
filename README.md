# Email Domain Restriction for WordPress

A comprehensive WordPress plugin that restricts user registration to whitelisted email domains with email verification and advanced analytics.

## Features

### Core Functionality
- **Domain Whitelisting**: Only allow registrations from approved email domains
- **Wildcard Support**: Use `*.example.com` to allow all subdomains
- **Email Verification**: Send confirmation emails to verify user email addresses (single-site)
- **Registration Logging**: Track all registration attempts (allowed and blocked)
- **Admin Dashboard**: Visual analytics and statistics
- **Multisite Compatible**: Works on both single-site and multisite installations

### Admin Features
- **Statistics Dashboard**: Visual charts and analytics
  - Total attempts, success rate, blocked registrations
  - Time-series data (attempts over time)
  - Top domains analysis
  - Activity by day of week and hour
- **Domain Management**: Easy-to-use interface for managing whitelisted domains
- **Bulk Import/Export**: CSV support for managing large domain lists
- **Registration Log**: Filterable, searchable log of all attempts
- **Configurable Settings**: Customize messages, retention periods, and verification settings

### Security
- CSRF protection on all forms
- SQL injection prevention
- Input sanitization and validation
- Capability checks (admin-only access)
- Secure email verification tokens

## Installation

### Automatic Installation (Recommended)

1. Download the latest version from [https://codeeffina.com/wordpress/plugins/email-domain-restriction](https://codeeffina.com/wordpress/plugins/email-domain-restriction)
2. In WordPress admin, go to Plugins > Add New
3. Click "Upload Plugin" at the top
4. Select the downloaded ZIP file and click "Install Now"
5. Click "Activate Plugin"

### Manual Installation

1. Download the latest version from [https://codeeffina.com/wordpress/plugins/email-domain-restriction](https://codeeffina.com/wordpress/plugins/email-domain-restriction)
2. Upload the `email-domain-restriction` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress

### Initial Setup

1. Navigate to Email Domain Restriction in the admin menu
2. Click "Domain Whitelist" to add your first approved domain
3. Configure settings as needed in the Settings tab
4. Test registration with a whitelisted and non-whitelisted email

## Updates

This plugin includes automatic update checking from CodeEffina's update server.

### How Updates Work

- The plugin checks for updates every 12 hours
- Update notifications appear in WordPress admin (Plugins page and Dashboard)
- One-click updates from the WordPress admin interface
- No WordPress.org account or repository needed

### Update Process

1. When a new version is available, you'll see an update notification
2. Click "Update Now" on the Plugins page
3. WordPress downloads and installs the update automatically
4. Your settings and data are preserved during updates

### Manual Updates

If automatic updates don't work:

1. Download the latest version from [https://codeeffina.com/wordpress/plugins/email-domain-restriction](https://codeeffina.com/wordpress/plugins/email-domain-restriction)
2. Deactivate the current plugin
3. Delete the old plugin files
4. Upload and activate the new version
5. Your settings and data will be preserved (stored in database)

### Update Server

- Update metadata: `https://codeeffina.com/wordpress/plugins/email-domain-restriction/metadata/info.json`
- Update checks use the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) library
- Hosted on Amazon S3 for reliability and performance

## Usage

### Adding Domains

**Individual Domains:**
1. Go to Settings > Email Domain Restriction > Domain Whitelist
2. Enter a domain (e.g., `example.com` or `*.example.com`)
3. Click "Add Domain"

**Bulk Import:**
1. Create a CSV file with one domain per line
2. Go to Domain Whitelist tab
3. Upload the CSV file
4. Click "Import Domains"

### Domain Formats

- **Exact match**: `example.com` - Only allows `user@example.com`
- **Wildcard**: `*.example.com` - Allows `user@sub.example.com`, `user@another.example.com`, etc.

### Viewing Statistics

1. Go to Settings > Email Domain Restriction (Dashboard)
2. Select date range (7, 30, 90 days, or all time)
3. View charts and recent activity

### Managing Logs

1. Go to Settings > Email Domain Restriction > Registration Log
2. Filter by status, domain, or date range
3. Export to CSV for analysis
4. Clear old logs to maintain database size

### Configuring Settings

1. Go to Settings > Email Domain Restriction > Settings
2. Configure:
   - Log retention period
   - Email verification (enable/disable)
   - Verification token expiry
   - Custom error messages
   - Default user role

## Email Verification

### Single-Site

The plugin implements email verification for single-site WordPress installations:

1. User registers with whitelisted domain
2. Account created with unverified status
3. Verification email sent with unique link
4. User clicks link to verify email
5. Account activated, user can login

### Multisite

For multisite installations, the plugin uses WordPress's native email verification system while still enforcing domain restrictions.

## Ultimate Member Compatibility

The plugin includes full compatibility with Ultimate Member registration forms.

### How It Works

- **Domain Validation**: Automatically validates email domains on UM registration forms
- **Registration Logging**: Tracks both allowed and blocked UM registration attempts
- **Email Verification**:
  - If UM's "Require Email Activation" is enabled, UM handles verification
  - If UM's email activation is disabled, our plugin handles verification
  - No conflicts between the two systems

### Requirements

- Ultimate Member plugin installed and activated
- No additional configuration needed - integration works automatically

### What's Supported

- All UM registration forms
- UM email activation system
- UM user roles and status management
- UM custom registration fields

### Admin Notice

When both UM email activation and our plugin's email verification are enabled, an informational notice appears on the plugin admin pages explaining that UM's system will be used for UM registrations.

## Hooks and Filters

### WordPress Core Hooks

The plugin uses standard WordPress hooks:

- `registration_errors` - Validates email domains during registration
- `user_register` - Logs successful registrations
- `wp_authenticate_user` - Blocks unverified users from logging in

### Ultimate Member Hooks

For Ultimate Member integration:

- `um_submit_form_errors_hook__registration` - Validates email domains on UM forms
- `um_registration_complete` - Logs successful UM registrations

### Custom Filters

- `edr_skip_email_verification` - Filter to skip email verification for specific users
  ```php
  apply_filters('edr_skip_email_verification', false, $user_id)
  ```

## Database Tables

The plugin creates one custom table:

**`wp_edr_registration_attempts`**
- Stores all registration attempts
- Includes email, domain, status, IP, user agent, and timestamp
- Indexed for fast queries

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## Frequently Asked Questions

**Q: Can I use wildcards for subdomains?**
A: Yes, use `*.example.com` to allow all subdomains.

**Q: Will this work with WooCommerce/BuddyPress?**
A: Yes, the plugin hooks into WordPress core registration, which these plugins use.

**Q: Does this work with Ultimate Member?**
A: Yes! The plugin includes full Ultimate Member integration. Domain validation works automatically on all UM registration forms, and the plugin intelligently handles email verification to avoid conflicts with UM's built-in system.

**Q: How do I export my domain list?**
A: Go to Domain Whitelist tab and click "Download CSV".

**Q: Can I customize the blocked domain error message?**
A: Yes, go to Settings tab and edit "Blocked Domain Message".

**Q: Does this work on multisite?**
A: Yes, it works on both single-site and multisite installations.

**Q: How long are logs kept?**
A: By default, 30 days. You can adjust this in Settings.

## Changelog

### 1.0.0
- Initial release
- Domain whitelisting with wildcard support
- Email verification for single-site
- Registration attempt logging
- Statistics dashboard with charts
- Bulk import/export
- Multisite compatible

## Developer Documentation

### Deploying Updates

For information on deploying plugin updates to S3, see [deploy/S3-DEPLOYMENT-GUIDE.md](deploy/S3-DEPLOYMENT-GUIDE.md).

This guide covers:
- Setting up S3 bucket and CloudFront
- Uploading releases and metadata
- Configuring automatic updates
- Troubleshooting update issues

### Plugin Structure

```
email-domain-restriction/
├── admin/                      # Admin UI components
├── includes/                   # Core plugin classes
│   ├── integrations/          # Third-party integrations (Ultimate Member)
│   └── class-updater.php      # Custom update system
├── vendor/                     # Third-party libraries
│   └── plugin-update-checker/ # Update checker library
├── deploy/                     # Deployment files (not included in distribution)
│   ├── homepage/              # Plugin homepage HTML/CSS
│   ├── metadata/              # Update metadata (info.json)
│   └── S3-DEPLOYMENT-GUIDE.md # Deployment documentation
└── email-domain-restriction.php # Main plugin file
```

## Support

For support, please visit:
- Plugin homepage: [https://codeeffina.com/wordpress/plugins/email-domain-restriction](https://codeeffina.com/wordpress/plugins/email-domain-restriction)
- GitHub repository: [https://github.com/erikcaineolson/email-domain-restriction](https://github.com/erikcaineolson/email-domain-restriction)
- Email: Contact via CodeEffina website

## License

This plugin is licensed under the GPL-2.0+ license.

## Credits

Built with:
- WordPress Plugin API
- [Chart.js](https://www.chartjs.org/) for analytics visualizations
- [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) by Yahnis Elsts for custom updates
- Love and coffee
