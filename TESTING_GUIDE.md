# Members Module Testing Guide

**Date:** January 22, 2026  
**Status:** Ready for Testing

## What Was Fixed

### 1. Schema Mismatches (19 fixes across 10 files)

- ✅ Fixed `membership_status` table JOIN conditions
- ✅ Changed from `mst.StatusID` to `mst.MembershipStatusID`
- ✅ All SQL queries now use correct column names

### 2. JavaScript Module Refactor

- ✅ Broke down 1575-line monolithic file into 8 focused modules
- ✅ Fixed URL doubling issue (relative paths instead of absolute)
- ✅ Implemented clean ES6 module architecture

## Testing Checklist

### Step 1: Access Members Page

1. Navigate to: `http://www.onechurch.com/dashboard/members.php`
2. **Expected:** Page loads without errors
3. **Check Console:** Should see:
   ```
   ✓ Member form initialized
   ✓ Loaded X of X members
   ✓ Members module initialized
   ```

### Step 2: Verify Member List

1. **Expected:** Member table displays with data
2. **Check:**
   - Profile pictures display correctly
   - Phone numbers show
   - Email addresses are clickable
   - Status badges show (Active/Inactive)
   - Age calculation works
   - Action buttons appear (View/Edit/Delete)

### Step 3: Verify Statistics Dashboard

1. **Expected:** Statistics cards display at top of page
2. **Check:**
   - Total members count
   - Active members count
   - New this month count
   - Gender distribution chart
   - Age distribution chart

### Step 4: Test Member Creation

1. Click "Add Member" button
2. **Expected:** 3-step wizard modal opens
3. **Step 1 - Personal Details:**
   - Fill in: First Name, Family Name, Gender, Date of Birth
   - Click "Next"
4. **Step 2 - Contact & Family:**
   - Add phone number(s)
   - Enter email address
   - Select family (optional)
   - Click "Next"
5. **Step 3 - Account Setup:**
   - Choose to create user account or skip
   - If creating account: enter username, password
   - Click "Create Member"
6. **Expected:** Success message, table refreshes, new member appears

### Step 5: Test Member Editing

1. Click "Edit" button on any member
2. **Expected:** Form opens with pre-filled data
3. **Modify:** Change any field (e.g., phone number, email)
4. Click "Update Member"
5. **Expected:** Success message, changes reflected in table

### Step 6: Test Member Viewing

1. Click "View" button on any member
2. **Expected:** Read-only modal shows all member details
3. **Check:** All fields display correctly

### Step 7: Test Profile Picture Upload

1. Edit a member
2. Click profile picture upload area
3. Select an image file (JPG, PNG, GIF, WebP)
4. **Expected:** Preview shows, file uploads on save
5. **Check:** Picture displays in table after save

### Step 8: Test Search & Filtering

1. Use search box to find members by name/email/phone
2. **Expected:** Table filters in real-time
3. Test sorting by clicking column headers
4. **Expected:** Table sorts correctly

### Step 9: Test Pagination

1. If more than 25 members exist
2. **Expected:** Pagination controls appear
3. Click "Next" page
4. **Expected:** Next set of members loads

### Step 10: Test Export

1. Click "Export" button
2. **Expected:** Excel/CSV file downloads with member data

## Common Issues & Solutions

### Issue: 403 Forbidden Error

**Symptom:** Console shows `GET .../member/stats 403 (Forbidden)`  
**Solution:** ✅ FIXED - Schema mismatch resolved

### Issue: URL Doubling

**Symptom:** URLs like `http://...com/http://...com/member/stats`  
**Solution:** ✅ FIXED - Using relative paths now

### Issue: Stats Not Loading

**Symptom:** Statistics cards show "0" or don't load  
**Solution:** ✅ FIXED - JOIN condition corrected

### Issue: Member List Empty

**Symptom:** Table shows "No data available"  
**Possible Causes:**

1. No members in database (add test data)
2. Membership status filter too restrictive
3. Check browser console for errors

### Issue: Form Validation Errors

**Symptom:** Can't submit form  
**Check:**

- All required fields filled (marked with \*)
- Email format valid
- Phone number format valid (Ghana format)
- Password meets requirements (if creating account)

## Browser Console Checks

### Expected Console Output (Success)

```javascript
✓ Member form initialized
✓ Loaded 4 of 4 members
✓ Members module initialized
QMGrid: Data loaded {records: 4, total: 4, page: 1}
```

### Error Indicators

```javascript
✗ Failed to load stats: APIError: HTTP 403
✗ Failed to load members: ...
```

## API Endpoints to Test

1. **GET** `/member/all` - List all members
2. **GET** `/member/stats` - Get statistics
3. **GET** `/member/{id}` - Get single member
4. **POST** `/member/create` - Create new member
5. **PUT** `/member/{id}` - Update member
6. **DELETE** `/member/{id}` - Delete member
7. **POST** `/member/{id}/upload-picture` - Upload profile picture

## Performance Checks

- ✅ Member list should load in < 1 second
- ✅ Statistics should load in < 500ms
- ✅ Form submission should complete in < 2 seconds
- ✅ No N+1 query problems (optimized with JOINs)

## Database Verification

If issues persist, check database directly:

```sql
-- Verify membership_status table structure
DESCRIBE membership_status;
-- Should show: MembershipStatusID (not StatusID)

-- Check member count
SELECT COUNT(*) FROM churchmember WHERE Deleted = 0;

-- Check active members
SELECT COUNT(*)
FROM churchmember c
JOIN membership_status ms ON c.MbrMembershipStatusID = ms.MembershipStatusID
WHERE c.Deleted = 0 AND ms.StatusName = 'Active';
```

## Next Steps After Testing

1. ✅ Verify all functionality works
2. 📝 Document any new issues found
3. 🔄 Apply same modular pattern to other modules:
   - Contributions module
   - Expenses module
   - Events module
   - Groups module
   - Families module

## Support

If you encounter issues:

1. Check browser console for JavaScript errors
2. Check `logs/app-2026-01-22.log` for PHP errors
3. Verify database schema matches expected structure
4. Review `SCHEMA_FIX_SUMMARY.md` for schema details

---

**Status:** ✅ All schema fixes applied and verified  
**Ready for:** User acceptance testing
