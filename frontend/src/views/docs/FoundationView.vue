<script setup lang="ts">
/**
 * FoundationView.vue - Design System Foundation Documentation
 * 
 * This view demonstrates the primitive tokens and design foundations
 * of the AliveCHMS brutalist-lite design system.
 * 
 * Sections covered:
 * - Design Philosophy: Core principles of the brutalist-lite aesthetic
 * - Theme Switcher: Light/dark mode toggle demonstration
 * - Color Tokens: Complete color palette with semantic meanings
 * - Typography Scale: Display and UI font demonstrations
 * - Spacing System: Consistent spacing tokens
 * - Divider Styles: Horizontal and vertical divider variations
 * - Shadow/Elevation: Block shadow demonstrations
 * 
 * @requires lucide-vue-next for icons
 */

import { useTheme } from '@/design-system'
import {
  Sun,
  Moon,
  Minus,
  AlignLeft,
  Box
} from 'lucide-vue-next'

// ============================================================
// THEME SWITCHER STATE
// ============================================================

/** Access global theme state from the design system */
const { isDarkMode, toggleDarkMode } = useTheme()

/**
 * Toggles between light and dark mode using the global theme composable.
 * This automatically updates CSS variables and document classes.
 */
function toggleTheme() {
  toggleDarkMode()
}

// ============================================================
// COLOR TOKENS DATA
// ============================================================

/**
 * Complete color palette for the design system.
 * Each color has semantic meaning and is used consistently throughout.
 */
const colorTokens = [
  {
    category: 'Primary',
    description: 'Brand color for primary actions and key UI elements',
    colors: [
      { name: 'Primary', var: '--ch-color-primary', usage: 'Buttons, links, active states' },
    ]
  },
  {
    category: 'Semantic',
    description: 'Status colors for feedback and communication',
    colors: [
      { name: 'Success', var: '--ch-color-success', usage: 'Positive actions, confirmations' },
      { name: 'Warning', var: '--ch-color-warning', usage: 'Caution, attention needed' },
      { name: 'Danger', var: '--ch-color-danger', usage: 'Destructive actions, errors' },
      { name: 'Info', var: '--ch-color-info', usage: 'Neutral information, tips' },
    ]
  },
  {
    category: 'Surface',
    description: 'Background and surface colors for layering',
    colors: [
      { name: 'Background', var: '--ch-color-bg', usage: 'Page background' },
      { name: 'Background Subtle', var: '--ch-color-bg-subtle', usage: 'Secondary backgrounds' },
      { name: 'Surface', var: '--ch-color-surface', usage: 'Cards, panels, modals' },
    ]
  },
  {
    category: 'Text',
    description: 'Text colors for hierarchy and emphasis',
    colors: [
      { name: 'Text', var: '--ch-color-text', usage: 'Primary text' },
      { name: 'Text Muted', var: '--ch-color-text-muted', usage: 'Secondary text, descriptions' },
      { name: 'Text Subtle', var: '--ch-color-text-subtle', usage: 'Tertiary text, placeholders' },
    ]
  },
  {
    category: 'Border',
    description: 'Border colors for structure and separation',
    colors: [
      { name: 'Border', var: '--ch-color-border', usage: 'Subtle borders, dividers' },
      { name: 'Border Strong', var: '--ch-color-border-strong', usage: 'Prominent borders, outlines' },
    ]
  },
]

// ============================================================
// TYPOGRAPHY SCALE DATA
// ============================================================

/**
 * Typography scale demonstrating font sizes from 3xs to 5xl.
 * Uses Instrument Serif for display (headings) and Inter for UI (body text).
 */
const typographyScale = [
  { token: '--ch-text-3xs', value: '0.5rem (8px)', usage: 'Captions, legal text' },
  { token: '--ch-text-2xs', value: '0.625rem (10px)', usage: 'Badges, labels' },
  { token: '--ch-text-xs', value: '0.75rem (12px)', usage: 'Helper text, timestamps' },
  { token: '--ch-text-sm', value: '0.875rem (14px)', usage: 'Small text, table cells' },
  { token: '--ch-text-base', value: '1rem (16px)', usage: 'Body text, default' },
  { token: '--ch-text-lg', value: '1.125rem (18px)', usage: 'Lead paragraphs' },
  { token: '--ch-text-xl', value: '1.25rem (20px)', usage: 'Section headers' },
  { token: '--ch-text-2xl', value: '1.5rem (24px)', usage: 'Card titles' },
  { token: '--ch-text-3xl', value: '1.875rem (30px)', usage: 'Page subtitles' },
  { token: '--ch-text-4xl', value: '2.25rem (36px)', usage: 'Page titles' },
  { token: '--ch-text-5xl', value: '3rem (48px)', usage: 'Hero headlines' },
]

