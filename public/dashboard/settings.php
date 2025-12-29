<?php
$pageTitle = 'General Settings';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">System Settings</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Settings</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="saveSettingsBtn" data-permission="manage_settings">
         <i class="bi bi-check-circle me-2"></i>Save All Settings
      </button>
   </div>

   <!-- Settings Tabs -->
   <div class="card">
      <div class="card-header">
         <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
               <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                  <i class="bi bi-building me-1"></i>General
               </button>
            </li>
            <li class="nav-item" role="presentation">
               <button class="nav-link" id="regional-tab" data-bs-toggle="tab" data-bs-target="#regional" type="button" role="tab">
                  <i class="bi bi-globe me-1"></i>Regional
               </button>
            </li>
            <li class="nav-item" role="presentation">
               <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                  <i class="bi bi-envelope me-1"></i>Email
               </button>
            </li>
            <li class="nav-item" role="presentation">
               <button class="nav-link" id="sms-tab" data-bs-toggle="tab" data-bs-target="#sms" type="button" role="tab">
                  <i class="bi bi-phone me-1"></i>SMS
               </button>
            </li>
            <li class="nav-item" role="presentation">
               <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                  <i class="bi bi-bell me-1"></i>Notifications
               </button>
            </li>
            <li class="nav-item" role="presentation">
               <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab">
                  <i class="bi bi-cash-coin me-1"></i>Financial
               </button>
            </li>
            <li class="nav-item" role="presentation">
               <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                  <i class="bi bi-gear me-1"></i>System
               </button>
            </li>
            <li class="nav-item" role="presentation">
               <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab">
                  <i class="bi bi-cloud-arrow-up me-1"></i>Backup
               </button>
            </li>
         </ul>
      </div>
      <div class="card-body">
         <div class="tab-content" id="settingsTabContent">
            <!-- General Settings -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
               <h5 class="mb-3">Church Information</h5>
               <div id="generalSettings"></div>
            </div>

            <!-- Regional Settings -->
            <div class="tab-pane fade" id="regional" role="tabpanel">
               <h5 class="mb-3">Regional & Localization Settings</h5>
               <div id="regionalSettings"></div>
            </div>

            <!-- Email Settings -->
            <div class="tab-pane fade" id="email" role="tabpanel">
               <h5 class="mb-3">Email Configuration</h5>
               <div id="emailSettings"></div>
            </div>

            <!-- SMS Settings -->
            <div class="tab-pane fade" id="sms" role="tabpanel">
               <h5 class="mb-3">SMS Configuration</h5>
               <div id="smsSettings"></div>
            </div>

            <!-- Notifications Settings -->
            <div class="tab-pane fade" id="notifications" role="tabpanel">
               <h5 class="mb-3">Notification Preferences</h5>
               <div id="notificationsSettings"></div>
            </div>

            <!-- Financial Settings -->
            <div class="tab-pane fade" id="financial" role="tabpanel">
               <h5 class="mb-3">Financial Settings</h5>
               <div id="financialSettings"></div>
            </div>

            <!-- System Settings -->
            <div class="tab-pane fade" id="system" role="tabpanel">
               <h5 class="mb-3">System Configuration</h5>
               <div id="systemSettings"></div>
            </div>

            <!-- Backup Settings -->
            <div class="tab-pane fade" id="backup" role="tabpanel">
               <h5 class="mb-3">Backup Configuration</h5>
               <div id="backupSettings"></div>
            </div>
         </div>
      </div>
   </div>
</div>
</main>

