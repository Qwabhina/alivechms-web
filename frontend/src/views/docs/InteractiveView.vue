<script setup lang="ts">
/**
 * InteractiveView.vue - Interactive Components Documentation
 *
 * Comprehensive documentation for interactive overlay components:
 * - ChTooltip: CSS-only tooltips with 4 placements
 * - ChPopover: Floating popups with click/hover/focus triggers
 * - ChDropdown: Dropdown menus with search functionality
 *
 * Each section includes live demos, multiple variants, and code examples.
 */

import { ref } from 'vue'
import {
  ChTooltip,
  ChPopover,
  ChDropdown,
  ChButton,
  ChCard,
  ChInput,
  ChBadge,
} from '@/design-system'
import {
  Info,
  MessageSquare,
  ChevronDown,
  Settings,
  MoreVertical,
  Search,
  Edit3,
  Share2,
  Download,
  Star,
  Heart,
  Bookmark,
} from 'lucide-vue-next'

// ============================================================
// ChTooltip STATE
// ============================================================

/** Tooltip placement options */
const tooltipPlacements = ['top', 'bottom', 'left', 'right'] as const

// ============================================================
// ChPopover STATE
// ============================================================

/** Popover open states */
const popoverOpen = ref(false)
const modalPopoverOpen = ref(false)
const newsletterEmail = ref('')
const newsletterSubscribed = ref(false)

/**
 * Simulates newsletter subscription
 */
function handleNewsletterSubmit() {
  if (newsletterEmail.value) {
    newsletterSubscribed.value = true
    setTimeout(() => {
      newsletterSubscribed.value = false
      newsletterEmail.value = ''
      modalPopoverOpen.value = false
    }, 2000)
  }
}

// ============================================================
// ChDropdown STATE
// ============================================================

