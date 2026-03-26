<script setup lang="ts">
/**
 * CoreView.vue - Design System Core Components Documentation
 * 
 * This view provides comprehensive demonstrations of all core UI components
 * in the AliveCHMS brutalist-lite design system.
 * 
 * Core Components Covered:
 * - ChButton: Interactive action triggers with all variants, sizes, and states
 * - ChCard: Versatile surface containers for grouping related content
 * - ChInput: Single-line text inputs with adornments and validation states
 * - ChAvatar: User identity display with image, initials, and status
 * - ChBadge: Status indicators and category labels
 * - ChTextarea: Multi-line text inputs for longer-form content
 * - ChDivider: Horizontal and vertical content separators
 * 
 * Each section includes:
 * - Live interactive demonstrations
 * - Multiple variants and configurations
 * - Code usage examples
 * - Real-world use case context
 * 
 * @requires lucide-vue-next for icons
 */

import { ref, computed } from 'vue'
import {
  // Icon imports for demonstrations
  Search,
  Mail,
  Lock,
  Eye,
  EyeOff,
  Check,
  Plus,
  Trash2,
  Edit3,
  Download,
  Settings,
  Bell,
  ChevronDown,
  ChevronRight,
  User,
  Calendar,
  CheckCircle2
} from 'lucide-vue-next'

// ============================================================
// REACTIVE STATE FOR INTERACTIVE DEMONSTRATIONS
// ============================================================

/** Basic text input value */
const basicInput = ref('')

/** Email input for validation demos */
const emailInput = ref('')

/** Password input with show/hide toggle */
const passwordInput = ref('MySecretPassword123')
const showPassword = ref(false)

/** Search input value */
const searchQuery = ref('')

/** Number input for prefix/suffix demos */
const amountInput = ref('99.99')

/** Validated input state */
const usernameInput = ref('john_addo')
const usernameTouched = ref(false)
const usernameError = computed(() => {
  if (!usernameTouched.value) return ''
  if (usernameInput.value.length < 3) return 'Username must be at least 3 characters'
  if (!/^[a-zA-Z0-9_]+$/.test(usernameInput.value)) return 'Only letters, numbers, and underscores allowed'
  return ''
})
const usernameValid = computed(() => usernameTouched.value && usernameInput.value.length >= 3 && !usernameError.value)

/** Textarea values */
const bioTextarea = ref('')
const notesTextarea = ref('This is a pre-filled note that demonstrates the textarea component with existing content.')
const messageTextarea = ref('')

/** Button loading states */
const isSaving = ref(false)
const isDeleting = ref(false)

/** Avatar data for group demonstration */
const avatarUsers = [
  { id: 1, name: 'Sarah Johnson', src: 'https://i.pravatar.cc/150?img=1', status: 'online' as const },
  { id: 2, name: 'Michael Chen', src: 'https://i.pravatar.cc/150?img=3', status: 'busy' as const },
  { id: 3, name: 'Grace Mensah', src: 'https://i.pravatar.cc/150?img=5', status: 'offline' as const },
  { id: 4, name: 'David Wilson', src: 'https://i.pravatar.cc/150?img=8', status: 'away' as const },
]

/** Card click tracking */
const clickedCard = ref<string | null>(null)

// ============================================================
// EVENT HANDLERS
// ============================================================

/**
 * Simulates a save operation with loading state
 */
async function handleSave() {
  isSaving.value = true
  await new Promise(resolve => setTimeout(resolve, 2000))
  isSaving.value = false
}

/**
 * Simulates a delete operation with loading state
 */
async function handleDelete() {
  isDeleting.value = true
  await new Promise(resolve => setTimeout(resolve, 2000))
  isDeleting.value = false
}

/**
 * Handles card click events
 */
function handleCardClick(cardId: string) {
  clickedCard.value = cardId
  // Clear the selection after a short delay
  setTimeout(() => {
    clickedCard.value = null
  }, 1000)
}

/**
 * Validates username on blur
 */
function handleUsernameBlur() {
  usernameTouched.value = true
}

/**
 * Clears the search input
 */
function clearSearch() {
  searchQuery.value = ''
}
</script>

<template>
  <div class="doc-page">
    <!-- ============================================================
         PAGE HEADER
         ============================================================ -->
    <header class="page-header">
      <h1 class="page-title">Core Components</h1>
      <p class="page-desc">
        Essential building blocks for the AliveCHMS interface. These components implement
        the brutalist-lite design language with sharp geometry, heavy borders, and
        offset shadows for visual hierarchy and tactile feedback.
      </p>
    </header>

    <!-- ============================================================
         SECTION 1: CHBUTTON
         The primary interactive element for all clickable actions
         ============================================================ -->
    <section class="doc-section">
      <div class="section-intro">
        <h2 class="doc-section-title">
          <span class="section-number">01</span>
          ChButton
        </h2>
        <p class="section-desc">
          The primary interactive element. Use <code>primary</code> for the single main action,
          <code>secondary</code> for alternatives, <code>danger</code> for destructive operations,
          and <code>ghost</code> for low-emphasis toolbar actions.
        </p>
      </div>

      <!-- ----------------------------------------------------------------
           BUTTON VARIANTS
           Five semantic variants communicate different action intents
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Variants</span>
          <span class="demo-tag">Semantic meaning</span>
        </div>
        <div class="demo-content">
          <div class="button-row">
            <ChButton variant="primary">Primary</ChButton>
            <ChButton variant="secondary">Secondary</ChButton>
            <ChButton variant="outline">Outline</ChButton>
            <ChButton variant="ghost">Ghost</ChButton>
            <ChButton variant="danger">Danger</ChButton>
          </div>
        </div>
        <div class="code-example">
          <code>
ChButton variant="primary"   -- Main action (Save, Submit)
ChButton variant="secondary" -- Alternative (Cancel, Back)  
ChButton variant="outline"   -- Branded secondary CTA
ChButton variant="ghost"     -- Low-emphasis toolbar actions
ChButton variant="danger"    -- Destructive (Delete, Remove)
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           BUTTON SIZES
           Three sizes for different UI contexts
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Sizes</span>
          <span class="demo-tag">sm | md | lg</span>
        </div>
        <div class="demo-content">
          <div class="button-row button-row--baseline">
            <ChButton size="sm">Small</ChButton>
            <ChButton size="md">Medium</ChButton>
            <ChButton size="lg">Large</ChButton>
          </div>
        </div>
        <div class="code-example">
          <code>
