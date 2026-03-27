<script setup lang="ts">
/**
 * LayoutPatternsView.vue - Architectural Layouts
 * 
 * Documents common page structures and responsive behaviors.
 */

import { ChCard, ChBadge } from '@/design-system'
import { 
  Layout, 
  Smartphone, 
  Maximize2,
  Minimize2
} from 'lucide-vue-next'
</script>

<template>
  <div class="doc-page">
    <header class="page-header">
      <h1 class="page-title">Layout Patterns</h1>
      <p class="page-desc">
        Standardized page structures and responsive behaviors to ensure consistency
        across the entire AliveCHMS application.
      </p>
    </header>

    <section class="doc-section">
      <h2 class="doc-section-title">Standard Page Layout</h2>
      <p>
        The typical application page is divided into a consistent set of regions.
        This ensures users always know where to look for navigation and actions.
      </p>

      <div class="layout-demo">
        <div class="layout-mockup">
          <div class="mock-sidebar"></div>
          <div class="mock-main">
            <div class="mock-topbar"></div>
            <div class="mock-content">
              <div class="mock-header"></div>
              <div class="mock-grid">
                <div class="mock-item"></div>
                <div class="mock-item"></div>
                <div class="mock-item"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="layout-info">
          <ul class="layout-parts">
            <li>
              <strong>Sidebar:</strong> Global navigation and identity. Remains 
              persistent on desktop, collapses to a drawer on mobile.
            </li>
            <li>
              <strong>Topbar:</strong> Contextual tools (Search, Notifications, 
              User Menu). Fixed at the top of the viewport.
            </li>
            <li>
              <strong>Page Header:</strong> Current context (Title, Description, 
              Primary Actions).
            </li>
            <li>
              <strong>Content Area:</strong> The main workspace where data and 
              forms are displayed.
            </li>
          </ul>
        </div>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Sidebar Behaviors</h2>
      <div class="card-grid card-grid--2">
        <ChCard padding="lg">
          <div class="pattern-header">
            <div class="header-icon">
              <Maximize2 :size="20" />
            </div>
            <h3>Expanded (Default)</h3>
          </div>
          <p>
            Default state on desktop (>=1280px). Shows full labels and 
            section headers for optimal click target size.
          </p>
          <ChBadge variant="info">280px Width</ChBadge>
        </ChCard>

        <ChCard padding="lg">
          <div class="pattern-header">
            <div class="header-icon">
              <Minimize2 :size="20" />
            </div>
            <h3>Collapsed</h3>
          </div>
          <p>
            Optional state for power users or smaller screens. Only shows 
            icons with tooltips for increased horizontal workspace.
          </p>
          <ChBadge variant="default">72px Width</ChBadge>
        </ChCard>

        <ChCard padding="lg">
          <div class="pattern-header">
            <div class="header-icon">
              <Smartphone :size="20" />
            </div>
            <h3>Mobile Drawer</h3>
          </div>
          <p>
            Hidden by default on mobile. Triggered by a hamburger menu in 
             the topbar. Slides in with a backdrop overlay.
          </p>
          <ChBadge variant="warning">Full Height Overlay</ChBadge>
        </ChCard>

        <ChCard padding="lg">
          <div class="pattern-header">
            <div class="header-icon">
              <Layout :size="20" />
            </div>
            <h3>Responsive Breakpoints</h3>
          </div>
          <p>
            The layout system uses defined breakpoints to adapt components 
            smoothly across devices.
          </p>
          <div class="breakpoint-list">
            <code>sm: 640px</code>
            <code>md: 768px</code>
            <code>lg: 1024px</code>
            <code>xl: 1280px</code>
          </div>
        </ChCard>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Content Containers</h2>
      <div class="demo-block">
        <div class="container-demo">
          <div class="container-item max-width-md">
            <span>Medium Container (max-width: 800px)</span>
          </div>
          <div class="container-item max-width-lg">
            <span>Large Container (max-width: 1200px)</span>
          </div>
          <div class="container-item full-width">
            <span>Full Width (100%)</span>
          </div>
        </div>
        <p class="demo-description">
          Use constrained containers for readability in text-heavy pages, 
          and full-width containers for dashboards and data tables.
        </p>
      </div>
    </section>

    <section class="doc-section">
      <h2 class="doc-section-title">Grid & Spacing Patterns</h2>
      <div class="grid-showcase">
        <div class="grid-item span-12">
          <h4>Full Row (12 Cols)</h4>
          <p>Used for page headers or major sections.</p>
        </div>
        <div class="grid-item span-6">
          <h4>Half Row (6 Cols)</h4>
        </div>
        <div class="grid-item span-6">
          <h4>Half Row (6 Cols)</h4>
        </div>
        <div class="grid-item span-4">
          <h4>Thirds (4 Cols)</h4>
        </div>
        <div class="grid-item span-4">
          <h4>Thirds (4 Cols)</h4>
        </div>
        <div class="grid-item span-4">
          <h4>Thirds (4 Cols)</h4>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.layout-demo {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--ch-space-8);
  margin-top: var(--ch-space-6);
  background: var(--ch-color-bg-subtle);
  padding: var(--ch-space-8);
  border: 1px dashed var(--ch-color-border-strong);
}

