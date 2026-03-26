<script setup lang="ts">
/**
 * FeedbackView.vue
 * 
 * Comprehensive documentation for feedback components including:
 * - ChProgress: Progress bars for task completion
 * - ChSpinner: Loading spinners for async operations
 * - ChSkeleton: Content placeholders during loading
 * - ChAlert: Persistent inline alerts
 * - ChToast: Ephemeral toast notifications
 * - usePullToRefresh: Pull-to-refresh gesture
 * 
 * @module Views/Docs/FeedbackView
 */

import { ref, computed } from 'vue'
import {
  ChProgress,
  ChSpinner,
  ChSkeleton,
  ChAlert,
  ChButton,
  ChBadge,
  useToast
} from '@/design-system'
import {
  CheckCircleIcon,
  XCircleIcon,
  AlertTriangleIcon,
  InfoIcon,
  RefreshCwIcon,
  PlusIcon,
  DollarSignIcon,
  UsersIcon,
  CalendarIcon,
  HeartIcon
} from 'lucide-vue-next'

// =============================================================================
// SECTION 1: TOAST NOTIFICATIONS (useToast)
// =============================================================================

/**
 * Toast composable for triggering notifications
 */
const toast = useToast()

/**
 * Progress value for interactive demos
 */
const progressValue = ref(45)

/**
 * Indeterminate progress toggle
 */
const showIndeterminate = ref(false)

/**
 * Skeleton loading state toggle
 */
const isLoading = ref(true)

/**
 * Alert dismissed state
 */
const alertDismissed = ref(false)

// Toast functions - Basic toasts
const showSuccessToast = () => {
  toast.success('Member added successfully! Sarah Johnson has been added to the directory.')
}

const showErrorToast = () => {
  toast.error('Unable to save changes. Please try again or contact support.')
}

const showWarningToast = () => {
  toast.warning('Your subscription will expire in 3 days. Please renew to avoid interruption.')
}

const showInfoToast = () => {
  toast.info('A new version of the app is available. Update now for new features.')
}

// Toast functions - Position variations (position is set by ChToastContainer placement)
// The following shows that all toasts appear in the configured position
const showTopLeftToast = () => {
  toast.info('Top-left toast demo - position set by container')
}

const showTopCenterToast = () => {
  toast.info('Top-center toast demo - position set by container')
}

const showTopRightToast = () => {
  toast.info('Top-right toast demo - position set by container')
}

const showBottomLeftToast = () => {
  toast.info('Bottom-left toast demo - position set by container')
}

const showBottomCenterToast = () => {
  toast.info('Bottom-center toast demo - position set by container')
}

const showBottomRightToast = () => {
  toast.info('Bottom-right toast demo - position set by container')
}

// Toast functions - With actions
const showToastWithAction = () => {
  toast.success('Contribution recorded!', {
    action: {
      label: 'View Receipt',
      onClick: () => console.log('View receipt clicked')
    }
  })
}

const showToastWithUndo = () => {
  toast.info('Member deleted', {
    action: {
      label: 'Undo',
      onClick: () => console.log('Undo clicked')
    },
    duration: 10000
  })
}

// Toast functions - Persistent toasts
const showPersistentToast = () => {
  toast.warning('This is a persistent notification that will not auto-dismiss.', {
    duration: 0
  })
}

// Toast functions - Church context
const showMemberAddedToast = () => {
  toast.success('New member registered: Michael Thompson has been added to the church directory.')
}

const showContributionRecordedToast = () => {
  toast.success('Contribution recorded: $500.00 (Tithes) from Sarah Johnson')
}

const showEventCreatedToast = () => {
  toast.success('Event created: "Sunday Service" scheduled for March 24, 2024')
}

const showErrorContextToast = () => {
  toast.error('Database connection failed. Unable to fetch member records.')
}

// Toast functions - Custom duration
const showQuickToast = () => {
  toast.info('Quick notification (2 seconds)', { duration: 2000 })
}

const showSlowToast = () => {
  toast.warning('Slow notification (10 seconds)', { duration: 10000 })
}

// =============================================================================
// SECTION 2: PROGRESS BAR DEMOS
// =============================================================================

/**
 * Fundraising goal progress
 */
const fundraisingProgress = ref(68)
const fundraisingGoal = 50000
const fundraisingRaised = computed(() => Math.round(fundraisingGoal * fundraisingProgress.value / 100))

/**
 * Event capacity progress
 */
const eventCapacity = ref(85)
const eventExpected = 500
const eventRegistered = computed(() => Math.round(eventExpected * eventCapacity.value / 100))

/**
 * Yearly attendance target
 */
const yearlyAttendanceTarget = ref(72)

/**
 * Animated progress value
 */
const animatedProgress = ref(0)

/**
 * Progress variants for demonstration
 */
const progressVariants = ['primary', 'success', 'warning', 'danger', 'info'] as const



/**
 * Simulate progress animation
 */
const animateProgress = () => {
  animatedProgress.value = 0
  const interval = setInterval(() => {
    if (animatedProgress.value >= 100) {
      clearInterval(interval)
    } else {
      animatedProgress.value += Math.random() * 10
      if (animatedProgress.value > 100) animatedProgress.value = 100
    }
  }, 200)
}

// =============================================================================
// SECTION 3: SKELETON LOADER DEMOS
// =============================================================================

/**
 * Toggle skeleton loading state
 */
const toggleLoading = () => {
  isLoading.value = !isLoading.value
}

/**
 * Simulate data fetch
 */
const simulateFetch = () => {
  isLoading.value = true
  setTimeout(() => {
    isLoading.value = false
  }, 3000)
}