ChButton size="sm" -- Table rows, compact toolbars (28px height)
ChButton size="md" -- Default for most contexts (36px height)
ChButton size="lg" -- Hero CTAs, prominent form actions (44px height)
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           BUTTON WITH ICONS
           Leading icons, trailing icons, and icon-only modes
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">With Icons</span>
          <span class="demo-tag">#icon and #trailingIcon slots</span>
        </div>
        <div class="demo-content">
          <div class="button-row">
            <!-- Leading icon - placed before the label -->
            <ChButton variant="primary">
              <template #icon>
                <Plus :size="16" />
              </template>
              Add Member
            </ChButton>

            <!-- Trailing icon - placed after the label -->
            <ChButton variant="secondary">
              View Details
              <template #trailingIcon>
                <ChevronRight :size="16" />
              </template>
            </ChButton>

            <!-- Icon-only - square button with only an icon -->
            <ChButton variant="ghost" :iconOnly="true" title="Edit">
              <Edit3 :size="18" />
            </ChButton>

            <!-- Icon-only danger -->
            <ChButton variant="danger" :iconOnly="true" title="Delete">
              <Trash2 :size="18" />
            </ChButton>

            <!-- Icon-only settings -->
            <ChButton variant="ghost" :iconOnly="true" title="Settings">
              <Settings :size="18" />
            </ChButton>
          </div>
        </div>
        <div class="code-example">
          <code>
