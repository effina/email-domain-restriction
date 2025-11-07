# WooCommerce Integration Testing Guide

This guide covers testing the WooCommerce integration for Email Domain Restriction PRO.

## Prerequisites

- WordPress 5.0+
- WooCommerce 3.0+ installed and activated
- Email Domain Restriction PRO plugin activated with valid license
- At least one whitelisted domain configured

## Test Environment Setup

### 1. Configure Test Domains

Add test domains to the whitelist:
- `example.com` (exact match)
- `*.testcompany.com` (wildcard for subdomains)

### 2. Configure WooCommerce Settings

Navigate to `Email Domain Restriction > Settings (PRO)` and configure:
- ✅ Validate email domains during checkout registration
- ✅ Validate email domains on My Account registration
- ✅ Track WooCommerce registration metadata
- Set custom error message (optional)

### 3. WooCommerce Configuration

Ensure WooCommerce is configured for testing:
- Go to `WooCommerce > Settings > Accounts & Privacy`
- ✅ Enable "Allow customers to create an account during checkout"
- ✅ Enable "Allow customers to create an account on the 'My account' page"
- ❌ Disable "When creating an account, send the new user a link to set their password"

---

## Test Scenarios

### Scenario 1: Checkout Registration - Whitelisted Domain ✅

**Steps:**
1. Add a product to cart
2. Go to checkout
3. Check "Create an account?"
4. Fill in billing details with email: `test@example.com`
5. Complete checkout

**Expected Results:**
- ✅ Order completes successfully
- ✅ User account created
- ✅ Registration logged with source: `woocommerce-checkout`
- ✅ User meta contains:
  - `_edr_registered_via_woocommerce` = `yes`
  - `_edr_registration_source` = `checkout`
  - `_edr_email_domain` = `example.com`
  - `_edr_domain_verified_date` = current timestamp

**Verification:**
```sql
SELECT * FROM wp_edr_registration_attempts
WHERE email = 'test@example.com'
AND source = 'woocommerce-checkout'
AND status = 'allowed';
```

---

### Scenario 2: Checkout Registration - Non-Whitelisted Domain ❌

**Steps:**
1. Add a product to cart
2. Go to checkout
3. Check "Create an account?"
4. Fill in billing details with email: `test@blocked.com`
5. Attempt to complete checkout

**Expected Results:**
- ❌ Checkout fails with error message
- ❌ User account NOT created
- ❌ Order NOT placed
- ✅ Blocked attempt logged with source: `woocommerce-checkout`
- ✅ Error message displayed: "Registration is restricted to approved email domains only..."

**Verification:**
```sql
SELECT * FROM wp_edr_registration_attempts
WHERE email = 'test@blocked.com'
AND source = 'woocommerce-checkout'
AND status = 'blocked';
```

---

### Scenario 3: My Account Registration - Whitelisted Domain ✅

**Steps:**
1. Navigate to `My Account` page (not logged in)
2. Enter username: `testuser`
3. Enter email: `user@example.com`
4. Submit registration form

**Expected Results:**
- ✅ Account created successfully
- ✅ User logged in automatically
- ✅ Registration logged with source: `woocommerce-myaccount`
- ✅ User meta tracked correctly

**Verification:**
- Check user exists in WordPress admin
- Verify registration log entry
- Check user meta fields

---

### Scenario 4: My Account Registration - Non-Whitelisted Domain ❌

**Steps:**
1. Navigate to `My Account` page (not logged in)
2. Enter username: `blockeduser`
3. Enter email: `user@blocked.com`
4. Submit registration form

**Expected Results:**
- ❌ Registration fails
- ❌ Error message displayed
- ❌ User NOT created
- ✅ Blocked attempt logged

---

### Scenario 5: Wildcard Domain Match ✅

**Steps:**
1. Go to checkout
2. Check "Create an account?"
3. Enter email: `employee@subdomain.testcompany.com`
4. Complete checkout

**Expected Results:**
- ✅ Registration successful (matches `*.testcompany.com`)
- ✅ Account created
- ✅ Logged correctly