// =============================================================================
// SECTION 4: PULL TO REFRESH DEMO
// =============================================================================

/**
 * Pull to refresh state
 */
const pullDistance = ref(0)
const isRefreshing = ref(false)
const refreshTriggered = ref(false)

/**
 * Items list for pull-to-refresh demo
 */
const refreshItems = ref([
  { id: 1, title: 'Sunday Service', date: 'Mar 24, 2024', attendees: 245 },
  { id: 2, title: 'Bible Study', date: 'Mar 20, 2024', attendees: 45 },
  { id: 3, title: 'Prayer Meeting', date: 'Mar 18, 2024', attendees: 30 }
])

/**
 * Handle pull-to-refresh
 */
const handleRefresh = () => {
  isRefreshing.value = true
  setTimeout(() => {
    refreshItems.value = [
      { id: 4, title: 'Youth Fellowship', date: 'Mar 25, 2024', attendees: 65 },
      { id: 1, title: 'Sunday Service', date: 'Mar 24, 2024', attendees: 245 },
      { id: 2, title: 'Bible Study', date: 'Mar 20, 2024', attendees: 45 },
      { id: 3, title: 'Prayer Meeting', date: 'Mar 18, 2024', attendees: 30 }
    ]
    isRefreshing.value = false
    refreshTriggered.value = true
    setTimeout(() => { refreshTriggered.value = false }, 2000)
  }, 1500)
}

/**
 * Simulate scroll for pull-to-refresh demo
 */
const handleScroll = (event: Event) => {
  const target = event.target as HTMLElement
  if (target.scrollTop === 0) {
    pullDistance.value = 50
  } else {
    pullDistance.value = 0
  }
}

// =============================================================================
// SECTION 5: MISC HELPERS
// =============================================================================

/**
 * Format currency
 */
const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount)
}
</script>

