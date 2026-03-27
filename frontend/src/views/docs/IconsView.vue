<script setup lang="ts">
/**
 * IconsView.vue - Iconography System
 * 
 * Showcases the Lucide-vue-next icons used in the system.
 */

import { ref, computed } from 'vue'
import { ChCard, ChInput } from '@/design-system'
import * as LucideIcons from 'lucide-vue-next'
import { 
  Search, 
  AlertCircle,
  Shapes,
  Palette,
  Layout
} from 'lucide-vue-next'

const searchQuery = ref('')

// Categorized core icons for the "Usage" section
const coreIcons = [
  { name: 'Navigation', icons: ['Home', 'Menu', 'X', 'ChevronRight', 'ChevronDown', 'Search'] },
  { name: 'Actions', icons: ['Plus', 'Trash2', 'Edit3', 'Download', 'Printer', 'Mail'] },
  { name: 'Status', icons: ['CheckCircle2', 'AlertCircle', 'Info', 'Clock', 'Bell', 'Shield'] },
  { name: 'Users', icons: ['User', 'Users', 'UserCheck', 'UserPlus', 'LogOut', 'Settings'] }
]

// Get all Lucide icons for the searchable grid
// Filter out non-component exports if any
const allIcons = Object.keys(LucideIcons)
  .filter(name => typeof LucideIcons[name as keyof typeof LucideIcons] === 'object')
  .sort()

const filteredIcons = computed(() => {
  if (!searchQuery.value) return allIcons.slice(0, 100) // Show first 100 by default
  const query = searchQuery.value.toLowerCase()
  return allIcons.filter(name => name.toLowerCase().includes(query))
})
</script>

<template>
  <div class="doc-page">
    <header class="page-header">
      <h1 class="page-title">Iconography</h1>
      <p class="page-desc">
        AliveCHMS uses <strong>Lucide</strong> for its iconography system—a clean, 
        consistent, and lightweight set of vector icons.
      </p>
    </header>

    <section class="doc-section">
      <h2 class="doc-section-title">Guidelines</h2>
      <div class="card-grid card-grid--3">
        <ChCard padding="md">
          <div class="guide-item">
            <div class="guide-icon"><Shapes :size="20" /></div>
            <h4>Size Scale</h4>
            <p>Use 16px for inline text, 20px for buttons/topbar, and 24px+ for hero sections.</p>
          </div>
        </ChCard>
        <ChCard padding="md">
          <div class="guide-item">
            <div class="guide-icon"><Palette :size="20" /></div>
            <h4>Stroke Weight</h4>
            <p>Maintain a consistent stroke width of <code>2px</code> for all UI icons.</p>
          </div>
        </ChCard>
        <ChCard padding="md">
          <div class="guide-item">
            <div class="guide-icon"><Layout :size="20" /></div>
            <h4>Alignment</h4>
            <p>Always center icons within their containers and align them visually with text.</p>
          </div>
        </ChCard>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Core Icon Set</h2>
      <p>Frequently used icons in the AliveCHMS interface.</p>
      
      <div class="core-grid">
        <div v-for="category in coreIcons" :key="category.name" class="core-category">
          <h4 class="category-name">{{ category.name }}</h4>
          <div class="icon-row">
            <div v-for="iconName in category.icons" :key="iconName" class="icon-preview-box">
              <component :is="(LucideIcons[iconName as keyof typeof LucideIcons] as any)" :size="20" />
              <span>{{ iconName }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="doc-section">
      <div class="search-header">
        <h2 class="doc-section-title">Full Icon Reference</h2>
        <ChInput 
          v-model="searchQuery" 
          placeholder="Search all icons..." 
          size="sm" 
          class="icon-search"
        >
          <template #leading><Search :size="16" /></template>
        </ChInput>
      </div>

      <div class="icon-grid">
        <div v-for="name in filteredIcons" :key="name" class="icon-tile">
          <div class="tile-icon">
             <component :is="(LucideIcons[name as keyof typeof LucideIcons] as any)" :size="24" />
          </div>
          <span class="tile-name">{{ name }}</span>
        </div>
      </div>

      <div v-if="filteredIcons.length === 0" class="no-results">
        <AlertCircle :size="32" />
        <p>No icons found for "{{ searchQuery }}"</p>
      </div>
      
      <div v-if="!searchQuery" class="grid-footer">
        <p>Showing first 100 icons. Search to find others.</p>
      </div>
    </section>
  </div>
</template>

<style scoped>
.guide-item h4 { font-weight: var(--ch-font-bold); margin-bottom: 4px; }
.guide-item p { font-size: var(--ch-text-sm); color: var(--ch-color-text-muted); }
.guide-icon { color: var(--ch-color-primary); margin-bottom: var(--ch-space-4); }

.core-grid { display: flex; flex-direction: column; gap: var(--ch-space-8); margin-top: var(--ch-space-6); }
.category-name { font-weight: var(--ch-font-bold); color: var(--ch-color-text-subtle); font-size: var(--ch-text-xs); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: var(--ch-space-4); }

.icon-row { display: flex; flex-wrap: wrap; gap: var(--ch-space-4); }

.icon-preview-box {
  width: 100px;
  height: 80px;
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--ch-space-2);
  transition: all 0.2s ease;
}

.icon-preview-box span { font-size: 10px; color: var(--ch-color-text-muted); text-align: center; }
.icon-preview-box:hover { border-color: var(--ch-color-primary); color: var(--ch-color-primary); transform: translateY(-2px); }

.search-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: var(--ch-space-6);
}

@media (max-width: 640px) {
  .search-header { flex-direction: column; gap: var(--ch-space-4); }
  .icon-search { width: 100%; }
}

.icon-search { width: 300px; }

.icon-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: var(--ch-space-4);
}

.icon-tile {
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border);
  padding: var(--ch-space-4);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--ch-space-3);
  transition: all 0.15s ease;
}

.icon-tile:hover {
  border-color: var(--ch-color-primary);
  background: var(--ch-color-bg-subtle);
}

.tile-icon { color: var(--ch-color-text); }
.tile-name { font-size: 11px; color: var(--ch-color-text-muted); word-break: break-all; }

.grid-footer { margin-top: var(--ch-space-8); text-align: center; color: var(--ch-color-text-subtle); font-size: var(--ch-text-sm); }
.no-results { text-align: center; padding: var(--ch-space-12); color: var(--ch-color-text-subtle); }
</style>
