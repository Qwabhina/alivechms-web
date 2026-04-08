<script setup lang="ts">
/**
 * GeneralSettingsView — Stub. Phase 6.
 */
import { Settings } from 'lucide-vue-next'
</script>

<template>
  <div class="view">
    <div class="view-header">
      <div>
        <h1 class="view-title">Settings</h1>
        <p class="view-subtitle">Configure your church management system.</p>
      </div>
    </div>

    <ChCard shadow="sm">
      <template #header>
        <span class="card-label">
          <Settings :size="16" style="display:inline;vertical-align:middle;margin-right:6px;" />
          General Settings
        </span>
      </template>
      <div class="coming-soon">
        <p>Church info, branding, and system preferences — coming in Phase 6.</p>
      </div>
    </ChCard>
  </div>
</template>

<style scoped>
.view { display: flex; flex-direction: column; gap: var(--ch-space-6); max-width: 900px; }
.view-header { display: flex; align-items: flex-start; justify-content: space-between; gap: var(--ch-space-4); }
.view-title { font-size: var(--ch-text-2xl); font-weight: var(--ch-font-bold); font-family: var(--ch-font-display); color: var(--ch-color-text); margin: 0; }
.view-subtitle { font-size: var(--ch-text-sm); color: var(--ch-color-text-muted); margin: var(--ch-space-1) 0 0; }
.card-label { font-size: var(--ch-text-base); font-weight: var(--ch-font-semibold); color: var(--ch-color-text); }
.coming-soon { padding: var(--ch-space-12) 0; text-align: center; color: var(--ch-color-text-subtle); font-size: var(--ch-text-sm); }
</style>