<template>
  <div class="doc-page">
    <!-- Page Header -->
    <header class="page-header">
      <h1 class="page-title">UI Cues & Feedback</h1>
      <p class="page-desc">
        Components that communicate system status and provide feedback to users. These include
        progress indicators, loading states, alerts, and toast notifications - all built with
        the zero-radius aesthetic.
      </p>
    </header>

    <!-- =======================================================================
    SECTION 1: CHALERT - ALERT COMPONENTS
    ======================================================================== -->
    <section class="doc-section">
      <h2 class="doc-section-title">ChAlert - Inline Alerts</h2>
      <p class="doc-section-desc">
        The <code>ChAlert</code> component displays persistent inline messages that remain visible
        until dismissed. Unlike toasts (which auto-dismiss), alerts are meant to convey important
        information that users should acknowledge.
      </p>

      <div class="demo-block">
        <h3>All Alert Variants</h3>
        <p class="demo-desc">Different severity levels for various message types</p>

        <div class="alerts-stack">
          <ChAlert variant="info" title="Information">
            Please make sure your email address is verified before changing the password.
          </ChAlert>

          <ChAlert variant="success" title="Success">
            Your profile has been updated successfully. Changes are now visible in the directory.
          </ChAlert>

          <ChAlert variant="warning" title="Warning">
            Your subscription will expire in 3 days. Please renew to avoid interruption of service.
          </ChAlert>

          <ChAlert variant="danger" title="System Error">
            Unable to connect to the database. Retrying in 30 seconds. If this persists, please contact support.
          </ChAlert>
        </div>
      </div>

      <div class="demo-grid">
        <div class="demo-block">
          <h3>With Icons</h3>
          <p class="demo-desc">Alerts with custom icon slots</p>

          <div class="alerts-stack">
            <ChAlert variant="success">
              <template #icon>
                <CheckCircleIcon />
              </template>
              Member registration completed successfully!
            </ChAlert>

            <ChAlert variant="danger">
              <template #icon>
                <XCircleIcon />
              </template>
              Failed to process payment. Please check your payment details.
            </ChAlert>

            <ChAlert variant="warning">
              <template #icon>
                <AlertTriangleIcon />
              </template>
              Storage limit at 90%. Consider cleaning up old documents.
            </ChAlert>
          </div>
        </div>

        <div class="demo-block">
          <h3>Dismissible Alerts</h3>
          <p class="demo-desc">Alerts that can be closed by the user</p>

          <ChAlert v-if="!alertDismissed" variant="info" title="Dismissible Alert" dismissible
            @dismiss="alertDismissed = true">
            Click the X button to dismiss this alert.
          </ChAlert>
          <ChButton v-else variant="outline" size="sm" @click="alertDismissed = false">
            Show Alert Again
          </ChButton>
        </div>

        <div class="demo-block">
          <h3>With Action Buttons</h3>
          <p class="demo-desc">Alerts with interactive buttons</p>

          <ChAlert variant="warning" title="Unsaved Changes">
            You have unsaved changes. Would you like to save before leaving?
            <template #actions>
              <ChButton variant="primary" size="sm">Save Changes</ChButton>
              <ChButton variant="outline" size="sm">Discard</ChButton>
            </template>
          </ChAlert>
        </div>

        <div class="demo-block">
          <h3>Church Context Alerts</h3>
          <p class="demo-desc">Real-world church management scenarios</p>

          <ChAlert variant="success" title="Contribution Confirmed">
            Thank you for your generous donation of $500.00 to the Building Fund.
          </ChAlert>

          <ChAlert variant="info" title="Event Reminder">
            "Sunday Service" starts in 30 minutes. Don't forget to check in attendees.
          </ChAlert>
        </div>
      </div>

      <div class="api-reference">
        <h3>ChAlert Props</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Prop</th>
              <th>Type</th>
              <th>Default</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>variant</code></td>
              <td><code>'info' | 'success' | 'warning' | 'danger'</code></td>
              <td><code>'info'</code></td>
              <td>Visual style of the alert</td>
            </tr>
            <tr>
              <td><code>title</code></td>
              <td><code>string</code></td>
              <td><code>''</code></td>
              <td>Optional bold title text</td>
            </tr>
            <tr>
              <td><code>dismissible</code></td>
              <td><code>boolean</code></td>
              <td><code>false</code></td>
              <td>Show close button</td>
            </tr>
          </tbody>
        </table>

        <h3>ChAlert Slots</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Slot</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>default</code></td>
              <td>Main alert content</td>
            </tr>
            <tr>
              <td><code>icon</code></td>
              <td>Custom icon (overrides default)</td>
            </tr>
            <tr>
              <td><code>actions</code></td>
              <td>Action buttons area</td>
            </tr>
          </tbody>
        </table>

        <h3>ChAlert Events</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Event</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>dismiss</code></td>
              <td>Fired when close button is clicked</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 2: USEToast - TOAST NOTIFICATIONS
    ======================================================================== -->
    <section class="doc-section">
      <h2 class="doc-section-title">useToast - Toast Notifications</h2>
      <p class="doc-section-desc">
        The <code>useToast</code> composable manages ephemeral toast notifications that appear
        temporarily and auto-dismiss. Toasts are perfect for confirming actions, showing
        success/error states, and providing contextual feedback.
      </p>

      <div class="demo-block">
        <h3>Basic Toasts</h3>
        <p class="demo-desc">Simple toast notifications with different severity levels</p>
        <div class="button-group">
          <ChButton variant="primary" size="sm" @click="showSuccessToast">
            <CheckCircleIcon class="btn-icon" /> Success
          </ChButton>
          <ChButton variant="danger" size="sm" @click="showErrorToast">
            <XCircleIcon class="btn-icon" /> Error
          </ChButton>
          <ChButton variant="primary" size="sm" @click="showWarningToast">
            <AlertTriangleIcon class="btn-icon" /> Warning
          </ChButton>
          <ChButton variant="outline" size="sm" @click="showInfoToast">
            <InfoIcon class="btn-icon" /> Info
          </ChButton>
        </div>
      </div>

      <div class="demo-block">
        <h3>Toast Positions</h3>
        <p class="demo-desc">Toasts can appear at different screen positions</p>
        <div class="position-grid">
          <ChButton variant="outline" size="sm" @click="showTopLeftToast">Top Left</ChButton>
          <ChButton variant="outline" size="sm" @click="showTopCenterToast">Top Center</ChButton>
          <ChButton variant="outline" size="sm" @click="showTopRightToast">Top Right</ChButton>
          <ChButton variant="outline" size="sm" @click="showBottomLeftToast">Bottom Left</ChButton>
          <ChButton variant="outline" size="sm" @click="showBottomCenterToast">Bottom Center</ChButton>
          <ChButton variant="outline" size="sm" @click="showBottomRightToast">Bottom Right</ChButton>
        </div>
      </div>

      <div class="demo-block">
        <h3>Toasts with Actions</h3>
        <p class="demo-desc">Toasts that include clickable action buttons</p>
        <div class="button-group">
          <ChButton variant="primary" size="sm" @click="showToastWithAction">
            Toast with Action
          </ChButton>
          <ChButton variant="outline" size="sm" @click="showToastWithUndo">
            Toast with Undo
          </ChButton>
        </div>
      </div>

      <div class="demo-block">
        <h3>Persistent Toasts</h3>
        <p class="demo-desc">Toasts that don't auto-dismiss (duration: 0)</p>
        <div class="button-group">
          <ChButton variant="primary" size="sm" @click="showPersistentToast">
            Persistent Toast
          </ChButton>
        </div>
      </div>

      <div class="demo-block">
        <h3>Custom Duration</h3>
        <p class="demo-desc">Control how long toasts stay visible</p>
        <div class="button-group">
          <ChButton variant="outline" size="sm" @click="showQuickToast">
            Quick (2s)
          </ChButton>
          <ChButton variant="outline" size="sm" @click="showSlowToast">
            Slow (10s)
          </ChButton>
        </div>
      </div>

      <div class="demo-block">
        <h3>Church Context Toasts</h3>
        <p class="demo-desc">Toast notifications for common church management actions</p>
        <div class="button-group">
          <ChButton variant="primary" size="sm" @click="showMemberAddedToast">
            <PlusIcon class="btn-icon" /> Member Added
          </ChButton>
          <ChButton variant="primary" size="sm" @click="showContributionRecordedToast">
            <DollarSignIcon class="btn-icon" /> Contribution Recorded
          </ChButton>
          <ChButton variant="primary" size="sm" @click="showEventCreatedToast">
            <CalendarIcon class="btn-icon" /> Event Created
          </ChButton>
          <ChButton variant="danger" size="sm" @click="showErrorContextToast">
            <XCircleIcon class="btn-icon" /> Error Context
          </ChButton>
        </div>
      </div>

      <div class="api-reference">
        <h3>useToast API</h3>
        <pre class="code-block"><code>const toast = useToast()

// Basic toasts
toast.success(message: string, options?: ToastOptions)
toast.error(message: string, options?: ToastOptions)
toast.warning(message: string, options?: ToastOptions)
toast.info(message: string, options?: ToastOptions)

// Custom toasts
toast.push(message: string, options?: ToastOptions)

