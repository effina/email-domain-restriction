# Advanced Analytics Testing Guide

This guide covers testing the Advanced Analytics feature for Email Domain Restriction PRO.

## Prerequisites

- WordPress 5.0+
- Email Domain Restriction PRO plugin activated with valid license
- Registration data in database (at least 30 days for meaningful charts)
- Chart.js CDN accessible

## Test Environment Setup

### 1. Generate Test Data

For testing, you need historical registration data. You can:
- Use existing production data
- Import test data via SQL
- Wait for registrations to accumulate

### 2. Configure Analytics Settings

Navigate to `Email Domain Restriction > Analytics > Settings`:
- Set data retention: 365 days
- Configure scheduled reports (optional)

---

## Test Scenarios

### Scenario 1: Dashboard Load and Overview Stats ✅

**Steps:**
1. Navigate to `Email Domain Restriction > Analytics`
2. Check page loads without errors
3. Verify overview statistics cards display

**Expected Results:**
- ✅ Page loads within 2 seconds
- ✅ Four stat cards visible:
  - Total Attempts
  - Allowed
  - Blocked
  - Conversion Rate
- ✅ Numbers accurate and formatted with commas
- ✅ Icons displayed correctly
- ✅ Cards have hover effect

**Verification:**
```javascript
// Check browser console for errors
console.log('No JavaScript errors');
```

---

### Scenario 2: Time Series Chart Display ✅

**Steps:**
1. View Analytics dashboard
2. Locate "Registration Trends" chart
3. Check chart renders correctly

**Expected Results:**
- ✅ Line chart displays
- ✅ Two lines visible: Allowed (green) and Blocked (red)
- ✅ X-axis shows dates
- ✅ Y-axis shows counts
- ✅ Tooltips work on hover
- ✅ Legend displays correctly
- ✅ Chart is responsive

---

### Scenario 3: Source Breakdown Chart ✅

**Steps:**
1. Locate "By Source" chart
2. Check doughnut chart renders

**Expected Results:**
- ✅ Doughnut chart displays
- ✅ Segments colored differently
- ✅ Legend shows sources:
  - WordPress
  - WooCommerce Checkout
  - WooCommerce My Account
  - BuddyPress
  - Ultimate Member
- ✅ Tooltips show counts
- ✅ Percentages accurate

---

### Scenario 4: Conversion Funnel Visualization ✅

**Steps:**
1. Locate "Conversion Funnel" section
2. Check funnel stages display

**Expected Results:**
- ✅ Four funnel stages visible:
  1. Attempts (100% width)
  2. Allowed (narrower)
  3. Users Created (narrower)
  4. Active Users (narrowest)
- ✅ Each stage shows count
- ✅ Colors gradient from blue to purple
- ✅ Hover effect on bars
- ✅ Percentages visually accurate

---

### Scenario 5: Top Domains Chart ✅

**Steps:**
1. Locate "Top Domains" chart
2. Check horizontal bar chart renders

**Expected Results:**
- ✅ Horizontal stacked bar chart displays
- ✅ Shows top 10 domains
- ✅ Two segments: Allowed (green) and Blocked (red)
- ✅ Domain names on Y-axis
- ✅ Counts on X-axis
- ✅ Stacking works correctly

---

### Scenario 6: Geographic Distribution Chart ✅

**Steps:**
1. Locate "Geographic Distribution" chart
2. Check bar chart renders

**Expected Results:**
- ✅ Vertical bar chart displays
- ✅ Country codes on X-axis
- ✅ Counts on Y-axis
- ✅ Bars colored blue
- ✅ Tooltips show country and count

---

### Scenario 7: Date Range Selector ✅

**Steps:**
1. Click date range dropdown
2. Select "Last 7 Days"
3. Wait for page reload
4. Check URL parameter

**Expected Results:**
- ✅ Dropdown shows options:
  - Last 7 Days
  - Last 30 Days
  - Last 90 Days
  - Last 6 Months
  - Last Year