/** Dropdown items */
const actionDropdownItems = [
  { value: 'edit', label: 'Edit', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
  { value: 'share', label: 'Share', icon: 'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z' },
  { value: 'download', label: 'Download', icon: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4' },
]

const iconDropdownItems = [
  { value: 'star', label: 'Favorite', icon: 'star' },
  { value: 'heart', label: 'Like', icon: 'heart' },
  { value: 'bookmark', label: 'Save', icon: 'bookmark' },
]

const searchDropdownItems = [
  { value: 'users', label: 'Users', description: 'Manage system users' },
  { value: 'roles', label: 'Roles', description: 'Configure access' },
  { value: 'audit', label: 'Audit Logs', description: 'View activities' },
  { value: 'settings', label: 'Settings', description: 'Configuration' },
]

/** Selected action */
const selectedAction = ref('')

/**
 * Handle dropdown item selection
 */
function handleDropdownSelect(item: any) {
  selectedAction.value = item.value
  console.log('Selected:', item.label)
}
</script>

<template>
  <div class="doc-page">
    <!-- ============================================================
         PAGE HEADER
         ============================================================ -->
    <header class="doc-page-header">
      <div class="doc-page-header__content">
        <h1 class="doc-page-header__title">Interactive Components</h1>
        <p class="doc-page-header__subtitle">
          Tooltips, Popovers, and Dropdowns for rich user interactions
        </p>
      </div>
    </header>

    <!-- ============================================================
         ChTooltip SECTION
         ============================================================ -->
    <section class="doc-section">
      <div class="section-intro">
        <h2 class="doc-section-title">
          <span class="section-number">01</span>
          <Info :size="20" />
          ChTooltip
        </h2>
        <p class="section-desc">
          CSS-only tooltips that display contextual information on hover or focus.
          Lightweight with zero JavaScript animation overhead.
        </p>
      </div>

      <!-- ----------------------------------------------------------------
           BASIC TOOLTIP
           Simple tooltip with text content
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Basic Tooltip</span>
          <span class="demo-tag">content | hover | focus</span>
        </div>
        <div class="demo-content">
          <ChTooltip content="This is a helpful tooltip!">
            <ChButton variant="primary">Hover me</ChButton>
          </ChTooltip>
        </div>
        <div class="code-example">
          <code>
&lt;ChTooltip content="This is a helpful tooltip!"&gt;
  &lt;ChButton variant="primary"&gt;Hover me&lt;/ChButton&gt;
&lt;/ChTooltip&gt;
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           TOOLTIP PLACEMENTS
           Four positioning options
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Tooltip Placements</span>
          <span class="demo-tag">top | bottom | left | right</span>
        </div>
        <div class="demo-content">
          <div class="tooltip-placement-grid">
            <ChTooltip v-for="placement in tooltipPlacements" :key="placement" :placement="placement"
              content="Tooltip content">
              <ChButton variant="outline" size="sm">{{ placement }}</ChButton>
            </ChTooltip>
          </div>
        </div>
        <div class="code-example">
          <code>
&lt;ChTooltip placement="top" content="..."&gt;
  &lt;ChButton&gt;top&lt;/ChButton&gt;
&lt;/ChTooltip&gt;

&lt;ChTooltip placement="bottom" content="..."&gt;
  &lt;ChButton&gt;bottom&lt;/ChButton&gt;
&lt;/ChTooltip&gt;

&lt;ChTooltip placement="left" content="..."&gt;
  &lt;ChButton&gt;left&lt;/ChButton&gt;
&lt;/ChTooltip&gt;

&lt;ChTooltip placement="right" content="..."&gt;
  &lt;ChButton&gt;right&lt;/ChButton&gt;
&lt;/ChTooltip&gt;
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           TOOLTIP WITH TITLE
           Add context with a title
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Tooltip with Title</span>
          <span class="demo-tag">title | content</span>
        </div>
        <div class="demo-content">
          <ChTooltip title="Keyboard Shortcut" content="Press Ctrl+K to open command palette">
            <ChButton variant="ghost" :icon-only="true">
              <Info :size="18" />
            </ChButton>
          </ChTooltip>
        </div>
        <div class="code-example">
          <code>
&lt;ChTooltip title="Keyboard Shortcut" content="Press Ctrl+K..."&gt;
  &lt;ChButton :icon-only="true"&gt;
    &lt;Info :size="18" /&gt;
  &lt;/ChButton&gt;
&lt;/ChTooltip&gt;
          </code>
        </div>
      </div>
    </section>

    <!-- ============================================================
         ChPopover SECTION
         ============================================================ -->
    <section class="doc-section">
      <div class="section-intro">
        <h2 class="doc-section-title">
          <span class="section-number">02</span>
          <MessageSquare :size="20" />
          ChPopover
        </h2>
        <p class="section-desc">
          Floating popups for rich interactive content. Supports click, hover, and focus
          triggers with optional modal backdrop.
        </p>
      </div>

      <!-- ----------------------------------------------------------------
           BASIC POPOVER
           Click-triggered popover
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Basic Popover</span>
          <span class="demo-tag">v-model:open | click trigger</span>
        </div>
        <div class="demo-content">
          <ChPopover v-model:open="popoverOpen" placement="bottom">
            <template #trigger>
              <ChButton variant="primary">
                Open Popover
                <ChevronDown :size="16" />
              </ChButton>
            </template>
            <div class="popover-content">
              <h4 class="popover-title">Popover Content</h4>
              <p class="popover-desc">
                This is a popover with rich content. You can put anything here -
                forms, lists, images, or custom components.
              </p>
              <div class="popover-actions">
                <ChButton size="sm" variant="ghost" @click="popoverOpen = false">
                  Cancel
                </ChButton>
                <ChButton size="sm" variant="primary" @click="popoverOpen = false">
                  Confirm
                </ChButton>
              </div>
            </div>
          </ChPopover>
        </div>
        <div class="code-example">
          <code>
&lt;ChPopover v-model:open="popoverOpen"&gt;
  &lt;template #trigger&gt;
    &lt;ChButton&gt;Open Popover&lt;/ChButton&gt;
  &lt;/template&gt;
  &lt;div class="p-4"&gt;
    &lt;h4&gt;Popover Content&lt;/h4&gt;
    &lt;p&gt;Rich content here...&lt;/p&gt;
  &lt;/div&gt;
&lt;/ChPopover&gt;
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           TRIGGER TYPES
           Click, hover, and focus triggers
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Trigger Types</span>
          <span class="demo-tag">click | hover | focus</span>
        </div>
        <div class="demo-content">
          <div class="trigger-types-row">
            <ChPopover trigger="click" placement="bottom">
              <template #trigger>
                <ChButton variant="outline">Click Trigger</ChButton>
              </template>
              <div class="popover-content-small">
                Click triggered content
              </div>
            </ChPopover>

            <ChPopover trigger="hover" placement="bottom">
              <template #trigger>
                <ChButton variant="outline">Hover Trigger</ChButton>
              </template>
              <div class="popover-content-small">
                Hover triggered content
              </div>
            </ChPopover>

            <ChPopover trigger="focus" placement="bottom">
              <template #trigger>
                <ChButton variant="outline">Focus Trigger</ChButton>
              </template>
              <div class="popover-content-small">
                Focus triggered content
              </div>
            </ChPopover>
          </div>
        </div>
        <div class="code-example">
          <code>
&lt;!-- Click trigger --&gt;
&lt;ChPopover trigger="click"&gt;
  &lt;ChButton&gt;Click Trigger&lt;/ChButton&gt;
&lt;/ChPopover&gt;

&lt;!-- Hover trigger --&gt;
&lt;ChPopover trigger="hover"&gt;
  &lt;ChButton&gt;Hover Trigger&lt;/ChButton&gt;
&lt;/ChPopover&gt;

&lt;!-- Focus trigger --&gt;
&lt;ChPopover trigger="focus"&gt;
  &lt;ChButton&gt;Focus Trigger&lt;/ChButton&gt;
&lt;/ChPopover&gt;
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           MODAL POPOVER
           With backdrop and form
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Modal Popover</span>
          <span class="demo-tag">modal | backdrop | form</span>
        </div>
        <div class="demo-content">
          <ChPopover v-model:open="modalPopoverOpen" modal placement="bottom">
            <template #trigger>
              <ChButton variant="primary">
                Subscribe
              </ChButton>
            </template>
            <div class="modal-popover-content">
              <div v-if="newsletterSubscribed" class="modal-popover-success">
                <Star :size="32" class="success-icon" />
                <p class="success-message">You're subscribed!</p>
              </div>
              <div v-else>
                <h4 class="modal-popover-title">Stay Updated</h4>
                <p class="modal-popover-desc">
                  Get the latest updates, news, and special offers delivered to your inbox.
                </p>
                <ChInput v-model="newsletterEmail" type="email" placeholder="Enter your email"
                  class="modal-popover-input" />
                <ChButton variant="primary" full-width :loading="newsletterSubscribed" @click="handleNewsletterSubmit">
                  Subscribe
                </ChButton>
              </div>
            </div>
          </ChPopover>
        </div>
        <div class="code-example">
          <code>
&lt;ChPopover modal v-model:open="open"&gt;
  &lt;template #trigger&gt;
    &lt;ChButton&gt;Subscribe&lt;/ChButton&gt;
  &lt;/template&gt;
  &lt;div&gt;
    &lt;h4&gt;Stay Updated&lt;/h4&gt;
    &lt;ChInput v-model="email" type="email" /&gt;
    &lt;ChButton&gt;Subscribe&lt;/ChButton&gt;
  &lt;/div&gt;
&lt;/ChPopover&gt;
          </code>
        </div>
      </div>
    </section>

    <!-- ============================================================
         ChDropdown SECTION
         ============================================================ -->
    <section class="doc-section">
      <div class="section-intro">
        <h2 class="doc-section-title">
          <span class="section-number">03</span>
          <ChevronDown :size="20" />
          ChDropdown
        </h2>
        <p class="section-desc">
          Dropdown menus for displaying lists of actions or options.
          Supports icons, dividers, search, and keyboard navigation.
        </p>
      </div>

      <!-- ----------------------------------------------------------------
           BASIC DROPDOWN
           Simple dropdown with items
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Basic Dropdown</span>
          <span class="demo-tag">items | @select</span>
        </div>
        <div class="demo-content">
          <ChDropdown :items="actionDropdownItems" @select="handleDropdownSelect">
            <template #trigger>
              <ChButton variant="outline">
                Actions
                <ChevronDown :size="16" />
              </ChButton>
            </template>
          </ChDropdown>
          <ChBadge v-if="selectedAction" variant="success" class="mt-3">
            Selected: {{ selectedAction }}
          </ChBadge>
        </div>
        <div class="code-example">
          <code>
&lt;ChDropdown
  :items="[
    { value: 'edit', label: 'Edit', icon: editIcon },
    { value: 'delete', label: 'Delete', variant: 'danger' },
  ]"
  @select="handleSelect"
&gt;
  &lt;template #trigger&gt;
    &lt;ChButton&gt;Actions&lt;/ChButton&gt;
  &lt;/template&gt;
&lt;/ChDropdown&gt;
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           DROPDOWN WITH SEARCH
           Filterable dropdown for long lists
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Dropdown with Search</span>
          <span class="demo-tag">searchable | filter</span>
        </div>
        <div class="demo-content">
          <ChDropdown :items="searchDropdownItems" searchable search-placeholder="Search settings..."
            @select="handleDropdownSelect">
            <template #trigger>
              <ChButton variant="outline">
                <Settings :size="16" />
                Settings
                <ChevronDown :size="16" />
              </ChButton>
            </template>
          </ChDropdown>
        </div>
        <div class="code-example">
          <code>
&lt;ChDropdown
  :items="items"
  searchable
  search-placeholder="Search..."
  @select="handleSelect"
&gt;
  &lt;template #trigger&gt;
    &lt;ChButton&gt;Search&lt;/ChButton&gt;
  &lt;/template&gt;
&lt;/ChDropdown&gt;
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           ICON DROPDOWN
           Custom content with ChDropdownItem
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Icon Dropdown</span>
          <span class="demo-tag">custom content | icons</span>
        </div>
        <div class="demo-content">
          <ChDropdown @select="handleDropdownSelect">
            <template #trigger>
              <ChButton :icon-only="true" variant="ghost">
                <MoreVertical :size="18" />
              </ChButton>
            </template>
            <div class="dropdown-custom-content">
              <div class="dropdown-item" @click="handleDropdownSelect({ value: 'star', label: 'Favorite' })">
                <Star :size="16" />
                <span>Favorite</span>
              </div>
              <div class="dropdown-item" @click="handleDropdownSelect({ value: 'heart', label: 'Like' })">
                <Heart :size="16" />
                <span>Like</span>
              </div>
              <div class="dropdown-item" @click="handleDropdownSelect({ value: 'bookmark', label: 'Save' })">
                <Bookmark :size="16" />
                <span>Save</span>
              </div>
            </div>
          </ChDropdown>
        </div>
        <div class="code-example">
          <code>
&lt;ChDropdown&gt;
  &lt;template #trigger&gt;
    &lt;ChButton :icon-only="true"&gt;
      &lt;MoreVertical /&gt;
    &lt;/ChButton&gt;
  &lt;/template&gt;
  &lt;div class="dropdown-custom"&gt;
    &lt;div class="dropdown-item"&gt;Favorite&lt;/div&gt;
    &lt;div class="dropdown-item"&gt;Like&lt;/div&gt;
    &lt;div class="dropdown-item"&gt;Save&lt;/div&gt;
  &lt;/div&gt;
&lt;/ChDropdown&gt;
          </code>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* ============================================================
   PAGE LAYOUT
   ============================================================ */

.doc-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.doc-page-header {
  margin-bottom: 3rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid var(--ch-color-border);
}

.doc-page-header__title {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-4xl);
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-text);
  margin: 0 0 1rem 0;
}

