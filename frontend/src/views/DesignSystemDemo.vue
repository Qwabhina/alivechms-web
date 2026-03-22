<script setup lang="ts">
import { ref, reactive } from 'vue'
import {
   ChButton,
   ChCard,
   ChAvatar,
   ChBadge,
   ChDivider,
   ChInput,
   ChTextarea,
   ChSelect,
   ChCheckbox,
   ChRadio,
   ChSwitch,
   ChSlider,
   ChFileUpload,
   ChDatePicker,
   ChModal,
   ChStepperWizard,
   ChStepperStep,
   ChTable,
   ChStatCard,
   ChDataList,
   ChTabs,
   ChTopbar,
   ChSpinner,
   ChSkeleton,
   ChProgress,
   ChToastContainer,
   useTheme,
   useModal,
   useToast,
   useStepperWizard,
   useTableExport,
   type ExportFormat
} from '@/design-system'

// ─────────────────────────────────────────────────────────────────────────────
// Sidebar Navigation
// ─────────────────────────────────────────────────────────────────────────────
const sidebarSections = ref([
   {
      title: 'Core',
      items: [
         { id: 'button', label: 'Button' },
         { id: 'card', label: 'Card' },
         { id: 'avatar', label: 'Avatar' },
         { id: 'badge', label: 'Badge' },
         { id: 'divider', label: 'Divider' },
         { id: 'input', label: 'Input' },
         { id: 'textarea', label: 'Textarea' }
      ]
   },
   {
      title: 'Forms',
      items: [
         { id: 'select', label: 'Select' },
         { id: 'checkbox', label: 'Checkbox' },
         { id: 'radio', label: 'Radio' },
         { id: 'switch', label: 'Switch' },
         { id: 'slider', label: 'Slider' },
         { id: 'file-upload', label: 'File Upload' },
         { id: 'date-picker', label: 'Date Picker' },
         { id: 'modal', label: 'Modal' },
         { id: 'stepper', label: 'Stepper Wizard' }
      ]
   },
   {
      title: 'Data',
      items: [
         { id: 'table', label: 'Table' },
         { id: 'stat-card', label: 'Stat Card' },
         { id: 'data-list', label: 'Data List' }
      ]
   },
   {
      title: 'Navigation',
      items: [
         { id: 'tabs', label: 'Tabs' },
         { id: 'topbar', label: 'Topbar' }
      ]
   },
   {
      title: 'Feedback',
      items: [
         { id: 'toast', label: 'Toast' },
         { id: 'spinner', label: 'Spinner' },
         { id: 'skeleton', label: 'Skeleton' },
         { id: 'progress', label: 'Progress' }
      ]
   },
   {
      title: 'Composables',
      items: [
         { id: 'use-theme', label: 'useTheme' },
         { id: 'use-modal', label: 'useModal' },
         { id: 'use-toast', label: 'useToast' },
         { id: 'use-stepper', label: 'useStepperWizard' },
         { id: 'use-export', label: 'useTableExport' }
      ]
   }
])

const activeItem = ref('button')

const handleNavClick = (itemId: string) => {
   activeItem.value = itemId
   const element = document.getElementById(`section-${itemId}`)
   if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' })
   }
}

// ─────────────────────────────────────────────────────────────────────────────
// Code Toggle State
// ─────────────────────────────────────────────────────────────────────────────
const showCode = ref<Record<string, boolean>>({})

const toggleCode = (id: string) => {
   showCode.value[id] = !showCode.value[id]
}

// ─────────────────────────────────────────────────────────────────────────────
// Core Components - Button
// ─────────────────────────────────────────────────────────────────────────────
const buttonLoading = ref(false)
const buttonClick = () => {
   buttonLoading.value = true
   setTimeout(() => {
      buttonLoading.value = false
   }, 2000)
}

// ─────────────────────────────────────────────────────────────────────────────
// Core Components - Avatar
// ─────────────────────────────────────────────────────────────────────────────
const avatarStatus = ref<'online' | 'offline' | 'busy' | 'away'>('online')

// ─────────────────────────────────────────────────────────────────────────────
// Core Components - Input
// ─────────────────────────────────────────────────────────────────────────────
const inputValue = ref('')
const inputWithError = ref('')
const inputDisabled = ref('Disabled value')
const inputWithPrefix = ref('')
const inputWithSuffix = ref('')

// ─────────────────────────────────────────────────────────────────────────────
// Core Components - Textarea
// ─────────────────────────────────────────────────────────────────────────────
const textareaValue = ref('')
const textareaWithCount = ref('')

// ─────────────────────────────────────────────────────────────────────────────
// Forms - Select
// ─────────────────────────────────────────────────────────────────────────────
const selectOptions = [
   { value: 'option1', label: 'Option 1' },
   { value: 'option2', label: 'Option 2' },
   { value: 'option3', label: 'Option 3' },
   { value: 'option4', label: 'Option 4' },
   { value: 'option5', label: 'Option 5 - Disabled', disabled: true }
]

const singleSelect = ref('')
const multiSelect = ref<string[]>([])
const selectWithSearch = ref('')

// ─────────────────────────────────────────────────────────────────────────────
// Forms - Checkbox
// ─────────────────────────────────────────────────────────────────────────────
const singleCheckbox = ref(false)
const checkboxGroup = ref<string[]>(['option1'])
const checkboxOptions = [
   { value: 'option1', label: 'Option 1' },
   { value: 'option2', label: 'Option 2' },
   { value: 'option3', label: 'Option 3' }
]
const indeterminate = ref(true)

