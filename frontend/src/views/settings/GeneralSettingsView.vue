<script setup lang="ts">
/**
 * GeneralSettingsView — Full implementation.
 * Category-based settings editor with boolean toggles, logo upload, and text inputs.
 */
import { settingsService } from '@/services/settings.service'
import { useToast } from '@/design-system'
import type { Setting } from '@/types/settings'
import { Settings, Save } from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme'
import { computed } from 'vue'

const toast = useToast()

// Theme controls (design-system / localStorage)
const { theme: currentTheme, setTheme } = useTheme()
const themeModel = computed<string>({
  get: () => (currentTheme).value ?? 'system',
  set: (v: string) => setTheme(v as 'light' | 'dark' | 'system'),
})

// ── State ─────────────────────────────────────────────────────────────────────

const allSettings = ref<Setting[]>([])
const settingsByCategory = ref<Record<string, Setting[]>>({})
const activeCategory = ref('')
const isLoading = ref(true)
const isSaving = ref(false)
const isUploadingLogo = ref(false)
const editedValues = ref<Record<string, string>>({})

// Track per-setting logo file selections
const logoFiles = ref<Record<string, File[]>>({})

// ── Data loaders ──────────────────────────────────────────────────────────────

async function loadSettings() {
  isLoading.value = true
  try {
    const { data } = await settingsService.getAll()
    allSettings.value = data.data!

    const grouped: Record<string, Setting[]> = {}
    for (const s of data.data!) {
      if (!grouped[s.Category]) grouped[s.Category] = []
      grouped[s.Category]!.push(s)
      // Pre-populate edited values with current values
      editedValues.value[s.SettingKey] = s.SettingValue ?? ''
    }

    settingsByCategory.value = grouped

    // Default to first category
    if (!activeCategory.value && Object.keys(grouped).length > 0) {
      activeCategory.value = Object.keys(grouped)[0]!
    }
  } catch {
    toast.error('Failed to load settings.')
  } finally {
    isLoading.value = false
  }
}

// ── Save ──────────────────────────────────────────────────────────────────────

async function saveSettings() {
  isSaving.value = true
  try {
    const settings = Object.entries(editedValues.value).map(([key, value]) => ({ key, value }))
    await settingsService.update({ settings })
    toast.success('Settings saved successfully.')
  } catch {
    toast.error('Failed to save settings.')
  } finally {
    isSaving.value = false
  }
}

// ── Logo upload ───────────────────────────────────────────────────────────────

async function handleLogoUpload(file: File | null) {
  if (!file) return
  isUploadingLogo.value = true
  try {
    const { data } = await settingsService.uploadLogo(file)
    // Update the setting value to show the new path
    const logoKey = allSettings.value.find((s) =>
      s.SettingKey.toLowerCase().includes('logo'),
    )?.SettingKey
    if (logoKey && data.data?.path) {
      editedValues.value[logoKey] = data.data.path
    }
    toast.success('Logo uploaded successfully.')
  } catch {
    toast.error('Failed to upload logo.')
  } finally {
    isUploadingLogo.value = false
  }
}