// Dismiss
toast.dismiss(id?: string)</code></pre>

        <h3>ToastOptions</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Option</th>
              <th>Type</th>
              <th>Default</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>duration</code></td>
              <td><code>number</code></td>
              <td><code>5000</code></td>
              <td>Auto-dismiss delay in ms (0 for persistent)</td>
            </tr>
            <tr>
              <td><code>position</code></td>
              <td><code>string</code></td>
              <td><code>'bottom-right'</code></td>
              <td>Screen position: top-left, top-center, top-right, bottom-left, bottom-center, bottom-right</td>
            </tr>
            <tr>
              <td><code>action</code></td>
              <td><code>object</code></td>
              <td><code>undefined</code></td>
              <td>{ label: string, onClick: () => void }</td>
            </tr>
            <tr>
              <td><code>icon</code></td>
              <td><code>Component</code></td>
              <td><code>undefined</code></td>
              <td>Custom icon component</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 3: CHPROGRESS - PROGRESS BARS
    ======================================================================== -->
    <section class="doc-section">
      <h2 class="doc-section-title">ChProgress - Progress Bars</h2>
      <p class="doc-section-desc">
        The <code>ChProgress</code> component displays task completion progress. Use determinate
        progress when you know the percentage, or indeterminate when the duration is unknown.
      </p>

      <div class="demo-block">
        <h3>Basic Progress Bars</h3>
        <p class="demo-desc">Default progress bar with percentage</p>
        <ChProgress v-model="progressValue" />
        <div class="progress-controls">
          <ChButton variant="outline" size="sm" @click="progressValue = Math.max(0, progressValue - 10)">-10%</ChButton>
          <ChButton variant="outline" size="sm" @click="progressValue = Math.min(100, progressValue + 10)">+10%
          </ChButton>
          <ChButton variant="outline" size="sm" @click="animateProgress">Animate</ChButton>
        </div>
      </div>

      <div class="demo-grid">
        <div class="demo-block">
          <h3>Progress Sizes</h3>
          <p class="demo-desc">Different height/size variants</p>
          <div class="sizes-demo">
            <div class="size-item">
              <span class="size-label">Small (sm)</span>
              <ChProgress :value="75" size="sm" />
            </div>
            <div class="size-item">
              <span class="size-label">Medium (md)</span>
              <ChProgress :value="75" size="md" />
            </div>
            <div class="size-item">
              <span class="size-label">Large (lg)</span>
              <ChProgress :value="75" size="lg" />
            </div>
          </div>
        </div>

        <div class="demo-block">
          <h3>Progress Variants</h3>
          <p class="demo-desc">Different color schemes</p>
          <div class="variants-demo">
            <div v-for="variant in progressVariants" :key="variant" class="variant-item">
              <span class="variant-label">{{ variant }}</span>
              <ChProgress :value="75" :variant="variant" />
            </div>
          </div>
        </div>

        <div class="demo-block">
          <h3>Indeterminate Progress</h3>
          <p class="demo-desc">Animated bar when duration is unknown</p>
          <div class="indeterminate-toggle">
            <label>
              <input type="checkbox" v-model="showIndeterminate" />
              Show indeterminate
            </label>
          </div>
          <ChProgress v-if="showIndeterminate" indeterminate />
          <ChProgress v-else :value="45" />
        </div>

        <div class="demo-block">
          <h3>Animated Progress</h3>
          <p class="demo-desc">Smooth animated value changes</p>
          <ChProgress :value="animatedProgress" variant="success" />
          <ChButton variant="outline" size="sm" @click="animateProgress" style="margin-top: 1rem">
            Start Animation
          </ChButton>
        </div>
      </div>

      <div class="demo-block">
        <h3>Church Context - Fundraising Goal</h3>
        <p class="demo-desc">Track progress toward financial goals</p>
        <div class="goal-card">
          <div class="goal-header">
            <HeartIcon class="goal-icon" />
            <div>
              <h4>Building Fund Campaign</h4>
              <p>{{ formatCurrency(fundraisingRaised) }} raised of {{ formatCurrency(fundraisingGoal) }} goal</p>
            </div>
          </div>
          <ChProgress v-model="fundraisingProgress" variant="success" size="lg" />
          <div class="goal-footer">
            <span class="goal-percent">{{ fundraisingProgress }}% funded</span>
            <ChButton variant="primary" size="sm">
              <DollarSignIcon class="btn-icon" /> Donate Now
            </ChButton>
          </div>
        </div>
      </div>

      <div class="demo-block">
        <h3>Church Context - Event Capacity</h3>
        <p class="demo-desc">Track event registrations vs capacity</p>
        <div class="capacity-card">
          <div class="capacity-header">
            <UsersIcon class="capacity-icon" />
            <div>
              <h4>Annual Conference 2024</h4>
              <p>{{ eventRegistered }} of {{ eventExpected }} spots filled</p>
            </div>
          </div>
          <ChProgress v-model="eventCapacity" variant="warning" />
          <div class="capacity-footer">
            <span class="spots-left">{{ eventExpected - eventRegistered }} spots remaining</span>
            <ChButton variant="outline" size="sm">Register</ChButton>
          </div>
        </div>
      </div>

      <div class="demo-block">
        <h3>Church Context - Yearly Attendance Target</h3>
        <p class="demo-desc">Track attendance rate against yearly goal</p>
        <div class="attendance-card">
          <div class="attendance-header">
            <CalendarIcon class="attendance-icon" />
            <div>
              <h4>Yearly Attendance Goal</h4>
              <p>Target: 80% average attendance rate</p>
            </div>
          </div>
          <ChProgress v-model="yearlyAttendanceTarget"
            :variant="yearlyAttendanceTarget >= 80 ? 'success' : 'warning'" />
          <div class="attendance-footer">
            <span>{{ yearlyAttendanceTarget >= 80 ? 'Goal achieved!' : 'Keep encouraging attendance!' }}</span>
          </div>
        </div>
      </div>

      <div class="api-reference">
        <h3>ChProgress Props</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Prop</th>
              <th>Type</th>
              <th>Default</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>modelValue</code> / <code>v-model</code></td>
              <td><code>number</code></td>
              <td><code>0</code></td>
              <td>Progress percentage (0-100)</td>
            </tr>
            <tr>
              <td><code>size</code></td>
              <td><code>'sm' | 'md' | 'lg'</code></td>
              <td><code>'md'</code></td>
              <td>Height/size of the progress bar</td>
            </tr>
            <tr>
              <td><code>variant</code></td>
              <td><code>'primary' | 'success' | 'warning' | 'danger' | 'info'</code></td>
              <td><code>'primary'</code></td>
              <td>Color scheme</td>
            </tr>
            <tr>
              <td><code>indeterminate</code></td>
              <td><code>boolean</code></td>
              <td><code>false</code></td>
              <td>Animated indeterminate mode</td>
            </tr>
            <tr>
              <td><code>showValue</code></td>
              <td><code>boolean</code></td>
              <td><code>false</code></td>
              <td>Display percentage value</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 4: CHSPINNER - LOADING SPINNERS
    ======================================================================== -->
    <section class="doc-section">
      <h2 class="doc-section-title">ChSpinner - Loading Spinners</h2>
      <p class="doc-section-desc">
        The <code>ChSpinner</code> component displays an animated loading indicator for
        async operations. Use spinners for short-duration operations, or when the exact
        progress percentage isn't known.
      </p>

      <div class="demo-block">
        <h3>Basic Spinners</h3>
        <p class="demo-desc">Different sizes of the default spinner</p>
        <div class="spinners-demo">
          <div class="spinner-item">
            <ChSpinner size="sm" />
            <span>Small</span>
          </div>
          <div class="spinner-item">
            <ChSpinner size="md" />
            <span>Medium</span>
          </div>
          <div class="spinner-item">
            <ChSpinner size="lg" />
            <span>Large</span>
          </div>
        </div>
      </div>

      <div class="demo-grid">
        <div class="demo-block">
          <h3>Spinner Variants</h3>
          <p class="demo-desc">Different color schemes</p>
          <div class="spinners-demo vertical">
            <div class="spinner-item">
              <ChSpinner variant="primary" />
              <span>Primary</span>
            </div>
            <div class="spinner-item">
              <ChSpinner variant="success" />
              <span>Success</span>
            </div>
            <div class="spinner-item">
              <ChSpinner variant="danger" />
              <span>Danger</span>
            </div>

            <div class="spinner-item">
              <ChSpinner variant="white" />
              <span>White</span>
            </div>
          </div>
        </div>

        <div class="demo-block">
          <h3>With Text Labels</h3>
          <p class="demo-desc">Spinners paired with loading text</p>
          <div class="spinners-demo vertical">
            <div class="spinner-item">
              <ChSpinner size="sm" />
              <span>Loading...</span>
            </div>
            <div class="spinner-item">
              <ChSpinner size="md" />
              <span>Processing...</span>
            </div>
            <div class="spinner-item">
              <ChSpinner size="lg" />
              <span>Please wait...</span>
            </div>
          </div>
        </div>

        <div class="demo-block">
          <h3>Inline Spinners</h3>
          <p class="demo-desc">Spinners embedded in text</p>
          <div class="inline-demo">
            <p>Saving member data
              <ChSpinner size="sm" />
            </p>
            <p>Uploading document
              <ChSpinner size="sm" />
            </p>
            <p>Sending notification
              <ChSpinner size="sm" />
            </p>
          </div>
        </div>

        <div class="demo-block">
          <h3>Church Context</h3>
          <p class="demo-desc">Real-world loading scenarios</p>
          <div class="spinners-demo vertical">
            <div class="spinner-item">
              <ChSpinner size="md" />
              <span>Fetching member records...</span>
            </div>
            <div class="spinner-item">
              <ChSpinner size="md" />
              <span>Processing contribution...</span>
            </div>
            <div class="spinner-item">
              <ChSpinner size="md" />
              <span>Loading event schedule...</span>
            </div>
          </div>
        </div>
      </div>

      <div class="api-reference">
        <h3>ChSpinner Props</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Prop</th>
              <th>Type</th>
              <th>Default</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>size</code></td>
              <td><code>'sm' | 'md' | 'lg'</code></td>
              <td><code>'md'</code></td>
              <td>Size of the spinner</td>
            </tr>
            <tr>
              <td><code>variant</code></td>
              <td><code>'primary' | 'success' | 'warning' | 'danger' | 'info'</code></td>
              <td><code>'primary'</code></td>
              <td>Color scheme</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 5: CHSKELETON - LOADING PLACEHOLDERS
    ======================================================================== -->
    <section class="doc-section">
      <h2 class="doc-section-title">ChSkeleton - Loading Placeholders</h2>
      <p class="doc-section-desc">
        The <code>ChSkeleton</code> component displays animated placeholder content that mimics
        the shape of actual content. Use skeletons when loading structured data like lists,
        cards, or detailed views.
      </p>

      <div class="demo-block">
        <h3>Skeleton Shapes</h3>
        <p class="demo-desc">Different skeleton placeholder shapes</p>
        <div class="shapes-demo">
          <div class="shape-item">
            <ChSkeleton shape="line" width="200px" height="20px" />
            <span>Line</span>
          </div>
          <div class="shape-item">
            <ChSkeleton shape="circle" width="48px" height="48px" />
            <span>Circle</span>
          </div>
          <div class="shape-item">
            <ChSkeleton shape="block" width="100%" height="120px" />
            <span>Block</span>
          </div>
        </div>
      </div>

      <div class="demo-grid">
        <div class="demo-block">
          <h3>Text Skeletons</h3>
          <p class="demo-desc">Simulate loading text content</p>
          <div class="text-skeleton">
            <ChSkeleton shape="line" width="80%" height="24px" />
            <ChSkeleton shape="line" width="100%" height="16px" />
            <ChSkeleton shape="line" width="95%" height="16px" />
            <ChSkeleton shape="line" width="60%" height="16px" />
          </div>
        </div>

        <div class="demo-block">
          <h3>Avatar Skeleton</h3>
          <p class="demo-desc">Loading state for user profiles</p>
          <div class="avatar-skeleton">
            <ChSkeleton shape="circle" width="64px" height="64px" />
            <div class="avatar-text">
              <ChSkeleton shape="line" width="150px" height="20px" />
              <ChSkeleton shape="line" width="100px" height="14px" />
            </div>
          </div>
        </div>

        <div class="demo-block">
          <h3>Card Skeleton</h3>
          <p class="demo-desc">Loading state for card components</p>
          <div class="card-skeleton">
            <ChSkeleton shape="block" width="100%" height="120px" />
            <div class="card-skeleton-content">
              <ChSkeleton shape="line" width="70%" height="18px" />
              <ChSkeleton shape="line" width="100%" height="14px" />
              <ChSkeleton shape="line" width="85%" height="14px" />
              <div class="card-skeleton-footer">
                <ChSkeleton shape="circle" width="32px" height="32px" />
                <ChSkeleton shape="line" width="80px" height="14px" />
              </div>
            </div>
          </div>
        </div>

        <div class="demo-block">
          <h3>Table Row Skeleton</h3>
          <p class="demo-desc">Loading state for data tables</p>
          <div class="table-skeleton">
            <div class="table-row-skeleton">
              <ChSkeleton shape="circle" width="32px" height="32px" />
              <ChSkeleton shape="line" width="150px" height="16px" />
              <ChSkeleton shape="line" width="120px" height="16px" />
              <ChSkeleton shape="line" width="80px" height="16px" />
            </div>
            <div class="table-row-skeleton">
              <ChSkeleton shape="circle" width="32px" height="32px" />
              <ChSkeleton shape="line" width="150px" height="16px" />
              <ChSkeleton shape="line" width="120px" height="16px" />
              <ChSkeleton shape="line" width="80px" height="16px" />
            </div>
            <div class="table-row-skeleton">
              <ChSkeleton shape="circle" width="32px" height="32px" />
              <ChSkeleton shape="line" width="150px" height="16px" />
              <ChSkeleton shape="line" width="120px" height="16px" />
              <ChSkeleton shape="line" width="80px" height="16px" />
            </div>
          </div>
        </div>
      </div>

      <div class="demo-block">
        <h3>Toggle Loading State</h3>
        <p class="demo-desc">Toggle between skeleton and actual content</p>
        <ChButton variant="outline" size="sm" @click="toggleLoading" style="margin-bottom: 1rem">
          {{ isLoading ? 'Show Content' : 'Show Loading' }}
        </ChButton>
        <ChButton variant="outline" size="sm" @click="simulateFetch">
          Simulate 3s Fetch
        </ChButton>

        <div class="toggle-demo" style="margin-top: 1rem">
          <template v-if="isLoading">
            <div class="member-skeleton">
              <div class="member-skeleton-header">
                <ChSkeleton shape="circle" width="80px" height="80px" />
                <div class="member-skeleton-info">
                  <ChSkeleton shape="line" width="200px" height="24px" />
                  <ChSkeleton shape="line" width="150px" height="16px" />
                  <ChSkeleton shape="line" width="120px" height="16px" />
                </div>
              </div>
              <div class="member-skeleton-body">
                <ChSkeleton shape="line" width="100%" height="16px" />
                <ChSkeleton shape="line" width="95%" height="16px" />
                <ChSkeleton shape="line" width="90%" height="16px" />
                <ChSkeleton shape="line" width="85%" height="16px" />
              </div>
            </div>
          </template>
          <template v-else>
            <div class="member-card">
              <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&h=200&fit=crop&crop=face"
                alt="Sarah" class="member-avatar" />
              <div class="member-info">
                <h4>Sarah Johnson</h4>
                <p>Active Member since 2018</p>
                <p>Women's Ministry Department</p>
              </div>
            </div>
          </template>
        </div>
      </div>

      <div class="demo-block">
        <h3>Church Context - Dashboard Stats</h3>
        <p class="demo-desc">Loading placeholders for dashboard cards</p>
        <div class="dashboard-skeleton">
          <div class="stat-card-skeleton">
            <ChSkeleton shape="block" width="48px" height="48px" />
            <ChSkeleton shape="line" width="80px" height="32px" />
            <ChSkeleton shape="line" width="100px" height="14px" />
          </div>
          <div class="stat-card-skeleton">
            <ChSkeleton shape="block" width="48px" height="48px" />
            <ChSkeleton shape="line" width="80px" height="32px" />
            <ChSkeleton shape="line" width="100px" height="14px" />
          </div>
          <div class="stat-card-skeleton">
            <ChSkeleton shape="block" width="48px" height="48px" />
            <ChSkeleton shape="line" width="80px" height="32px" />
            <ChSkeleton shape="line" width="100px" height="14px" />
          </div>
          <div class="stat-card-skeleton">
            <ChSkeleton shape="block" width="48px" height="48px" />
            <ChSkeleton shape="line" width="80px" height="32px" />
            <ChSkeleton shape="line" width="100px" height="14px" />
          </div>
        </div>
      </div>

      <div class="api-reference">
        <h3>ChSkeleton Props</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Prop</th>
              <th>Type</th>
              <th>Default</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>shape</code></td>
              <td><code>'line' | 'circle' | 'block'</code></td>
              <td><code>'line'</code></td>
              <td>Shape of the skeleton</td>
            </tr>
            <tr>
              <td><code>width</code></td>
              <td><code>string</code></td>
              <td><code>'100%'</code></td>
              <td>Width (CSS value)</td>
            </tr>
            <tr>
              <td><code>height</code></td>
              <td><code>string</code></td>
              <td><code>'16px'</code></td>
              <td>Height (CSS value)</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- =======================================================================
    SECTION 6: USEPULLTOREFRESH - PULL TO REFRESH
    ======================================================================== -->
    <section class="doc-section">
      <h2 class="doc-section-title">usePullToRefresh - Pull to Refresh Gesture</h2>
      <p class="doc-section-desc">
        The <code>usePullToRefresh</code> composable provides a pull-down gesture that triggers
        a refresh action. This is common in mobile apps and provides intuitive content refresh.
      </p>

      <div class="demo-block">
        <h3>Pull to Refresh Demo</h3>
        <p class="demo-desc">Pull down on the list below to refresh</p>

        <div class="pull-container" @scroll="handleScroll">
          <div class="pull-indicator" :class="{ refreshing: isRefreshing, triggered: refreshTriggered }">
            <ChSpinner v-if="isRefreshing" size="sm" />
            <RefreshCwIcon v-else class="refresh-icon" :class="{ rotated: pullDistance > 0 }" />
            <span v-if="isRefreshing">Refreshing...</span>
            <span v-else-if="refreshTriggered">Refreshed!</span>
            <span v-else>Pull to refresh</span>
          </div>

          <div class="refresh-list">
            <div v-for="item in refreshItems" :key="item.id" class="refresh-item">
              <div class="refresh-item-icon">
                <CalendarIcon />
              </div>
              <div class="refresh-item-content">
                <h4>{{ item.title }}</h4>
                <p>{{ item.date }}</p>
              </div>
              <ChBadge variant="primary">{{ item.attendees }}</ChBadge>
            </div>
          </div>
        </div>

        <ChButton variant="outline" size="sm" @click="handleRefresh" :disabled="isRefreshing" style="margin-top: 1rem">
          <RefreshCwIcon class="btn-icon" /> Manual Refresh
        </ChButton>
      </div>

      <div class="api-reference">
        <h3>usePullToRefresh API</h3>
        <pre class="code-block"><code>import { usePullToRefresh } from '@/design-system'

