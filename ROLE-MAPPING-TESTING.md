# Role Mapping Testing Guide

This guide covers testing the Role Mapping feature for Email Domain Restriction PRO.

## Prerequisites

- WordPress 5.0+
- Email Domain Restriction PRO plugin activated with valid license
- At least one whitelisted domain configured
- Multiple WordPress roles configured for testing

## Test Environment Setup

### 1. Configure Test Domains

Add test domains to the whitelist:
- `example.com` (exact match)
- `partner.com` (exact match)
- `*.contractor.com` (wildcard for subdomains)

### 2. Create Test Role Mappings

Navigate to `Email Domain Restriction > Role Mappings` and create:

| Domain Pattern | Role | Priority |
|----------------|------|----------|
| example.com | Subscriber | 10 |
| partner.com | Editor | 20 |
| *.contractor.com | Contributor | 15 |
| sales.contractor.com | Author | 25 |

---

## Test Scenarios

### Scenario 1: Exact Domain Match ✅

**Setup:**
- Role mapping: `example.com` → `Subscriber` (Priority: 10)

**Steps:**
1. Register new user with email: `user@example.com`
2. Complete registration process
3. Check assigned role in WordPress Users page

**Expected Results:**
- ✅ User created successfully
- ✅ User role is "Subscriber"
- ✅ No other role assigned

**Verification:**
```php
$user = get_user_by('email', 'user@example.com');
$roles = $user->roles;
// Should contain 'subscriber'
```

---

### Scenario 2: Wildcard Domain Match ✅

**Setup:**
- Role mapping: `*.contractor.com` → `Contributor` (Priority: 15)

**Steps:**
1. Register new user with email: `john@team.contractor.com`
2. Complete registration process
3. Check assigned role

**Expected Results:**
- ✅ User created successfully
- ✅ User role is "Contributor"
- ✅ Wildcard pattern matched correctly

**Test Multiple Subdomains:**
- `user@dev.contractor.com` → Contributor ✅
- `user@staging.contractor.com` → Contributor ✅
- `user@mail.contractor.com` → Contributor ✅

---

### Scenario 3: Conflict Resolution - Priority Wins 🔒

**Setup:**
- Mapping 1: `*.contractor.com` → `Contributor` (Priority: 15)
- Mapping 2: `sales.contractor.com` → `Author` (Priority: 25)

**Steps:**
1. Register user with email: `user@sales.contractor.com`
2. Check which role is assigned

**Expected Results:**
- ✅ User role is "Author" (higher priority wins)
- ✅ Lower priority mapping ignored
- ✅ Test shows both patterns match but Author is selected

**Verification:**
Use the "Test Role Assignment" tool in admin:
- Input: `user@sales.contractor.com`
- Should show both patterns match
- Should highlight "Author" as selected role

---

### Scenario 4: Conflict Resolution - Specificity Wins 🔒

**Setup:**
- Mapping 1: `*.com` → `Subscriber` (Priority: 10)
- Mapping 2: `example.com` → `Editor` (Priority: 10)

**Steps:**
1. Register user with email: `user@example.com`
2. Check which role is assigned

**Expected Results:**
- ✅ User role is "Editor" (more specific pattern wins)
- ✅ Exact match beats wildcard when priorities are equal
- ✅ Test tool shows Editor selected due to specificity

---

### Scenario 5: No Matching Pattern - Default Role ✅

**Setup:**
- Multiple role mappings configured
- No mapping for `unmapped.com`

**Steps:**
1. Register user with email: `user@unmapped.com`
2. Check assigned role

**Expected Results:**
- ✅ User created successfully
- ✅ User assigned WordPress default role (usually "Subscriber")
- ✅ No custom role assignment applied

---

### Scenario 6: Add Role Mapping ✅

**Steps:**
1. Navigate to `Email Domain Restriction > Role Mappings`
2. Click "Add New"
3. Fill in form:
   - Domain: `newdomain.com`
   - Role: `Author`
   - Priority: `15`
4. Click "Add Role Mapping"

**Expected Results:**
- ✅ Success message displayed
- ✅ Mapping appears in list
- ✅ All fields saved correctly
- ✅ Priority displayed with correct badge color

---

### Scenario 7: Edit Role Mapping ✅

**Steps:**
1. Navigate to role mappings list
2. Click "Edit" on existing mapping
3. Change domain to `updateddomain.com`
4. Change priority to `30`
5. Click "Update Role Mapping"