function onLogoFileChange(settingKey: string, files: File[]) {
  if (files.length > 0) {
    handleLogoUpload(files[0] ?? null)
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function isLogoSetting(setting: Setting): boolean {
  return setting.SettingKey.toLowerCase().includes('logo')
}

function isBooleanSetting(setting: Setting): boolean {
  return setting.SettingType === 'boolean'
}

/** Human-friendly label from a snake_case or dot.notation key */
function formatSettingLabel(setting: Setting): string {
  if (setting.Description) return setting.Description
  return setting.SettingKey.replace(/[_\.]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(loadSettings)
</script>

<template>
  <div class="view">
    <!-- ── Page Header ──────────────────────────────────────────────────────── -->
    <div class="view-header">
      <div>
        <h1 class="view-title">
          <Settings :size="22" class="view-title__icon" aria-hidden="true" />
          Settings
        </h1>
        <p class="view-subtitle">Configure your church management system.</p>
      </div>
      <ChButton variant="primary" :loading="isSaving" @click="saveSettings">
        <template #icon><Save :size="16" /></template>
        Save Changes
      </ChButton>
    </div>

    <!-- ── Loading ────────────────────────────────────────────────────────── -->
    <div v-if="isLoading" class="loading-wrap">
      <ChSpinner />
    </div>

    <!-- ── Settings Layout ────────────────────────────────────────────────── -->
    <div v-else-if="Object.keys(settingsByCategory).length > 0" class="settings-layout">
      <!-- Left: Category sidebar as a card -->
      <aside class="settings-nav">
        <ChCard shadow="sm">
          <template #header>
            <span class="card-label">Categories</span>
          </template>
          <div class="settings-nav-card">
            <div class="settings-nav__list">
              <button
                v-for="cat in Object.keys(settingsByCategory)"
                :key="cat"
                class="settings-nav__item"
                :class="{ 'settings-nav__item--active': activeCategory === cat }"
                type="button"
                @click="activeCategory = cat"
              >
                {{ cat }}
              </button>
            </div>
          </div>
        </ChCard>

        <!-- Theme selector placed beneath categories to avoid distracting main content -->
        <ChCard class="settings-theme-card--aside" shadow="sm">
          <template #header>
            <span class="card-label">Theme</span>
          </template>
          <div class="settings-fields">
            <div class="settings-field">
              <ChFormField :label="'UI Theme'" :hint="'Local preference (light, dark, system)'">
                <div class="radio-group">
                  <ChRadio v-model="themeModel" value="light" label="Light" />
                  <ChRadio v-model="themeModel" value="dark" label="Dark" />
                  <ChRadio v-model="themeModel" value="system" label="System (Auto)" />
                </div>
              </ChFormField>
            </div>
          </div>
        </ChCard>
      </aside>

      <!-- Right: Settings form -->
      <div class="settings-content">
        

        <ChCard v-if="activeCategory" shadow="sm">
          <template #header>
            <span class="card-label">{{ activeCategory }}</span>
          </template>

          <div class="settings-fields">
            <template
              v-for="setting in settingsByCategory[activeCategory]"
              :key="setting.SettingID"
            >
              <!-- ── Logo Upload Field ──────────────────────────────────── -->
              <div v-if="isLogoSetting(setting)" class="settings-field">
                <ChFormField
                  :label="formatSettingLabel(setting)"
                  :hint="setting.SettingKey"
                  :input-id="`setting-${setting.SettingID}`"
                >
                  <ChFileUpload
                    :id="`setting-${setting.SettingID}`"
                    v-model="logoFiles[setting.SettingKey]"
                    accept="image/png,image/jpeg,image/svg+xml,image/webp"
                    :multiple="false"
                    :max-size="2 * 1024 * 1024"
                    button-text="Choose Logo"
                    drop-text="Drop a logo image here"
                    sub-text="PNG, JPG, SVG or WebP · max 2 MB"
                    :disabled="isUploadingLogo"
                    @change="(files) => onLogoFileChange(setting.SettingKey, files)"
                  />
                  <!-- Current logo path preview -->
                  <p v-if="editedValues[setting.SettingKey]" class="logo-current-path">
                    <span class="logo-current-path__label">Current:</span>
                    <code class="logo-current-path__value">{{
                      editedValues[setting.SettingKey]
                    }}</code>
                  </p>
                </ChFormField>
              </div>

              <!-- ── Boolean Toggle ────────────────────────────────────── -->
              <div
                v-else-if="isBooleanSetting(setting)"
                class="settings-field settings-field--switch"
              >
                <ChSwitch
                  :model-value="
                    editedValues[setting.SettingKey] === 'true' ||
                    editedValues[setting.SettingKey] === '1'
                  "
                  :label="formatSettingLabel(setting)"
                  :hint="setting.SettingKey"
                  @update:model-value="
                    (val) => {
                      editedValues[setting.SettingKey] = val ? 'true' : 'false'
                    }
                  "
                />
              </div>

              <!-- Theme is managed by the design system (localStorage 'ch-theme') -->

              <!-- ── Text / Generic Input ──────────────────────────────── -->
              <div v-else class="settings-field">
                <ChFormField
                  :label="formatSettingLabel(setting)"
                  :hint="setting.SettingKey"
                  :input-id="`setting-${setting.SettingID}`"
                >
                  <ChInput
                    :id="`setting-${setting.SettingID}`"
                    v-model="editedValues[setting.SettingKey]"
                    :placeholder="`Enter ${formatSettingLabel(setting).toLowerCase()}…`"
                  />
                </ChFormField>
              </div>
            </template>
          </div>
        </ChCard>

        <!-- Save again at the bottom for long forms -->
        <div class="settings-save-bottom">
          <ChButton variant="primary" :loading="isSaving" @click="saveSettings">
            <template #icon><Save :size="16" /></template>
            Save Changes
          </ChButton>
        </div>
      </div>
    </div>

    <!-- ── Empty state (no settings found) ────────────────────────────────── -->
    <ChCard v-else shadow="sm">
      <ChEmptyState
        icon="document"
        title="No settings found"
        description="No system settings have been configured yet."
      >
        <ChButton variant="outline" @click="settingsService.initialize().then(loadSettings)">
          Initialize Defaults
        </ChButton>
      </ChEmptyState>
    </ChCard>
  </div>
</template>

<style scoped>
/* ─── Page shell ──────────────────────────────────────────────────────────── */
.view {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
  max-width: 1040px;
}

/* ─── Header ──────────────────────────────────────────────────────────────── */
.view-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--ch-space-4);
}

.view-title {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  font-size: var(--ch-text-2xl);
  font-weight: var(--ch-font-bold);
  font-family: var(--ch-font-display);
  color: var(--ch-color-text);
  margin: 0;
}

.view-title__icon {
  color: var(--ch-color-primary);
  flex-shrink: 0;
}

.view-subtitle {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin: var(--ch-space-1) 0 0;
}

/* ─── Loading ─────────────────────────────────────────────────────────────── */
.loading-wrap {
  display: flex;
  justify-content: center;
  padding: var(--ch-space-12) 0;
}

/* ─── Two-column layout ───────────────────────────────────────────────────── */
.settings-layout {
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: var(--ch-space-6);
  align-items: flex-start;
}

@media (max-width: 768px) {
  .settings-layout {
    grid-template-columns: 1fr;
  }
}

/* ─── Sidebar navigation ──────────────────────────────────────────────────── */
.settings-nav {
  position: sticky;
  top: var(--ch-space-6);
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-3);
}

.settings-nav__label {
  font-size: var(--ch-text-xs);
  font-weight: var(--ch-font-semibold);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--ch-color-text-subtle);
  padding: var(--ch-space-1) var(--ch-space-2) var(--ch-space-2);
  margin: 0;
}