const { pullDistance, phase, attach, triggerRefresh } = usePullToRefresh({
  onRefresh: async () => {
    await fetchData()
  },
  threshold: 80 // px to trigger refresh
})</code></pre>

        <h3>Options</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Option</th>
              <th>Type</th>
              <th>Default</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>onRefresh</code></td>
              <td><code>() =&gt; Promise&lt;void&gt;</code></td>
              <td><code>required</code></td>
              <td>Async function called on refresh</td>
            </tr>
            <tr>
              <td><code>threshold</code></td>
              <td><code>number</code></td>
              <td><code>80</code></td>
              <td>Distance in px to trigger refresh</td>
            </tr>
          </tbody>
        </table>

        <h3>Return Value</h3>
        <table class="api-table">
          <thead>
            <tr>
              <th>Property</th>
              <th>Type</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>pullDistance</code></td>
              <td><code>Ref&lt;number&gt;</code></td>
              <td>Current pull distance in pixels</td>
            </tr>
            <tr>
              <td><code>phase</code></td>
              <td><code>Ref&lt;string&gt;</code></td>
              <td>Current pull phase</td>
            </tr>
            <tr>
              <td><code>triggerRefresh</code></td>
              <td><code>() =&gt; void</code></td>
              <td>Manually trigger a refresh</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* =============================================================================