@media (max-width: 1024px) {
  .layout-demo {
    grid-template-columns: 1fr;
  }
}

.layout-mockup {
  height: 300px;
  background: var(--ch-color-surface);
  border: 2px solid var(--ch-color-border-strong);
  display: flex;
  overflow: hidden;
  box-shadow: 4px 4px 0px var(--ch-color-border-strong);
}

.mock-sidebar {
  width: 60px;
  height: 100%;
  background: var(--ch-color-bg-subtle);
  border-right: 1px solid var(--ch-color-border);
}

.mock-main {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.mock-topbar {
  height: 40px;
  background: var(--ch-color-surface);
  border-bottom: 1px solid var(--ch-color-border);
}

.mock-content {
  padding: 1rem;
  flex: 1;
}

.mock-header {
  height: 20px;
  width: 60%;
  background: var(--ch-color-border);
  margin-bottom: 1rem;
}

.mock-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
}

.mock-item {
  height: 60px;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
}

.layout-parts {
  list-style: none;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
}

.layout-parts li {
  line-height: 1.5;
}

.layout-parts strong {
  color: var(--ch-color-primary);
  display: block;
}

.pattern-header {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  margin-bottom: var(--ch-space-3);
}

.header-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border-strong);
  color: var(--ch-color-primary);
}

.pattern-header h3 {
  font-weight: var(--ch-font-bold);
  margin: 0;
}

.breakpoint-list {
  display: flex;
  flex-wrap: wrap;
  gap: var(--ch-space-2);
  margin-top: var(--ch-space-4);
}

.breakpoint-list code {
  background: var(--ch-color-bg-subtle);
  padding: 2px 6px;
  border: 1px solid var(--ch-color-border);
  font-size: var(--ch-text-xs);
}

.container-demo {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
}

.container-item {
  height: 48px;
  background: var(--ch-color-primary);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--ch-text-xs);
  font-weight: var(--ch-font-bold);
}

.max-width-md { max-width: 300px; }
.max-width-lg { max-width: 450px; }
.full-width { width: 100%; border: 1px dashed white; }

.grid-showcase {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: var(--ch-space-4);
}

.grid-item {
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  padding: var(--ch-space-4);
  text-align: center;
}

.grid-item h4 {
  font-size: var(--ch-text-sm);
  margin-bottom: var(--ch-space-1);
}

.grid-item p {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

.span-12 { grid-column: span 12; }
.span-6 { grid-column: span 6; }
.span-4 { grid-column: span 4; }
</style>