- ✅ Page reloads with new data
- ✅ URL includes `?days=7`
- ✅ All charts update
- ✅ Stats cards update

---

### Scenario 8: Refresh Data Button ✅

**Steps:**
1. Click "Refresh" button
2. Wait for page reload

**Expected Results:**
- ✅ Page reloads
- ✅ Data updates to latest
- ✅ Selected date range preserved

---

### Scenario 9: Export CSV - Basic 🔒

**Steps:**
1. Click "Export CSV" button
2. Leave all filters empty
3. Click "Export CSV" in modal
4. Check downloaded file

**Expected Results:**
- ✅ Modal opens
- ✅ Form has fields:
  - Start Date
  - End Date
  - Source dropdown
  - Status dropdown
- ✅ CSV downloads automatically
- ✅ Filename: `edr-analytics-[timestamp].csv`
- ✅ Contains all columns:
  - id, email, ip_address, status, source, etc.
- ✅ Data properly escaped

**Verify CSV:**
```csv
id,email,ip_address,status,source,country_code,created_at
1,"user@example.com","192.168.1.1","allowed","wordpress","US","2025-01-15 10:30:00"
```

---

### Scenario 10: Export CSV - With Filters 🔒

**Steps:**
1. Click "Export CSV"
2. Set filters:
   - Start Date: 2025-01-01
   - End Date: 2025-01-31
   - Source: WooCommerce Checkout
   - Status: Allowed
3. Click "Export CSV"

**Expected Results:**
- ✅ Only filtered records exported
- ✅ Date range respected
- ✅ Source filter applied
- ✅ Status filter applied

---

### Scenario 11: Export PDF 🔒

**Prerequisites:**
- TCPDF library available

**Steps:**
1. Click "Export PDF" button
2. Wait for generation
3. Check opened PDF

**Expected Results:**
- ✅ Loading indicator shows
- ✅ PDF opens in new tab
- ✅ PDF contains:
  - Report title
  - Date range
  - Overall statistics
  - Top domains table
- ✅ Professional formatting
- ✅ Filename: `edr-report-[timestamp].pdf`

**If TCPDF Missing:**
- ❌ Error message: "PDF library not available"

---

### Scenario 12: Scheduled Reports - Daily 🔒

**Steps:**
1. Click "Settings" button
2. Set scheduled reports:
   - Frequency: Daily
   - Email: admin@example.com
3. Click "Save Settings"

**Expected Results:**
- ✅ Success message displayed
- ✅ WordPress cron scheduled
- ✅ Next email scheduled for tomorrow 9:00 AM

**Verification:**
```php
$timestamp = wp_next_scheduled('edr_send_scheduled_report', ['admin@example.com']);
echo date('Y-m-d H:i:s', $timestamp);
// Should show tomorrow at 9:00
```

---

### Scenario 13: Scheduled Reports - Weekly 🔒

**Steps:**
1. Configure scheduled reports
2. Set frequency: Weekly
3. Save settings

**Expected Results:**
- ✅ Scheduled for next Monday 9:00 AM
- ✅ Recurs weekly

---

### Scenario 14: Scheduled Reports - Monthly 🔒

**Steps:**
1. Configure scheduled reports
2. Set frequency: Monthly
3. Save settings

**Expected Results:**
- ✅ Scheduled for first day of next month 9:00 AM
- ✅ Recurs monthly

---

### Scenario 15: Cancel Scheduled Reports 🔒

**Steps:**
1. Open settings
2. Set frequency: Never
3. Save settings

**Expected Results:**
- ✅ Success message: "Scheduled reports cancelled"
- ✅ Cron event cleared
- ✅ No future emails scheduled

---

### Scenario 16: Receive Scheduled Email 🔒

**Prerequisites:**
- Scheduled report configured
- Wait for scheduled time (or manually trigger)

**Steps:**
1. Manually trigger:
```php
do_action('edr_send_scheduled_report', 'admin@example.com');
```
2. Check email inbox