---

### Scenario 6: Rate Limiting (PRO) 🔒

**Prerequisites:**
- PRO license active
- Rate limiting enabled
- Domain limit set to 3 attempts per hour

**Steps:**
1. Attempt 4 checkout registrations with `test@example.com` within 1 hour
2. Use different usernames for each attempt

**Expected Results:**
- ✅ First 3 attempts succeed
- ❌ 4th attempt blocked with rate limit message
- ✅ All attempts logged
- ✅ Rate limit record created in `wp_edr_rate_limits`

**Verification:**
```sql
SELECT * FROM wp_edr_rate_limits
WHERE identifier = 'example.com'
AND identifier_type = 'domain';
```

---

### Scenario 7: Role Assignment by Domain (PRO) 🔒

**Prerequisites:**
- PRO license active
- Role mapping configured: `example.com` → `wholesale_customer`

**Steps:**
1. Create WooCommerce customer with `test@example.com`
2. Complete checkout

**Expected Results:**
- ✅ Account created
- ✅ User role set to `wholesale_customer` (not default `customer`)
- ✅ Role assignment logged

**Verification:**
- Check user role in WordPress admin
- Verify via: `wp_capabilities` user meta

---

### Scenario 8: B2B Mode (PRO) 🔒

**Prerequisites:**
- PRO license active
- B2B mode enabled
- B2B domains: `corporate.com, *.partners.com`

**Steps:**
1. Attempt checkout with `user@corporate.com` ✅
2. Attempt checkout with `user@subdomain.partners.com` ✅
3. Attempt checkout with `user@personal.com` ❌

**Expected Results:**
- ✅ Corporate and partner domains succeed
- ❌ Personal domains blocked
- ✅ Appropriate logging for each

---

### Scenario 9: Guest Checkout (No Account Creation)

**Steps:**
1. Go to checkout
2. DO NOT check "Create an account?"
3. Complete checkout as guest

**Expected Results:**
- ✅ Order completes
- ❌ No domain validation performed
- ❌ No registration logged
- ✅ Guest customer record created

---

### Scenario 10: Existing User Checkout

**Steps:**
1. Login as existing user
2. Place order via checkout

**Expected Results:**
- ✅ Order completes
- ❌ No registration validation (user already exists)
- ❌ No registration logged

---

## Edge Cases

### Edge Case 1: Empty Email
- Enter empty email → WooCommerce validation catches first

### Edge Case 2: Invalid Email Format
- Enter `notanemail` → WooCommerce validation catches first

### Edge Case 3: Domain with No TLD
- Enter `test@localhost` → Handled gracefully

### Edge Case 4: Case Sensitivity
- Whitelist: `Example.com`
- Register: `test@EXAMPLE.COM`
- Expected: ✅ Match (case-insensitive)

### Edge Case 5: Subdomain of Exact Match
- Whitelist: `example.com` (exact)
- Register: `test@sub.example.com`
- Expected: ❌ Blocked (not wildcard)

---

## Dashboard Verification

### Check WooCommerce Widget

Navigate to `Email Domain Restriction > Dashboard`

Verify widget displays:
- Checkout registrations count
- My Account registrations count
- Total allowed
- Total blocked

### Check Registration Log

Navigate to `Email Domain Restriction > Registration Log`

Filter by source:
- `woocommerce-checkout`
- `woocommerce-myaccount`

Verify all test registrations appear with correct:
- Email
- Domain
- Status (allowed/blocked)
- Source
- Timestamp
- IP address

---

## Settings Verification

### Navigate to PRO Settings

`Email Domain Restriction > Settings (PRO) > WooCommerce Integration`

Verify settings exist:
- ✅ Validate email domains during checkout registration
- ✅ Validate email domains on My Account registration
- ✅ Custom error message field
- ✅ Track WooCommerce registration metadata
- ✅ B2B Mode toggle
- ✅ B2B Domains textarea (when enabled)

