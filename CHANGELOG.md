# Changelog

All notable changes to the Email Domain Restriction plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-15

### Added
- Initial release of Email Domain Restriction plugin
- Domain whitelisting with exact and wildcard (`*.example.com`) support
- Email verification system for single-site WordPress
- Registration attempt logging (tracks both allowed and blocked attempts)
- Comprehensive analytics dashboard with Chart.js visualizations
  - Quick stats cards (Total, Allowed, Blocked, Success Rate)
  - Registration attempts over time (line chart)
  - Allowed vs Blocked ratio (pie chart)
  - Top 10 attempted domains (bar chart)
  - Attempts by day of week (bar chart)
  - Attempts by hour of day (bar chart)
  - Recent activity feed
- Domain management interface
  - Add/remove individual domains
  - Bulk CSV import/export
  - Domain type indicators (wildcard vs exact)
- Registration log viewer
  - Filterable by status, domain, date range
  - Pagination (25/50/100 per page)
  - CSV export functionality
  - Automatic cleanup of old logs
- Plugin settings page
  - Configurable log retention period (default: 30 days)
  - Email verification toggle
  - Verification token expiry configuration
  - Custom error messages for blocked domains
  - Default user role selection
- Ultimate Member integration
  - Automatic domain validation on UM registration forms
  - Intelligent email verification handling
  - Registration attempt logging for UM users
  - Admin notice for verification conflict awareness
- Multisite compatibility
  - Works on single-site and multisite installations
  - Uses WordPress native verification on multisite
- Security features
  - CSRF protection on all forms
  - SQL injection prevention
  - Input sanitization and validation
  - Admin-only access with capability checks
- Custom update system integration
  - Automatic update checks from codeeffina.com
  - One-click updates from WordPress admin
  - Update notifications in admin dashboard

### Security
- All user inputs sanitized and validated
- Nonce verification on form submissions
- SQL queries use prepared statements
- Admin capability checks on all admin actions
- Secure token generation for email verification

### Technical
- PSR-12 coding standards
- Translation-ready with text domain
- Hooks and filters for extensibility
- Comprehensive error handling
- Database indexes for performance

## [Unreleased]

### Planned
- Rate limiting for registration attempts
- Geolocation-based restrictions
- Integration with form builders (Gravity Forms, Contact Form 7)
- REST API for domain management
- Email notifications to admins on blocked attempts
- Advanced regex pattern support
- Statistics export functionality

---

For more information, visit [https://codeeffina.com/wordpress/plugins/email-domain-restriction](https://codeeffina.com/wordpress/plugins/email-domain-restriction)
