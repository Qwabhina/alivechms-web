# Quick Reference Guide - AliveChMS Members Module

**Last Updated:** January 23, 2026

---

## 🎯 What Was Fixed

### 1. Lookup Endpoint (500 Error)

**File:** `routes/LookupRoutes.php`  
**Fix:** Removed `WHERE IsActive = 1` from tables without that column

### 2. Profile Pictures (404 Error)

**File:** `public/assets/js/modules/members/form.js`  
**Fix:** Changed `/../` to `/public/` in URL construction

### 3. Modular Structure Alignment

**Files:** All files in `public/assets/js/modules/members/`  
**Fix:** Updated to match original `members.js` logic

---

## 📁 File Structure

```
public/assets/js/modules/members/
├── index.js          ← Main entry, error handling, auto-refresh
├── state.js          ← State management
├── api.js            ← API calls
├── table.js          ← Table rendering (QMGrid)
├── form.js           ← Form handling, member view, print
├── stats.js          ← Statistics and charts
├── form-stepper.js   ← Multi-step form navigation
└── form-validator.js ← Form validation
```

---

## 🔧 Key Features

### Error Handling

```javascript
// Handles: 401, 403, 404, 422, 500, network errors
handleAPIError(error, "Context");
```

### Auto-Refresh

- Refreshes every 5 minutes
- Pauses when tab hidden
- Resumes when tab visible

### Member View

- Comprehensive profile display
- Print functionality
- Quick actions (Email, Call, Print)

### Form Handling

- Multi-step stepper
- Profile picture upload
- Phone number management
- FormData for file uploads

---

## 🚀 Testing Checklist

- [x] Login works
- [x] Member list displays
- [x] Statistics show
- [x] Charts render
- [x] Add member works
- [x] Edit member works
- [x] View member works
- [x] Delete member works
- [x] Profile pictures display
- [x] Print works
- [x] Export works
- [x] Filters work
- [x] Auto-refresh works

---

## 🐛 Common Issues

### Profile Pictures Not Showing

1. Check file exists in `public/uploads/members/`
2. Clear browser cache
3. Check file permissions

### Lookup Data Not Loading

1. Check `routes/LookupRoutes.php`
2. Verify database tables exist
3. Check browser console for errors

### Form Not Saving

1. Check network tab for API errors
2. Verify FormData is being sent
3. Check server logs

---

## 📊 Database Tables

### Tables WITH IsActive Column

- marital_status
- education_level
- membership_status
- payment_method
- branch
- church_role
- contribution_type
- expense_category
- milestone_type
- pledge_type
- group_type

### Tables WITHOUT IsActive Column

- phone_type
- asset_condition
- asset_status
- communication_channel
- communication_status
- family_relationship
- document_category

---

## 🔗 API Endpoints

### Member Endpoints

- `GET /member/all` - List members
- `GET /member/view/{id}` - View member
- `POST /member/create` - Create member
- `PUT /member/update/{id}` - Update member
- `DELETE /member/delete/{id}` - Delete member
- `GET /member/stats` - Get statistics

### Lookup Endpoints

- `GET /lookups/all` - Get all lookup data (combined)
- `GET /family/all?limit=1000` - Get families

---

## 💡 Tips

### Development

- Use browser DevTools (F12) for debugging
- Check Network tab for API calls
- Check Console for JavaScript errors
- Check Application tab for localStorage

### Performance

- Auto-refresh only runs when tab is visible
- Lookup data is cached in state
- Charts are destroyed before re-rendering

### Security

- All API calls require authentication
- Permissions checked before actions
- File uploads validated (type and size)

---

## 📚 Documentation Files

1. **LOOKUP_AND_PROFILE_FIX.md** - Detailed fix documentation
2. **MODULAR_ALIGNMENT_SUMMARY.md** - Modular structure details
3. **SESSION_COMPLETE_SUMMARY.md** - Complete session overview
4. **QUICK_REFERENCE.md** - This file

---

## 🎓 Code Examples

### Adding a New Column to Table

```javascript
// In table.js, add to getColumns() array:
{
   key: 'ColumnName',
   title: 'Display Name',
   width: '100px',
   exportable: true,
   render: (value) => {
      return value || '-';
   }
}
```

### Adding a New Stat Card

```javascript
// In stats.js, add to cards array:
{
   title: 'Card Title',
   value: stats.value || 0,
   change: 'Description',
   icon: 'bootstrap-icon-name',
   color: 'primary|success|danger|warning'
}
```

### Adding a New Form Field

```javascript
// In form.js save() method:
formData.append("field_name", document.getElementById("fieldId").value);
```

---

## ✅ System Status

**Current Status:** PRODUCTION READY ✅

All features working:

- ✅ Member CRUD operations
- ✅ Profile pictures
- ✅ Statistics and charts
- ✅ Print functionality
- ✅ Export functionality
- ✅ Auto-refresh
- ✅ Error handling

---

## 📞 Support

### Error Logs

- Backend: `logs/app-2026-01-23.log`
- Browser: Developer Console (F12)

### Quick Fixes

1. Clear browser cache: Ctrl+Shift+Delete
2. Hard reload: Ctrl+Shift+R
3. Check console: F12 → Console tab
4. Check network: F12 → Network tab

---

**Remember:** The modular structure provides better organization while maintaining all original features!