.settings-nav__item {
  display: block;
  width: 100%;
  text-align: left;
  padding: var(--ch-space-2) var(--ch-space-3);
  font-size: var(--ch-text-sm);
  font-family: var(--ch-font-sans);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text-muted);
  background: none;
  border: none;
  border-radius: var(--ch-radius-sm);
  cursor: pointer;
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    color var(--ch-duration-fast) var(--ch-ease-out);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.settings-nav__item:hover {
  background-color: var(--ch-color-bg-subtle);
  color: var(--ch-color-text);
}

.settings-nav__item--active {
  background-color: var(--ch-color-primary-muted);
  color: var(--ch-color-primary);
  font-weight: var(--ch-font-semibold);
}

.settings-nav__item--active:hover {
  background-color: var(--ch-color-primary-muted);
  color: var(--ch-color-primary);
}

/* When the nav is wrapped in a ChCard, adjust inner list spacing */
.settings-nav .settings-nav-card {
  padding: var(--ch-space-3) 0 var(--ch-space-3) var(--ch-space-3);
}

.settings-theme-card--aside {
  margin-top: var(--ch-space-4);
}

/* ─── Settings content area ───────────────────────────────────────────────── */
.settings-content {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
}

/* ─── Card header label ───────────────────────────────────────────────────── */
.card-label {
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

/* ─── Settings fields container ──────────────────────────────────────────── */
.settings-fields {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-5);
}

/* ─── Individual setting field ────────────────────────────────────────────── */
.settings-field {
  display: block;
}

/* Switch fields need a bit of extra padding for visual breathing room */
.settings-field--switch {
  padding: var(--ch-space-1) 0;
}

/* ─── Logo preview path ───────────────────────────────────────────────────── */
.logo-current-path {
  display: flex;
  align-items: baseline;
  gap: var(--ch-space-2);
  margin: var(--ch-space-2) 0 0;
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

.logo-current-path__label {
  font-weight: var(--ch-font-medium);
  flex-shrink: 0;
}

.logo-current-path__value {
  font-family: monospace;
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
  background-color: var(--ch-color-bg-subtle);
  padding: var(--ch-space-0_5) var(--ch-space-1_5);
  border-radius: var(--ch-radius-sm);
  border: 1px solid var(--ch-color-border);
  word-break: break-all;
}

/* ─── Save button at bottom ───────────────────────────────────────────────── */
.settings-save-bottom {
  display: flex;
  justify-content: flex-end;
  padding-top: var(--ch-space-2);
}

/* ─── Theme selector dropdown ───────────────────────────────────────────── */
.theme-select {
  width: 100%;
  padding: var(--ch-space-2) var(--ch-space-3);
  font-size: var(--ch-text-sm);
  font-family: inherit;
  color: var(--ch-color-text);
  background-color: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border);
  border-radius: var(--ch-radius-md);
  cursor: pointer;
  transition: border-color var(--ch-duration-fast) var(--ch-ease-out);
}

.theme-select:hover {
  border-color: var(--ch-color-border-strong);
}

.theme-select:focus {
  outline: none;
  border-color: var(--ch-color-primary);
  box-shadow: 0 0 0 3px var(--ch-color-primary-subtle);
}
</style>
