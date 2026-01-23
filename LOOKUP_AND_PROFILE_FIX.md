# Lookup Endpoint and Profile Picture Fixes

**Date:** January 23, 2026  
**Status:** ✅ COMPLETED

---

## Issue 1: Lookup Endpoint Failing with IsActive Column Error

### Problem

The `/lookups/all` endpoint was returning a 500 error:

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'IsActive' in 'where clause'
```

The error occurred when querying the `asset_condition` table, which doesn't have an `IsActive` column.

### Root Cause

Several small reference/lookup tables don't have the `IsActive` column for soft delete functionality. The queries in `LookupRoutes.php` were incorrectly trying to filter by `WHERE IsActive = 1` on all tables.

### Tables WITHOUT IsActive Column

- `phone_type` ✓ (already handled correctly)
- `asset_condition` ✗ (caused the error)
- `asset_status` ✗
- `communication_channel` ✗
- `communication_status` ✗
- `family_relationship` ✗
- `document_category` ✗

### Tables WITH IsActive Column

- `marital_status` ✓
- `education_level` ✓
- `membership_status` ✓
- `payment_method` ✓
- `branch` ✓
- `church_role` ✓
- `contribution_type` ✓
- `expense_category` ✓
- `milestone_type` ✓
- `pledge_type` ✓
- `group_type` ✓

### Solution

Removed `WHERE IsActive = 1` clause from queries for tables that don't have this column.

**File Modified:** `routes/LookupRoutes.php`

**Changes:**

- Line 78-82: `asset_condition` - Removed `WHERE IsActive = 1`
- Line 84-88: `asset_status` - Removed `WHERE IsActive = 1`
- Line 90-94: `communication_channel` - Removed `WHERE IsActive = 1`
- Line 96-100: `communication_status` - Removed `WHERE IsActive = 1`
- Line 102-106: `family_relationship` - Removed `WHERE IsActive = 1`
- Line 108-112: `document_category` - Removed `WHERE IsActive = 1`

---

## Issue 2: Profile Picture 404 Errors

### Problem

Member profile pictures were returning 404 errors:

```
GET http://www.onechurch.com/uploads/members/member_697249b997fed.jpg 404 (Not Found)
```

The correct URL should be:

```
GET http://www.onechurch.com/public/uploads/members/member_697249b997fed.jpg
```

### Root Cause

The database stores profile picture paths as: `uploads/members/member_697249b997fed.jpg`

The actual file location is: `public/uploads/members/member_697249b997fed.jpg`

In `form.js`, the code was constructing incorrect URLs:

- Line 223: `${Config.API_BASE_URL}/../${member.MbrProfilePicture}` → `http://www.onechurch.com/../uploads/...` ✗
- Line 428: `${Config.API_BASE_URL}/../${member.MbrProfilePicture}` → `http://www.onechurch.com/../uploads/...` ✗

### Solution

Changed the path construction to prepend `/public/` instead of `/../`:

**File Modified:** `public/assets/js/modules/members/form.js`

**Changes:**

- Line 223: Changed to `${Config.API_BASE_URL}/public/${member.MbrProfilePicture}`
- Line 428: Changed to `${Config.API_BASE_URL}/public/${member.MbrProfilePicture}`

**Note:** The QMGrid helper (`qmgrid-helper.js` line 607) was already correct:

```javascript
const imageUrl = `${Config.API_BASE_URL}/public/${profilePicture}`;
```

---

## Testing Checklist

- [x] Verify `/lookups/all` endpoint returns data without errors
- [x] Verify member edit form loads successfully
- [x] Verify member create form loads successfully
- [x] Verify profile pictures display correctly in member table
- [x] Verify profile pictures display correctly in member view modal
- [x] Verify profile pictures display correctly in member edit form

---

## Impact

### Before Fix

- ❌ Cannot open member edit form (lookups endpoint failing)
- ❌ Cannot open member create form (lookups endpoint failing)
- ❌ Profile pictures show broken image icon (404 errors)

### After Fix

- ✅ Member edit form loads with all lookup data
- ✅ Member create form loads with all lookup data
- ✅ Profile pictures display correctly throughout the application

---

## Files Modified

1. `routes/LookupRoutes.php` - Removed `WHERE IsActive = 1` from 6 tables
2. `public/assets/js/modules/members/form.js` - Fixed profile picture URL construction (2 locations)

---

## Related Issues

This fix resolves:

- User Query #5: "Unknown column 'IsActive' in 'where clause'" error
- User Query #5: Profile picture 404 errors
- User Query #5: "I noticed too that the members profile page has changed!"

---

## Notes

- Small reference tables like `phone_type`, `asset_condition`, etc. typically don't need soft delete functionality
- The `IsActive` column is used for soft deletes on larger entity tables
- Profile picture paths in the database are relative to the `public/` directory
- All profile picture URLs must include `/public/` prefix when constructing full URLs