// ─────────────────────────────────────────────────────────────────────────────
// Forms - Radio
// ─────────────────────────────────────────────────────────────────────────────
const radioValue = ref('option1')

// ─────────────────────────────────────────────────────────────────────────────
// Forms - Switch
// ─────────────────────────────────────────────────────────────────────────────
const switchValue = ref(false)
const switchWithLabel = ref(true)
const switchDisabled = ref(false)

// ─────────────────────────────────────────────────────────────────────────────
// Forms - Slider
// ─────────────────────────────────────────────────────────────────────────────
const sliderValue = ref(50)
const rangeValue = ref(80)
const sliderWithTooltip = ref(75)

// ─────────────────────────────────────────────────────────────────────────────
// Forms - File Upload
// ─────────────────────────────────────────────────────────────────────────────
const singleFile = ref<File[]>([])
const multipleFiles = ref<File[]>([])
const dragDropFiles = ref<File[]>([])

// ─────────────────────────────────────────────────────────────────────────────
// Forms - Date Picker
// ─────────────────────────────────────────────────────────────────────────────
const singleDate = ref<Date | null>(null)
const dateRange = ref<{ start: Date | null; end: Date | null }>({ start: null, end: null })

// ─────────────────────────────────────────────────────────────────────────────
// Forms - Modal
// ─────────────────────────────────────────────────────────────────────────────
const modalOpen = ref(false)
const modalSize = ref<'sm' | 'md' | 'lg' | 'xl' | 'full'>('md')

const openModal = (size: 'sm' | 'md' | 'lg' | 'xl' | 'full') => {
   modalSize.value = size
   modalOpen.value = true
}

// ─────────────────────────────────────────────────────────────────────────────
// Forms - Stepper Wizard
// ─────────────────────────────────────────────────────────────────────────────
const stepperWizard = useStepperWizard([
   { id: 'personal', label: 'Personal Info', sublabel: 'Enter your details' },
   { id: 'contact', label: 'Contact', sublabel: 'Add contact info' },
   { id: 'review', label: 'Review', sublabel: 'Confirm details' }
])

const stepperData = reactive({
   firstName: '',
   lastName: '',
   email: '',
   phone: ''
})

// ─────────────────────────────────────────────────────────────────────────────
// Data - Table
// ─────────────────────────────────────────────────────────────────────────────
interface User {
   id: number
   name: string
   email: string
   role: string
   status: string
}

const tableData = ref<User[]>([
   { id: 1, name: 'John Doe', email: 'john@example.com', role: 'Admin', status: 'Active' },
   { id: 2, name: 'Jane Smith', email: 'jane@example.com', role: 'User', status: 'Active' },
   { id: 3, name: 'Bob Johnson', email: 'bob@example.com', role: 'Editor', status: 'Inactive' },
   { id: 4, name: 'Alice Brown', email: 'alice@example.com', role: 'User', status: 'Active' },
   { id: 5, name: 'Charlie Wilson', email: 'charlie@example.com', role: 'User', status: 'Pending' }
])

const tableColumns = [
   { key: 'id', label: 'ID', sortable: true },
   { key: 'name', label: 'Name', sortable: true },
   { key: 'email', label: 'Email', sortable: true },
   { key: 'role', label: 'Role', sortable: true },
   { key: 'status', label: 'Status', sortable: true }
]

const tableLoading = ref(false)
const tablePagination = ref({
   page: 1,
   perPage: 10,
   total: 100
})

const handleSort = (key: string, direction: 'asc' | 'desc' | null) => {
   console.log('Sort:', key, direction)
}

const handlePageChange = (page: number) => {
   tablePagination.value.page = page
}

const handleAction = (action: string, row: User) => {
   console.log('Action:', action, row)
}

// ─────────────────────────────────────────────────────────────────────────────
// Data - Stat Card
// ─────────────────────────────────────────────────────────────────────────────
const statCards = [
   { label: 'Total Users', value: 1234, trend: 12, trendLabel: '+12%', icon: 'users' },
   { label: 'Revenue', value: 45678, trend: 8, trendLabel: '+8%', icon: 'dollar' },
   { label: 'Bounce Rate', value: 24.5, trend: -3, trendLabel: '-3%', icon: 'chart' },
   { label: 'New Signups', value: 89, trend: -5, trendLabel: '-5%', icon: 'user-plus' }
]

// ─────────────────────────────────────────────────────────────────────────────
// Data - Data List
// ─────────────────────────────────────────────────────────────────────────────
const dataListItems = ref([
   { id: 1, label: 'Item 1', value: 'Description for item 1', avatar: 'https://i.pravatar.cc/150?img=1' },
   { id: 2, label: 'Item 2', value: 'Description for item 2', avatar: 'https://i.pravatar.cc/150?img=2' },
   { id: 3, label: 'Item 3', value: 'Description for item 3', avatar: 'https://i.pravatar.cc/150?img=3' }
])

// ─────────────────────────────────────────────────────────────────────────────
// Navigation - Tabs
// ─────────────────────────────────────────────────────────────────────────────
const tabs = [
   { value: 'tab1', label: 'Tab 1' },
   { value: 'tab2', label: 'Tab 2' },
   { value: 'tab3', label: 'Tab 3' }
]