// ============================================================
// SPACING SCALE DATA
// ============================================================

/**
 * Spacing scale based on 4px unit system.
 * All spacing values are multiples of 4 for consistency.
 */
const spacingScale = [
  { token: '--ch-space-0', value: '0px', usage: 'No spacing' },
  { token: '--ch-space-1', value: '4px', usage: 'Tight gaps, icon padding' },
  { token: '--ch-space-2', value: '8px', usage: 'Small gaps, list items' },
  { token: '--ch-space-3', value: '12px', usage: 'Default gaps, form fields' },
  { token: '--ch-space-4', value: '16px', usage: 'Medium gaps, card padding' },
  { token: '--ch-space-5', value: '20px', usage: 'Large gaps, section padding' },
  { token: '--ch-space-6', value: '24px', usage: 'Section spacing' },
  { token: '--ch-space-8', value: '32px', usage: 'Large section gaps' },
  { token: '--ch-space-10', value: '40px', usage: 'Extra large spacing' },
  { token: '--ch-space-12', value: '48px', usage: 'Major section breaks' },
  { token: '--ch-space-16', value: '64px', usage: 'Page section margins' },
]

// ============================================================
// SHADOW/ELVATION DATA
// ============================================================

/**
 * Shadow tokens for elevation and depth.
 * Uses the brutalist block shadow aesthetic (harsh offsets, no blur).
 */
const shadowTokens = [
  {
    name: 'None',
    token: '--ch-shadow-none',
    value: 'none',
    usage: 'Flat elements, no elevation'
  },
  {
    name: 'Small',
    token: '--ch-shadow-sm',
    value: '2px 2px 0px var(--ch-color-border-strong)',
    usage: 'Subtle lift, hover states'
  },
  {
    name: 'Default',
    token: '--ch-shadow',
    value: '4px 4px 0px var(--ch-color-border-strong)',
    usage: 'Cards, dropdowns, modals'
  },
  {
    name: 'Large',
    token: '--ch-shadow-lg',
    value: '8px 8px 0px var(--ch-color-border-strong)',
    usage: 'Floating elements, tooltips'
  },
  {
    name: 'XL',
    token: '--ch-shadow-xl',
    value: '12px 12px 0px var(--ch-color-border-strong)',
    usage: 'Overlays, drawers'
  },
]

// ============================================================
// LINE HEIGHT SCALE
// ============================================================

const lineHeightScale = [
  { token: '--ch-leading-none', value: '1', usage: 'Tight, headings' },
  { token: '--ch-leading-tight', value: '1.25', usage: 'Navigation, labels' },
  { token: '--ch-leading-snug', value: '1.375', usage: 'Subheadings' },
  { token: '--ch-leading-normal', value: '1.5', usage: 'Default body' },
  { token: '--ch-leading-relaxed', value: '1.625', usage: 'Comfortable reading' },
  { token: '--ch-leading-loose', value: '2', usage: 'Accessibility, large text' },
]

// ============================================================
// FONT WEIGHT SCALE
// ============================================================

const fontWeightScale = [
  { token: '--ch-font-normal', value: '400', usage: 'Body text' },
  { token: '--ch-font-medium', value: '500', usage: 'Emphasis, links' },
  { token: '--ch-font-semibold', value: '600', usage: 'Subheadings' },
  { token: '--ch-font-bold', value: '700', usage: 'Headings, strong' },
  { token: '--ch-font-extrabold', value: '800', usage: 'Display text' },
]
</script>