-- Leading icon slot
ChButton
  template(#icon)&#60;Plus /&#62;
  Add Member

-- Trailing icon slot  
ChButton
  View Details
  template(#trailingIcon)&#60;ChevronRight /&#62;

-- Icon-only (square) button
ChButton :iconOnly="true" variant="ghost"
  Edit3
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           BUTTON STATES
           Loading, disabled, and full-width demonstrations
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">States</span>
          <span class="demo-tag">loading | disabled | fullWidth</span>
        </div>
        <div class="demo-content">
          <div class="state-row">
            <ChButton disabled>Disabled Button</ChButton>
            <ChButton :loading="isSaving" @click="handleSave">
              {{ isSaving ? 'Saving...' : 'Save Changes' }}
            </ChButton>
            <ChButton variant="danger" :loading="isDeleting" @click="handleDelete">
              {{ isDeleting ? 'Deleting...' : 'Delete Record' }}
            </ChButton>
          </div>
        </div>
        <div class="demo-content" style="margin-top: 1rem;">
          <ChButton variant="primary" :fullWidth="true">
            Full Width Button
          </ChButton>
        </div>
        <div class="code-example">
          <code>
-- Disabled prevents all interactions
ChButton disabled
  Disabled Button

-- Loading shows spinner and prevents clicks
ChButton :loading="isSaving" @click="handleSave"
  Save Changes

-- Full width for mobile or full-column CTAs
ChButton variant="primary" :fullWidth="true"
  Full Width Button
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           BUTTON REAL-WORLD USE CASES
           Common application patterns
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Common Patterns</span>
          <span class="demo-tag">Real-world application</span>
        </div>
        <div class="demo-content">
          <div class="pattern-grid">
            <!-- Form action buttons -->
            <div class="pattern-card">
              <h4 class="pattern-title">Form Actions</h4>
              <p class="pattern-desc">Primary action with alternatives</p>
              <div class="pattern-demo" style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <ChButton variant="secondary">Cancel</ChButton>
                <ChButton variant="primary">
                  <template #icon>
                    <Check :size="16" />
                  </template>
                  Save
                </ChButton>
              </div>
            </div>

            <!-- Data table actions -->
            <div class="pattern-card">
              <h4 class="pattern-title">Table Actions</h4>
              <p class="pattern-desc">Compact toolbar with icon buttons</p>
              <div class="pattern-demo" style="display: flex; gap: 0.5rem;">
                <ChButton size="sm" variant="ghost" :iconOnly="true" title="Edit">
                  <Edit3 :size="16" />
                </ChButton>
                <ChButton size="sm" variant="ghost" :iconOnly="true" title="Download">
                  <Download :size="16" />
                </ChButton>
                <ChButton size="sm" variant="ghost" :iconOnly="true" title="Notify">
                  <Bell :size="16" />
                </ChButton>
                <ChButton size="sm" variant="danger" :iconOnly="true" title="Delete">
                  <Trash2 :size="16" />
                </ChButton>
              </div>
            </div>

            <!-- Hero CTA -->
            <div class="pattern-card">
              <h4 class="pattern-title">Call to Action</h4>
              <p class="pattern-desc">Large, full-width primary action</p>
              <div class="pattern-demo">
                <ChButton variant="primary" :fullWidth="true" size="lg">
                  <template #icon>
                    <Plus :size="20" />
                  </template>
                  Add New Member
                </ChButton>
              </div>
            </div>

            <!-- Icon + text action -->
            <div class="pattern-card">
              <h4 class="pattern-title">Download Action</h4>
              <p class="pattern-desc">Document or export action</p>
              <div class="pattern-demo">
                <ChButton variant="outline">
                  <template #icon>
                    <Download :size="16" />
                  </template>
                  Export Report
                </ChButton>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         SECTION 2: CHCARD
         Versatile surface containers for grouping related content
         ============================================================ -->
    <section class="doc-section">
      <div class="section-intro">
        <h2 class="doc-section-title">
          <span class="section-number">02</span>
          ChCard
        </h2>
        <p class="section-desc">
          Surface containers that group related content with optional header and footer sections.
          Cards can be hoverable, clickable, and styled with various shadow levels.
        </p>
      </div>

      <!-- ----------------------------------------------------------------
           BASIC CARD
           Standard card with header, content, and footer
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Basic Card</span>
          <span class="demo-tag">header | default | footer slots</span>
        </div>
        <div class="demo-content">
          <ChCard>
            <template #header>
              <div class="card-header-content">
                <h3 class="card-title">Upcoming Events</h3>
                <ChBadge variant="info" :dot="true">3 New</ChBadge>
              </div>
            </template>
            <div class="event-list">
              <div class="event-item">
                <Calendar :size="16" class="event-icon" />
                <div class="event-details">
                  <span class="event-name">Sunday Service</span>
                  <span class="event-date">March 30, 2026 • 9:00 AM</span>
                </div>
              </div>
              <div class="event-item">
                <Calendar :size="16" class="event-icon" />
                <div class="event-details">
                  <span class="event-name">Bible Study</span>
                  <span class="event-date">April 2, 2026 • 6:30 PM</span>
                </div>
              </div>
              <div class="event-item">
                <Calendar :size="16" class="event-icon" />
                <div class="event-details">
                  <span class="event-name">Prayer Meeting</span>
                  <span class="event-date">April 5, 2026 • 7:00 PM</span>
                </div>
              </div>
            </div>
            <template #footer>
              <ChButton variant="outline" size="sm">
                View All Events
                <template #trailingIcon>
                  <ChevronRight :size="14" />
                </template>
              </ChButton>
            </template>
          </ChCard>
        </div>
        <div class="code-example">
          <code>
ChCard
  template(#header)
    h3.card-title Upcoming Events
    ChBadge variant="info" :dot="true" 3 New
  -- Default slot: card content --
  template(#footer)
    ChButton variant="outline" size="sm"
      View All Events
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           HOVERABLE CARDS
           Cards with hover shadow lift effect
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Hoverable Cards</span>
          <span class="demo-tag">hoverable prop</span>
        </div>
        <div class="demo-content">
          <div class="card-grid card-grid--2">
            <ChCard hoverable>
              <div class="stat-display">
                <span class="stat-value">247</span>
                <span class="stat-label">Active Members</span>
              </div>
            </ChCard>
            <ChCard hoverable>
              <div class="stat-display">
                <span class="stat-value">18</span>
                <span class="stat-label">New This Month</span>
              </div>
            </ChCard>
            <ChCard hoverable>
              <div class="stat-display">
                <span class="stat-value">89%</span>
                <span class="stat-label">Attendance Rate</span>
              </div>
            </ChCard>
            <ChCard hoverable>
              <div class="stat-display">
                <span class="stat-value">12</span>
                <span class="stat-label">Small Groups</span>
              </div>
            </ChCard>
          </div>
        </div>
        <div class="code-example">
          <code>
-- Hoverable adds shadow lift on hover
ChCard hoverable
  .stat-display
    span.stat-value 247
    span.stat-label Active Members
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           CLICKABLE CARDS
           Cards that respond to click events
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Clickable Cards</span>
          <span class="demo-tag">clickable | @click</span>
        </div>
        <div class="demo-content">
          <div class="card-grid card-grid--2">
            <ChCard clickable :class="{ 'card-selected': clickedCard === 'member-1' }"
              @click="handleCardClick('member-1')">
              <div class="member-card">
                <ChAvatar src="https://i.pravatar.cc/150?img=12" name="Sarah Johnson" size="lg" status="online" />
                <div class="member-info">
                  <h4 class="member-name">Sarah Johnson</h4>
                  <p class="member-role">Women's Ministry Leader</p>
                  <ChBadge variant="success" size="sm">Active</ChBadge>
                </div>
              </div>
            </ChCard>

            <ChCard clickable :class="{ 'card-selected': clickedCard === 'member-2' }"
              @click="handleCardClick('member-2')">
              <div class="member-card">
                <ChAvatar src="https://i.pravatar.cc/150?img=15" name="Michael Chen" size="lg" status="busy" />
                <div class="member-info">
                  <h4 class="member-name">Michael Chen</h4>
                  <p class="member-role">Worship Team Lead</p>
                  <ChBadge variant="success" size="sm">Active</ChBadge>
                </div>
              </div>
            </ChCard>
          </div>
          <p v-if="clickedCard" class="click-feedback">
            <CheckCircle2 :size="16" /> Card selected: {{ clickedCard }}
          </p>
        </div>
        <div class="code-example">
          <code>
-- Clickable cards with hover and active states
ChCard clickable @click="handleCardClick('member-1')"
  .member-card
    ChAvatar src="..." name="Sarah Johnson" status="online"
    .member-info
      h4.member-name Sarah Johnson
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           CARD WITH MEDIA
           Cards featuring images or rich media content
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Cards with Media</span>
          <span class="demo-tag">Image headers and rich content</span>
        </div>
        <div class="demo-content">
          <div class="card-grid card-grid--2">
            <ChCard padding="none">
              <div class="media-card">
                <img src="https://images.unsplash.com/photo-1438232992991-995b7058bbb3?w=600&h=200&fit=crop"
                  alt="Church community" class="media-image" />
                <div class="media-content">
                  <h4 class="media-title">Community Outreach</h4>
                  <p class="media-desc">Join us this weekend as we serve our local community through various outreach
                    programs.</p>
                  <ChButton variant="primary" size="sm">Learn More</ChButton>
                </div>
              </div>
            </ChCard>

            <ChCard padding="none">
              <div class="media-card">
                <img src="https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=600&h=200&fit=crop"
                  alt="Worship service" class="media-image" />
                <div class="media-content">
                  <h4 class="media-title">Sunday Celebration</h4>
                  <p class="media-desc">Every Sunday at 9:00 AM and 11:00 AM. Experience inspiring worship and biblical
                    teaching.</p>
                  <ChButton variant="primary" size="sm">Join Us</ChButton>
                </div>
              </div>
            </ChCard>
          </div>
        </div>
        <div class="code-example">
          <code>
-- Media cards use padding="none" for edge-to-edge content
ChCard padding="none"
  .media-card
    img.media-image src="..."
    .media-content
      h4.media-title Card Title
      p.media-desc Description text
      ChButton variant="primary" size="sm"
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           SHADOW LEVELS
           Different elevation levels for card hierarchy
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Shadow Levels</span>
          <span class="demo-tag">shadow="none | sm | md | lg"</span>
        </div>
        <div class="demo-content">
          <div class="shadow-demo-row">
            <ChCard shadow="none">
              <span class="shadow-label">None</span>
            </ChCard>
            <ChCard shadow="sm">
              <span class="shadow-label">Small</span>
            </ChCard>
            <ChCard shadow="md">
              <span class="shadow-label">Medium</span>
            </ChCard>
            <ChCard shadow="lg">
              <span class="shadow-label">Large</span>
            </ChCard>
          </div>
        </div>
        <div class="code-example">
          <code>
ChCard shadow="none" -- No shadow, flat
ChCard shadow="sm"   -- Default, subtle lift (4px offset)
ChCard shadow="md"   -- Medium elevation (6px offset)
ChCard shadow="lg"   -- Maximum elevation (8px offset)
          </code>
        </div>
      </div>
    </section>

    <!-- ============================================================
         SECTION 3: CHINPUT
         Single-line text inputs with adornments and validation
         ============================================================ -->
    <section class="doc-section">
      <div class="section-intro">
        <h2 class="doc-section-title">
          <span class="section-number">03</span>
          ChInput
        </h2>
        <p class="section-desc">
          Controlled text inputs with support for leading/trailing icons, prefix/suffix text,
          error states, helper text, and three sizes. Uses the wrapper pattern for consistent
          border and focus styling across all states.
        </p>
      </div>

      <!-- ----------------------------------------------------------------
           BASIC INPUT
           Standard text input with label
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Basic Input</span>
          <span class="demo-tag">v-model | label | placeholder</span>
        </div>
        <div class="demo-content">
          <div class="input-grid input-grid--2">
            <ChInput v-model="basicInput" label="Full Name" placeholder="Enter your full name" />
            <ChInput v-model="emailInput" label="Email Address" type="email" placeholder="you@example.com" />
          </div>
        </div>
        <div class="code-example">
          <code>
ChInput
  v-model="fullName"
  label="Full Name"
  placeholder="Enter your full name"

ChInput
  v-model="email"
  label="Email Address"
  type="email"
  placeholder="you@example.com"
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           INPUT WITH LEADING ICONS
           Search, mail, and other adornments
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">With Leading Icons</span>
          <span class="demo-tag">#leading slot</span>
        </div>
        <div class="demo-content">
          <div class="input-grid input-grid--2">
            <!-- Search input with icon -->
            <div class="input-wrapper">
              <ChInput v-model="searchQuery" placeholder="Search members, events..." clearable @clear="clearSearch">
                <template #leading>
                  <Search :size="18" class="input-icon" />
                </template>
              </ChInput>
            </div>

            <!-- Email input with mail icon -->
            <ChInput v-model="emailInput" type="email" label="Email" placeholder="name@church.org">
              <template #leading>
                <Mail :size="18" class="input-icon" />
              </template>
            </ChInput>

            <!-- Password input with lock icon -->
            <ChInput v-model="passwordInput" :type="showPassword ? 'text' : 'password'" label="Password"
              placeholder="Enter password">
              <template #leading>
                <Lock :size="18" class="input-icon" />
              </template>
            </ChInput>

            <!-- Username with validation feedback -->
            <ChInput v-model="usernameInput" label="Username" placeholder="Choose a username" :error="usernameError"
              :success="usernameValid" @blur="handleUsernameBlur">
              <template #leading>
                <User :size="18" class="input-icon" />
              </template>
            </ChInput>
          </div>
        </div>
        <div class="code-example">
          <code>
-- Search with leading icon
ChInput v-model="searchQuery" placeholder="Search..."
  template(#leading)
    Search

-- Password with show/hide toggle (see trailing icons)
ChInput :type="showPassword ? 'text' : 'password'"
  template(#leading)
    Lock

-- Username with validation
ChInput v-model="username" :error="errorMsg" :success="isValid"
  template(#leading)
    User
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           INPUT WITH TRAILING ICONS
           Password toggle, clear buttons, action icons
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">With Trailing Icons</span>
          <span class="demo-tag">#trailing slot</span>
        </div>
        <div class="demo-content">
          <div class="input-grid input-grid--2">
            <!-- Password with visibility toggle -->
            <ChInput v-model="passwordInput" :type="showPassword ? 'text' : 'password'" label="Password"
              placeholder="Enter your password">
              <template #trailing>
                <button type="button" class="icon-button" @click="showPassword = !showPassword"
                  :title="showPassword ? 'Hide password' : 'Show password'">
                  <EyeOff v-if="showPassword" :size="18" />
                  <Eye v-else :size="18" />
                </button>
              </template>
            </ChInput>

            <!-- Valid input with check icon -->
            <ChInput model-value="john.addo@church.org" label="Email (verified)" readonly success="Email verified">
              <template #trailing>
                <CheckCircle2 :size="18" class="icon-success" />
              </template>
            </ChInput>

            <!-- Search with trailing action -->
            <ChInput v-model="searchQuery" placeholder="Search..." clearable @clear="clearSearch">
              <template #trailing>
                <button type="button" class="icon-button">
                  <Search :size="18" />
                </button>
              </template>
            </ChInput>

            <!-- Input with dropdown indicator -->
            <ChInput model-value="Select Department" label="Department" readonly>
              <template #trailing>
                <ChevronDown :size="18" class="input-icon" />
              </template>
            </ChInput>
          </div>
        </div>
        <div class="code-example">
          <code>
-- Password visibility toggle
ChInput :type="showPassword ? 'text' : 'password'"
  template(#trailing)
    button.icon-button @click="showPassword = !showPassword"
      Eye v-if="showPassword" :size="18"
      Eye v-else :size="18"

-- Verified state
ChInput model-value="..." success="Email verified"
  template(#trailing)
    CheckCircle2.icon-success

-- Dropdown indicator
ChInput model-value="Select Department"
  template(#trailing)
    ChevronDown
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           PREFIX AND SUFFIX
           Currency, units, and other text adornments
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Prefix & Suffix</span>
          <span class="demo-tag">prefix | suffix props</span>
        </div>
        <div class="demo-content">
          <div class="input-grid input-grid--2">
            <ChInput v-model="amountInput" label="Amount" type="number" prefix="$" placeholder="0.00" />
            <ChInput v-model="amountInput" label="Percentage" type="number" suffix="%" placeholder="0" />
            <ChInput v-model="amountInput" label="Weight" type="number" suffix="kg" placeholder="0" />
            <ChInput v-model="amountInput" label="Duration" type="number" prefix="~" suffix="min" placeholder="0" />
          </div>
        </div>
        <div class="code-example">
          <code>
ChInput v-model="amount" prefix="$"
ChInput v-model="percent" suffix="%"
ChInput v-model="weight" suffix="kg"
ChInput v-model="duration" prefix="~" suffix="min"
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           INPUT STATES
           Error, success, helper text, disabled, required
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Validation States</span>
          <span class="demo-tag">error | success | helper | disabled</span>
        </div>
        <div class="demo-content">
          <div class="input-grid input-grid--2">
            <!-- Error state -->
            <ChInput v-model="emailInput" label="Email" type="email" placeholder="Enter email"
              error="Please enter a valid email address" />

            <!-- Success state -->
            <ChInput model-value="available@church.org" label="Username" success="Username is available" />

            <!-- With helper text -->
            <ChInput v-model="basicInput" label="Phone Number" type="tel" placeholder="(555) 123-4567"
              helper="We'll only use this for important notifications" />

            <!-- Disabled state -->
            <ChInput model-value="readonly@example.com" label="Account Email" disabled
              helper="Contact admin to change your email" />

            <!-- Required indicator -->
            <ChInput v-model="basicInput" label="First Name" placeholder="Enter first name" required />

            <!-- Readonly -->
            <ChInput model-value="CH-2024-0142" label="Member ID" readonly />
          </div>
        </div>
        <div class="code-example">
          <code>
-- Error with message
ChInput v-model="email" error="Please enter a valid email"

-- Success message
ChInput model-value="..." success="Username is available"

-- Helper text
ChInput v-model="phone" helper="We'll only use this for notifications"

-- Disabled (non-editable)
ChInput model-value="..." disabled

-- Required field indicator
ChInput v-model="name" required

-- Readonly (viewable but not editable)
ChInput model-value="CH-2024-0142" readonly
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           INPUT SIZES
           Three sizes for different density contexts
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Sizes</span>
          <span class="demo-tag">sm | md | lg</span>
        </div>
        <div class="demo-content">
          <div class="size-demo-stack">
            <ChInput v-model="basicInput" label="Small Input" size="sm" placeholder="Compact size for dense UIs" />
            <ChInput v-model="basicInput" label="Medium Input" size="md" placeholder="Default size for most contexts" />
            <ChInput v-model="basicInput" label="Large Input" size="lg"
              placeholder="Prominent size for hero sections" />
          </div>
        </div>
        <div class="code-example">
          <code>
ChInput size="sm" -- Compact for tables, inline forms
ChInput size="md" -- Default for standard forms
ChInput size="lg" -- Hero sections, prominent callouts
          </code>
        </div>
      </div>
    </section>

    <!-- ============================================================
         SECTION 4: CHAVATAR
         User identity display with image, initials, and status
         ============================================================ -->
    <section class="doc-section">
      <div class="section-intro">
        <h2 class="doc-section-title">
          <span class="section-number">04</span>
          ChAvatar
        </h2>
        <p class="section-desc">
          Displays user identity visually. Shows a profile photo when available,
          falls back to colored initials, and can include a status indicator dot.
        </p>
      </div>

      <!-- ----------------------------------------------------------------
           AVATAR SIZES
           Five sizes from extra-small to extra-large
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Sizes</span>
          <span class="demo-tag">xs | sm | md | lg | xl</span>
        </div>
        <div class="demo-content">
          <div class="avatar-row">
            <div class="avatar-item">
              <ChAvatar name="XS User" size="xs" />
              <span class="avatar-label">XS (24px)</span>
            </div>
            <div class="avatar-item">
              <ChAvatar name="SM User" size="sm" />
              <span class="avatar-label">SM (32px)</span>
            </div>
            <div class="avatar-item">
              <ChAvatar name="MD User" size="md" />
              <span class="avatar-label">MD (40px)</span>
            </div>
            <div class="avatar-item">
              <ChAvatar name="LG User" size="lg" />
              <span class="avatar-label">LG (56px)</span>
            </div>
            <div class="avatar-item">
              <ChAvatar name="XL User" size="xl" />
              <span class="avatar-label">XL (80px)</span>
            </div>
          </div>
        </div>
        <div class="code-example">
          <code>
ChAvatar name="XS User" size="xs" -- 24px - Dense lists
ChAvatar name="SM User" size="sm" -- 32px - Compact
ChAvatar name="MD User" size="md" -- 40px - Default
ChAvatar name="LG User" size="lg" -- 56px - Cards
ChAvatar name="XL User" size="xl" -- 80px - Profiles
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           AVATAR WITH IMAGES
           Profile photos with fallback to initials
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">With Images</span>
          <span class="demo-tag">src | name (initials fallback)</span>
        </div>
        <div class="demo-content">
          <div class="avatar-row">
            <ChAvatar src="https://i.pravatar.cc/150?img=1" name="Sarah Johnson" size="lg" />
            <ChAvatar src="https://i.pravatar.cc/150?img=3" name="Michael Chen" size="lg" />
            <ChAvatar src="https://i.pravatar.cc/150?img=5" name="Grace Mensah" size="lg" />
            <ChAvatar src="https://i.pravatar.cc/150?img=8" name="David Wilson" size="lg" />
            <ChAvatar src="https://i.pravatar.cc/150?img=12" name="Emma Thompson" size="lg" />
          </div>
        </div>
        <div class="code-example">
          <code>
-- With image (loads photo)
ChAvatar src="/photos/sarah.jpg" name="Sarah Johnson" size="lg"

-- Without image (shows initials)
ChAvatar name="Michael Chen" size="lg"
-- Renders as colored circle with "MC" initials
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           AVATAR INITALS FALLBACK
           Generated from name when no image
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Initials Fallback</span>
          <span class="demo-tag">Name generates colored initials</span>
        </div>
        <div class="demo-content">
          <div class="avatar-row">
            <ChAvatar name="Sarah Johnson" size="lg" />
            <ChAvatar name="Michael Chen" size="lg" />
            <ChAvatar name="Grace Mensah" size="lg" />
            <ChAvatar name="David K. Wilson" size="lg" />
            <ChAvatar size="lg" />
            <!-- No name shows "?" -->
          </div>
        </div>
        <div class="code-example">
          <code>
-- Full name generates 1-2 initials
ChAvatar name="Sarah Johnson"  -- Shows "SJ"
ChAvatar name="Michael Chen"    -- Shows "MC"
ChAvatar name="David K. Wilson"  -- Shows "DW"

-- No props shows "?"
ChAvatar
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           AVATAR WITH STATUS
           Online presence indicators
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Status Indicators</span>
          <span class="demo-tag">online | offline | away | busy</span>
        </div>
        <div class="demo-content">
          <div class="avatar-row">
            <div class="avatar-item">
              <ChAvatar src="https://i.pravatar.cc/150?img=20" name="Online User" size="lg" status="online" />
              <span class="avatar-label">Online</span>
            </div>
            <div class="avatar-item">
              <ChAvatar src="https://i.pravatar.cc/150?img=21" name="Away User" size="lg" status="away" />
              <span class="avatar-label">Away</span>
            </div>
            <div class="avatar-item">
              <ChAvatar src="https://i.pravatar.cc/150?img=22" name="Busy User" size="lg" status="busy" />
              <span class="avatar-label">Busy</span>
            </div>
            <div class="avatar-item">
              <ChAvatar src="https://i.pravatar.cc/150?img=23" name="Offline User" size="lg" status="offline" />
              <span class="avatar-label">Offline</span>
            </div>
          </div>
        </div>
        <div class="code-example">
          <code>
ChAvatar src="..." status="online"  -- Green dot
ChAvatar src="..." status="away"     -- Yellow dot  
ChAvatar src="..." status="busy"     -- Red dot
ChAvatar src="..." status="offline"  -- Gray dot
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           AVATAR GROUPS
           Stacked avatars for team/group display
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Avatar Groups</span>
          <span class="demo-tag">Stacked with overflow count</span>
        </div>
        <div class="demo-content">
          <!-- Compact group with +N overflow -->
          <div class="avatar-group-row">
            <div class="avatar-group">
              <ChAvatar v-for="user in avatarUsers.slice(0, 3)" :key="user.id" :src="user.src" :name="user.name"
                size="sm" class="group-avatar" />
              <div class="avatar-overflow">+2</div>
            </div>
            <span class="group-label">Team Members</span>
          </div>

          <!-- Extended group -->
          <div class="avatar-group-row">
            <div class="avatar-group">
              <ChAvatar v-for="user in avatarUsers" :key="user.id" :src="user.src" :name="user.name" size="sm"
                class="group-avatar" />
            </div>
            <span class="group-label">Small Group Alpha</span>
          </div>

          <!-- Large group -->
          <div class="avatar-group-row">
            <div class="avatar-group avatar-group--lg">
              <ChAvatar v-for="user in avatarUsers.slice(0, 4)" :key="user.id" :src="user.src" :name="user.name"
                size="md" class="group-avatar" />
            </div>
            <span class="group-label">Worship Team</span>
          </div>
        </div>
        <div class="code-example">
          <code>
-- Stacked avatar group
.avatar-group
  ChAvatar v-for="user in users" :src="user.src" size="sm"
  .avatar-overflow +{{ '{count}' }}

-- CSS for overlap effect
.group-avatar
  margin-left: -8px
  border: 2px solid var(--ch-color-surface)
  
.avatar-overflow
  width: 32px
  background: var(--ch-color-bg-muted)
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           AVATAR USE CASES
           Common application patterns
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Common Patterns</span>
          <span class="demo-tag">Real-world application</span>
        </div>
        <div class="demo-content">
          <div class="pattern-grid">
            <!-- Member card with avatar -->
            <ChCard hoverable>
              <div class="member-preview">
                <ChAvatar src="https://i.pravatar.cc/150?img=32" name="Rachel Adams" size="lg" status="online" />
                <div class="preview-info">
                  <h4>Rachel Adams</h4>
                  <p>Women's Fellowship Leader</p>
                  <ChBadge variant="success" size="sm" :dot="true">Active</ChBadge>
                </div>
              </div>
            </ChCard>

            <!-- Notification with avatar -->
            <div class="notification-preview">
              <ChAvatar src="https://i.pravatar.cc/150?img=33" name="James Lee" size="sm" />
              <div class="notification-content">
                <p class="notification-text">
                  <strong>James Lee</strong> registered for Sunday Service
                </p>
                <span class="notification-time">2 minutes ago</span>
              </div>
            </div>

            <!-- User menu -->
            <div class="user-menu-preview">
              <ChAvatar src="https://i.pravatar.cc/150?img=34" name="Current User" size="sm" status="online" />
              <div class="user-menu-info">
                <span class="user-menu-name">Alex Morgan</span>
                <span class="user-menu-role">Administrator</span>
              </div>
              <ChevronDown :size="16" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================
         SECTION 5: CHBADGE
         Status indicators and category labels
         ============================================================ -->
    <section class="doc-section">
      <div class="section-intro">
        <h2 class="doc-section-title">
          <span class="section-number">05</span>
          ChBadge
        </h2>
        <p class="section-desc">
          Small, non-interactive labels for communicating status, category, or metadata.
          Use to answer "what is this?" or "what's the state?".
        </p>
      </div>

      <!-- ----------------------------------------------------------------
           BADGE VARIANTS
           Six semantic color variants
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Variants</span>
          <span class="demo-tag">default | primary | success | warning | danger | info</span>
        </div>
        <div class="demo-content">
          <div class="badge-row">
            <ChBadge variant="default">Default</ChBadge>
            <ChBadge variant="primary">Primary</ChBadge>
            <ChBadge variant="success">Success</ChBadge>
            <ChBadge variant="warning">Warning</ChBadge>
            <ChBadge variant="danger">Danger</ChBadge>
            <ChBadge variant="info">Info</ChBadge>
          </div>
        </div>
        <div class="code-example">
          <code>
ChBadge variant="default"  -- Neutral labels
ChBadge variant="primary"  -- Featured/Selected
ChBadge variant="success"  -- Active/Complete
ChBadge variant="warning"  -- Pending/Attention
ChBadge variant="danger"   -- Overdue/Cancelled
ChBadge variant="info"     -- Draft/Scheduled
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           BADGE SIZES
           Three sizes for different contexts
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Sizes</span>
          <span class="demo-tag">sm | md | lg</span>
        </div>
        <div class="demo-content">
          <div class="badge-row badge-row--baseline">
            <ChBadge variant="primary" size="sm">Small</ChBadge>
            <ChBadge variant="primary" size="md">Medium</ChBadge>
            <ChBadge variant="primary" size="lg">Large</ChBadge>
          </div>
        </div>
        <div class="code-example">
          <code>
ChBadge variant="primary" size="sm" -- Compact, inline
ChBadge variant="primary" size="md" -- Default
ChBadge variant="primary" size="lg" -- Prominent
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           BADGE WITH DOT
           Status indicator dots
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">With Dot</span>
          <span class="demo-tag">:dot="true"</span>
        </div>
        <div class="demo-content">
          <div class="badge-row">
            <ChBadge variant="default" :dot="true">Default</ChBadge>
            <ChBadge variant="primary" :dot="true">Primary</ChBadge>
            <ChBadge variant="success" :dot="true">Active</ChBadge>
            <ChBadge variant="warning" :dot="true">Pending</ChBadge>
            <ChBadge variant="danger" :dot="true">Overdue</ChBadge>
            <ChBadge variant="info" :dot="true">Scheduled</ChBadge>
          </div>
        </div>
        <div class="code-example">
          <code>
-- Dot adds a colored indicator before the text
ChBadge variant="success" :dot="true" Active
ChBadge variant="warning" :dot="true" Pending
ChBadge variant="danger" :dot="true" Overdue
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           BADGE SHAPES
           Pill vs rounded rectangle
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Shapes</span>
          <span class="demo-tag">pill (default) | rounded</span>
        </div>
        <div class="demo-content">
          <div class="badge-row">
            <ChBadge variant="primary" :pill="true">Pill (default)</ChBadge>
            <ChBadge variant="primary" :pill="false">Rounded</ChBadge>
          </div>
        </div>
        <div class="code-example">
          <code>
ChBadge :pill="true"  -- Fully rounded (pill shape)
ChBadge :pill="false" -- Slightly rounded rectangle
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           BADGE USE CASES
           Common application patterns
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Common Patterns</span>
          <span class="demo-tag">Real-world application</span>
        </div>
        <div class="demo-content">
          <div class="badge-patterns">
            <!-- Member status badges -->
            <div class="pattern-section">
              <h4 class="pattern-section-title">Member Status</h4>
              <div class="badge-row">
                <ChBadge variant="success" :dot="true">Active</ChBadge>
                <ChBadge variant="warning" :dot="true">Inactive</ChBadge>
                <ChBadge variant="info" :dot="true">Visitor</ChBadge>
                <ChBadge variant="default">Archived</ChBadge>
              </div>
            </div>

            <!-- Event status badges -->
            <div class="pattern-section">
              <h4 class="pattern-section-title">Event Registration</h4>
              <div class="badge-row">
                <ChBadge variant="success">Registered</ChBadge>
                <ChBadge variant="warning">Waitlisted</ChBadge>
                <ChBadge variant="danger">Cancelled</ChBadge>
                <ChBadge variant="info">Pending Payment</ChBadge>
              </div>
            </div>

            <!-- Role badges -->
            <div class="pattern-section">
              <h4 class="pattern-section-title">Roles</h4>
              <div class="badge-row">
                <ChBadge variant="primary">Leader</ChBadge>
                <ChBadge variant="info">Volunteer</ChBadge>
                <ChBadge variant="default">Member</ChBadge>
              </div>
            </div>

            <!-- Payment status badges -->
            <div class="pattern-section">
              <h4 class="pattern-section-title">Payments</h4>
              <div class="badge-row">
                <ChBadge variant="success" :dot="true">Paid</ChBadge>
                <ChBadge variant="warning" :dot="true">Pending</ChBadge>
                <ChBadge variant="danger" :dot="true">Overdue</ChBadge>
              </div>
            </div>
          </div>
        </div>
        <div class="code-example">
          <code>
-- Member status
ChBadge variant="success" :dot="true" Active
ChBadge variant="warning" :dot="true" Inactive
ChBadge variant="info" :dot="true" Visitor

-- Event registration  
ChBadge variant="success" Registered
ChBadge variant="warning" Waitlisted
ChBadge variant="danger" Cancelled

-- Payment status
ChBadge variant="success" :dot="true" Paid
ChBadge variant="warning" :dot="true" Pending
ChBadge variant="danger" :dot="true" Overdue
          </code>
        </div>
      </div>
    </section>

    <!-- ============================================================
         SECTION 6: CHTEXTAREA
         Multi-line text inputs for longer-form content
         ============================================================ -->
    <section class="doc-section">
      <div class="section-intro">
        <h2 class="doc-section-title">
          <span class="section-number">06</span>
          ChTextarea
        </h2>
        <p class="section-desc">
          Multi-line text inputs for longer-form content. Supports character counting,
          auto-resize, and all the same validation states as ChInput.
        </p>
      </div>

      <!-- ----------------------------------------------------------------
           BASIC TEXTAREA
           Standard multi-line input
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Basic Textarea</span>
          <span class="demo-tag">v-model | label | placeholder</span>
        </div>
        <div class="demo-content">
          <ChTextarea v-model="bioTextarea" label="Biography"
            placeholder="Tell us about yourself, your testimony, and how you came to faith..." :rows="4" />
        </div>
        <div class="code-example">
          <code>
ChTextarea
  v-model="bio"
  label="Biography"
  placeholder="Tell us about yourself..."
  :rows="4"
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           TEXTAREA WITH CHARACTER COUNT
           Shows current/max characters
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">With Character Count</span>
          <span class="demo-tag">showCount | maxlength</span>
        </div>
        <div class="demo-content">
          <ChTextarea v-model="messageTextarea" label="Message" placeholder="Enter your prayer request or message..."
            :rows="5" :maxlength="500" :showCount="true" helper="This message will be sent to the pastoral team" />
        </div>
        <div class="code-example">
          <code>
ChTextarea
  v-model="message"
  label="Message"
  placeholder="Enter your prayer request..."
  :rows="5"
  :maxlength="500"
  :showCount="true"
  helper="This will be sent to the pastoral team"
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           TEXTAREA STATES
           Error, success, disabled, readonly
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Validation States</span>
          <span class="demo-tag">error | success | disabled | readonly</span>
        </div>
        <div class="demo-content">
          <div class="input-grid">
            <!-- Error state -->
            <ChTextarea v-model="messageTextarea" label="Notes" placeholder="Add notes..." :rows="3"
              error="Notes cannot be empty when submitting" />

            <!-- Success state -->
            <ChTextarea v-model="notesTextarea" label="Description" :rows="3" success="Description looks good!" />

            <!-- Disabled -->
            <ChTextarea model-value="This textarea is disabled and cannot be edited." label="Archived Notes" :rows="3"
              disabled helper="Contact admin to modify archived content" />

            <!-- Readonly -->
            <ChTextarea model-value="This is read-only content that cannot be modified." label="Terms & Conditions"
              :rows="3" readonly />
          </div>
        </div>
        <div class="code-example">
          <code>
-- Error state
ChTextarea v-model="notes" error="Notes cannot be empty"

-- Success state
ChTextarea v-model="desc" success="Description looks good!"

-- Disabled
ChTextarea model-value="..." disabled

-- Readonly
ChTextarea model-value="..." readonly
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           TEXTAREA SIZES
           Three sizes for different contexts
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Sizes</span>
          <span class="demo-tag">sm | md | lg</span>
        </div>
        <div class="demo-content">
          <ChTextarea v-model="notesTextarea" label="Small Textarea" size="sm"
            placeholder="Compact height for inline forms" :rows="2" />
          <ChTextarea v-model="notesTextarea" label="Medium Textarea" size="md"
            placeholder="Default height for most forms" :rows="4" />
          <ChTextarea v-model="notesTextarea" label="Large Textarea" size="lg"
            placeholder="Extended height for detailed content" :rows="8" />
        </div>
        <div class="code-example">
          <code>
ChTextarea size="sm" -- Compact for inline forms
ChTextarea size="md" -- Default height
ChTextarea size="lg" -- Extended for detailed content
          </code>
        </div>
      </div>
    </section>

    <!-- ============================================================
         SECTION 7: CHDIVIDER
         Content separators (also documented in Foundation)
         ============================================================ -->
    <section class="doc-section">
      <div class="section-intro">
        <h2 class="doc-section-title">
          <span class="section-number">07</span>
          ChDivider
        </h2>
        <p class="section-desc">
          Horizontal and vertical separators for organizing content into visual sections.
          See the Foundation view for complete token documentation.
        </p>
      </div>

      <!-- ----------------------------------------------------------------
           DIVIDER DEMONSTRATIONS
           Various styles and contexts
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Horizontal Dividers</span>
          <span class="demo-tag">Default, with margin, inset</span>
        </div>
        <div class="demo-content">
          <div class="divider-demo">
            <p>Content above the divider</p>
            <ChDivider />
            <p>Content below the divider</p>
          </div>

          <div class="divider-demo" style="margin-top: 2rem;">
            <p>With generous vertical margin</p>
            <ChDivider />
            <p>Creating breathing room between sections</p>
          </div>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           DIVIDER WITH LABELS
           Section labels in dividers
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">With Labels</span>
          <span class="demo-tag">label prop</span>
        </div>
        <div class="demo-content">
          <div class="divider-demo">
            <p>Form Section 1</p>
            <ChDivider label="Personal Information" />
            <p>Name, email, phone fields</p>
            <ChDivider label="Ministry Details" />
            <p>Role, department, start date</p>
          </div>
        </div>
        <div class="code-example">
          <code>
-- Divider acts as a section separator in forms
ChDivider label="Personal Information"
ChDivider label="Ministry Details"
          </code>
        </div>
      </div>

      <!-- ----------------------------------------------------------------
           DIVIDER VARIANTS
           Different visual styles
           ---------------------------------------------------------------- -->
      <div class="demo-block">
        <div class="demo-header">
          <span class="demo-title">Variants</span>
          <span class="demo-tag">solid | dashed | dotted</span>
        </div>
        <div class="demo-content">
          <div class="divider-demo">
            <p>Solid divider (default)</p>
            <ChDivider variant="solid" />
            <p>Clean edge for clear separation</p>
          </div>

          <div class="divider-demo" style="margin-top: 1.5rem;">
            <p>Dashed divider</p>
            <ChDivider variant="dashed" />
            <p>Optional or secondary separation</p>
          </div>

          <div class="divider-demo" style="margin-top: 1.5rem;">
            <p>Dotted divider</p>
            <ChDivider variant="dotted" />
            <p>Decorative use sparingly</p>
          </div>
        </div>
        <div class="code-example">
          <code>
ChDivider variant="solid"   -- Clean edge, default
ChDivider variant="dashed"  -- For optional sections
ChDivider variant="dotted" -- Decorative, use sparingly
          </code>
        </div>
      </div>
    </section>

  </div>
</template>

<style scoped>
/* ============================================================
   PAGE LAYOUT & TYPOGRAPHY
   ============================================================ */

.doc-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.page-header {
  margin-bottom: 3rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid var(--ch-color-border);
}

.page-title {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-4xl);
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-text);
  margin: 0 0 1rem 0;
}

.page-desc {
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
}

.section-desc {
  font-size: var(--ch-text-base);
  color: var(--ch-color-text-muted);
  max-width: 80ch;
  line-height: 1.6;
  margin: 0;
}

/* ============================================================
   DEMO BLOCK LAYOUT
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
  padding: 1rem 1.5rem;
  background: var(--ch-color-bg-subtle);
  border-bottom: 1px solid var(--ch-color-border);
}

.demo-title {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

.demo-tag {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
  font-family: var(--ch-font-mono);
}

.demo-content {
  padding: 1.5rem;
}

.code-example {
  padding: 1rem 1.5rem;
  background: var(--ch-color-bg);
  border-top: 1px solid var(--ch-color-border);
}

.code-example code {
  font-family: var(--ch-font-mono);
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  white-space: pre;
  line-height: 1.6;
}

/* ============================================================
   BUTTON DEMONSTRATIONS
   ============================================================ */

.button-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
}

.button-row--baseline {
  align-items: baseline;
}

.state-row {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
}

.pattern-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.pattern-card {
  padding: 1.25rem;
  background: var(--ch-color-bg);
  border: 1px solid var(--ch-color-border);
  border-radius: var(--ch-radius-sm);
}

.pattern-title {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  margin: 0 0 0.5rem 0;
}

.pattern-desc {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  margin: 0 0 1rem 0;
}

.pattern-demo {
  display: flex;
  align-items: center;
}

/* ============================================================
   CARD DEMONSTRATIONS
   ============================================================ */

.card-grid {
  display: grid;
  gap: 1rem;
}

.card-grid--2 {
  grid-template-columns: repeat(2, 1fr);
}

.card-header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

.card-title {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-lg);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  margin: 0;
}

.event-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.event-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: var(--ch-color-bg);
  border-radius: var(--ch-radius-sm);
}

.event-icon {
  color: var(--ch-color-text-muted);
  flex-shrink: 0;
}

.event-details {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.event-name {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
}

.event-date {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

.card-selected {
  border-color: var(--ch-color-primary) !important;
  box-shadow: var(--ch-shadow-md);
}

.stat-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  text-align: center;
}

.stat-value {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-3xl);
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-text);
}

.stat-label {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin-top: 0.25rem;
}

.member-card {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.member-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.member-name {
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  margin: 0;
}

.member-role {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin: 0 0 0.5rem 0;
}

.click-feedback {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  background: var(--ch-color-success-subtle);
  border-radius: var(--ch-radius-sm);
  font-size: var(--ch-text-sm);
  color: var(--ch-color-success);
}

/* Media Card */
.media-card {
  display: flex;
  flex-direction: column;
}

.media-image {
  width: 100%;
  height: 160px;
  object-fit: cover;
}

.media-content {
  padding: 1.25rem;
}

.media-title {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-lg);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  margin: 0 0 0.5rem 0;
}

.media-desc {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin: 0 0 1rem 0;
  line-height: 1.5;
}

/* Shadow Demo */
.shadow-demo-row {
  display: flex;
  gap: 1rem;
}

.shadow-label {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
}

/* ============================================================
   INPUT DEMONSTRATIONS
   ============================================================ */

.input-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

.input-wrapper {
  grid-column: span 2;
}

.input-grid--2 {
  grid-column: span 2;
}

.input-icon {
  color: var(--ch-color-text-muted);
}

.icon-button {
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: none;
  padding: 0.25rem;
  cursor: pointer;
  color: var(--ch-color-text-muted);
  border-radius: var(--ch-radius-sm);
  transition: color var(--ch-duration-fast);
}

.icon-button:hover {
  color: var(--ch-color-text);
}

.icon-success {
  color: var(--ch-color-success);
}

.size-demo-stack {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* ============================================================
   AVATAR DEMONSTRATIONS
   ============================================================ */

.avatar-row {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  align-items: center;
}

.avatar-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.avatar-label {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

/* Avatar Group */
.avatar-group-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.avatar-group {
  display: flex;
  align-items: center;
}

.avatar-group :deep(.group-avatar) {
  margin-left: -8px;
  border: 2px solid var(--ch-color-surface);
}

.avatar-group :deep(.group-avatar:first-child) {
  margin-left: 0;
}

.avatar-group--lg :deep(.group-avatar) {
  margin-left: -12px;
}

.avatar-overflow {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  margin-left: -8px;
  background: var(--ch-color-bg-muted);
  border: 2px solid var(--ch-color-surface);
  border-radius: 50%;
  font-size: var(--ch-text-xs);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text-muted);
}

.group-label {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
}

/* Avatar patterns */
.member-preview {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.preview-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.preview-info h4 {
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  margin: 0;
}

.preview-info p {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin: 0;
}

.notification-preview {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem;
  background: var(--ch-color-bg);
  border-radius: var(--ch-radius-sm);
}

.notification-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.notification-text {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text);
  margin: 0;
}

.notification-text strong {
  font-weight: var(--ch-font-medium);
}

.notification-time {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
}

.user-menu-preview {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: var(--ch-color-bg);
  border-radius: var(--ch-radius-sm);
  cursor: pointer;
}

.user-menu-info {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.user-menu-name {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
}

.user-menu-role {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

/* ============================================================
   BADGE DEMONSTRATIONS
   ============================================================ */

.badge-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
}

.badge-row--baseline {
  align-items: baseline;
}

.badge-patterns {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.pattern-section-title {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text-muted);
  margin: 0 0 0.5rem 0;
}

/* ============================================================
   DIVIDER DEMONSTRATIONS
   ============================================================ */

.divider-demo {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.divider-demo p {
  margin: 0;
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
}

/* ============================================================
   RESPONSIVE ADJUSTMENTS
   ============================================================ */

@media (max-width: 768px) {
  .doc-page {
    padding: 1rem;
  }

  .page-title {
    font-size: var(--ch-text-3xl);
  }

  .input-grid {
    grid-template-columns: 1fr;
  }

  .input-wrapper,
  .input-grid--2 {
    grid-column: span 1;
  }

  .card-grid--2 {
    grid-template-columns: 1fr;
  }

  .pattern-grid {
    grid-template-columns: 1fr;
  }

  .shadow-demo-row {
    flex-wrap: wrap;
  }

  .avatar-group-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