DOCUMENTATION STYLES
================================================================================ */

.doc-page {
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 2px solid var(--ch-color-border-strong);
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--ch-color-text);
  margin: 0 0 0.5rem 0;
  letter-spacing: -0.02em;
}

.page-desc {
  font-size: 1rem;
  color: var(--ch-color-text-muted);
  margin: 0;
  line-height: 1.6;
}

.doc-section {
  margin-bottom: 3rem;
  padding: 1.5rem;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
}

.doc-section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--ch-color-text);
  margin: 0 0 0.5rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--ch-color-border);
}

.doc-section-desc {
  font-size: 0.9rem;
  color: var(--ch-color-text-muted);
  margin: 0 0 1.5rem 0;
  line-height: 1.6;
}

.demo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
}

.demo-block {
  background: var(--ch-color-bg);
  border: 1px solid var(--ch-color-border);
  padding: 1.5rem;
  margin-top: 1rem;
}

.demo-block h3 {
  font-size: 0.9rem;
  font-weight: 600;
  margin: 0 0 0.25rem 0;
  color: var(--ch-color-text);
}

.demo-desc {
  font-size: 0.8rem;
  color: var(--ch-color-text-muted);
  margin: 0 0 1rem 0;
}

/* =============================================================================
ALERTS
================================================================================ */

