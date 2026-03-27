<script setup lang="ts">
/**
 * ChangelogView.vue - Version History
 * 
 * Tracks the evolution of the AliveCHMS design system.
 */

import { ChCard, ChBadge } from '@/design-system'
import { 
  GitBranch, 
  Calendar,
  ExternalLink
} from 'lucide-vue-next'

const updates = [
  {
    version: '2.0.0',
    date: 'March 25, 2026',
    status: 'latest',
    summary: 'The "Brutalist-Lite" Evolution',
    changes: [
      { type: 'feat', text: 'Complete visual overhaul with sharp geometry and block shadows.' },
      { type: 'feat', text: 'Switched display font to Instrument Serif (editorial feel).' },
      { type: 'feat', text: 'Implemented reactive theme system (useTheme composable).' },
      { type: 'feat', text: 'New Documentation Site with multi-page navigation.' },
      { type: 'refactor', text: 'Replaced static tokens.css with JS-based CSS variable injection.' },
      { type: 'refactor', text: 'Migrated all components to use the new semantic scale.' }
    ]
  },
  {
    version: '1.5.0',
    date: 'February 10, 2026',
    status: 'stable',
    summary: 'Component Expansion Pack',
    changes: [
      { type: 'feat', text: 'Added ChTable with sorting, pagination, and CSV export.' },
      { type: 'feat', text: 'Added ChChart for reactive data visualization.' },
      { type: 'feat', text: 'Added ChStatCard for dashboard metrics.' },
      { type: 'fix', text: 'Resolved layout flickering on mobile drawer toggle.' },
      { type: 'fix', text: 'Fixed accessible labeling for icon-only buttons.' }
    ]
  },
  {
    version: '1.0.0',
    date: 'January 15, 2026',
    status: 'deprecated',
    summary: 'Initial Release',
    changes: [
      { type: 'feat', text: 'Core primitive components: Button, Input, Card, Badge.' },
      { type: 'feat', text: 'Initial token system for colors, spacing, and typography.' },
      { type: 'feat', text: 'Layout foundations with flexbox/grid utilities.' }
    ]
  }
]

const getBadgeVariant = (type: string) => {
  switch (type) {
    case 'feat': return 'success'
    case 'fix': return 'danger'
    case 'refactor': return 'info'
    default: return 'default'
  }
}

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'latest': return 'primary'
    case 'stable': return 'success'
    case 'deprecated': return 'danger'
    default: return 'default'
  }
}
</script>

<template>
  <div class="doc-page">
    <header class="page-header">
      <h1 class="page-title">Changelog</h1>
      <p class="page-desc">
        Release notes and version history for the AliveCHMS design system.
      </p>
    </header>

    <div class="changelog-timeline">
      <div v-for="update in updates" :key="update.version" class="changelog-entry">
        <!-- Entry Sidebar -->
        <div class="entry-sidebar">
          <div class="version-badge">
            <GitBranch :size="16" />
            <span>v{{ update.version }}</span>
          </div>
          <div class="entry-date">
            <Calendar :size="14" />
            <span>{{ update.date }}</span>
          </div>
          <ChBadge :variant="getStatusVariant(update.status)" size="sm" :dot="true" class="status-badge">
            {{ update.status }}
          </ChBadge>
        </div>

        <!-- Entry Content -->
        <div class="entry-main">
          <ChCard>
            <div class="entry-header">
              <h2 class="entry-summary">{{ update.summary }}</h2>
              <div class="entry-meta">
                <span>{{ update.changes.length }} changes included</span>
              </div>
            </div>

            <div class="change-list">
              <div v-for="(change, index) in update.changes" :key="index" class="change-item">
                <div class="change-tag">
                  <ChBadge :variant="getBadgeVariant(change.type)" size="sm">
                    {{ change.type }}
                  </ChBadge>
                </div>
                <div class="change-text">{{ change.text }}</div>
              </div>
            </div>
          </ChCard>
        </div>
      </div>
    </div>

    <section class="doc-section footer-notice">
      <p>
        Looking for older versions? 
        <a href="#" class="legacy-link">View Legacy Documentation <ExternalLink :size="14" /></a>
      </p>
    </section>
  </div>
</template>

<style scoped>
.changelog-timeline {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-12);
  margin-top: var(--ch-space-8);
  position: relative;
}

.changelog-timeline::before {
  content: '';
  position: absolute;
  left: 140px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: var(--ch-color-border);
  z-index: 0;
}

@media (max-width: 768px) {
  .changelog-timeline::before {
    display: none;
  }
}

.changelog-entry {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: var(--ch-space-12);
  position: relative;
  z-index: 1;
}

@media (max-width: 768px) {
  .changelog-entry {
    grid-template-columns: 1fr;
    gap: var(--ch-space-4);
  }
}

.entry-sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-3);
  padding-top: var(--ch-space-4);
}

.version-badge {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  font-family: var(--ch-font-mono);
  font-weight: var(--ch-font-bold);
  font-size: var(--ch-text-lg);
  color: var(--ch-color-primary);
}

.entry-date {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

.status-badge {
  text-transform: capitalize;
  width: fit-content;
}

.entry-header {
  margin-bottom: var(--ch-space-6);
  border-bottom: 1px solid var(--ch-color-border-subtle);
  padding-bottom: var(--ch-space-4);
}

.entry-summary {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-2xl);
  font-weight: var(--ch-font-bold);
  margin: 0 0 var(--ch-space-1) 0;
}

.entry-meta {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-subtle);
}

.change-list {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-3);
}

.change-item {
  display: flex;
  gap: var(--ch-space-4);
  align-items: flex-start;
}

.change-tag {
  width: 70px;
  flex-shrink: 0;
  display: flex;
  justify-content: flex-end;
}

.change-text {
  font-size: var(--ch-text-base);
  line-height: var(--ch-leading-normal);
  color: var(--ch-color-text-muted);
}

.footer-notice {
  margin-top: var(--ch-space-12);
  text-align: center;
  color: var(--ch-color-text-subtle);
  font-size: var(--ch-text-sm);
}

.legacy-link {
  color: var(--ch-color-primary);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.legacy-link:hover {
  text-decoration: underline;
}
</style>
