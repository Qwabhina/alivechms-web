<script setup lang="ts">
/**
 * DataPatternsView.vue - Advanced Data Manipulation
 * 
 * Documents patterns for filtering, bulk actions, and data visualization.
 */

import { ChCard, ChButton, ChInput, ChBadge } from '@/design-system'
import { 
  Filter, 
  Search, 
  Download, 
  Trash2, 
  CheckSquare,
  BarChart3,
  FileQuestion,
  Plus
} from 'lucide-vue-next'
</script>

<template>
  <div class="doc-page">
    <header class="page-header">
      <h1 class="page-title">Data Patterns</h1>
      <p class="page-desc">
        Standardized approaches for managing large datasets, complex filtering, 
        and visualizing metrics.
      </p>
    </header>

    <section class="doc-section">
      <h2 class="doc-section-title">Common Filter Bar</h2>
      <p>
        The standard filter bar for data tables should prioritize the primary search 
        input followed by contextual filters and actions.
      </p>

      <div class="demo-block">
        <div class="filter-bar-demo">
          <div class="filter-left">
            <ChInput placeholder="Search records..." size="sm" class="search-field">
              <template #leading><Search :size="14" /></template>
            </ChInput>
            <ChButton variant="outline" size="sm">
              <template #icon><Filter :size="14" /></template>
              Filters
              <ChBadge variant="primary" size="sm" class="filter-count">2</ChBadge>
            </ChButton>
          </div>
          <div class="filter-right">
            <ChButton variant="outline" size="sm">
              <template #icon><Download :size="14" /></template>
              Export
            </ChButton>
            <ChButton variant="primary" size="sm">
              <Plus :size="14" />
              Add Record
            </ChButton>
          </div>
        </div>
        <p class="demo-description">
          The filter bar maintains a consistent height of <code>40px</code> and 
          adapts its layout for mobile screens.
        </p>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Bulk Actions</h2>
      <p>
        When multiple items are selected in a table, show a floating action bar 
        to provide context-sensitive operations.
      </p>

      <div class="demo-block">
        <div class="bulk-action-mock">
          <div class="selection-status">
            <CheckSquare :size="16" />
            <strong>12 items selected</strong>
          </div>
          <div class="bulk-buttons">
            <ChButton variant="outline" size="sm">Archive</ChButton>
            <ChButton variant="outline" size="sm">Mark as Paid</ChButton>
            <ChButton variant="danger" size="sm">
              <template #icon><Trash2 :size="14" /></template>
              Delete
            </ChButton>
          </div>
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Empty States</h2>
      <div class="card-grid card-grid--2">
        <ChCard padding="lg">
          <div class="empty-state">
            <div class="empty-icon">
              <FileQuestion :size="48" />
            </div>
            <h3 class="empty-title">No results found</h3>
            <p class="empty-text">
              Try adjusting your filters or search terms to find what you're 
              looking for.
            </p>
            <ChButton variant="outline" size="sm">Clear All Filters</ChButton>
          </div>
        </ChCard>

        <ChCard padding="lg">
          <div class="empty-state">
            <div class="empty-icon primary">
              <Plus :size="48" />
            </div>
            <h3 class="empty-title">Start your first campaign</h3>
            <p class="empty-text">
              You haven't created any outreach campaigns yet. Start now to 
              engage your community.
            </p>
            <ChButton variant="primary" size="sm">Create Campaign</ChButton>
          </div>
        </ChCard>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Visualization Density</h2>
      <div class="demo-block">
        <div class="chart-pattern-grid">
          <div class="chart-item">
            <div class="chart-header">
              <h4>Weekly Growth</h4>
              <BarChart3 :size="16" />
            </div>
            <div class="chart-placeholder">
              <!-- Mock bar chart -->
              <div class="bar" style="height: 40%"></div>
              <div class="bar" style="height: 60%"></div>
              <div class="bar" style="height: 30%"></div>
              <div class="bar" style="height: 80%"></div>
              <div class="bar" style="height: 50%"></div>
            </div>
          </div>
          <div class="info-panel">
            <h4>Analytic Card Pattern</h4>
            <p>
              Combine a summary metric with a simplified trend chart for immediate 
              context. Avoid cluttering with too many labels in small cards.
            </p>
            <ChBadge variant="info">Use simplified tooltips</ChBadge>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.filter-bar-demo {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--ch-space-4);
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border-strong);
}

@media (max-width: 768px) {
  .filter-bar-demo {
    flex-direction: column;
    gap: var(--ch-space-4);
    align-items: stretch;
  }
}

.filter-left, .filter-right {
  display: flex;
  gap: var(--ch-space-3);
  align-items: center;
}

.search-field { width: 240px; }
.filter-count { margin-left: 4px; }

.bulk-action-mock {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--ch-space-4) var(--ch-space-6);
  background: var(--ch-color-text);
  color: white;
  border: 1px solid var(--ch-color-border-strong);
  box-shadow: 4px 4px 0px var(--ch-color-border-strong);
}

.selection-status {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
}

.bulk-buttons {
  display: flex;
  gap: var(--ch-space-3);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.empty-icon {
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto var(--ch-space-6) auto;
  border: 2px solid var(--ch-color-border);
  color: var(--ch-color-text-subtle);
}

.empty-icon.primary {
  border-color: var(--ch-color-primary);
  color: var(--ch-color-primary);
  background: var(--ch-color-bg-subtle);
}

.empty-title {
  font-weight: var(--ch-font-bold);
  font-size: var(--ch-text-xl);
  margin-bottom: var(--ch-space-2);
}

.empty-text {
  color: var(--ch-color-text-muted);
  font-size: var(--ch-text-sm);
  margin-bottom: var(--ch-space-8);
  max-width: 250px;
}

.chart-pattern-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: var(--ch-space-8);
}

.chart-item {
  border: 1px solid var(--ch-color-border-strong);
  padding: var(--ch-space-4);
  background: var(--ch-color-surface);
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--ch-space-4);
}

.chart-header h4 { font-weight: var(--ch-font-bold); margin: 0; }

.chart-placeholder {
  height: 120px;
  display: flex;
  align-items: flex-end;
  gap: 8px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--ch-color-border);
}

.bar {
  flex: 1;
  background: var(--ch-color-primary);
  opacity: 0.8;
}

.info-panel h4 { font-weight: var(--ch-font-bold); margin-bottom: var(--ch-space-2); }
</style>