.alerts-stack {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* =============================================================================
TOAST POSITIONS
================================================================================ */

.position-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
}

/* =============================================================================
PROGRESS
================================================================================ */

.progress-controls {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}

.sizes-demo,
.variants-demo {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.size-item,
.variant-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.size-label,
.variant-label {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
  text-transform: capitalize;
}

.indeterminate-toggle {
  margin-bottom: 1rem;
}

.indeterminate-toggle label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  cursor: pointer;
}

/* Church Context Cards */
.goal-card,
.capacity-card,
.attendance-card {
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
  padding: 1.5rem;
  margin-top: 1rem;
}

.goal-header,
.capacity-header,
.attendance-header {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.goal-icon,
.capacity-icon,
.attendance-icon {
  width: 40px;
  height: 40px;
  color: var(--ch-color-primary);
}

.goal-header h4,
.capacity-header h4,
.attendance-header h4 {
  margin: 0;
  font-size: 1rem;
}

.goal-header p,
.capacity-header p,
.attendance-header p {
  margin: 0.25rem 0 0 0;
  font-size: 0.875rem;
  color: var(--ch-color-text-muted);
}

.goal-footer,
.capacity-footer,
.attendance-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1rem;
}

.goal-percent,
.spots-left {
  font-size: 0.875rem;
  color: var(--ch-color-text-muted);
}