**Expected Results:**
- ✅ Email received
- ✅ Subject: "[Site Name] Registration Analytics Report"
- ✅ Body contains:
  - Overall statistics
  - By source breakdown
  - Link to dashboard
- ✅ Plain text format
- ✅ Professional formatting

**Sample Email:**
```
Registration Analytics Report for My Site

Overall Statistics (Last 30 Days):
-------------------------------------------
Total Attempts: 150
Allowed: 120
Blocked: 30
Conversion Rate: 80%

By Source:
-------------------------------------------
wordpress: 80 attempts (70 allowed, 10 blocked)
woocommerce-checkout: 50 attempts (40 allowed, 10 blocked)
buddypress: 20 attempts (10 allowed, 10 blocked)

View detailed analytics:
https://example.com/wp-admin/admin.php?page=edr-analytics
```

---

### Scenario 17: Data Retention - Set Policy 🔒

**Steps:**
1. Open analytics settings
2. Set data retention: 90 days
3. Save settings

**Expected Results:**
- ✅ Setting saved
- ✅ Option value: `edr_data_retention_days` = 90
- ✅ Daily cron scheduled for cleanup

---

### Scenario 18: Data Retention - Cleanup Execution 🔒

**Prerequisites:**
- Data retention set to 90 days
- Test data older than 90 days exists

**Steps:**
1. Manually trigger cleanup:
```php
$analytics = new EDR_Advanced_Analytics();
$analytics->cleanup_old_data();
```
2. Check database

**Expected Results:**
- ✅ Records older than 90 days deleted
- ✅ Records within 90 days preserved

**Verification:**
```sql
SELECT COUNT(*) FROM wp_edr_registration_attempts
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
-- Should return 0
```

---

### Scenario 19: Recent Blocked Attempts Table ✅

**Steps:**
1. Scroll to "Recent Blocked Attempts"
2. Check table displays

**Expected Results:**
- ✅ Table shows last 10 blocked attempts
- ✅ Columns:
  - Email
  - IP Address (monospace)
  - Source (human-readable)
  - Reason
  - Country
  - Date (formatted)
- ✅ If no blocked attempts: "No blocked attempts in this period"

---

### Scenario 20: Settings Modal UI ✅

**Steps:**
1. Click "Settings" button
2. Check modal displays
3. Change frequency dropdown
4. Observe email field

**Expected Results:**
- ✅ Modal opens smoothly
- ✅ Form fields populated with current values
- ✅ When frequency = "Never": email field hidden
- ✅ When frequency != "Never": email field shown
- ✅ Close button (X) works
- ✅ Cancel button works
- ✅ Click outside modal closes it

---

### Scenario 21: Chart Responsiveness 📱

**Steps:**
1. Open Analytics page on desktop (1920px)
2. Resize to tablet (768px)
3. Resize to mobile (375px)

**Expected Results:**
- ✅ Desktop: All charts visible in grid
- ✅ Tablet: Charts stack in 1 column
- ✅ Mobile:
  - Header buttons stack vertically
  - Stat cards stack in 1 column
  - Charts full width
  - Tables scroll horizontally
- ✅ Chart.js maintains aspect ratio
- ✅ No layout breaks

---

### Scenario 22: Empty State - No Data ✅

**Steps:**
1. Use fresh install with no registration data
2. Navigate to Analytics

**Expected Results:**
- ✅ Stat cards show 0
- ✅ Charts display empty state or placeholder
- ✅ "No blocked attempts in this period" message
- ✅ No JavaScript errors
- ✅ Page still usable

---

### Scenario 23: Chart Tooltips and Interactions ✅

**Steps:**
1. Hover over time series chart
2. Hover over doughnut chart
3. Click legend items

**Expected Results:**
- ✅ Tooltips appear on hover
- ✅ Show values for hovered point
- ✅ Tooltips positioned correctly
- ✅ Clicking legend toggles data visibility
- ✅ Smooth animations