**Expected Results:**
- ✅ Success message displayed
- ✅ Changes reflected in list
- ✅ Updated values saved to database

---

### Scenario 8: Delete Role Mapping ✅

**Steps:**
1. Navigate to role mappings list
2. Click "Delete" on a mapping
3. Confirm deletion in browser prompt

**Expected Results:**
- ✅ Confirmation dialog appears
- ✅ Mapping removed from list (with fade animation)
- ✅ No database errors
- ✅ If last mapping deleted, empty state shown

---

### Scenario 9: Bulk Delete Role Mappings 🔒

**Steps:**
1. Navigate to role mappings list
2. Select multiple mappings with checkboxes
3. Click "Delete Selected"
4. Confirm deletion

**Expected Results:**
- ✅ "Delete Selected" button only enabled when items checked
- ✅ Confirmation dialog appears
- ✅ All selected mappings deleted
- ✅ Success message shows count deleted

---

### Scenario 10: Export Role Mappings 🔒

**Steps:**
1. Navigate to role mappings list
2. Click "Export" button
3. Check downloaded file

**Expected Results:**
- ✅ JSON file downloads automatically
- ✅ Filename includes timestamp
- ✅ JSON format valid
- ✅ Contains all mappings with correct structure:
```json
[
  {
    "domain": "example.com",
    "role": "subscriber",
    "priority": 10
  }
]
```

---

### Scenario 11: Import Role Mappings 🔒

**Steps:**
1. Export existing mappings as backup
2. Click "Import" button
3. Select valid JSON file
4. Choose "Replace existing mappings"
5. Click "Import"

**Expected Results:**
- ✅ Success message shows count imported
- ✅ Old mappings replaced (if option selected)
- ✅ New mappings appear in list
- ✅ All values imported correctly

**Test Invalid Import:**
- Upload non-JSON file → Error message ❌
- Upload malformed JSON → Error message ❌
- Upload JSON with invalid role → Partial import with errors ⚠️

---

### Scenario 12: Test Role Assignment Tool 🔒

**Steps:**
1. Navigate to role mappings list
2. Click "Test Role Assignment" button
3. Enter email: `test@sales.contractor.com`
4. Click "Test"

**Expected Results:**
- ✅ Results displayed in modal
- ✅ Domain extracted correctly
- ✅ All matching patterns listed
- ✅ Selected role highlighted in green
- ✅ Priority values shown
- ✅ Conflict resolution explained visually

**Test Multiple Scenarios:**
- Email with no matches → "None (will use default)"
- Email with one match → That role selected
- Email with multiple matches → Highest priority/most specific selected

---

### Scenario 13: Validation - Invalid Domain ❌

**Steps:**
1. Try to add mapping with invalid domain patterns:
   - Empty domain
   - Domain with spaces: `my domain.com`
   - Invalid characters: `domain!.com`
   - Malformed wildcard: `**.example.com`

**Expected Results:**
- ❌ Error message displayed
- ❌ Mapping not created
- ✅ Form validation prevents submission

---

### Scenario 14: Validation - Invalid Role ❌

**Steps:**
1. Try to add mapping with non-existent role slug

**Expected Results:**
- ❌ Error message: "The specified role does not exist"
- ❌ Mapping not created

---

### Scenario 15: Validation - Invalid Priority ❌

**Steps:**
1. Try to add mapping with priority values:
   - Negative number: `-5`
   - Too high: `150`
   - Non-numeric: `high`

**Expected Results:**
- ❌ Error message displayed
- ❌ HTML5 validation prevents submission

---

### Scenario 16: Validation - Duplicate Mapping ❌

**Steps:**
1. Create mapping: `example.com` → `Subscriber`
2. Try to create same mapping again

**Expected Results:**
- ❌ Error message: "A role mapping already exists for this domain and role combination"
- ❌ Duplicate not created
- ✅ Original mapping unchanged

---

### Scenario 17: WooCommerce Integration 🔒

**Prerequisites:**
- WooCommerce installed and active
- Role mappings configured

**Steps:**
1. Add product to cart
2. Go to checkout
3. Check "Create an account?"
4. Enter email matching a role mapping
5. Complete checkout

**Expected Results:**
- ✅ Order completes successfully
- ✅ User account created
- ✅ Correct role assigned based on email domain

---

### Scenario 18: BuddyPress Integration 🔒

**Prerequisites:**
- BuddyPress installed and active
- Role mappings configured

