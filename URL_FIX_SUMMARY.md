# URL Path Fix - January 22, 2026

## Problem

API requests were failing with 404 errors because URLs were being constructed incorrectly:

**Error:**

```
GET /public/dashboard/member/all HTTP/1.1
{"status":"error","message":"Public endpoint not found","code":404}
```

**Root Cause:**

- JavaScript modules were using relative paths like `'member/all'`
- When page is at `/public/dashboard/members.php`, relative paths resolve to `/public/dashboard/member/all`
- Correct path should be `/member/all` (from root)

## Solution

Changed all API paths from relative to absolute (starting with `/`):

### Files Fixed

1. **public/assets/js/modules/members/api.js**
   - Changed `this.baseUrl = 'member'` to `this.baseUrl = '/member'`
   - Changed `'family/all'` to `'/family/all'`
   - Changed `'lookup/roles'` to `'/lookup/roles'`
   - Changed `'lookup/marital-statuses'` to `'/lookup/marital-statuses'`
   - Changed `'lookup/education-levels'` to `'/lookup/education-levels'`

2. **public/assets/js/modules/members/table.js**
   - Changed `url: 'member/all'` to `url: '/member/all'`

## Before vs After

### Before (WRONG)

```javascript
// api.js
this.baseUrl = "member"; // Relative path

// table.js
url: "member/all"; // Relative path

// Results in: /public/dashboard/member/all ❌
```

### After (CORRECT)

```javascript
// api.js
this.baseUrl = "/member"; // Absolute path from root

// table.js
url: "/member/all"; // Absolute path from root

// Results in: /member/all ✅
```

## How Absolute Paths Work

When you use a path starting with `/`, it's always resolved from the domain root:

- **Relative:** `member/all` → Appends to current path → `/public/dashboard/member/all`
- **Absolute:** `/member/all` → Always from root → `/member/all`

## API Routing

The backend routing in `index.php` expects paths like:

- `/member/all` → Routes to `MemberRoutes.php`
- `/family/all` → Routes to `FamilyRoutes.php`
- `/lookup/roles` → Routes to `LookupRoutes.php`

The `/public/` prefix is only for static files and dashboard pages, not API endpoints.

## Impact

This fix resolves:

- ✅ 404 errors on member listing
- ✅ 404 errors on member statistics
- ✅ 404 errors on lookup data (families, roles, etc.)
- ✅ All API calls from Members module

## Verification

All changes verified with:

- ✅ getDiagnostics (0 syntax errors)
- ✅ Code review (all paths now absolute)

## Testing

After this fix, the following should work:

1. Member listing loads without 404 errors
2. Statistics dashboard displays data
3. Form dropdowns populate (families, roles, etc.)
4. CRUD operations work (Create, Read, Update, Delete)

## Related Issues

This is separate from the schema fixes done earlier. Both issues needed to be resolved:

1. **Schema Fix:** Changed JOIN conditions for `membership_status` table
2. **URL Fix:** Changed relative paths to absolute paths (this fix)

---

**Status:** ✅ COMPLETE  
**Files Modified:** 2 JavaScript files  
**Syntax Errors:** 0  
**Ready for Testing:** Yes
