# RBAC Security Audit Report

**Date:** January 17, 2026  
**System:** AliveChMS Role-Based Access Control  
**Status:** CRITICAL ISSUES FOUND

## Executive Summary

The RBAC system has several critical security vulnerabilities and design flaws that could lead to unauthorized access, privilege escalation, and data breaches.

---

## 🔴 CRITICAL ISSUES

### 1. **Multiple Role Assignment Vulnerability**

**Severity:** CRITICAL  
**Location:** `core/Auth.php`, `core/Member.php`, `core/Role.php`

**Problem:**

- Users can have multiple roles via `memberrole` table (many-to-many)
- `Auth::checkPermission()` checks ALL roles a user has
- `Role::assignToMember()` updates `churchmember.ChurchRoleID` (single role)
- **INCONSISTENCY:** Two different role assignment mechanisms exist simultaneously

**Attack Vector:**

```sql
-- Attacker adds themselves to Admin role via memberrole table
INSERT INTO memberrole (MbrID, ChurchRoleID) VALUES (attacker_id, admin_role_id);
-- Now has admin permissions while churchmember.ChurchRoleID shows "Member"
```

**Impact:** Privilege escalation, unauthorized access to all system functions

---

### 2. **No Permission Caching - Performance & Race Conditions**

**Severity:** HIGH  
**Location:** `Auth::checkPermission()`

**Problem:**

- Every API request performs 4-table JOIN to fetch permissions
- No caching mechanism
- Permissions can change mid-request
- Race condition: User permissions revoked but token still valid for 30 minutes

**Impact:**

- Performance degradation under load
- Inconsistent authorization state
- Delayed permission revocation

---

### 3. **Frontend Permission Bypass**

**Severity:** CRITICAL  
**Location:** `public/assets/js/core/auth.js`

**Problem:**

- Frontend has hardcoded role-to-permission mapping (`getRolePermissions()`)
- Backend has database-driven permissions
- **MISMATCH:** Frontend and backend permission systems are completely separate
- Frontend checks can be bypassed via browser DevTools

**Attack Vector:**

```javascript
// In browser console:
Auth._user.permissions = Object.values(Config.PERMISSIONS);
// Now has all permissions on frontend, can access any UI
```

**Impact:** UI-level access control bypass, unauthorized feature access

---

### 4. **Missing Permission Checks in Routes**

**Severity:** CRITICAL  
**Location:** Multiple route files

**Problem:**

- `role/all` endpoint has NO authentication (`self::authenticate(false)`)
- Anyone can list all roles and their permissions
- Information disclosure vulnerability

**Found in:**

- `routes/RoleRoutes.php` - Line 82: `self::authenticate(false)`

**Impact:** Information disclosure, reconnaissance for attackers

---

### 5. **Token Contains Roles, Not Permissions**

**Severity:** HIGH  
**Location:** `Auth::generateToken()`

**Problem:**

- JWT token contains `role` array (role names)
- Does NOT contain actual permissions
- Every request must query database for permissions
- Token refresh doesn't update permissions

**Impact:**

- Permission changes require re-login
- Performance overhead
- Stale permissions in active sessions

---

### 6. **No Audit Trail for Permission Changes**

**Severity:** MEDIUM  
**Location:** `Role::assignPermissions()`, `Permission::delete()`

**Problem:**

- Permission changes are logged to file only
- No database audit trail
- Cannot track who changed what permissions when
- No rollback capability

**Impact:** Compliance issues, forensic investigation difficulties

---

### 7. **Weak Role Deletion Protection**

**Severity:** MEDIUM  
**Location:** `Role::delete()`

**Problem:**

- Only checks `memberrole` table
- Doesn't check `churchmember.ChurchRoleID`
- Inconsistent with dual role assignment system

---

### 8. **No Rate Limiting on Permission Checks**

**Severity:** LOW  
**Location:** `Auth::checkPermission()`

**Problem:**

- No rate limiting on authorization checks
- Attacker can brute-force permission names
- Timing attacks possible

---

## 🟡 DESIGN FLAWS

### 9. **Dual Role Assignment System**

**Problem:** Two mechanisms for assigning roles:

1. `churchmember.ChurchRoleID` (single role, used by `Role::assignToMember()`)
2. `memberrole` table (multiple roles, used by `Auth::checkPermission()`)

**Recommendation:** Choose ONE system and remove the other

---

### 10. **No Role Hierarchy**

**Problem:**

- Flat role structure
- No inheritance (e.g., Admin should inherit all permissions)
- Hardcoded "Admin" check in frontend

---

### 11. **Permission Names Not Validated**

**Problem:**

- No enum/whitelist of valid permission names
- Typos can create orphaned permissions
- No naming convention enforcement

---

## 📋 RECOMMENDATIONS

### Immediate Actions (Critical)

1. **Fix Role Assignment Inconsistency**

   - Remove `churchmember.ChurchRoleID` column
   - Use ONLY `memberrole` table for all role assignments
   - Update all queries to use `memberrole`

2. **Add Permission Caching**

   - Cache user permissions in JWT token
   - Implement Redis/Memcached for permission cache
   - Add cache invalidation on permission changes

3. **Remove Frontend Permission Checks**

   - Frontend should ONLY hide UI elements
   - ALL authorization must happen on backend
   - Remove `getRolePermissions()` hardcoded mapping

4. **Secure Public Endpoints**

   - Add authentication to `role/all`
   - Create separate public endpoint for role dropdown (names only)

5. **Add Audit Trail**
   - Create `permission_audit` table
   - Log all permission changes with user, timestamp, old/new values

### Short-term Improvements

6. **Implement Permission Caching**
7. **Add Role Hierarchy**
8. **Create Permission Enum**
9. **Add Rate Limiting**
10. **Implement Permission Versioning**

---

## 🔧 PROPOSED FIXES

See `RBAC_FIXES.md` for detailed implementation plan.