**Steps:**
1. Navigate to BuddyPress registration page
2. Complete registration with email matching role mapping
3. Activate account

**Expected Results:**
- ✅ Registration succeeds
- ✅ Account activated
- ✅ Correct role assigned based on email domain

---

### Scenario 19: Select All Checkbox ✅

**Steps:**
1. Navigate to role mappings list with multiple items
2. Click "Select All" checkbox in table header
3. Verify all items checked
4. Click "Select All" again

**Expected Results:**
- ✅ First click: all items checked
- ✅ "Delete Selected" button enabled
- ✅ Second click: all items unchecked
- ✅ "Delete Selected" button disabled

---

### Scenario 20: Empty State Display ✅

**Steps:**
1. Delete all role mappings
2. Check page display

**Expected Results:**
- ✅ Empty state message shown
- ✅ Helpful text displayed
- ✅ "Add Your First Mapping" button prominent
- ✅ No table displayed
- ✅ Tool buttons still available

---

## Performance Testing

### Test Large Number of Mappings

**Setup:**
Create 100+ role mappings via import

**Tests:**
1. Page load time < 2 seconds
2. Search/filter functionality responsive
3. Bulk operations complete < 5 seconds
4. Test tool responds < 1 second

---

## Integration Testing

### Test with Other Plugins

Verify compatibility with:
- ✅ Ultimate Member (role assignment still works)
- ✅ WooCommerce (checkout role assignment)
- ✅ BuddyPress (registration role assignment)
- ✅ User Role Editor (custom roles recognized)

---

## Admin Interface Testing

### UI Elements Checklist

- ✅ Add/Edit forms display correctly
- ✅ Validation messages clear and helpful
- ✅ Success messages display and dismiss
- ✅ Modals open and close properly
- ✅ Tables responsive on mobile
- ✅ Buttons have proper hover states
- ✅ Priority badges color-coded correctly
- ✅ Wildcard badges display for wildcard patterns
- ✅ Help text clear and useful

### Browser Testing

Test in:
- ✅ Chrome (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Edge (latest)

---

## Database Verification

After creating/updating/deleting mappings:

```sql
-- Check mappings table
SELECT * FROM wp_edr_role_mappings ORDER BY priority DESC;

-- Verify no orphaned records
SELECT * FROM wp_edr_role_mappings WHERE role NOT IN
  (SELECT role_name FROM wp_roles);

-- Check indexes exist
SHOW INDEX FROM wp_edr_role_mappings;
```

---

## Security Testing

### Permission Checks

1. Login as Editor (not Administrator)
2. Try to access role mappings page
3. Should be denied access ❌

### Nonce Verification

1. Submit form without valid nonce
2. Should fail with security error ❌

### SQL Injection

1. Try to add mapping with SQL injection attempts:
   - Domain: `'; DROP TABLE wp_users; --`
   - Should be safely sanitized ✅

---

## Success Criteria

Phase 4 is complete when:

- ✅ All 20 test scenarios pass
- ✅ Wildcard domain matching works correctly
- ✅ Conflict resolution properly prioritizes
- ✅ Import/export functions work
- ✅ Bulk operations function correctly
- ✅ Test tool provides accurate results
- ✅ Form validation prevents invalid data
- ✅ Integration with WooCommerce works (if installed)
- ✅ Integration with BuddyPress works (if installed)
- ✅ No PHP errors in debug.log
- ✅ No JavaScript errors in browser console
- ✅ UI is responsive and intuitive
- ✅ Help documentation clear

---

## Troubleshooting

### Role Not Assigned

**Check:**
1. Is domain whitelisted?
2. Does role mapping exist?
3. Check WordPress default role setting
4. Use test tool to verify pattern matching

### Pattern Not Matching

**Check:**
1. Exact vs wildcard syntax
2. Case sensitivity (domains are case-insensitive)
3. Verify domain extraction from email
4. Check priority/specificity conflicts

### Import Fails

**Check:**
1. JSON file valid format
2. Role slugs exist in WordPress
3. Domain patterns valid
4. Check PHP error log

---

## Next Steps

After Phase 4 completion:
- **Phase 5:** Advanced Analytics Dashboard
- **Phase 6:** Rate Limiting & Anti-Abuse Enhancement
- **Phase 7:** Advanced Features (Geolocation, Webhooks, API)
- **Phase 8:** Testing & Documentation
- **Phase 9:** Launch

---

*Last Updated: November 2025*
*Version: 1.0.0*
