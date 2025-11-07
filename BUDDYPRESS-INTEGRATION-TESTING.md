# BuddyPress Integration Testing Guide

This guide covers testing the BuddyPress integration for Email Domain Restriction PRO.

## Prerequisites

- WordPress 5.0+
- BuddyPress 6.0+ installed and activated
- Email Domain Restriction PRO plugin activated with valid license
- At least one whitelisted domain configured

## Test Environment Setup

### 1. Configure Test Domains

Add test domains to the whitelist:
- `example.com` (exact match)
- `*.testcompany.com` (wildcard for subdomains)

### 2. Configure BuddyPress Settings

Navigate to `Email Domain Restriction > Settings (PRO) > BuddyPress Integration` and configure:
- ✅ Validate email domains during BuddyPress registration
- ✅ Track BuddyPress registration metadata
- ✅ Enable group-level domain restrictions (PRO)
- Set custom error message (optional)

### 3. BuddyPress Configuration

Ensure BuddyPress is configured for testing:
- Go to `Settings > BuddyPress > Settings`
- ✅ Enable "Allow account registration"
- Configure member types if needed

---

## Test Scenarios

### Scenario 1: Standard Registration - Whitelisted Domain ✅

**Steps:**
1. Navigate to BuddyPress registration page
2. Fill in username, email (`test@example.com`), and password
3. Complete extended profile fields
4. Submit registration

**Expected Results:**
- ✅ Registration succeeds
- ✅ Activation email sent
- ✅ Registration logged with source: `buddypress`
- ✅ User meta contains:
  - `_edr_registered_via_buddypress` = `yes`
  - `_edr_bp_activation_key` = activation key
  - `_edr_email_domain` = `example.com`
  - `_edr_domain_verified_date` = timestamp

**Verification:**
```sql
SELECT * FROM wp_edr_registration_attempts
WHERE email = 'test@example.com'
AND source = 'buddypress'
AND status = 'allowed';
```

---

### Scenario 2: Standard Registration - Non-Whitelisted Domain ❌

**Steps:**
1. Navigate to BuddyPress registration page
2. Fill in details with email: `test@blocked.com`
3. Submit registration

**Expected Results:**
- ❌ Registration fails with error
- ❌ User account NOT created
- ✅ Blocked attempt logged with source: `buddypress`
- ✅ Error message displayed

---

### Scenario 3: Member Type Assignment by Domain (PRO) 🔒

**Prerequisites:**
- PRO license active
- Member type "Partner" created
- Member type mapping: `example.com` → `partner`

**Steps:**
1. Register with `user@example.com`
2. Activate account

**Expected Results:**
- ✅ Account created
- ✅ Member type set to "partner"
- ✅ User meta `_edr_bp_member_type` = `partner`

---

### Scenario 4: Group Join - Domain Restricted (PRO) 🔒

**Prerequisites:**
- PRO license active
- Group restrictions enabled
- Private group created with allowed domains: `example.com`

**Steps:**
1. Login as user with `user@example.com` ✅
2. Attempt to join restricted group
3. Login as user with `user@other.com` ❌
4. Attempt to join same group

**Expected Results:**
- ✅ First user can join (domain matches)
- ❌ Second user blocked (domain doesn't match)
- ✅ Error message displayed to second user

---

### Scenario 5: Invitation-Based Registration ✅

**Prerequisites:**
- User invitations enabled in BuddyPress

**Steps:**
1. Existing member invites `newuser@example.com`
2. Invited user clicks invitation link
3. Completes registration

**Expected Results:**
- ✅ Registration succeeds (whitelisted)
- ✅ Logged with source: `buddypress-invitation`
- ✅ Meta `_edr_bp_invited_by` = inviter's user ID

---

### Scenario 6: Rate Limiting (PRO) 🔒

**Prerequisites:**
- PRO license active
- Rate limiting enabled
- Domain limit set to 3 attempts per hour

**Steps:**
1. Attempt 4 registrations with different usernames but same domain
2. All within 1 hour

**Expected Results:**
- ✅ First 3 attempts succeed
- ❌ 4th attempt blocked with rate limit message
- ✅ Rate limit recorded in database

---

### Scenario 7: Extended Profile Fields

**Steps:**
1. BuddyPress has required profile fields configured
2. Register with whitelisted domain
3. Fill all required fields

**Expected Results:**
- ✅ Registration completes
- ✅ Domain validation happens before profile validation
- ✅ All BuddyPress data saved correctly

---

### Scenario 8: Group Domain Settings (PRO Admin) 🔒

**Prerequisites:**
- PRO license, group restrictions enabled
- User is group admin

**Steps:**
1. Go to group settings as admin
2. Find "Email Domain Restrictions" section
3. Add `example.com` and `*.partner.com`
4. Save group settings

**Expected Results:**
- ✅ Settings saved to group meta
- ✅ Group meta `edr_allowed_domains` contains domains
- ✅ Only users with matching domains can join

---

## Dashboard Verification

Navigate to `Email Domain Restriction > Dashboard`

Verify BuddyPress widget displays:
- Standard registrations count
- Invitation registrations count
- Total allowed
- Total blocked

---

## Integration Points Checklist

### BuddyPress Hooks Implemented
- ✅ `bp_signup_validate` - Registration validation
- ✅ `bp_core_signup_user` - Registration logging
- ✅ `bp_core_activated_user` - Meta tracking & member type assignment
- ✅ `groups_join_group` - Group join validation (PRO)

### Custom Meta Fields
- ✅ `_edr_registered_via_buddypress`
- ✅ `_edr_bp_activation_key`
- ✅ `_edr_bp_member_type`
- ✅ `_edr_bp_invited_by`
- ✅ `_edr_email_domain`
- ✅ `_edr_domain_verified_date`

### Database Tables Used
- ✅ `wp_edr_registration_attempts` (logging)
- ✅ `wp_edr_rate_limits` (PRO rate limiting)
- ✅ `wp_edr_bp_member_type_mappings` (PRO member types)
- ✅ Group meta: `edr_allowed_domains` (PRO group restrictions)

---

## Success Criteria

Phase 3 is complete when:

- ✅ All 8 test scenarios pass
- ✅ Dashboard widget displays correct statistics
- ✅ Settings page functional
- ✅ Registration logs show correct source tracking
- ✅ User meta tracked correctly
- ✅ Rate limiting works (PRO)
- ✅ Member type assignment works (PRO)
- ✅ Group restrictions work (PRO)
- ✅ No PHP errors in debug.log
- ✅ No JavaScript errors in browser console

---

## Compatibility Testing

### Test with BuddyPress Versions
- ✅ BuddyPress 12.0+
- ✅ BuddyPress 11.x
- ✅ BuddyPress 10.x (if still supported)

### Test with Other Plugins
- ✅ BuddyPress Activity Plus
- ✅ BuddyPress Group Email Subscription
- ✅ Youzer (BuddyPress community)

---

## Next Steps

After Phase 3 completion:
- **Phase 4:** Role-Based Restrictions Enhancement
- **Phase 5:** Advanced Analytics Dashboard
- **Phase 6:** Rate Limiting & Anti-Abuse

---

*Last Updated: November 2025*
*Version: 1.0.0*