/* =============================================================================
SPINNERS
================================================================================ */

.spinners-demo {
  display: flex;
  gap: 2rem;
  align-items: center;
}

.spinners-demo.vertical {
  flex-direction: column;
  align-items: flex-start;
  gap: 1rem;
}

.spinner-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.spinner-item span {
  font-size: 0.875rem;
  color: var(--ch-color-text-muted);
}

.inline-demo {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.inline-demo p {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
  font-size: 0.875rem;
}

/* =============================================================================
SKELETONS
================================================================================ */

.shapes-demo {
  display: flex;
  gap: 2rem;
  align-items: center;
}

.shape-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: center;
}

.shape-item span {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

.text-skeleton {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.avatar-skeleton {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.avatar-text {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.card-skeleton {
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
  overflow: hidden;
}

.card-skeleton-content {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.card-skeleton-footer {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.table-skeleton {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.table-row-skeleton {
  display: flex;
  gap: 1rem;
  align-items: center;
  padding: 0.75rem;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
}

/* Toggle Demo */
.toggle-demo {
  border: 1px solid var(--ch-color-border);
  padding: 1rem;
}

.member-skeleton {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.member-skeleton-header {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.member-skeleton-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.member-skeleton-body {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.member-card {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.member-avatar {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border: 2px solid var(--ch-color-border-strong);
}

.member-info h4 {
  margin: 0;
  font-size: 1.1rem;
}

.member-info p {
  margin: 0.25rem 0 0 0;
  font-size: 0.875rem;
  color: var(--ch-color-text-muted);
}

/* Dashboard Skeleton */
.dashboard-skeleton {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.stat-card-skeleton {
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

/* =============================================================================
PULL TO REFRESH
================================================================================ */

.pull-container {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid var(--ch-color-border);
  background: var(--ch-color-bg);
}

.pull-indicator {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: var(--ch-color-bg-subtle);
  border-bottom: 1px solid var(--ch-color-border);
  transition: all 0.3s ease;
}

.pull-indicator span {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted);
}

.refresh-icon {
  width: 20px;
  height: 20px;
  color: var(--ch-color-text-muted);
  transition: transform 0.3s ease;
}

.refresh-icon.rotated {
  transform: rotate(180deg);
}

.pull-indicator.refreshing {
  background: var(--ch-color-primary-bg);
}

.pull-indicator.triggered {
  background: var(--ch-color-success-bg);
}

.refresh-list {
  padding: 0;
}

.refresh-item {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid var(--ch-color-border);
  align-items: center;
}

.refresh-item:last-child {
  border-bottom: none;
}

.refresh-item-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
  color: var(--ch-color-primary);
}

.refresh-item-content {
  flex: 1;
}

.refresh-item-content h4 {
  margin: 0;
  font-size: 0.9rem;
}

.refresh-item-content p {
  margin: 0.25rem 0 0 0;
  font-size: 0.8rem;
  color: var(--ch-color-text-muted);
}

/* =============================================================================
SHARED UTILITIES
================================================================================ */

.button-group {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.btn-icon {
  width: 14px;
  height: 14px;
  margin-right: 0.25rem;
}

/* =============================================================================
API REFERENCE
================================================================================ */

.api-reference {
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--ch-color-border);
}

.api-reference h3 {
  font-size: 0.9rem;
  font-weight: 600;
  margin: 1.5rem 0 1rem 0;
  color: var(--ch-color-text);
}

.api-reference h3:first-child {
  margin-top: 0;
}

.api-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
  margin-bottom: 1rem;
}

.api-table th,
.api-table td {
  padding: 0.5rem 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--ch-color-border);
}

.api-table th {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ch-color-text-muted);
  background: var(--ch-color-bg-subtle);
}

.api-table code {
  background: var(--ch-color-bg-subtle);
  padding: 0.125rem 0.25rem;
  font-size: 0.75rem;
}

.code-block {
  background: var(--ch-color-bg-subtle);
  border: 1px solid var(--ch-color-border);
  padding: 1rem;
  overflow-x: auto;
  font-size: 0.75rem;
  line-height: 1.5;
  margin-bottom: 1rem;
}

.code-block code {
  color: var(--ch-color-text);
  background: none;
  padding: 0;
}
</style>