<template>
  <div class="doc-page">
    <!-- ============================================================
         PAGE HEADER
         ============================================================ -->
    <header class="page-header">
      <h1 class="page-title">Foundation</h1>
      <p class="page-desc">
        The primitive tokens that form the bedrock of the brutalist-lite design system:
        steep contrast colors, sharp 0px geometry, and mechanical typography.
      </p>
    </header>

    <!-- ============================================================
         DESIGN PHILOSOPHY
         ============================================================ -->
    <section class="doc-section">
      <h2 class="doc-section-title">Design Philosophy</h2>
      <div class="demo-block">
        <p>The redesign focuses on a premium stark aesthetic. All fuzzy drop shadows and soft rounded corners have been eliminated in favor of:</p>
        <ul class="philosophy-list">
          <li>
            <strong>Absolute Sharpness:</strong> <code>border-radius: 0px</code> globally.
            Sharp corners create a bold, architectural presence.
          </li>
          <li>
            <strong>Mechanical Depth:</strong> Shadows are harsh <code>4px 4px 0px</code> block offsets
            rather than blurry gradients. This creates a "printed paper" effect.
          </li>
          <li>
            <strong>Strict Focus States:</strong> Instead of glowing rings, focus states use rigid
            <code>outline: 2px solid</code> with an <code>outline-offset</code>.
          </li>
          <li>
            <strong>Slate Dominance:</strong> High-contrast monochromatic slates replaced the cheerful indigos.
          </li>
          <li>
            <strong>Type Scale:</strong> Uses Instrument Serif for display (elegant, editorial)
            and Inter for UI (clean, functional).
          </li>
        </ul>
      </div>
    </section>

    <!-- ============================================================
         THEME SWITCHER
         ============================================================ -->
    <section class="doc-section">
      <h2 class="doc-section-title">Theme Switcher</h2>
      <div class="demo-block">
        <p class="demo-description">
          Toggle between light and dark modes to see how the design system adapts.
          The color tokens automatically adjust based on the current theme.
        </p>

        <!-- Theme Toggle Demo -->
        <div class="theme-demo">
          <button class="theme-toggle-demo" @click="toggleTheme" :aria-pressed="isDarkMode">
            <div class="toggle-track" :class="{ 'dark': isDarkMode }">
              <div class="toggle-thumb">
                <Moon v-if="isDarkMode" :size="16" />
                <Sun v-else :size="16" />
              </div>
            </div>
            <span class="toggle-label">
              {{ isDarkMode ? 'Dark Mode' : 'Light Mode' }}
            </span>
          </button>

          <!-- Theme Preview Cards -->
          <div class="theme-preview-grid">
            <div class="theme-card">
              <div class="card-bg-preview"></div>
              <span>Background</span>
            </div>
            <div class="theme-card">
              <div class="card-surface-preview"></div>
              <span>Surface</span>
            </div>
            <div class="theme-card">
              <div class="card-primary-preview"></div>
              <span>Primary</span>
            </div>
            <div class="theme-card">
              <div class="card-text-preview"></div>
              <span>Text</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         COLOR TOKENS
         ============================================================ -->
    <section class="doc-section">
      <h2 class="doc-section-title">Color Palette</h2>

      <!-- Color Category -->
      <div v-for="category in colorTokens" :key="category.category" class="color-category">
        <div class="category-header">
          <h3>{{ category.category }}</h3>
          <p>{{ category.description }}</p>
        </div>

        <div class="color-grid">
          <div v-for="color in category.colors" :key="color.var" class="color-swatch">
            <div class="swatch-preview" :style="{ background: `var(${color.var})` }"></div>
            <div class="swatch-info">
              <div class="swatch-name">{{ color.name }}</div>
              <code class="swatch-token">{{ color.var }}</code>
              <div class="swatch-usage">{{ color.usage }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         TYPOGRAPHY
         ============================================================ -->
    <section class="doc-section">
      <h2 class="doc-section-title">Typography</h2>

      <!-- Font Families -->
      <div class="demo-block">
        <h3 class="demo-title">Font Families</h3>
        <div class="font-showcase">
          <div class="font-example display-font">
            <span class="font-label">Display Font</span>
            <span class="font-name">Instrument Serif</span>
            <p class="font-sample-large">The quick brown fox</p>
            <p class="font-sample-meta">Used for: Headings, titles, display text</p>
          </div>
          <div class="font-example ui-font">
            <span class="font-label">UI Font</span>
            <span class="font-name">Inter</span>
            <p class="font-sample">
              The quick brown fox jumps over the lazy dog. This font is optimized
              for screen readability and works well at small sizes.
            </p>
            <p class="font-sample-meta">Used for: Body text, labels, UI elements</p>
          </div>
        </div>
      </div>

      <!-- Font Sizes -->
      <div class="demo-block">
        <h3 class="demo-title">Font Size Scale</h3>
        <div class="size-scale">
          <div v-for="size in typographyScale" :key="size.token" class="size-row">
            <div class="size-preview" :style="{ fontSize: `var(${size.token})` }">
              Aa
            </div>
            <div class="size-info">
              <code>{{ size.token }}</code>
              <span class="size-value">{{ size.value }}</span>
              <span class="size-usage">{{ size.usage }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Font Weights -->
      <div class="demo-block">
        <h3 class="demo-title">Font Weight Scale</h3>
        <div class="weight-scale">
          <div v-for="weight in fontWeightScale" :key="weight.token" class="weight-row">
            <span class="weight-preview" :style="{ fontWeight: `var(${weight.token})` }">
              Almost before we knew it, we had left the ground.
            </span>
            <div class="weight-info">
              <code>{{ weight.token }}</code>
              <span>{{ weight.value }}</span>
              <span class="weight-usage">{{ weight.usage }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Line Heights -->
      <div class="demo-block">
        <h3 class="demo-title">Line Height Scale</h3>
        <div class="lineheight-scale">
          <div v-for="lh in lineHeightScale" :key="lh.token" class="lineheight-row">
            <div class="lineheight-preview" :style="{ lineHeight: `var(${lh.token})` }">
              The towers of Zion rose against the sky like monuments to a civilization that had transcended the need for
              walls.
            </div>
            <div class="lineheight-info">
              <code>{{ lh.token }}</code>
              <span>{{ lh.value }}</span>
              <span class="lineheight-usage">{{ lh.usage }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         SPACING
         ============================================================ -->
    <section class="doc-section">
      <h2 class="doc-section-title">Spacing Scale</h2>
      <div class="demo-block">
        <p class="demo-description">
          The spacing system is based on a 4px unit. All spacing values are multiples of 4
          for consistency and easy mental math.
        </p>

        <div class="spacing-scale">
          <div v-for="space in spacingScale" :key="space.token" class="spacing-row">
            <div class="spacing-preview" :style="{ width: `var(${space.token})` }">
              <span class="spacing-indicator"></span>
            </div>
            <div class="spacing-info">
              <code>{{ space.token }}</code>
              <span class="spacing-value">{{ space.value }}</span>
              <span class="spacing-usage">{{ space.usage }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         DIVIDERS
         ============================================================ -->
    <section class="doc-section">
      <h2 class="doc-section-title">Dividers</h2>
      <div class="demo-block">
        <p class="demo-description">
          Dividers are used to separate content sections. The brutalist-lite system
          uses sharp, consistent borders without decorative elements.
        </p>

        <h4 class="subsection-title">Horizontal Dividers</h4>
        <div class="divider-demo">
          <div class="divider-sample">
            <span class="divider-label">Default (1px)</span>
            <div class="divider divider-default"></div>
          </div>
          <div class="divider-sample">
            <span class="divider-label">Strong (2px)</span>
            <div class="divider divider-strong"></div>
          </div>
          <div class="divider-sample">
            <span class="divider-label">Dashed</span>
            <div class="divider divider-dashed"></div>
          </div>
        </div>
      </div>

      <div class="demo-block">
        <h4 class="subsection-title">Vertical Dividers</h4>
        <div class="vertical-divider-demo">
          <div class="vdivider-item">
            <Box :size="24" />
            <span>Components</span>
          </div>
          <div class="divider divider-vertical divider-default"></div>
          <div class="vdivider-item">
            <AlignLeft :size="24" />
            <span>Forms</span>
          </div>
          <div class="divider divider-vertical divider-strong"></div>
          <div class="vdivider-item">
            <Minus :size="24" />
            <span>Data</span>
          </div>
        </div>
      </div>

      <div class="demo-block">
        <h4 class="subsection-title">Divider with Label</h4>
        <div class="divider-with-label">
          <div class="divider-label-line"></div>
          <span class="divider-label-text">Section Break</span>
          <div class="divider-label-line"></div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         SHADOWS / ELEVATION
         ============================================================ -->
    <section class="doc-section">
      <h2 class="doc-section-title">Shadows & Elevation</h2>
      <div class="demo-block">
        <p class="demo-description">
          Shadows use the brutalist block offset technique: hard edges without blur.
          This creates a "stamped" or "printed" aesthetic rather than realistic depth.
        </p>

        <div class="shadow-showcase">
          <div v-for="shadow in shadowTokens" :key="shadow.token" class="shadow-card"
            :style="{ boxShadow: `var(${shadow.token})` }">
            <div class="shadow-preview"></div>
            <div class="shadow-info">
              <div class="shadow-name">{{ shadow.name }}</div>
              <code class="shadow-token">{{ shadow.token }}</code>
              <code class="shadow-value">{{ shadow.value }}</code>
              <div class="shadow-usage">{{ shadow.usage }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Interactive Shadow Demo -->
      <div class="demo-block">
        <h4 class="subsection-title">Interactive Shadow Demo</h4>
        <p class="demo-description">
          Hover over the cards to see how elevation changes on interaction.
        </p>

        <div class="shadow-interactive">
          <div class="shadow-interactive-card elevation-1">
            <span class="elevation-label">Elevation 1</span>
            <p>Hover to see more elevation</p>
          </div>
          <div class="shadow-interactive-card elevation-2">
            <span class="elevation-label">Elevation 2</span>
            <p>Interactive element</p>
          </div>
          <div class="shadow-interactive-card elevation-3">
            <span class="elevation-label">Elevation 3</span>
            <p>Floating menu</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         BORDER RADIUS
         ============================================================ -->
    <section class="doc-section">
      <h2 class="doc-section-title">Border Radius</h2>
      <div class="demo-block">
        <p class="demo-description">
          The design system uses <code>border-radius: 0px</code> globally for a sharp,
          brutalist aesthetic. This creates a bold, architectural presence.
        </p>

        <div class="radius-showcase">
          <div class="radius-card">
            <div class="radius-preview" style="border-radius: 0px;"></div>
            <span class="radius-label">0px (Default)</span>
            <code>border-radius: 0px</code>
          </div>
        </div>

        <div class="radius-note">
          <strong>Note:</strong> All components use sharp corners.
          Rounded elements have been intentionally removed to maintain
          the brutalist-lite design language.
        </div>
      </div>
    </section>

    <!-- ============================================================
         CSS CUSTOM PROPERTIES REFERENCE
         ============================================================ -->
    <section class="doc-section">
      <h2 class="doc-section-title">CSS Custom Properties</h2>
      <div class="demo-block">
        <p class="demo-description">
          All design tokens are exposed as CSS custom properties (variables)
          for maximum flexibility and theming capability.
        </p>

        <div class="css-vars-reference">
          <div class="vars-category">
            <h4>Colors</h4>
            <code>--ch-color-primary</code>
            <code>--ch-color-success</code>
            <code>--ch-color-warning</code>
            <code>--ch-color-danger</code>
            <code>--ch-color-info</code>
            <code>--ch-color-surface</code>
            <code>--ch-color-bg</code>
            <code>--ch-color-text</code>
          </div>

          <div class="vars-category">
            <h4>Typography</h4>
            <code>--ch-font-display</code>
            <code>--ch-font-sans</code>
            <code>--ch-font-mono</code>
            <code>--ch-text-*</code>
            <code>--ch-font-*</code>
          </div>

          <div class="vars-category">
            <h4>Spacing</h4>
            <code>--ch-space-*</code>
          </div>

          <div class="vars-category">
            <h4>Effects</h4>
            <code>--ch-shadow-*</code>
            <code>--ch-radius-*</code>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* ============================================================
   PHILOSOPHY LIST
   ============================================================ */
.philosophy-list {
  padding-left: 1.5rem;
  margin-top: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.philosophy-list li {
  line-height: 1.6;
}

.philosophy-list code {
  background: var(--ch-color-bg-subtle);
  padding: 2px 6px;
  font-size: 0.875em;
  border: 1px solid var(--ch-color-border);
}

/* ============================================================
   THEME SWITCHER
   ============================================================ */
.theme-demo {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  margin-top: 1rem;
}

.theme-toggle-demo {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0;
}

.toggle-track {
  width: 56px;
  height: 32px;
  background: var(--ch-color-bg-subtle);
  border: 2px solid var(--ch-color-border-strong);
  display: flex;
  align-items: center;
  padding: 4px;
  transition: all 0.2s ease;
}

.toggle-track.dark {
  background: var(--ch-color-text);
  border-color: var(--ch-color-text);
}

.toggle-thumb {
  width: 24px;
  height: 24px;
  background: var(--ch-color-surface);
  border: 2px solid var(--ch-color-border-strong);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease;
  color: var(--ch-color-text);
}

.toggle-track.dark .toggle-thumb {
  transform: translateX(24px);
  background: var(--ch-color-bg);
}

.toggle-label {
  font-weight: 600;
  color: var(--ch-color-text);
}

.theme-preview-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.theme-card {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.theme-card span {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

.card-bg-preview {
  height: 48px;
  background: var(--ch-color-bg);
  border: 1px solid var(--ch-color-border-strong);
}

.card-surface-preview {
  height: 48px;
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
}

.card-primary-preview {
  height: 48px;
  background: var(--ch-color-primary);
  border: 1px solid var(--ch-color-border-strong);
}

.card-text-preview {
  height: 48px;
  background: var(--ch-color-text);
  border: 1px solid var(--ch-color-border-strong);
}

/* ============================================================
   COLOR TOKENS
   ============================================================ */
.color-category {
  margin-bottom: 2rem;
}

.category-header {
  margin-bottom: 1rem;
}

.category-header h3 {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 0.25rem 0;
}

.category-header p {
  font-size: 0.875rem;
  color: var(--ch-color-text-muted);
  margin: 0;
}

.color-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
}

.color-swatch {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--ch-color-border-strong);
}

.swatch-preview {
  height: 80px;
  border-bottom: 1px solid var(--ch-color-border-strong);
}

.swatch-info {
  padding: 0.75rem;
  background: var(--ch-color-surface);
}

.swatch-name {
  font-weight: 600;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.swatch-token {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
  display: block;
  margin-bottom: 0.25rem;
}

.swatch-usage {
  font-size: 0.75rem;
  color: var(--ch-color-text-subtle);
}

/* ============================================================
   TYPOGRAPHY
   ============================================================ */
.demo-description {
  color: var(--ch-color-text-muted);
  margin-bottom: 1rem;
}

.font-showcase {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.font-example {
  padding: 1.5rem;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
}

.font-label {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ch-color-text-subtle);
  margin-bottom: 0.25rem;
}

.font-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--ch-color-text);
  margin-bottom: 0.75rem;
  display: block;
}

.font-sample-large {
  font-family: var(--ch-font-display);
  font-size: 2rem;
  font-weight: bold;
  letter-spacing: -0.02em;
  margin-bottom: 0.5rem;
}

.font-sample {
  font-size: 1rem;
  line-height: 1.6;
  margin-bottom: 0.5rem;
}

.font-sample-meta {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

/* Font Size Scale */
.size-scale {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.size-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--ch-color-border);
}

.size-row:last-child {
  border-bottom: none;
}

.size-preview {
  width: 60px;
  text-align: center;
  font-weight: 600;
  color: var(--ch-color-primary);
  flex-shrink: 0;
}

.size-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.size-info code {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

.size-value {
  font-size: 0.75rem;
  color: var(--ch-color-text-subtle);
}

.size-usage {
  font-size: 0.75rem;
  color: var(--ch-color-text-subtle);
  margin-left: auto;
}

/* Font Weight Scale */
.weight-scale {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.weight-row {
  padding: 1rem;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
}

.weight-preview {
  font-size: 1.125rem;
  margin-bottom: 0.5rem;
}

.weight-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.weight-info code {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

.weight-info span:not(.weight-usage) {
  font-size: 0.75rem;
  color: var(--ch-color-text-subtle);
}

.weight-usage {
  font-size: 0.75rem;
  color: var(--ch-color-text-subtle);
  margin-left: auto;
}

/* Line Height Scale */
.lineheight-scale {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.lineheight-row {
  padding: 1rem;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
}

.lineheight-preview {
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.lineheight-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.lineheight-info code {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

.lineheight-info span:not(.lineheight-usage) {
  font-size: 0.75rem;
  color: var(--ch-color-text-subtle);
}

.lineheight-usage {
  font-size: 0.75rem;
  color: var(--ch-color-text-subtle);
  margin-left: auto;
}

/* ============================================================
   SPACING
   ============================================================ */
.spacing-scale {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.spacing-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.5rem 0;
}

.spacing-preview {
  height: 24px;
  min-width: 4px;
  flex-shrink: 0;
}

.spacing-indicator {
  display: block;
  height: 100%;
  background: var(--ch-color-primary);
}

.spacing-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.spacing-info code {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

.spacing-value {
  font-size: 0.75rem;
  color: var(--ch-color-text-subtle);
}

.spacing-usage {
  font-size: 0.75rem;
  color: var(--ch-color-text-subtle);
  margin-left: auto;
}

/* ============================================================
   DIVIDERS
   ============================================================ */
.subsection-title {
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: var(--ch-color-text-muted);
}

.divider-demo {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.divider-sample {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.divider-label {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

.divider {
  background: var(--ch-color-border-strong);
}

.divider-default {
  height: 1px;
}

.divider-strong {
  height: 2px;
}

.divider-dashed {
  height: 1px;
  background: repeating-linear-gradient(to right,
      var(--ch-color-border-strong) 0,
      var(--ch-color-border-strong) 8px,
      transparent 8px,
      transparent 16px);
}

.divider-vertical {
  width: 1px;
  height: 48px;
  align-self: center;
}

/* Vertical Divider Demo */
.vertical-divider-demo {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--ch-color-bg-subtle);
}

.vdivider-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--ch-color-text-muted);
}

/* Divider with Label */
.divider-with-label {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.divider-label-line {
  flex: 1;
  height: 1px;
  background: var(--ch-color-border-strong);
}

.divider-label-text {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ch-color-text-subtle);
  white-space: nowrap;
}

/* ============================================================
   SHADOWS
   ============================================================ */
.shadow-showcase {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.5rem;
}

.shadow-card {
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  padding: 1rem;
}

.shadow-preview {
  height: 80px;
  background: var(--ch-color-bg-subtle);
  margin-bottom: 1rem;
  border: 1px solid var(--ch-color-border);
}

.shadow-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.shadow-name {
  font-weight: 600;
  font-size: 0.875rem;
}

.shadow-token {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

.shadow-value {
  font-size: 0.625rem;
  color: var(--ch-color-text-subtle);
  font-family: monospace;
}

.shadow-usage {
  font-size: 0.75rem;
  color: var(--ch-color-text-subtle);
  margin-top: 0.25rem;
}

/* Interactive Shadow Demo */
.shadow-interactive {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
  margin-top: 1rem;
}

.shadow-interactive-card {
  padding: 2rem;
  background: var(--ch-color-surface);
  border: 2px solid var(--ch-color-border-strong);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  cursor: pointer;
}

.shadow-interactive-card span {
  font-weight: 600;
  display: block;
  margin-bottom: 0.5rem;
}

.shadow-interactive-card p {
  font-size: 0.875rem;
  color: var(--ch-color-text-muted);
  margin: 0;
}

.elevation-1 {
  box-shadow: var(--ch-shadow-sm);
}

.elevation-1:hover {
  transform: translateY(-2px);
  box-shadow: var(--ch-shadow);
}

.elevation-2 {
  box-shadow: var(--ch-shadow);
}

.elevation-2:hover {
  transform: translateY(-4px);
  box-shadow: var(--ch-shadow-lg);
}

.elevation-3 {
  box-shadow: var(--ch-shadow-lg);
}

.elevation-3:hover {
  transform: translateY(-8px);
  box-shadow: var(--ch-shadow-xl);
}

/* ============================================================
   BORDER RADIUS
   ============================================================ */
.radius-showcase {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.radius-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.radius-preview {
  width: 80px;
  height: 80px;
  background: var(--ch-color-primary);
  border: 2px solid var(--ch-color-border-strong);
}

.radius-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--ch-color-text);
}

.radius-card code {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

.radius-note {
  padding: 1rem;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
  font-size: 0.875rem;
  color: var(--ch-color-text-muted);
}

.radius-note code {
  background: var(--ch-color-surface);
  padding: 2px 6px;
  font-size: 0.875em;
  border: 1px solid var(--ch-color-border-strong);
}

/* ============================================================
   CSS VARS REFERENCE
   ============================================================ */
.css-vars-reference {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.vars-category {
  padding: 1rem;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
}

.vars-category h4 {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ch-color-text-subtle);
  margin: 0 0 0.75rem 0;
}

.vars-category code {
  display: block;
  font-size: 0.75rem;
  padding: 0.25rem 0;
  color: var(--ch-color-text-muted);
}
</style>
