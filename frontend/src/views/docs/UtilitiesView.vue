<script setup lang="ts">
/**
 * UtilitiesView.vue - CSS Helpers
 * 
 * Documents global utility classes for layout, typography, and spacing.
 */

import { ChCard, ChBadge } from '@/design-system'
import { 
  Zap, 
  AlignCenter
} from 'lucide-vue-next'

const spacingUtils = [
  { class: '.m-0', prop: 'margin', value: '0' },
  { class: '.p-4', prop: 'padding', value: '16px' },
  { class: '.gap-6', prop: 'gap', value: '24px' },
  { class: '.mt-auto', prop: 'margin-top', value: 'auto' }
]

const typoUtils = [
  { class: '.text-center', icon: AlignCenter, desc: 'Horizontal text alignment' },
  { class: '.font-bold', icon: null, desc: 'Sets font-weight to 700' },
  { class: '.uppercase', icon: null, desc: 'Text transformation' },
  { class: '.truncate', icon: null, desc: 'Ellipsis for long text' }
]
</script>

<template>
  <div class="doc-page">
    <header class="page-header">
      <h1 class="page-title">Utilities</h1>
      <p class="page-desc">
        A collection of tactical CSS utility classes to speed up development 
        and maintain consistency without writing custom styles.
      </p>
    </header>

    <section class="doc-section">
      <h2 class="doc-section-title">Concept</h2>
      <div class="demo-block">
        <p>
          While we prioritize component-based styling, utilities are provided for 
          minor layout adjustments and text overrides. All utilities follow 
          the <code>.ch-[property]-[value]</code> prefix convention to avoid 
          collisions with third-party libraries.
        </p>
        <ChBadge variant="info" :dot="true">Prefix: .ch-</ChBadge>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Spacing Utilities</h2>
      <div class="card-grid">
        <ChCard padding="none">
          <table class="util-table">
            <thead>
              <tr>
                <th>Class</th>
                <th>Property</th>
                <th>Token</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="util in spacingUtils" :key="util.class">
                <td><code class="util-code">{{ util.class }}</code></td>
                <td>{{ util.prop }}</td>
                <td><code>{{ util.value }}</code></td>
              </tr>
              <tr>
                <td colspan="3" class="table-note">...follow this pattern for all spacing tokens (0-16)</td>
              </tr>
            </tbody>
          </table>
        </ChCard>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Typography Utilities</h2>
      <div class="card-grid card-grid--2">
        <div v-for="util in typoUtils" :key="util.class" class="util-feature">
          <div class="feature-icon">
            <component :is="util.icon" v-if="util.icon" :size="20" />
            <Zap v-else :size="20" />
          </div>
          <div class="feature-content">
            <code class="util-code">{{ util.class }}</code>
            <p>{{ util.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Display & Visibility</h2>
      <div class="demo-block">
        <div class="visibility-demo">
          <div class="vis-item">.ch-flex</div>
          <div class="vis-item">.ch-grid</div>
          <div class="vis-item">.ch-hidden</div>
          <div class="vis-item">.ch-block</div>
        </div>
        <div class="responsive-note">
          <p>
            Standard responsive prefixes are supported: <br/>
            <code>md:ch-hidden</code>, <code>lg:ch-flex</code>
          </p>
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Interactive Helpers</h2>
      <div class="card-grid card-grid--3">
        <ChCard hoverable padding="md">
          <code class="util-code">.ch-cursor-pointer</code>
          <p class="util-desc">Force pointer cursor on any element.</p>
        </ChCard>
        <ChCard hoverable padding="md">
          <code class="util-code">.ch-select-none</code>
          <p class="util-desc">Prevent text selection on UI elements.</p>
        </ChCard>
        <ChCard hoverable padding="md">
          <code class="util-code">.ch-pointer-events-none</code>
          <p class="util-desc">Disable all pointer interactions.</p>
        </ChCard>
      </div>
    </section>
  </div>
</template>

<style scoped>
.util-table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--ch-text-sm);
}

.util-table th {
  text-align: left;
  padding: var(--ch-space-3) var(--ch-space-4);
  background: var(--ch-color-bg-subtle);
  color: var(--ch-color-text-subtle);
  font-weight: var(--ch-font-semibold);
  border-bottom: 2px solid var(--ch-color-border-strong);
}

.util-table td {
  padding: var(--ch-space-3) var(--ch-space-4);
  border-bottom: 1px solid var(--ch-color-border);
}

.util-code {
  color: var(--ch-color-primary);
  font-weight: var(--ch-font-bold);
}

.table-note {
  text-align: center;
  color: var(--ch-color-text-subtle);
  font-style: italic;
  font-size: var(--ch-text-xs);
}

.util-feature {
  display: flex;
  gap: var(--ch-space-4);
  padding: var(--ch-space-4);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border);
}

.feature-icon {
  width: 40px;
  height: 40px;
  background: var(--ch-color-bg-subtle);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--ch-color-primary);
  flex-shrink: 0;
}

.feature-content p {
  margin-top: 4px;
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
}

.visibility-demo {
  display: flex;
  gap: var(--ch-space-3);
  flex-wrap: wrap;
  margin-bottom: var(--ch-space-6);
}

.vis-item {
  background: var(--ch-color-text);
  color: white;
  padding: 4px 12px;
  font-family: var(--ch-font-mono);
  font-size: var(--ch-text-xs);
}

.responsive-note {
  padding: var(--ch-space-4);
  background: var(--ch-color-bg-subtle);
  border-left: 4px solid var(--ch-color-primary);
}

.responsive-note code { font-weight: var(--ch-font-bold); }
.util-desc { font-size: var(--ch-text-xs); color: var(--ch-color-text-muted); margin-top: 8px; }
</style>
