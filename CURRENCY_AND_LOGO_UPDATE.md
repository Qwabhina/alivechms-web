# Currency Symbol & Church Logo Implementation

## Overview

Fixed hardcoded currency symbols throughout the application and added church logo upload functionality with display in header, login page, and other relevant locations.

## Changes Made

### 1. Currency Symbol Fix

#### A. Global Currency Formatter (footer.php)

Added global JavaScript functions available on all pages:

```javascript
window.formatCurrency(amount); // Returns: "GH₵ 1,234.56"
window.formatCurrencyLocale(amount); // Returns: "GH₵ 1,234.56" (with locale formatting)
```

**Usage in Dashboard Pages**:
Replace hardcoded currency with:

```javascript
// OLD (hardcoded)
document.getElementById("totalAmount").textContent = `GH₵ ${amount.toFixed(2)}`;

// NEW (dynamic)
document.getElementById("totalAmount").textContent = formatCurrency(amount);
```

#### B. Utils.formatCurrency() Updated

The `Utils.formatCurrency()` function now uses dynamic currency symbol from settings:

```javascript
Utils.formatCurrency(1234.56); // Returns: "GH₵ 1,234.56" (or your configured symbol)
```

### 2. Church Logo Feature

#### A. Database & Settings

**New Setting Added**:

- `church_logo` - Stores relative path to logo file (e.g., `uploads/logos/church_logo_1234567890.jpg`)

**SettingsHelper Methods Added**:

```php
SettingsHelper::getChurchLogo()      // Returns: "uploads/logos/logo.jpg" or null
SettingsHelper::hasChurchLogo()      // Returns: true/false
SettingsHelper::getChurchLogoUrl()   // Returns: "/public/uploads/logos/logo.jpg" or null
```

#### B. Logo Upload API

**New Endpoint**: `POST /settings/upload-logo`

**Features**:

- Accepts: JPG, PNG, GIF, SVG, WebP
- Max size: 2MB
- Auto-deletes old logo when new one uploaded
- Stores in: `public/uploads/logos/`
- Requires: `manage_settings` permission

**Request**:

```javascript
const formData = new FormData();
formData.append("logo", fileInput.files[0]);
await api.upload("settings/upload-logo", formData);
```

**Response**:

```json
{
  "status": "success",
  "message": "Logo uploaded successfully",
  "data": {
    "path": "uploads/logos/church_logo_1234567890.jpg",
    "url": "/public/uploads/logos/church_logo_1234567890.jpg"
  }
}
```

#### C. Logo Display Locations

**1. Header (header.php)**:

```php
<?php if (SettingsHelper::hasChurchLogo()): ?>
   <img src="<?= SettingsHelper::getChurchLogoUrl() ?>" alt="..." style="height: 32px;">
<?php else: ?>
   <i class="bi bi-church"></i>
<?php endif; ?>
```

**2. Login Page (login/index.php)**:

```php
<?php if (SettingsHelper::hasChurchLogo()): ?>
   <img src="<?= SettingsHelper::getChurchLogoUrl() ?>" alt="..." style="max-width: 120px;">
<?php else: ?>
   <img src="../assets/img/logo.png" onerror="this.style.display='none'">
<?php endif; ?>
```

**3. Settings Page**:

- Upload interface with preview
- Remove logo button
- Real-time preview before upload

#### D. Public API Updated

Logo now included in public settings endpoint:

```javascript
// GET /public/settings
{
  "church_name": "Your Church",
  "church_logo": "/public/uploads/logos/logo.jpg",  // NEW
  "currency_symbol": "GH₵",
  ...
}
```

### 3. Files Modified

#### Backend (PHP):

1. **core/Settings.php**

   - Added `church_logo` to default settings
   - Added `church_motto` to default settings

2. **core/SettingsHelper.php**

   - Added `getChurchLogo()`
   - Added `hasChurchLogo()`
   - Added `getChurchLogoUrl()`

3. **routes/SettingsRoutes.php**

   - Added `POST /settings/upload-logo` endpoint
   - Added cache clearing after settings update