const activeTab = ref('tab1')
const verticalTabs = [
   { value: 'vtab1', label: 'First Tab' },
   { value: 'vtab2', label: 'Second Tab' },
   { value: 'vtab3', label: 'Third Tab' }
]
const activeVerticalTab = ref('vtab1')

// ─────────────────────────────────────────────────────────────────────────────
// Navigation - Topbar
// ─────────────────────────────────────────────────────────────────────────────
const topbarUser = {
   name: 'John Doe',
   email: 'john@example.com',
   avatar: 'https://i.pravatar.cc/150?img=1'
}

const topbarNotifications = ref(3)
const topbarSearch = ref('')

// ─────────────────────────────────────────────────────────────────────────────
// Feedback - Toast
// ─────────────────────────────────────────────────────────────────────────────
const toast = useToast()

const showToast = (variant: 'success' | 'warning' | 'danger' | 'info', message: string) => {
   if (variant === 'success') {
      toast.success(message)
   } else if (variant === 'danger') {
      toast.error(message)
   } else if (variant === 'warning') {
      toast.warning(message)
   } else {
      toast.info(message)
   }
}

// ─────────────────────────────────────────────────────────────────────────────
// Feedback - Spinner
// ─────────────────────────────────────────────────────────────────────────────
const spinnerSizes = ['sm', 'md', 'lg', 'xl'] as const
const spinnerColors = ['primary', 'success', 'warning', 'danger', 'info']

// ─────────────────────────────────────────────────────────────────────────────
// Feedback - Progress
// ─────────────────────────────────────────────────────────────────────────────
const progressValue = ref(65)

// ─────────────────────────────────────────────────────────────────────────────
// Composables - useTheme
// ─────────────────────────────────────────────────────────────────────────────
const { isDarkMode, toggleDarkMode } = useTheme()

// ─────────────────────────────────────────────────────────────────────────────
// Composables - useModal
// ─────────────────────────────────────────────────────────────────────────────
const { isOpen: composableModalOpen, open: openComposableModal, close: closeComposableModal } = useModal()
const modalForm = reactive({
   name: '',
   email: ''
})

// ─────────────────────────────────────────────────────────────────────────────
// Composables - useStepperWizard
// ─────────────────────────────────────────────────────────────────────────────
const {
   currentIdx,
   next,
   back: prev,
   goTo,
   isFirstStep: isFirst,
   isLastStep: isLast,
   steps: wizardSteps
} = useStepperWizard([
   { id: 'wstep1', label: 'Step 1' },
   { id: 'wstep2', label: 'Step 2' },
   { id: 'wstep3', label: 'Step 3' }
])

const wizardData = reactive({
   field1: '',
   field2: '',
   field3: ''
})

// ─────────────────────────────────────────────────────────────────────────────
// Composables - useTableExport
// ─────────────────────────────────────────────────────────────────────────────
const exportData = ref([
   { id: 1, name: 'John', age: 30 },
   { id: 2, name: 'Jane', age: 25 },
   { id: 3, name: 'Bob', age: 35 }
])

const { exportData: doExport, isExporting } = useTableExport()

const handleExport = async (format: ExportFormat) => {
   await doExport({
      rows: exportData.value,
      columns: [
         { key: 'id', label: 'ID' },
         { key: 'name', label: 'Name' },
         { key: 'age', label: 'Age' }
      ],
      format,
      filename: 'exported-data'
   })
}
</script>