<script>
   let settingsByCategory = {};

   document.addEventListener('DOMContentLoaded', async () => {
      if (!Auth.requireAuth()) return;
      await loadSettings();
      initEventListeners();
   });

   async function loadSettings() {
      try {
         Alerts.loading('Loading settings...');
         const response = await api.get('settings/category');
         settingsByCategory = response?.data || {};

         // Render each category
         renderCategorySettings('general', 'generalSettings');
         renderCategorySettings('regional', 'regionalSettings');
         renderCategorySettings('email', 'emailSettings');
         renderCategorySettings('sms', 'smsSettings');
         renderCategorySettings('notifications', 'notificationsSettings');
         renderCategorySettings('financial', 'financialSettings');
         renderCategorySettings('system', 'systemSettings');
         renderCategorySettings('backup', 'backupSettings');

         Alerts.closeLoading();
      } catch (error) {
         Alerts.closeLoading();
         console.error('Load settings error:', error);
         Alerts.error('Failed to load settings');
      }
   }

   function renderCategorySettings(category, containerId) {
      const container = document.getElementById(containerId);
      const settings = settingsByCategory[category] || [];

      if (settings.length === 0) {
         container.innerHTML = '<p class="text-muted">No settings available in this category</p>';
         return;
      }

      let html = '<div class="row g-3">';

      settings.forEach(setting => {
         html += `<div class="col-md-6">`;
         html += renderSettingField(setting);
         html += `</div>`;
      });

      html += '</div>';
      container.innerHTML = html;
   }

   function renderSettingField(setting) {
      const {
         key,
         value,
         type,
         description
      } = setting;
      let fieldHtml = '';

      switch (type) {
         case 'boolean':
            fieldHtml = `
               <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" 
                     data-setting="${key}" data-type="${type}" 
                     ${value ? 'checked' : ''} id="${key}">
                  <label class="form-check-label" for="${key}">
                     ${formatLabel(key)}
                  </label>
               </div>
               ${description ? `<small class="text-muted d-block mt-1">${description}</small>` : ''}
            `;
            break;

         case 'number':
            fieldHtml = `
               <label class="form-label">${formatLabel(key)}</label>
               <input type="number" class="form-control" 
                  data-setting="${key}" data-type="${type}" 
                  value="${value || 0}" id="${key}">
               ${description ? `<small class="text-muted">${description}</small>` : ''}
            `;
            break;

         case 'string':
         default:
            // Check if it's a password field
            const inputType = key.includes('password') || key.includes('api_key') ? 'password' : 'text';

            // Check if it's a select field
            if (key === 'timezone') {
               fieldHtml = `
                  <label class="form-label">${formatLabel(key)}</label>
                  <select class="form-select" data-setting="${key}" data-type="${type}" id="${key}">
                     <option value="Africa/Accra" ${value === 'Africa/Accra' ? 'selected' : ''}>Africa/Accra (GMT)</option>
                     <option value="Africa/Lagos" ${value === 'Africa/Lagos' ? 'selected' : ''}>Africa/Lagos (WAT)</option>
                     <option value="Africa/Nairobi" ${value === 'Africa/Nairobi' ? 'selected' : ''}>Africa/Nairobi (EAT)</option>
                     <option value="America/New_York" ${value === 'America/New_York' ? 'selected' : ''}>America/New York (EST)</option>
                     <option value="Europe/London" ${value === 'Europe/London' ? 'selected' : ''}>Europe/London (GMT)</option>
                  </select>
                  ${description ? `<small class="text-muted">${description}</small>` : ''}
               `;
            } else if (key === 'date_format') {
               fieldHtml = `
                  <label class="form-label">${formatLabel(key)}</label>
                  <select class="form-select" data-setting="${key}" data-type="${type}" id="${key}">
                     <option value="Y-m-d" ${value === 'Y-m-d' ? 'selected' : ''}>YYYY-MM-DD (2025-12-29)</option>
                     <option value="d/m/Y" ${value === 'd/m/Y' ? 'selected' : ''}>DD/MM/YYYY (29/12/2025)</option>
                     <option value="m/d/Y" ${value === 'm/d/Y' ? 'selected' : ''}>MM/DD/YYYY (12/29/2025)</option>
                     <option value="d-M-Y" ${value === 'd-M-Y' ? 'selected' : ''}>DD-MMM-YYYY (29-Dec-2025)</option>
                  </select>
                  ${description ? `<small class="text-muted">${description}</small>` : ''}
               `;
            } else if (key === 'smtp_encryption') {
               fieldHtml = `
                  <label class="form-label">${formatLabel(key)}</label>
                  <select class="form-select" data-setting="${key}" data-type="${type}" id="${key}">
                     <option value="tls" ${value === 'tls' ? 'selected' : ''}>TLS</option>
                     <option value="ssl" ${value === 'ssl' ? 'selected' : ''}>SSL</option>
                     <option value="none" ${value === 'none' ? 'selected' : ''}>None</option>
                  </select>
                  ${description ? `<small class="text-muted">${description}</small>` : ''}
               `;
            } else if (key === 'backup_frequency') {
               fieldHtml = `
                  <label class="form-label">${formatLabel(key)}</label>
                  <select class="form-select" data-setting="${key}" data-type="${type}" id="${key}">
                     <option value="daily" ${value === 'daily' ? 'selected' : ''}>Daily</option>
                     <option value="weekly" ${value === 'weekly' ? 'selected' : ''}>Weekly</option>
                     <option value="monthly" ${value === 'monthly' ? 'selected' : ''}>Monthly</option>
                  </select>
                  ${description ? `<small class="text-muted">${description}</small>` : ''}
               `;
            } else if (key.includes('address') || key.includes('description')) {
               fieldHtml = `
                  <label class="form-label">${formatLabel(key)}</label>
                  <textarea class="form-control" rows="2" 
                     data-setting="${key}" data-type="${type}" 
                     id="${key}">${value || ''}</textarea>
                  ${description ? `<small class="text-muted">${description}</small>` : ''}
               `;
            } else {
               fieldHtml = `
                  <label class="form-label">${formatLabel(key)}</label>
                  <input type="${inputType}" class="form-control" 
                     data-setting="${key}" data-type="${type}" 
                     value="${value || ''}" id="${key}">
                  ${description ? `<small class="text-muted">${description}</small>` : ''}
               `;
            }
            break;
      }

      return `<div class="mb-3">${fieldHtml}</div>`;
   }

   function formatLabel(key) {
      return key
         .split('_')
         .map(word => word.charAt(0).toUpperCase() + word.slice(1))
         .join(' ');
   }

   function initEventListeners() {
      document.getElementById('saveSettingsBtn').addEventListener('click', saveSettings);
   }

   async function saveSettings() {
      if (!Auth.hasPermission('manage_settings')) {
         Alerts.error('You do not have permission to manage settings');
         return;
      }

      const settings = [];

      document.querySelectorAll('[data-setting]').forEach(field => {
         const key = field.getAttribute('data-setting');
         const type = field.getAttribute('data-type');
         let value;

         if (type === 'boolean') {
            value = field.checked;
         } else if (type === 'number') {
            value = parseInt(field.value) || 0;
         } else {
            value = field.value;
         }

         // Find the original setting to get category and description
         let category = null;
         let description = null;

         for (const cat in settingsByCategory) {
            const setting = settingsByCategory[cat].find(s => s.key === key);
            if (setting) {
               category = setting.category;
               description = setting.description;
               break;
            }
         }

         settings.push({
            key,
            value,
            type,
            category,
            description
         });
      });

      try {
         Alerts.loading('Saving settings...');
         await api.post('settings/update', {
            settings
         });
         Alerts.closeLoading();
         Alerts.success('Settings saved successfully');
         await loadSettings(); // Reload to confirm
      } catch (error) {
         Alerts.closeLoading();
         console.error('Save settings error:', error);
         Alerts.handleApiError(error);
      }
   }
</script>

<?php require_once '../includes/footer.php'; ?>