---

### Scenario 24: AJAX Error Handling ❌

**Steps:**
1. Disable internet connection
2. Try to export CSV
3. Re-enable internet
4. Try again

**Expected Results:**
- ❌ Error alert: "An error occurred. Please try again."
- ✅ Button re-enabled after error
- ✅ Retry works after re-enabling internet

---

### Scenario 25: Performance - Large Datasets 🔒

**Prerequisites:**
- Database with 10,000+ registration attempts

**Steps:**
1. Navigate to Analytics page
2. Measure load time
3. Change date range to "Last Year"

**Expected Results:**
- ✅ Initial load < 3 seconds
- ✅ Charts render < 2 seconds
- ✅ Date range change < 3 seconds
- ✅ No browser lag
- ✅ Smooth animations

---

## Integration Testing

### Test with WooCommerce Data

**Verify:**
- ✅ WooCommerce registrations appear in charts
- ✅ Source breakdown includes checkout and my-account
- ✅ Filtering by WooCommerce source works

### Test with BuddyPress Data

**Verify:**
- ✅ BuddyPress registrations tracked
- ✅ Source breakdown includes buddypress and buddypress-invitation
- ✅ Stats accurate

### Test with Ultimate Member Data

**Verify:**
- ✅ Ultimate Member registrations tracked
- ✅ Source appears in dropdown and charts

---

## Security Testing

### Permission Checks

1. **Login as Editor (not Administrator)**
2. Try to access `/wp-admin/admin.php?page=edr-analytics`

**Expected:**
- ❌ Access denied: "You do not have sufficient permissions"

### AJAX Security

1. **Submit AJAX request without nonce**
2. Try to export CSV

**Expected:**
- ❌ Error response
- ❌ Action blocked

### SQL Injection

1. **Try to inject SQL in date filters**
2. Enter: `'; DROP TABLE wp_users; --`

**Expected:**
- ✅ Input sanitized
- ✅ No SQL execution

---

## Browser Compatibility

Test in:
- ✅ Chrome (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Edge (latest)
- ✅ Mobile Safari (iOS)
- ✅ Chrome Mobile (Android)

---

## Success Criteria

Phase 5 is complete when:

- ✅ All 25 test scenarios pass
- ✅ Dashboard loads without errors
- ✅ All charts render correctly
- ✅ Time series, source, domains, geographic charts work
- ✅ Conversion funnel displays accurately
- ✅ CSV export functions correctly
- ✅ PDF export works (if TCPDF available)
- ✅ Scheduled reports can be configured
- ✅ Email reports sent successfully
- ✅ Data retention cleanup works
- ✅ Settings save correctly
- ✅ Responsive design works on all screen sizes
- ✅ No PHP errors in debug.log
- ✅ No JavaScript errors in console
- ✅ Performance acceptable with large datasets
- ✅ Security checks pass

---

## Troubleshooting

### Charts Not Rendering

**Check:**
1. Chart.js loaded from CDN
2. JavaScript console for errors
3. AJAX responses successful
4. Data exists for selected date range

### PDF Export Fails

**Check:**
1. TCPDF library installed
2. Write permissions on uploads directory
3. PHP memory limit (increase if needed)
4. Error in PHP log

### Scheduled Reports Not Sending

**Check:**
1. WordPress cron working (`wp_next_scheduled()`)
2. Email configuration correct
3. Manually trigger to test
4. Check spam folder

### Slow Performance

**Optimize:**
1. Add database indexes
2. Implement query caching
3. Limit data returned to charts
4. Consider pagination for tables

---

## Next Steps

After Phase 5 completion:
- **Phase 6:** Rate Limiting & Anti-Abuse Enhancement
- **Phase 7:** Advanced Features (Geolocation, Webhooks, API)
- **Phase 8:** Testing & Documentation
- **Phase 9:** Launch

---

*Last Updated: November 2025*
*Version: 1.0.0*