Test setting changes:
1. Disable checkout validation
2. Attempt checkout registration with non-whitelisted domain
3. Expected: ✅ Registration succeeds (validation disabled)

---

## Integration Points Checklist

### WooCommerce Hooks Implemented
- ✅ `woocommerce_checkout_process` - Checkout validation
- ✅ `woocommerce_register_post` - My Account validation
- ✅ `woocommerce_created_customer` - Logging
- ✅ `woocommerce_created_customer` - Meta tracking
- ✅ `woocommerce_created_customer` - Role assignment (PRO)

### Custom Meta Fields
- ✅ `_edr_registered_via_woocommerce`
- ✅ `_edr_registration_source`
- ✅ `_edr_order_id_at_registration`
- ✅ `_edr_domain_verified_date`
- ✅ `_edr_email_domain`

### Database Tables Used
- ✅ `wp_edr_registration_attempts` (logging)
- ✅ `wp_edr_rate_limits` (PRO rate limiting)
- ✅ `wp_edr_role_mappings` (PRO role assignment)

---

## Performance Testing

### Load Test Scenario

**Setup:**
- 100 concurrent checkout registrations
- Mix of whitelisted and non-whitelisted domains

**Metrics to Monitor:**
- Average validation time: < 100ms
- Database query count: Should not exceed 5 per validation
- Memory usage: Should not spike significantly

**Tools:**
- Apache JMeter or LoadStorm
- Query Monitor plugin for WordPress

---

## Compatibility Testing

### Test with WooCommerce Versions
- ✅ WooCommerce 8.0+
- ✅ WooCommerce 7.x
- ✅ WooCommerce 6.x (if still supported)

### Test with Other Plugins
- ✅ WooCommerce Subscriptions
- ✅ WooCommerce Memberships
- ✅ WooCommerce B2B
- ✅ YITH WooCommerce Wholesale

### Test with Payment Gateways
- ✅ Stripe
- ✅ PayPal
- ✅ Cash on Delivery
- ✅ Bank Transfer

---

## Troubleshooting

### Registration Not Being Validated

**Check:**
1. WooCommerce is active
2. PRO license is valid
3. WooCommerce validation settings are enabled
4. Domains are in whitelist

**Debug:**
```php
// Add to functions.php temporarily
add_action('woocommerce_checkout_process', function() {
    error_log('WooCommerce checkout process triggered');
}, 1);
```

### Logging Not Working

**Check:**
1. `wp_edr_registration_attempts` table exists
2. Table has `source` column
3. `EDR_Attempt_Logger` class is loaded

**Verify:**
```sql
SHOW COLUMNS FROM wp_edr_registration_attempts LIKE 'source';
```

### Rate Limiting Not Working

**Check:**
1. PRO is active: `edr_is_pro_active()` returns true
2. Rate limit table exists
3. Rate limit settings are configured

---

## Success Criteria

Phase 2 is complete when:

- ✅ All 10 test scenarios pass
- ✅ Edge cases handled gracefully
- ✅ Dashboard widget displays correct statistics
- ✅ Settings page functional
- ✅ Registration logs show correct source tracking
- ✅ User meta tracked correctly
- ✅ Rate limiting works (PRO)
- ✅ Role assignment works (PRO)
- ✅ B2B mode works (PRO)
- ✅ No PHP errors in debug.log
- ✅ No JavaScript errors in browser console
- ✅ Performance within acceptable limits

---

## Reporting Issues

When reporting issues, include:

1. **WordPress Version:**
2. **WooCommerce Version:**
3. **Plugin Version:**
4. **PRO License Status:**
5. **Steps to Reproduce:**
6. **Expected Behavior:**
7. **Actual Behavior:**
8. **Error Messages:**
9. **Debug Log Output:**
10. **Screenshots:**

---

## Next Steps

After Phase 2 completion:
- **Phase 3:** BuddyPress Integration
- **Phase 4:** Role-Based Restrictions Enhancement
- **Phase 5:** Advanced Analytics Dashboard

---

*Last Updated: November 2025*
*Version: 1.0.0*