<template>
   <div class="design-system-demo">
      <!-- Main Content -->
      <main class="demo-content">
         <div class="demo-container">
            <h1 class="page-title">Design System Components</h1>
            <p class="page-description">
               A comprehensive showcase of all design system components with their variants and usage examples.
            </p>

            <!-- Quick Navigation -->
            <div class="quick-nav">
              <ChButton v-for="item in sidebarSections.flatMap(s => s.items)" :key="item.id" variant="ghost" size="sm"
                  @click="handleNavClick(item.id)">
                  {{ item.label }}
               </ChButton>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- CORE COMPONENTS -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->

            <!-- Button Section -->
            <section id="section-button" class="component-section">
               <h2 class="section-title">Button</h2>
               <p class="section-description">The Button component is used to trigger actions.</p>

               <!-- Variants -->
               <ChCard class="demo-card">
                  <template #header>
                     <h3>Variants</h3>
                  </template>
                  <div class="button-row">
                     <ChButton variant="primary">Primary</ChButton>
                     <ChButton variant="secondary">Secondary</ChButton>
                     <ChButton variant="ghost">Ghost</ChButton>
                     <ChButton variant="danger">Danger</ChButton>
                     <ChButton variant="outline">Outline</ChButton>
                  </div>
                  <template #footer>
                     <ChButton variant="ghost" size="sm" @click="toggleCode('button-variants')">
                        {{ showCode['button-variants'] ? 'Hide' : 'Show' }} Code
                     </ChButton>
                  </template>
               </ChCard>

               <div v-if="showCode['button-variants']" class="code-block">
                  <pre><code>
         <ChButton variant="primary">Primary</ChButton>
         <ChButton variant="secondary">Secondary</ChButton>
         <ChButton variant="ghost">Ghost</ChButton>
         <ChButton variant="danger">Danger</ChButton>
         <ChButton variant="outline">Outline</ChButton>
      </code></pre>
               </div>

               <!-- Sizes -->
               <ChCard class="demo-card">
                  <template #header>
                     <h3>Sizes</h3>
                  </template>
                  <div class="button-row">
                     <ChButton size="sm">Small</ChButton>
                     <ChButton size="md">Medium</ChButton>
                     <ChButton size="lg">Large</ChButton>
                  </div>
               </ChCard>

               <!-- States -->
               <ChCard class="demo-card">
                  <template #header>
                     <h3>States</h3>
                  </template>
                  <div class="button-row">
                     <ChButton :loading="buttonLoading" @click="buttonClick">
                        {{ buttonLoading ? 'Loading...' : 'Loading State' }}
                     </ChButton>
                     <ChButton disabled>Disabled</ChButton>
                     <ChButton>
                        <template #prefix>★</template>
                        With Icon
                     </ChButton>
                  </div>
               </ChCard>
            </section>

            <!-- Card Section -->
            <section id="section-card" class="component-section">
               <h2 class="section-title">Card</h2>
               <p class="section-description">Cards are used to group related content.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Default Card</h3>
                  </template>
                  <p>This is a basic card with content in the body.</p>
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Card with Header and Footer</h3>
                  </template>
                  <p>Card content goes here.</p>
                  <template #footer>
                     <ChButton size="sm">Action</ChButton>
                  </template>
               </ChCard>

               <ChCard class="demo-card" hoverable>
                  <template #header>
                     <h3>Hoverable Card</h3>
                  </template>
                  <p>Hover over this card to see the effect.</p>
               </ChCard>
            </section>

            <!-- Avatar Section -->
            <section id="section-avatar" class="component-section">
               <h2 class="section-title">Avatar</h2>
               <p class="section-description">Avatars represent users or entities.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Sizes</h3>
                  </template>
                  <div class="avatar-row">
                     <ChAvatar size="xs" src="https://i.pravatar.cc/150?img=1" />
                     <ChAvatar size="sm" src="https://i.pravatar.cc/150?img=2" />
                     <ChAvatar size="md" src="https://i.pravatar.cc/150?img=3" />
                     <ChAvatar size="lg" src="https://i.pravatar.cc/150?img=4" />
                     <ChAvatar size="xl" src="https://i.pravatar.cc/150?img=5" />
                  </div>
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Initials Fallback</h3>
                  </template>
                  <div class="avatar-row">
                     <ChAvatar size="md">JD</ChAvatar>
                     <ChAvatar size="md" variant="success">AB</ChAvatar>
                     <ChAvatar size="md" variant="warning">CD</ChAvatar>
                     <ChAvatar size="md" variant="danger">EF</ChAvatar>
                  </div>
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Status Indicator</h3>
                  </template>
                  <div class="avatar-row">
                     <ChAvatar size="lg" src="https://i.pravatar.cc/150?img=1" :status="avatarStatus" />
                     <div class="status-buttons">
                        <ChButton size="sm" variant="outline" @click="avatarStatus = 'online'">Online</ChButton>
                        <ChButton size="sm" variant="outline" @click="avatarStatus = 'offline'">Offline</ChButton>
                        <ChButton size="sm" variant="outline" @click="avatarStatus = 'busy'">Busy</ChButton>
                        <ChButton size="sm" variant="outline" @click="avatarStatus = 'away'">Away</ChButton>
                     </div>
                  </div>
               </ChCard>
            </section>

            <!-- Badge Section -->
            <section id="section-badge" class="component-section">
               <h2 class="section-title">Badge</h2>
               <p class="section-description">Badges are used to highlight status or counts.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Variants</h3>
                  </template>
                  <div class="badge-row">
                     <ChBadge variant="default">Default</ChBadge>
                     <ChBadge variant="success">Success</ChBadge>
                     <ChBadge variant="warning">Warning</ChBadge>
                     <ChBadge variant="danger">Danger</ChBadge>
                     <ChBadge variant="info">Info</ChBadge>
                  </div>
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>With Icons</h3>
                  </template>
                  <div class="badge-row">
                     <ChBadge variant="success">✓ Success</ChBadge>
                     <ChBadge variant="warning">⚠ Warning</ChBadge>
                     <ChBadge variant="danger">✕ Error</ChBadge>
                     <ChBadge variant="info">ℹ Info</ChBadge>
                  </div>
               </ChCard>
            </section>

            <!-- Divider Section -->
            <section id="section-divider" class="component-section">
               <h2 class="section-title">Divider</h2>
               <p class="section-description">Dividers separate content visually.</p>

               <ChCard class="demo-card">
                  <p>Content above</p>
                  <ChDivider />
                  <p>Content below</p>
               </ChCard>

               <ChCard class="demo-card">
                  <p>Content with vertical divider</p>
                  <div class="vertical-divider-demo">
                     <span>Left</span>
                     <ChDivider vertical />
                     <span>Right</span>
                  </div>
               </ChCard>
            </section>

            <!-- Input Section -->
            <section id="section-input" class="component-section">
               <h2 class="section-title">Input</h2>
               <p class="section-description">Input fields for user text entry.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Default</h3>
                  </template>
                  <ChInput v-model="inputValue" placeholder="Enter text..." />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>With Label</h3>
                  </template>
                  <ChInput v-model="inputValue" label="Email Address" placeholder="Enter email..." />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>With Error</h3>
                  </template>
                  <ChInput v-model="inputWithError" label="Username" placeholder="Enter username..."
                     error="Username is required" />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Disabled</h3>
                  </template>
                  <ChInput v-model="inputDisabled" disabled />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>With Prefix/Suffix</h3>
                  </template>
                  <div class="input-group">
                     <ChInput v-model="inputWithPrefix" placeholder="Search...">
                        <template #prefix>🔍</template>
                     </ChInput>
                     <ChInput v-model="inputWithSuffix" placeholder="Amount">
                        <template #prefix>$</template>
                        <template #suffix>.00</template>
                     </ChInput>
                  </div>
               </ChCard>
            </section>

            <!-- Textarea Section -->
            <section id="section-textarea" class="component-section">
               <h2 class="section-title">Textarea</h2>
               <p class="section-description">Multi-line text input.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Default</h3>
                  </template>
                  <ChTextarea v-model="textareaValue" placeholder="Enter description..." />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>With Character Count</h3>
                  </template>
                  <ChTextarea v-model="textareaWithCount" placeholder="Enter text..." :maxlength="200" show-count />
               </ChCard>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- FORMS COMPONENTS -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->

            <!-- Select Section -->
            <section id="section-select" class="component-section">
               <h2 class="section-title">Select</h2>
               <p class="section-description">Select dropdown for choosing options.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Single Select</h3>
                  </template>
                  <ChSelect v-model="singleSelect" :options="selectOptions" placeholder="Select an option..." />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Multi-Select</h3>
                  </template>
                  <ChSelect v-model="multiSelect" :options="selectOptions" multiple placeholder="Select multiple..." />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>With Search</h3>
                  </template>
                  <ChSelect v-model="selectWithSearch" :options="selectOptions" searchable
                     placeholder="Search and select..." />
               </ChCard>
            </section>

            <!-- Checkbox Section -->
            <section id="section-checkbox" class="component-section">
               <h2 class="section-title">Checkbox</h2>
               <p class="section-description">Checkbox inputs for boolean or multiple selection.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Single Checkbox</h3>
                  </template>
                  <ChCheckbox v-model="singleCheckbox" label="I agree to the terms" />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Checkbox Group</h3>
                  </template>
                  <ChCheckbox v-model="checkboxGroup" :options="checkboxOptions" label="Select options" />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Indeterminate</h3>
                  </template>
                  <ChCheckbox v-model="indeterminate" :indeterminate="true" label="Indeterminate checkbox" />
               </ChCard>
            </section>

            <!-- Radio Section -->
            <section id="section-radio" class="component-section">
               <h2 class="section-title">Radio</h2>
               <p class="section-description">Radio buttons for single selection.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Radio Group</h3>
                  </template>
                 <ChRadio v-model="radioValue" value="option1" label="Option 1" />
                  <ChRadio v-model="radioValue" value="option2" label="Option 2" />
                  <ChRadio v-model="radioValue" value="option3" label="Option 3" />
               </ChCard>
            </section>

            <!-- Switch Section -->
            <section id="section-switch" class="component-section">
               <h2 class="section-title">Switch</h2>
               <p class="section-description">Toggle switches for boolean values.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Default</h3>
                  </template>
                  <ChSwitch v-model="switchValue" label="Enable feature" />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>With Labels</h3>
                  </template>
                  <div class="switch-row">
                     <ChSwitch v-model="switchWithLabel" label-left="Off" label-right="On" />
                  </div>
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Disabled</h3>
                  </template>
                  <ChSwitch v-model="switchDisabled" disabled label="Disabled switch" />
               </ChCard>
            </section>

            <!-- Slider Section -->
            <section id="section-slider" class="component-section">
               <h2 class="section-title">Slider</h2>
               <p class="section-description">Sliders for selecting numeric values.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Single Value</h3>
                  </template>
                  <ChSlider v-model="sliderValue" :min="0" :max="100" />
                  <p>Value: {{ sliderValue }}</p>
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Range</h3>
                  </template>
                 <ChSlider v-model="rangeValue" :min="0" :max="100" show-value />
                  <p>Value: {{ rangeValue }}</p>
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>With Tooltip</h3>
                  </template>
                  <ChSlider v-model="sliderWithTooltip" :min="0" :max="100" show-tooltip />
                  <p>Value: {{ sliderWithTooltip }}</p>
               </ChCard>
            </section>

            <!-- File Upload Section -->
            <section id="section-file-upload" class="component-section">
               <h2 class="section-title">File Upload</h2>
               <p class="section-description">File upload component with drag and drop.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Single File</h3>
                  </template>
                  <ChFileUpload v-model="singleFile" accept="image/*" />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Multiple Files</h3>
                  </template>
                  <ChFileUpload v-model="multipleFiles" multiple accept=".pdf,.doc,.docx" />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Drag and Drop</h3>
                  </template>
                  <ChFileUpload v-model="dragDropFiles" drag-drop multiple />
               </ChCard>
            </section>

            <!-- Date Picker Section -->
            <section id="section-date-picker" class="component-section">
               <h2 class="section-title">Date Picker</h2>
               <p class="section-description">Date selection components.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Single Date</h3>
                  </template>
                  <ChDatePicker v-model="singleDate" />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Date Range</h3>
                  </template>
                  <ChDatePicker v-model="dateRange" range />
               </ChCard>
            </section>

            <!-- Modal Section -->
            <section id="section-modal" class="component-section">
               <h2 class="section-title">Modal</h2>
               <p class="section-description">Modal dialogs for focused interactions.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Modal Sizes</h3>
                  </template>
                  <div class="button-row">
                     <ChButton @click="openModal('sm')">Small</ChButton>
                     <ChButton @click="openModal('md')">Medium</ChButton>
                     <ChButton @click="openModal('lg')">Large</ChButton>
                     <ChButton @click="openModal('xl')">Extra Large</ChButton>
                     <ChButton @click="openModal('full')">Full</ChButton>
                  </div>
               </ChCard>

               <ChModal :open="modalOpen" :size="modalSize" title="Modal Title" @close="modalOpen = false">
                  <p>This is a {{ modalSize }} modal.</p>
                  <p>You can put any content here.</p>
                  <template #footer>
                     <ChButton variant="ghost" @click="modalOpen = false">Cancel</ChButton>
                     <ChButton @click="modalOpen = false">Confirm</ChButton>
                  </template>
               </ChModal>
            </section>

            <!-- Stepper Wizard Section -->
            <section id="section-stepper" class="component-section">
               <h2 class="section-title">Stepper Wizard</h2>
               <p class="section-description">Multi-step form wizard.</p>

               <ChCard class="demo-card">
                  <ChStepperWizard :wizard="stepperWizard">
                     <ChStepperStep step-id="personal" :wizard="stepperWizard">
                        <div class="step-content">
                           <ChInput v-model="stepperData.firstName" label="First Name" />
                           <ChInput v-model="stepperData.lastName" label="Last Name" />
                        </div>
                     </ChStepperStep>
                     <ChStepperStep step-id="contact" :wizard="stepperWizard">
                        <div class="step-content">
                           <ChInput v-model="stepperData.email" label="Email" type="email" />
                           <ChInput v-model="stepperData.phone" label="Phone" type="tel" />
                        </div>
                     </ChStepperStep>
                     <ChStepperStep step-id="review" :wizard="stepperWizard">
                        <div class="step-content">
                           <p><strong>First Name:</strong> {{ stepperData.firstName }}</p>
                           <p><strong>Last Name:</strong> {{ stepperData.lastName }}</p>
                           <p><strong>Email:</strong> {{ stepperData.email }}</p>
                           <p><strong>Phone:</strong> {{ stepperData.phone }}</p>
                        </div>
                     </ChStepperStep>
                  </ChStepperWizard>
               </ChCard>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- DATA COMPONENTS -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->

            <!-- Table Section -->
            <section id="section-table" class="component-section">
               <h2 class="section-title">Table</h2>
               <p class="section-description">Data tables for displaying structured information.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Basic Table</h3>
                  </template>
                  <ChTable :rows="tableData" :columns="tableColumns" @sort="handleSort" />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>With Pagination</h3>
                  </template>
                  <ChTable :rows="tableData" :columns="tableColumns" :pagination="tablePagination" @sort="handleSort"
                     @page-change="handlePageChange" />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>With Actions</h3>
                  </template>
                  <ChTable :rows="tableData" :columns="tableColumns" :actions="[
                     { label: 'Edit', action: 'edit' },
                     { label: 'Delete', action: 'delete', variant: 'danger' }
                  ]" @action="handleAction" />
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Loading State</h3>
                  </template>
                  <ChTable :rows="tableData" :columns="tableColumns" :loading="tableLoading" />
               </ChCard>
            </section>

            <!-- Stat Card Section -->
            <section id="section-stat-card" class="component-section">
               <h2 class="section-title">Stat Card</h2>
               <p class="section-description">Cards for displaying key metrics.</p>

               <div class="stat-cards-grid">
                 <ChStatCard v-for="stat in statCards" :key="stat.label" :label="stat.label" :value="stat.value"
                     :trend="stat.trend" :trend-label="stat.trendLabel" />
               </div>
            </section>

            <!-- Data List Section -->
            <section id="section-data-list" class="component-section">
               <h2 class="section-title">Data List</h2>
               <p class="section-description">List components for displaying data items.</p>

               <ChCard class="demo-card">
                  <ChDataList :items="dataListItems" />
               </ChCard>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- NAVIGATION COMPONENTS -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->

            <!-- Tabs Section -->
            <section id="section-tabs" class="component-section">
               <h2 class="section-title">Tabs</h2>
               <p class="section-description">Tab navigation for organizing content.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Horizontal Tabs</h3>
                  </template>
                  <ChTabs v-model="activeTab" :tabs="tabs" />
                  <div class="tab-content">
                     <p v-if="activeTab === 'tab1'">Content for Tab 1</p>
                     <p v-if="activeTab === 'tab2'">Content for Tab 2</p>
                     <p v-if="activeTab === 'tab3'">Content for Tab 3</p>
                  </div>
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Vertical Tabs</h3>
                  </template>
                  <div class="vertical-tabs-demo">
                     <ChTabs v-model="activeVerticalTab" :tabs="verticalTabs" vertical />
                     <div class="tab-content">
                        <p v-if="activeVerticalTab === 'vtab1'">Content for First Tab</p>
                        <p v-if="activeVerticalTab === 'vtab2'">Content for Second Tab</p>
                        <p v-if="activeVerticalTab === 'vtab3'">Content for Third Tab</p>
                     </div>
                  </div>
               </ChCard>
            </section>

            <!-- Topbar Section -->
            <section id="section-topbar" class="component-section">
               <h2 class="section-title">Topbar</h2>
               <p class="section-description">Top navigation bar.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Topbar Example</h3>
                  </template>
                  <ChTopbar v-model:search="topbarSearch" :user="topbarUser" :notifications="topbarNotifications">
                     <template #search>
                        <ChInput v-model="topbarSearch" placeholder="Search..." style="width: 300px;" />
                     </template>
                  </ChTopbar>
               </ChCard>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- FEEDBACK COMPONENTS -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->

            <!-- Toast Section -->
            <section id="section-toast" class="component-section">
               <h2 class="section-title">Toast</h2>
               <p class="section-description">Notification toasts for user feedback.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Toast Variants</h3>
                  </template>
                  <div class="button-row">
                     <ChButton variant="primary" @click="showToast('success', 'Operation completed successfully!')">
                        Success
                     </ChButton>
                     <ChButton variant="primary" @click="showToast('warning', 'Warning: Please review your input.')">
                        Warning
                     </ChButton>
                     <ChButton variant="danger" @click="showToast('danger', 'Error: Something went wrong.')">
                        Error
                     </ChButton>
                     <ChButton variant="primary" @click="showToast('info', 'Information: New updates available.')">
                        Info
                     </ChButton>
                  </div>
               </ChCard>

               <ChToastContainer />
            </section>

            <!-- Spinner Section -->
            <section id="section-spinner" class="component-section">
               <h2 class="section-title">Spinner</h2>
               <p class="section-description">Loading spinners.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Sizes</h3>
                  </template>
                  <div class="spinner-row">
                     <ChSpinner v-for="size in spinnerSizes" :key="size" :size="size" />
                  </div>
               </ChCard>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Colors</h3>
                  </template>
                  <div class="spinner-row">
                     <ChSpinner v-for="color in spinnerColors" :key="color" :color="color" />
                  </div>
               </ChCard>
            </section>

            <!-- Skeleton Section -->
            <section id="section-skeleton" class="component-section">
               <h2 class="section-title">Skeleton</h2>
               <p class="section-description">Loading skeleton placeholders.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Skeleton Examples</h3>
                  </template>
                  <div class="skeleton-demo">
                     <ChSkeleton type="text" width="200px" />
                     <ChSkeleton type="avatar" size="md" />
                     <ChSkeleton type="card" />
                  </div>
               </ChCard>
            </section>

            <!-- Progress Section -->
            <section id="section-progress" class="component-section">
               <h2 class="section-title">Progress</h2>
               <p class="section-description">Progress indicators.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Linear Progress</h3>
                  </template>
                  <ChProgress :value="progressValue" show-label />
               </ChCard>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════════ -->
            <!-- COMPOSABLES -->
            <!-- ═══════════════════════════════════════════════════════════════════ -->

            <!-- useTheme Section -->
            <section id="section-use-theme" class="component-section">
               <h2 class="section-title">useTheme</h2>
               <p class="section-description">Theme management composable.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Dark Mode Toggle</h3>
                  </template>
                  <div class="theme-demo">
                    <p>Current theme: {{ isDarkMode ? 'Dark' : 'Light' }}</p>
                     <ChButton @click="toggleDarkMode">
                        Toggle Theme
                     </ChButton>
                  </div>
               </ChCard>
            </section>

            <!-- useModal Section -->
            <section id="section-use-modal" class="component-section">
               <h2 class="section-title">useModal</h2>
               <p class="section-description">Modal management composable.</p>

               <ChCard class="demo-card">
                  <ChButton @click="openComposableModal">Open Modal</ChButton>
               </ChCard>

               <ChModal :open="composableModalOpen" title="Form Modal" @close="closeComposableModal">
                  <div class="modal-form">
                     <ChInput v-model="modalForm.name" label="Name" />
                     <ChInput v-model="modalForm.email" label="Email" type="email" />
                  </div>
                  <template #footer>
                     <ChButton variant="ghost" @click="closeComposableModal">Cancel</ChButton>
                     <ChButton @click="closeComposableModal">Submit</ChButton>
                  </template>
               </ChModal>
            </section>

            <!-- useToast Section -->
            <section id="section-use-toast" class="component-section">
               <h2 class="section-title">useToast</h2>
               <p class="section-description">Toast notification composable.</p>

               <ChCard class="demo-card">
                  <div class="button-row">
                     <ChButton @click="showToast('success', 'Success message')">Show Success</ChButton>
                     <ChButton @click="showToast('danger', 'Error message')">Show Error</ChButton>
                     <ChButton @click="showToast('warning', 'Warning message')">Show Warning</ChButton>
                     <ChButton @click="showToast('info', 'Info message')">Show Info</ChButton>
                  </div>
               </ChCard>
            </section>

            <!-- useStepperWizard Section -->
            <section id="section-use-stepper" class="component-section">
               <h2 class="section-title">useStepperWizard</h2>
               <p class="section-description">Stepper wizard composable.</p>

               <ChCard class="demo-card">
                  <div class="wizard-demo">
                     <div class="wizard-steps">
                        <div v-for="(step, index) in wizardSteps" :key="step.id" class="wizard-step"
                           :class="{ active: currentIdx === index, completed: index < currentIdx }"
                           @click="goTo(index)">
                           {{ index + 1 }}. {{ step.label }}
                        </div>
                     </div>

                     <div class="wizard-content">
                        <p>Step {{ currentIdx + 1 }} content</p>
                        <ChInput v-model="wizardData.field1" label="Field 1" />
                     </div>

                     <div class="wizard-actions">
                        <ChButton variant="ghost" :disabled="isFirst" @click="prev">
                           Previous
                        </ChButton>
                        <ChButton :disabled="isLast" @click="next">
                           Next
                        </ChButton>
                     </div>
                  </div>
               </ChCard>
            </section>

            <!-- useTableExport Section -->
            <section id="section-use-export" class="component-section">
               <h2 class="section-title">useTableExport</h2>
               <p class="section-description">Table export composable.</p>

               <ChCard class="demo-card">
                  <template #header>
                     <h3>Export Data</h3>
                  </template>
                  <div class="export-demo">
                     <p>Sample data to export:</p>
                     <pre>{{ exportData }}</pre>
                     <div class="button-row">
                        <ChButton :loading="isExporting" @click="handleExport('csv')">Export CSV</ChButton>
                       <ChButton :loading="isExporting" @click="handleExport('pdf')">Export PDF</ChButton>
                        <ChButton :loading="isExporting" @click="handleExport('excel')">Export Excel</ChButton>
                     </div>
                  </div>
               </ChCard>
            </section>

         </div>
      </main>
   </div>