4. **routes/PublicRoutes.php**

   - Added `church_logo` to public settings response

5. **public/includes/header.php**

   - Added logo display with fallback to icon

6. **public/login/index.php**

   - Added logo display with fallback

7. **public/includes/footer.php**
   - Added global `formatCurrency()` functions

#### Frontend (JavaScript):

1. **public/dashboard/settings.php**
   - Added logo upload UI
   - Added logo preview
   - Added upload/remove functionality
   - Added logo loading on page load

### 4. Usage Examples

#### Displaying Currency in Dashboard Pages

**Example 1: Stat Cards**

```javascript
// Load stats
async function loadStats() {
  const stats = await api.get("contribution/stats");

  // Use formatCurrency() for display
  document.getElementById("totalAmount").textContent = formatCurrency(
    stats.total
  );
  document.getElementById("monthAmount").textContent = formatCurrency(
    stats.month
  );
}
```

**Example 2: Tabulator Tables**

```javascript
{
   title: "Amount",
   field: "amount",
   formatter: cell => formatCurrency(cell.getValue())
}
```

**Example 3: Form Labels**

```html
<!-- Dynamic currency in label -->
<label class="form-label">
  Amount (<span id="currencySymbol"></span>) <span class="text-danger">*</span>
</label>

<script>
  // Set currency symbol from settings
  document.getElementById("currencySymbol").textContent = Config.getSetting(
    "currency_symbol",
    "GH₵"
  );
</script>
```

#### Uploading Church Logo

**From Settings Page**:

1. Navigate to Settings → General tab
2. Click "Choose File" under Church Logo
3. Select image (JPG, PNG, GIF, SVG, WebP - max 2MB)
4. Preview appears automatically
5. Click "Upload Logo"
6. Logo appears in header and login page immediately

**Programmatically**:

```javascript
async function uploadChurchLogo(file) {
  const formData = new FormData();
  formData.append("logo", file);

  const result = await api.upload("settings/upload-logo", formData);
  console.log("Logo uploaded:", result.url);
}
```

#### Displaying Logo in Custom Pages

**PHP**:

```php
<?php if (SettingsHelper::hasChurchLogo()): ?>
   <img src="<?= SettingsHelper::getChurchLogoUrl() ?>"
        alt="<?= SettingsHelper::getChurchName() ?>"
        style="max-height: 50px;">
<?php endif; ?>
```

**JavaScript** (after settings loaded):

```javascript
const logoUrl = Config.getSetting("church_logo");
if (logoUrl) {
  document.getElementById("logo").src = logoUrl;
}
```

### 5. Pages That Need Currency Update

All dashboard pages have been updated to use dynamic currency from settings:

**Completed**:

- ✅ `public/includes/footer.php` - Global function added
- ✅ `public/dashboard/index.php` - All currency displays updated
- ✅ `public/dashboard/contributions.php` - All stat cards and form labels updated
- ✅ `public/dashboard/pledges.php` - All stat cards and form labels updated
- ✅ `public/dashboard/expenses.php` - All stat cards and form labels updated
- ✅ `public/dashboard/budgets.php` - All budget totals updated
- ✅ `public/dashboard/financial-reports.php` - All reports updated (from previous work)
- ✅ `public/includes/header.php` - Mobile responsiveness added (logo only on mobile)

**Implementation Details**:

- All stat card initial values changed from `GH₵ 0.00` to `-` (populated dynamically)
- All form labels changed from `Amount (GH₵)` to `Amount (<span id="currencySymbol"></span>)`
- Currency symbol populated on page load from settings
- JavaScript uses `formatCurrency()` or `formatCurrencyLocale()` for all amounts
- Header shows only logo on mobile/small screens (church name hidden with `d-none d-md-inline`)

### 6. Testing Checklist

#### Currency Symbol:

- [ ] Update currency symbol in Settings
- [ ] Verify stat cards show new symbol
- [ ] Verify tables show new symbol
- [ ] Verify form labels show new symbol
- [ ] Verify reports show new symbol
- [ ] Clear browser cache and test again