.doc-page-header__subtitle {
  font-size: var(--ch-text-lg);
  color: var(--ch-color-text-muted);
  max-width: 70ch;
  line-height: 1.6;
  margin: 0;
}

/* ============================================================
   SECTION LAYOUT
   ============================================================ */

.doc-section {
  margin-bottom: 4rem;
}

.section-intro {
  margin-bottom: 2rem;
}

.section-number {
  display: inline-block;
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-primary);
  background: var(--ch-color-primary-subtle);
  padding: 0.25rem 0.5rem;
  margin-right: 0.75rem;
  border-radius: var(--ch-radius-sm);
}

.doc-section-title {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-2xl);
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-text);
  margin: 0 0 0.75rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-desc {
  font-size: var(--ch-text-base);
  color: var(--ch-color-text-muted);
  max-width: 80ch;
  line-height: 1.6;
  margin: 0;
}

/* ============================================================
   DEMO BLOCK
   ============================================================ */

.demo-block {
  margin-bottom: 2.5rem;
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border);
  border-radius: var(--ch-radius-md);
  overflow: hidden;
}

.demo-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: var(--ch-color-bg-subtle);
  border-bottom: 1px solid var(--ch-color-border);
}

.demo-title {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

.demo-tag {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  background: var(--ch-color-bg-muted);
  padding: 0.25rem 0.5rem;
  border-radius: var(--ch-radius-sm);
}

.demo-content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: flex-start;
}

