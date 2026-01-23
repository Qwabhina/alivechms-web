# Members Module - Quick Start Guide

**For Developers Working on the Members Module**

---

## 📁 File Structure

```
Members Module Files:
├── Backend
│   └── core/Member.php                    # Member business logic
│
├── Frontend (PHP)
│   └── public/dashboard/members.php       # Main page
│
└── Frontend (JavaScript) - MODULAR!
    └── public/assets/js/modules/members/
        ├── index.js                       # Start here - main entry point
        ├── state.js                       # State management
        ├── api.js                         # API calls
        ├── table.js                       # Data grid
        ├── stats.js                       # Statistics & charts
        ├── form.js                        # Form management
        ├── form-stepper.js                # Wizard UI
        └── form-validator.js              # Validation
```

---

## 🎯 Common Tasks

### Adding a New Field to Member Form

1. **Database:** Add column to `churchmember` table

   ```sql
   ALTER TABLE churchmember ADD COLUMN NewField VARCHAR(100);
   ```

2. **Backend:** Update `core/Member.php`

   ```php
   // In register() method
   'NewField' => $data['new_field'] ?? null,

   // In update() method
   'NewField' => $data['new_field'] ?? null,
   ```

3. **Frontend HTML:** Edit `public/dashboard/members.php`

   ```html
   <div class="col-md-6">
     <label class="form-label">New Field</label>
     <input type="text" class="form-control" id="newField" />
   </div>
   ```

4. **Frontend JS:** Edit `form.js`

   ```javascript
   // In collectFormData()
   new_field: (document.getElementById("newField").value.trim(),
     // In populateForm()
     (document.getElementById("newField").value = member.NewField || ""));
   ```

5. **Table (Optional):** Edit `table.js` to add column
   ```javascript
   {
      key: 'NewField',
      title: 'New Field',
      exportable: true,
      render: (value) => value || '-'
   }
   ```

### Modifying Validation Rules

Edit `form-validator.js`:

```javascript
validateStep(step) {
   if (step === 0) {
      const newField = document.getElementById('newField').value.trim();
      if (!newField) {
         Alerts.warning('New field is required');
         return false;
      }
   }
   return true;
}
```

### Changing Table Columns

Edit `table.js` → `getColumns()` method:

```javascript
{
   key: 'FieldName',           // Database field name
   title: 'Display Name',      // Column header
   width: '100px',             // Optional width
   sortable: true,             // Enable sorting
   exportable: true,           // Include in exports
   render: (value, row) => {   // Custom rendering
      return value || '-';
   }
}
```

### Adding a New API Endpoint

1. **Backend:** Add method to `core/Member.php`
2. **Route:** Add route in `routes/MemberRoutes.php`
3. **Frontend:** Add method to `api.js`
   ```javascript
   async newMethod(params) {
      return await api.get(`${this.baseUrl}/new-endpoint`, params);
   }
   ```

---

## 🐛 Debugging

### Check Console

```javascript
// All modules log their actions
console.log("✓ Members table initialized");
console.log("✓ Loaded 150 families, 8 roles");
```

### Common Issues

**Module not loading?**

- Check browser console for errors
- Verify file paths are correct
- Ensure `type="module"` in script tag

**Form not saving?**

- Check `form.js` → `collectFormData()`
- Verify field IDs match HTML
- Check backend validation

**Table not displaying?**

- Check API endpoint is working
- Verify column keys match backend response
- Check browser console for errors

---

## 🧪 Testing Checklist

```
Basic Operations:
□ View members list
□ Search members
□ Sort columns
□ Export data

Create Member:
□ Fill all required fields
□ Upload profile picture
□ Add phone numbers
□ Assign to family
□ Create with/without login
□ Validation works
□ Save successfully

Edit Member:
□ Load existing data
□ Update fields
□ Save changes

Delete Member:
□ Confirmation shows
□ Soft delete works
□ Table refreshes
```

---

## 📚 Key Concepts

### State Management

```javascript
// Centralized in state.js
State.currentMemberId; // Currently selected member
State.isEditMode; // Create vs Edit
State.currentStep; // Wizard step (0-2)
State.profilePictureFile; // Uploaded file
```

### API Layer

```javascript
// All backend calls in api.js
await this.api.get(id); // Get member
await this.api.create(data); // Create member
await this.api.update(id, data); // Update member
await this.api.delete(id); // Delete member
```

### Form Wizard

```javascript
// 3 steps managed by form-stepper.js
Step 0: Personal Details
Step 1: Contact & Family
Step 2: Account Setup (optional)
```

---

## 🎨 Styling

All styles are in `public/dashboard/members.php` at the bottom:

- `.stepper` - Wizard steps
- `.profile-upload-zone` - Photo upload
- `.stat-card` - Statistics cards
- `.phone-row` - Phone number rows

---

## 🔗 Dependencies

Required libraries (already included):

- Bootstrap 5 - UI framework
- Chart.js - Statistics charts
- Flatpickr - Date picker
- Choices.js - Enhanced selects
- QMGrid - Data table

---

## 💡 Tips

1. **Always test in dev first** - Don't break production
2. **Check schema alignment** - Field names must match database
3. **Use browser DevTools** - Console, Network, Elements tabs
4. **Follow the pattern** - Consistency is key
5. **Document changes** - Help future developers

---

## 📞 Need Help?

1. Check `docs/MEMBERS_MODULE_REFACTOR.md` for detailed docs
2. Look at existing code for examples
3. Check browser console for errors
4. Review backend logs for API issues

---

**Last Updated:** January 22, 2026  
**Module Version:** 6.0.0