#### Church Logo:

- [ ] Upload logo in Settings → General
- [ ] Verify logo appears in header
- [ ] Verify logo appears in login page
- [ ] Verify logo preview works
- [ ] Remove logo and verify fallback icon appears
- [ ] Upload different logo and verify old one deleted
- [ ] Test with different image formats (JPG, PNG, SVG)
- [ ] Test file size limit (try uploading >2MB)
- [ ] Test invalid file type (try uploading PDF)

### 7. Migration Steps

**For Existing Installations**:

1. **Update Database**:

   ```sql
   -- Add church_logo setting
   INSERT INTO settings (setting_key, setting_value, setting_type, category, description)
   VALUES ('church_logo', '', 'string', 'general', 'Church logo path (relative to public folder)');

   -- Add church_motto if missing
   INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, category, description)
   VALUES ('church_motto', 'Faith, Hope, and Love', 'string', 'general', 'Church motto or tagline');
   ```

2. **Create Upload Directory**:

   ```bash
   mkdir -p public/uploads/logos
   chmod 755 public/uploads/logos
   ```

3. **Clear Caches**:

   ```php
   // In PHP
   SettingsHelper::clearCache();
   ```

   ```javascript
   // In browser
   localStorage.clear();
   location.reload(true);
   ```

4. **Upload Logo**:

   - Go to Settings → General
   - Upload your church logo
   - Verify it appears everywhere

5. **Update Currency**:
   - Go to Settings → Regional
   - Update currency symbol if needed
   - Verify it appears in financial pages

### 8. Troubleshooting

#### Logo Not Displaying:

1. Check file exists: `ls -la public/uploads/logos/`
2. Check file permissions: `chmod 644 public/uploads/logos/*`
3. Check setting value: `SELECT * FROM settings WHERE setting_key = 'church_logo'`
4. Check SettingsHelper: `SettingsHelper::getChurchLogoUrl()`
5. Clear cache: `SettingsHelper::clearCache()`

#### Currency Not Updating:

1. Check setting value: `SELECT * FROM settings WHERE setting_key = 'currency_symbol'`
2. Check Config.SETTINGS in browser console
3. Verify `formatCurrency()` function exists
4. Clear browser cache (Ctrl+F5)
5. Check if page is using hardcoded currency

#### Upload Fails:

1. Check directory exists and is writable
2. Check file size (<2MB)
3. Check file type (image/\*)
4. Check user has `manage_settings` permission
5. Check PHP upload_max_filesize setting

### 9. Future Enhancements

**Potential Improvements**:

1. Logo cropping/resizing tool
2. Multiple logo variants (light/dark theme)
3. Favicon generation from logo
4. Logo in PDF reports
5. Logo in email templates
6. Watermark for documents
7. Theme color extraction from logo
8. Logo usage guidelines

### 10. Security Notes

**Logo Upload Security**:

- ✅ File type validation (MIME type check)
- ✅ File size limit (2MB)
- ✅ Unique filename generation
- ✅ Permission check (`manage_settings`)
- ✅ Old file deletion
- ✅ Directory outside web root option available

**Best Practices**:

- Store logos in dedicated directory
- Use unique filenames (timestamp-based)
- Validate file types server-side
- Limit file sizes
- Set proper file permissions (644)
- Consider CDN for production

---

**Implementation Date**: December 30, 2025
**Version**: 1.0.0
**Status**: ✅ Complete - All Currency & Logo Features Implemented

**Completed Tasks**:

1. ✅ Currency symbol refactoring across all dashboard pages
2. ✅ Church logo upload and display functionality
3. ✅ Mobile responsiveness (logo only on small screens)
4. ✅ Dynamic currency in all stat cards and forms
5. ✅ Global formatCurrency functions available on all pages

**Next Steps** (Optional Enhancements):

1. Add logo to PDF reports
2. Add logo to email templates
3. Add logo cropping/resizing tool
4. Add multiple logo variants (light/dark theme)
