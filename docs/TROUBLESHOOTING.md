# Troubleshooting Guide - AliveChMS

**Common issues and solutions**

---

## 🔴 API URL Issues

### Problem: Doubled URL in API Calls

```
Error: GET http://www.onechurch.com/http://www.onechurch.com/member/stats 403
```

**Cause:** Using `Config.API_BASE_URL` in module when the API helper already adds it.

**Solution:** Use relative paths in API modules.

**Wrong:**

```javascript
this.baseUrl = `${Config.API_BASE_URL}/member`;
await api.get(`${Config.API_BASE_URL}/family/all`);
```

**Correct:**

```javascript
this.baseUrl = "member";
await api.get("family/all");
```

**Files to check:**

- `public/assets/js/modules/*/api.js` - Use relative paths
- `public/assets/js/modules/*/table.js` - Use relative paths in QMGrid url

---

## 🔴 Module Loading Issues

### Problem: Module not found

```
Error: Failed to load module script
```

**Causes & Solutions:**

1. **Missing `type="module"` in script tag**

   ```html
   <!-- Wrong -->
   <script src="../assets/js/modules/members/index.js"></script>

   <!-- Correct -->
   <script type="module" src="../assets/js/modules/members/index.js"></script>
   ```

2. **Incorrect file paths**
   - Check that all import paths are correct
   - Use relative paths: `./state.js` not `state.js`

3. **Missing export/import statements**

   ```javascript
   // Each module must export
   export class MyClass {}

   // And be imported
   import { MyClass } from "./my-class.js";
   ```

---

## 🔴 Permission Errors (403 Forbidden)

### Problem: API returns 403

```
Error: HTTP 403: Forbidden
```

**Causes & Solutions:**

1. **User not logged in**
   - Check `Auth.requireAuth()` is called
   - Verify session is valid

2. **Missing permissions**
   - Check user has required permission
   - Use `Auth.hasPermission(Config.PERMISSIONS.VIEW_MEMBERS)`

3. **CORS issues**
   - Check `.htaccess` has CORS headers
   - Verify API endpoint allows the request method

---

## 🔴 Form Validation Issues

### Problem: Form submits without validation

```
Data saved with invalid fields
```

**Solution:** Ensure validator is called before save

```javascript
// In form.js save() method
if (!this.validator.validateStep(this.state.currentStep)) {
  return; // Stop if validation fails
}
```

---

## 🔴 Data Not Loading

### Problem: Table shows "No data"

```
Table is empty but data exists
```

**Checks:**

1. **API endpoint working?**
   - Open browser DevTools → Network tab
   - Check if API call succeeds
   - Verify response format

2. **Column keys match response?**

   ```javascript
   // In table.js
   {
      key: 'MbrFirstName', // Must match API response field
      title: 'First Name'
   }
   ```

3. **Pagination parameters correct?**
   - Check `page` and `limit` parameters
   - Verify backend returns `pagination` object

---

## 🔴 Profile Picture Upload Issues

### Problem: Picture not uploading

```
Upload fails silently
```

**Checks:**

1. **File size limit**
   - Max 5MB (check `form.js`)
   - Server upload_max_filesize setting

2. **File type**
   - Only images allowed (JPG, PNG, GIF, WebP)
   - Check MIME type validation

3. **Upload directory permissions**
   - `uploads/members/` must be writable
   - Check folder exists and has 755 permissions

---

## 🔴 Charts Not Rendering

### Problem: Statistics charts don't show

```
Charts area is blank
```

**Checks:**

1. **Chart.js loaded?**
   - Check `<script src="...chart.js">` in header
   - Verify Chart.js version compatibility

2. **Canvas element exists?**

   ```html
   <canvas id="genderChart"></canvas> <canvas id="ageChart"></canvas>
   ```

3. **Data format correct?**
   ```javascript
   // stats.js expects:
   {
      gender_distribution: { Male: 10, Female: 15 },
      age_distribution: { '18-30': 5, '31-45': 10 }
   }
   ```

---

## 🔴 Stepper Not Working

### Problem: Can't navigate between steps

```
Next button doesn't work
```

**Checks:**

1. **Validation passing?**
   - Check browser console for validation errors
   - Ensure required fields are filled

2. **Event listeners attached?**

   ```javascript
   // In form.js initEventListeners()
   document
     .getElementById("nextStepBtn")
     ?.addEventListener("click", () => this.nextStep());
   ```

3. **State updating?**
   ```javascript
   // Check state.currentStep is incrementing
   console.log("Current step:", this.state.currentStep);
   ```

---

## 🔴 Phone Number Validation

### Problem: Valid Ghana numbers rejected

```
"Invalid phone number format"
```

**Ghana Phone Format:**

- `0XXXXXXXXX` (10 digits starting with 0)
- `+233XXXXXXXXX` (12 digits starting with +233)
- Valid prefixes: 02, 03, 05 (MTN, Vodafone, AirtelTigo)

**Regex in form-validator.js:**

```javascript
/^(\+?233|0)[2-5][0-9]{8}$/;
```

**Valid examples:**

- `0241234567` ✓
- `+233241234567` ✓
- `0501234567` ✓

**Invalid examples:**

- `241234567` ✗ (missing 0 or +233)
- `0191234567` ✗ (invalid prefix 01)
- `024123456` ✗ (too short)

---

## 🔴 Family/Role Selects Not Working

### Problem: Choices.js dropdowns not initializing

```
Selects show as plain dropdowns
```

**Checks:**

1. **Choices.js loaded?**
   - Check `<script src="...choices.js">` in header
   - Verify CSS is also loaded

2. **Initialized at right time?**

   ```javascript
   // In form.js
   // Initialize when reaching step 1 (families)
   if (this.state.currentStep === 1) this.initFamilySelect();
   // Initialize when reaching step 2 (roles)
   if (this.state.currentStep === 2) this.initRoleSelect();
   ```

3. **Data loaded?**
   ```javascript
   // Check state has data
   console.log("Families:", this.state.familiesData);
   console.log("Roles:", this.state.rolesData);
   ```

---

## 🛠️ Debugging Tips

### Enable Verbose Logging

```javascript
// In index.js
console.log("✓ Members module initialized");
console.log("State:", this.state);
console.log("API:", this.api);
```

### Check API Responses

```javascript
// In api.js
async get(id) {
   const response = await api.get(`${this.baseUrl}/${id}`);
   console.log('API Response:', response);
   return response;
}
```

### Verify State Changes

```javascript
// In state.js
nextStep() {
   console.log('Before:', this.currentStep);
   if (this.currentStep < this.totalSteps - 1) {
      this.currentStep++;
      console.log('After:', this.currentStep);
      return true;
   }
   return false;
}
```

### Browser DevTools

1. **Console** - Check for JavaScript errors
2. **Network** - Verify API calls and responses
3. **Elements** - Inspect DOM structure
4. **Application** - Check localStorage/sessionStorage

---

## 📞 Still Having Issues?

1. Check browser console for errors
2. Check server error logs
3. Verify database schema matches code
4. Review recent changes in git
5. Test in incognito mode (clear cache)

---

**Last Updated:** January 22, 2026