.code-example {
  padding: 1rem 1.25rem;
  background: var(--ch-color-bg);
  border-top: 1px solid var(--ch-color-border);
  overflow-x: auto;
}

.code-example code {
  font-family: var(--ch-font-mono);
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  white-space: pre;
  line-height: 1.6;
}

/* ============================================================
   TOOLTIP DEMONSTRATIONS
   ============================================================ */
.tooltip-placement-grid {
  display: flex;
  gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
    padding: 1rem;
  }
  
  /* ============================================================
     POPOVER CONTENT
     ============================================================ */
  
  .popover-content {
    padding: 1.25rem;
    width: 320px;
  }
  
  .popover-title {
    font-family: var(--ch-font-display);
    font-size: var(--ch-text-base);
    font-weight: var(--ch-font-semibold);
    color: var(--ch-color-text);
    margin: 0 0 0.5rem 0;
  }
  
  .popover-desc {
    font-size: var(--ch-text-sm);
    color: var(--ch-color-text-muted);
    line-height: 1.5;
    margin: 0 0 1rem 0;
  }
  
  .popover-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
  }
  
  .popover-content-small {
    padding: 1rem;
    font-size: var(--ch-text-sm);
    color: var(--ch-color-text);
  }
  
  /* Modal Popover */
  .modal-popover-content {
    padding: 1.25rem;
    width: 340px;
  }
  
  .modal-popover-title {
    font-family: var(--ch-font-display);
    font-size: var(--ch-text-base);
    font-weight: var(--ch-font-semibold);
    color: var(--ch-color-text);
    margin: 0 0 0.5rem 0;
  }
  
  .modal-popover-desc {
    font-size: var(--ch-text-sm);
    color: var(--ch-color-text-muted);
    line-height: 1.5;
    margin: 0 0 1rem 0;
  }
  
  .modal-popover-input {
    margin-bottom: 1rem;
  }
  
  .modal-popover-success {
    padding: 1.5rem;
    text-align: center;
  }
  
  .success-icon {
    color: var(--ch-color-success);
    margin-bottom: 0.75rem;
  }
  
  .success-message {
    font-size: var(--ch-text-base);
    font-weight: var(--ch-font-medium);
    color: var(--ch-color-success);
    margin: 0;
  }
  
  /* Trigger Types */
  .trigger-types-row {
    display: flex;
    gap: 1rem;
  flex-wrap: wrap;
  justify-content: center;
    padding: 1rem;
  }
  
  /* ============================================================
     DROPDOWN CONTENT
     ============================================================ */
  
  .dropdown-custom-content {
    display: flex;
    flex-direction: column;
    min-width: 180px;
  }
  
  .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: background-color var(--ch-duration-fast);
    font-size: var(--ch-text-sm);
    color: var(--ch-color-text);
  }
  
  .dropdown-item:hover {
    background-color: var(--ch-color-bg-muted);
  }
  
  .dropdown-item svg {
    color: var(--ch-color-text-muted);
    flex-shrink: 0;
  }
  
  /* ============================================================
     UTILITY CLASSES
     ============================================================ */
  
  .mt-3 {
    margin-top: 0.75rem;
  }
  
  /* ============================================================
     RESPONSIVE
     ============================================================ */
  
  @media (max-width: 768px) {
    .doc-page {
      padding: 1rem;
    }
  
    .doc-page-header__title {
      font-size: var(--ch-text-3xl);
    }
  
    .demo-header {
      flex-direction: column;
      gap: 0.5rem;
      align-items: flex-start;
    }
  
    .popover-content,
    .modal-popover-content {
      width: 280px;
    }
  
    .trigger-types-row {
      flex-direction: column;
      align-items: stretch;
    }
}
</style>