</template>

<style scoped>
.design-system-demo {
   display: flex;
   min-height: 100vh;
   background: var(--ch-colors-gray-50, #f9fafb);
}

.demo-content {
   flex: 1;
   padding: 24px;
}

.demo-container {
   max-width: 1200px;
   margin: 0 auto;
}

.page-title {
   font-size: 32px;
   font-weight: 700;
   margin: 0 0 8px 0;
   color: var(--ch-colors-gray-900, #111827);
}

.page-description {
   font-size: 16px;
   color: var(--ch-colors-gray-600, #4b5563);
   margin: 0 0 24px 0;
}

.quick-nav {
   display: flex;
   flex-wrap: wrap;
   gap: 8px;
   margin-bottom: 32px;
   padding-bottom: 24px;
   border-bottom: 1px solid var(--ch-colors-gray-200, #e5e7eb);
}

.component-section {
   margin-bottom: 48px;
}

.section-title {
   font-size: 24px;
   font-weight: 600;
   margin: 0 0 8px 0;
   color: var(--ch-colors-gray-900, #111827);
}

.section-description {
   font-size: 14px;
   color: var(--ch-colors-gray-600, #4b5563);
   margin: 0 0 16px 0;
}

.demo-card {
   margin-bottom: 16px;
}

.button-row {
   display: flex;
   flex-wrap: wrap;
   gap: 12px;
   align-items: center;
}

.avatar-row {
   display: flex;
   flex-wrap: wrap;
   gap: 16px;
   align-items: center;
}

.badge-row {
   display: flex;
   flex-wrap: wrap;
   gap: 8px;
   align-items: center;
}

.vertical-divider-demo {
   display: flex;
   align-items: center;
   gap: 16px;
}

.input-group {
   display: flex;
   flex-direction: column;
   gap: 16px;
}

.switch-row {
   display: flex;
   flex-direction: column;
   gap: 16px;
}

.stat-cards-grid {
   display: grid;
   grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
   gap: 16px;
}

.vertical-tabs-demo {
   display: flex;
   gap: 24px;
}

.tab-content {
   margin-top: 16px;
   padding: 16px;
   background: var(--ch-colors-gray-50, #f9fafb);
   border-radius: 8px;
}

.spinner-row {
   display: flex;
   flex-wrap: wrap;
   gap: 24px;
   align-items: center;
}

.skeleton-demo {
   display: flex;
   flex-direction: column;
   gap: 16px;
}

.theme-demo {
   display: flex;
   flex-direction: column;
   gap: 16px;
   align-items: flex-start;
}

.modal-form {
   display: flex;
   flex-direction: column;
   gap: 16px;
}

.export-demo pre {
   background: var(--ch-colors-gray-100, #f3f4f6);
   padding: 12px;
   border-radius: 8px;
   overflow-x: auto;
}

.wizard-demo {
   display: flex;
   flex-direction: column;
   gap: 24px;
}

.wizard-steps {
   display: flex;
   gap: 8px;
}

.wizard-step {
   flex: 1;
   padding: 12px;
   text-align: center;
   background: var(--ch-colors-gray-100, #f3f4f6);
   border-radius: 8px;
   cursor: pointer;
   transition: all 0.2s;
}

.wizard-step.active {
   background: var(--ch-colors-primary-500, #3b82f6);
   color: white;
}

.wizard-step.completed {
   background: var(--ch-colors-success-100, #d1fae5);
   color: var(--ch-colors-success-600, #059669);
}

.wizard-content {
   padding: 24px;
   background: var(--ch-colors-gray-50, #f9fafb);
   border-radius: 8px;
}

.wizard-actions {
   display: flex;
   justify-content: space-between;
}

.code-block {
   background: var(--ch-colors-gray-900, #111827);
   color: var(--ch-colors-gray-100, #f3f4f6);
   padding: 16px;
   border-radius: 8px;
   overflow-x: auto;
   margin-bottom: 16px;
}

.code-block pre {
   margin: 0;
}

.code-block code {
   font-family: 'Fira Code', monospace;
   font-size: 14px;
}

.status-buttons {
   display: flex;
   gap: 8px;
   flex-wrap: wrap;
}

.step-content {
   display: flex;
   flex-direction: column;
   gap: 16px;
}

@media (max-width: 768px) {
   .demo-content {
      padding: 16px;
   }
}
</style>
