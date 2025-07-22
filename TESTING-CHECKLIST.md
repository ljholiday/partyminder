# PartyMinder Communities Foundation Testing Checklist

## Pre-Testing Setup
- [ ] Ensure WordPress site is running locally
- [ ] PartyMinder plugin is activated
- [ ] No PHP errors in logs during plugin activation
- [ ] Existing events and conversations still work

## Phase 1: Safety Tests (Features Disabled)

### 1. Plugin Activation Safety
- [ ] Plugin activates without errors
- [ ] No fatal errors or warnings in PHP logs
- [ ] Existing PartyMinder functionality still works
- [ ] Events page loads correctly
- [ ] Conversations page loads correctly
- [ ] Create event still works
- [ ] RSVP system still works

### 2. Database Tables Created
Navigate to your database (phpMyAdmin or similar) and verify these tables exist:
- [ ] `wp_partyminder_communities`
- [ ] `wp_partyminder_community_members`
- [ ] `wp_partyminder_community_events` 
- [ ] `wp_partyminder_member_identities`
- [ ] `wp_partyminder_community_invitations`
- [ ] `wp_partyminder_at_protocol_sync`

### 3. Feature Flags Default State
Go to WordPress Admin → PartyMinder → Settings:
- [ ] "Communities Feature" checkbox is UNCHECKED by default
- [ ] "AT Protocol Integration" checkbox is UNCHECKED by default
- [ ] Warning messages about feature flags are visible
- [ ] Other settings still work normally

### 4. Admin Menu Safety
In WordPress Admin → PartyMinder:
- [ ] Dashboard loads normally
- [ ] Create Event works
- [ ] All Events shows existing events
- [ ] AI Assistant works (if configured)
- [ ] Settings page loads
- [ ] **NO "Communities" menu item visible** (features disabled)
- [ ] **NO "Members" menu item visible** (features disabled)

### 5. Frontend Safety
Visit your site frontend:
- [ ] Events page loads normally
- [ ] Individual event pages work
- [ ] RSVP forms work
- [ ] Conversations page works
- [ ] No JavaScript errors in browser console
- [ ] No communities-related content visible anywhere

## Phase 2: Feature Flag Tests

### 6. Enable Communities Feature
Go to Admin → PartyMinder → Settings:
- [ ] Check "Enable communities feature" 
- [ ] Save settings
- [ ] Page refreshes successfully
- [ ] **"Communities" menu item now appears** in PartyMinder admin menu
- [ ] No PHP errors

### 7. Test Communities Admin Page
Go to Admin → PartyMinder → Communities:
- [ ] Page loads successfully
- [ ] Shows "Community Statistics" with zeros
- [ ] Shows "No communities created yet" message
- [ ] Stats grid displays properly
- [ ] **"Members" submenu item appears**

### 8. Test Members Admin Page  
Go to Admin → PartyMinder → Members:
- [ ] Page loads successfully
- [ ] Shows member identity statistics
- [ ] Shows "No member identities created yet" message
- [ ] Stats show all zeros initially

### 9. Enable AT Protocol Feature
Go to Admin → PartyMinder → Settings:
- [ ] Check "Enable AT Protocol DID generation"
- [ ] Save settings
- [ ] **AT Protocol Tools section appears** on Communities page
- [ ] Members page shows additional AT Protocol columns

## Phase 3: Functionality Tests (Features Enabled)

### 10. Test Community Manager Class
Create a test script or use WordPress admin:
- [ ] PartyMinder_Community_Manager class loads
- [ ] can_user_create_community() returns true for admin users
- [ ] Feature flag functions work correctly

### 11. Test Member Identity Creation
With AT Protocol enabled:
- [ ] Log out and log back in 
- [ ] Go to Admin → PartyMinder → Members
- [ ] **Your user should now have a DID generated**
- [ ] Member identity table should show your user
- [ ] DID format should be: `did:partyminder:user:XXXX`

### 12. Test User Registration (if possible)
- [ ] Create a new WordPress user
- [ ] Check Members page for automatic DID creation
- [ ] Verify member count increases

### 13. Test Community Creation (Backend)
Using WordPress admin or custom code:
- [ ] Try to create a test community programmatically
- [ ] Verify community appears in Communities admin page
- [ ] Check community gets AT Protocol DID when AT Protocol enabled
- [ ] Verify member count updates

## Phase 4: Safety Verification

### 14. Disable Features Again
Go back to Settings:
- [ ] Uncheck "Enable communities feature"
- [ ] Uncheck "Enable AT Protocol DID generation"  
- [ ] Save settings
- [ ] **Communities menu disappears**
- [ ] **Members menu disappears**
- [ ] No errors occur
- [ ] Existing data preserved in database

### 15. Performance Test
- [ ] Frontend loads at normal speed
- [ ] Admin pages load normally
- [ ] No additional database queries when features disabled
- [ ] Plugin doesn't slow down existing functionality

### 16. Cross-Browser Test
Test in different browsers:
- [ ] Chrome: All functionality works
- [ ] Firefox: All functionality works  
- [ ] Safari: All functionality works
- [ ] No JavaScript errors in any browser

## Phase 5: Production Readiness

### 17. Error Log Check
Check WordPress error logs:
- [ ] No PHP fatal errors
- [ ] No undefined function errors
- [ ] No class not found errors
- [ ] Only expected "feature coming soon" logs for AT Protocol

### 18. Database Integrity
- [ ] All original tables intact
- [ ] All original data preserved
- [ ] New tables created with proper structure
- [ ] No orphaned data

### 19. Plugin Deactivation/Reactivation
- [ ] Plugin deactivates cleanly
- [ ] Plugin reactivates without errors
- [ ] Feature flags remain as set
- [ ] Database tables preserved

### 20. Cleanup
- [ ] Remove test files: `test-communities-foundation.php`, `test-simple.php`
- [ ] Remove this testing checklist if deploying to production
- [ ] Document any issues found

## Test Results

**Date Tested:** ___________  
**Tested By:** ___________  
**WordPress Version:** ___________  
**PHP Version:** ___________  

**Overall Result:** 
- [ ] ✅ PASS - Safe for production deployment
- [ ] ⚠️ PARTIAL - Some issues need fixing
- [ ] ❌ FAIL - Major issues found

**Notes:**
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________

## Next Steps After Testing

If all tests pass:
1. **Deploy to production** with features disabled
2. **Enable communities** when ready to use
3. **Enable AT Protocol** when ready for cross-site features
4. **Begin Phase 2 development** (community pages and UI)

If tests fail:
1. Review error logs
2. Fix identified issues
3. Re-run failed tests
4. Document any workarounds needed