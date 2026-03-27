<script setup lang="ts">
/**
 * InteractiveView.vue - Interactive Components Documentation
 */

import { ref } from 'vue'
import {
  ChTooltip,
  ChPopover,
  ChDropdown,
  ChButton,
  ChCard,
  ChInput,
} from '@/design-system'
import {
  Info,
  MessageSquare,
  ChevronDown,
  Settings,
  MoreVertical,
} from 'lucide-vue-next'

// Tooltip state
const tooltipPlacements = ['top', 'bottom', 'left', 'right'] as const

// Popover state
const popoverOpen = ref(false)
const modalPopoverOpen = ref(false)
const newsletterEmail = ref('')
const newsletterSubscribed = ref(false)

// Dropdown state
const dropdownOpen = ref(false)

const actionDropdownItems = [
  { value: 'edit', label: 'Edit' },
  { value: 'share', label: 'Share' },
  { value: 'download', label: 'Download' },
]

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
</script>

<template>
  <div class="doc-page">
    <header class="doc-page-header">
      <div class="doc-page-header__content">
        <h1 class="doc-page-header__title">Interactive Components</h1>
        <p class="doc-page-header__subtitle">
          Tooltips, Popovers, and Dropdowns for rich user interactions
        </p>
      </div>
    </header>

    <!-- ChTooltip Section -->
    <section class="doc-section">
      <div class="doc-section-header">
        <div class="doc-section-header__content">
          <h2 class="doc-section-header__title">
            <Info :size="20" />
            ChTooltip
          </h2>
          <p class="doc-section-header__description">
            CSS-only tooltips that display contextual information on hover or focus.
          </p>
        </div>
      </div>

      <div class="doc-section-card">
        <h3 class="doc-section-card__title">Basic Tooltip</h3>
        <div class="doc-example">
          <div class="doc-example__preview">
            <ChTooltip content="This is a helpful tooltip!">
              <ChButton variant="primary">Hover me</ChButton>
            </ChTooltip>
          </div>
          <div class="doc-example__code">
            <pre><code>&lt;ChTooltip content="This is a helpful tooltip!"&gt;
  &lt;ChButton variant="primary"&gt;Hover me&lt;/ChButton&gt;
&lt;/ChTooltip&gt;</code></pre>
          </div>
        </div>
      </div>

      <div class="doc-section-card">
        <h3 class="doc-section-card__title">Tooltip Placements</h3>
        <div class="doc-example">
          <div class="doc-example__preview">
            <div class="flex gap-3 flex-wrap">
              <ChTooltip v-for="placement in tooltipPlacements" :key="placement" :placement="placement" content="Tooltip">
                <ChButton variant="outline" size="sm">{{ placement }}</ChButton>
              </ChTooltip>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ChPopover Section -->
    <section class="doc-section">
      <div class="doc-section-header">
        <div class="doc-section-header__content">
          <h2 class="doc-section-header__title">
            <MessageSquare :size="20" />
            ChPopover
          </h2>
          <p class="doc-section-header__description">
            Floating popups for rich interactive content with click, hover, and focus triggers.
          </p>
        </div>
      </div>

      <div class="doc-section-card">
        <h3 class="doc-section-card__title">Basic Popover</h3>
        <div class="doc-example">
          <div class="doc-example__preview">
            <ChPopover v-model:open="popoverOpen" placement="bottom">
              <template #trigger>
                <ChButton variant="primary">
                  Open Popover
                  <ChevronDown :size="16" />
                </ChButton>
              </template>
              <div class="p-4" style="width: 280px;">
                <h4 class="font-semibold mb-2">Popover Content</h4>
                <p class="text-sm text-muted">Rich content goes here.</p>
                <div class="flex gap-2 mt-4">
                  <ChButton size="sm" variant="ghost" @click="popoverOpen = false">Cancel</ChButton>
                  <ChButton size="sm" variant="primary" @click="popoverOpen = false">Confirm</ChButton>
                </div>
              </div>
            </ChPopover>
          </div>
        </div>
      </div>

      <div class="doc-section-card">
        <h3 class="doc-section-card__title">Modal Popover</h3>
        <div class="doc-example">
          <div class="doc-example__preview">
            <ChPopover v-model:open="modalPopoverOpen" modal placement="bottom">
              <template #trigger>
                <ChButton variant="primary">Subscribe</ChButton>
              </template>
              <div style="width: 320px;">
                <div v-if="newsletterSubscribed" class="p-6 text-center">
                  <p class="font-medium">You're subscribed!</p>
                </div>
                <div v-else class="p-4">
                  <ChInput v-model="newsletterEmail" type="email" placeholder="Email" class="mb-3" />
                  <ChButton variant="primary" full-width @click="handleNewsletterSubmit">Subscribe</ChButton>
                </div>
              </div>
            </ChPopover>
          </div>
        </div>
      </div>
    </section>

    <!-- ChDropdown Section -->
    <section class="doc-section">
      <div class="doc-section-header">
        <div class="doc-section-header__content">
          <h2 class="doc-section-header__title">
            <ChevronDown :size="20" />
            ChDropdown
          </h2>
          <p class="doc-section-header__description">
            Dropdown menus for displaying lists of actions or options.
          </p>
        </div>
      </div>

      <div class="doc-section-card">
        <h3 class="doc-section-card__title">Basic Dropdown</h3>
        <div class="doc-example">
          <div class="doc-example__preview">
            <ChDropdown :items="actionDropdownItems">
              <template #trigger>
                <ChButton variant="outline">
                  Actions
                  <ChevronDown :size="16" />
                </ChButton>
              </template>
            </ChDropdown>
          </div>
        </div>
      </div>

      <div class="doc-section-card">
        <h3 class="doc-section-card__title">Dropdown with Search</h3>
        <div class="doc-example">
          <div class="doc-example__preview">
            <ChDropdown searchable search-placeholder="Search...">
              <template #trigger>
                <ChButton variant="outline">
                  <Settings :size="16" />
                  Settings
                  <ChevronDown :size="16" />
                </ChButton>
              </template>
            </ChDropdown>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.tooltip-placement-grid {
  display: flex;
  gap: var(--ch-space-3);
  flex-wrap: wrap;
}
</style>
