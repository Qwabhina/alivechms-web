<script setup lang="ts">
/**
 * FormsView.vue - Forms & Flows Documentation
 * 
 * Comprehensive demonstration of all form components and flow patterns
 * in the AliveCHMS brutalist-lite design system.
 * 
 * @requires lucide-vue-next for icons
 */

import { ref, computed, reactive } from 'vue'
import {
  ChSelect,
  ChCheckbox,
  ChRadio,
  ChSwitch,
  ChSlider,
  ChFileUpload,
  ChDatePicker,
  ChModal,
  ChButton,
  ChTimeline,
  ChTimelineItem,
  ChInput,
  ChDivider,
} from '@/design-system'

import {
  User,
  Users,
  Heart,
  BookOpen,
  Music,
  Home,
  Briefcase,
  GraduationCap,
  Cross,
  Baby,
  Check,
  X,
  Circle,
  UserCheck,
  UserPlus,
  Power,
  Bell,
  Shield,
  Percent,
  Upload,
  File,
  FileText,
  CalendarDays,
  AlertTriangle,
  CheckCircle,
  Loader,
  Trash,
  Settings,
  Eye,
  ChevronDown,
  CheckSquare,
  CalendarCheck,
  UserCircle,
  HeartHandshake,
  Handshake,
  Award,
  ArrowRight,
  ArrowLeft,
  Clipboard,
  Building,
  Phone,
  Banknote,
} from 'lucide-vue-next'

// Browser URL reference for file previews
const browserURL = globalThis.URL

// =============================================================================
// SECTION 1: ChSelect - Dropdown Select Component
// =============================================================================

const selectedMember = ref<string | null>(null)
const memberOptions = [
  { value: 'pastor-john', label: 'Pastor John Mensah', subtitle: 'Senior Pastor', icon: Cross },
  { value: 'elder-grace', label: 'Elder Grace Adjei', subtitle: 'Women\'s Fellowship Leader', icon: Heart },
  { value: 'deacon-michael', label: 'Deacon Michael Owusu', subtitle: 'Deacon Board Chairman', icon: Shield },
  { value: 'sister-ama', label: 'Sister Ama Boateng', subtitle: 'Choir Director', icon: Music },
  { value: 'bro-kwame', label: 'Brother Kwame Asante', subtitle: 'Youth President', icon: GraduationCap },
]

const selectedGroups = ref<string[]>([])
const groupOptions = [
  {
    group: 'Worship & Music',
    options: [
      { value: 'choir', label: 'Choir', icon: Music },
      { value: 'worship-team', label: 'Worship Team', icon: Heart },
      { value: 'instrumentalists', label: 'Instrumentalists', icon: BookOpen },
    ]
  },
  {
    group: 'Ministries',
    options: [
      { value: 'youth', label: 'Youth Ministry', icon: Users },
      { value: 'women', label: 'Women\'s Fellowship', icon: Heart },
      { value: 'men', label: 'Men\'s Fellowship', icon: Briefcase },
      { value: 'children', label: 'Children\'s Church', icon: Baby },
    ]
  },
  {
    group: 'Administration',
    options: [
      { value: 'ushering', label: 'Ushering Team', icon: User },
      { value: 'media', label: 'Media Team', icon: BookOpen },
      { value: 'hospitality', label: 'Hospitality', icon: Home },
    ]
  }
]

const searchableSelect = ref<string | null>(null)
const departmentOptions = [
  { value: 'pastorate', label: 'Pastorate' },
  { value: 'worship', label: 'Worship & Music Ministry' },
  { value: 'education', label: 'Christian Education' },
  { value: 'youth', label: 'Youth & Young Adults' },
  { value: 'children', label: 'Children\'s Ministry' },
  { value: 'women', label: 'Women\'s Ministry' },
  { value: 'men', label: 'Men\'s Ministry' },
  { value: 'outreach', label: 'Community Outreach' },
  { value: 'finance', label: 'Finance Committee' },
  { value: 'facilities', label: 'Facilities Management' },
]

const selectedStatus = ref<string>('active')
const statusOptions = [
  { value: 'active', label: 'Active Member' },
  { value: 'inactive', label: 'Inactive Member' },
  { value: 'pending', label: 'Pending Review' },
  { value: 'archived', label: 'Archived', disabled: true },
  { value: 'transferred', label: 'Transferred Out', disabled: true },
]

const smallSelect = ref('')
const mediumSelect = ref('')
const largeSelect = ref('')

// =============================================================================
// SECTION 2: ChCheckbox
// =============================================================================

const acceptTerms = ref(false)
const isActiveMember = ref(true)
const emailNotifications = ref(true)
const smsNotifications = ref(false)
const newsletterSubscription = ref(true)

const selectedMinistries = ref<string[]>(['choir', 'youth'])
const ministryOptions = [
  { value: 'choir', label: 'Choir' },
  { value: 'worship-team', label: 'Worship Team' },
  { value: 'youth', label: 'Youth Ministry' },
  { value: 'ushering', label: 'Ushering' },
  { value: 'media', label: 'Media Team' },
  { value: 'children', label: 'Children\'s Church' },
]

const selectAllVolunteers = ref(false)
const volunteerCategories = reactive({
  worship: false,
  teaching: false,
  pastoral: false,
  administrative: false,
})

const isIndeterminate = computed(() => {
  const values = Object.values(volunteerCategories)
  const selectedCount = values.filter(Boolean).length
  return selectedCount > 0 && selectedCount < values.length
})

import { watch } from 'vue'
watch(volunteerCategories, () => {
  const values = Object.values(volunteerCategories)
  const selectedCount = values.filter(Boolean).length
  if (selectedCount === 0) {
    selectAllVolunteers.value = false
  } else if (selectedCount === values.length) {
    selectAllVolunteers.value = true
  }
}, { deep: true })

function toggleAllVolunteers() {
  const newValue = !selectAllVolunteers.value
  selectAllVolunteers.value = newValue
  Object.keys(volunteerCategories).forEach(key => {
    volunteerCategories[key as keyof typeof volunteerCategories] = newValue
  })
}

const disabledCheckbox = ref(true)

const selectedDays = ref<string[]>(['sunday', 'wednesday'])
const availableDays = [
  { value: 'sunday', label: 'Sunday' },
  { value: 'monday', label: 'Monday' },
  { value: 'tuesday', label: 'Tuesday' },
  { value: 'wednesday', label: 'Wednesday' },
  { value: 'thursday', label: 'Thursday' },
  { value: 'friday', label: 'Friday' },
  { value: 'saturday', label: 'Saturday' },
]

// =============================================================================
// SECTION 3: ChRadio
// =============================================================================

const membershipType = ref('full')
const membershipOptions = [
  { value: 'full', label: 'Full Member' },
  { value: 'associate', label: 'Associate Member' },
  { value: 'probationary', label: 'Probationary' },
]

const servicePreference = ref('first')
const serviceOptions = [
  { value: 'first', label: '1st Service (6:00 AM)', description: 'Traditional hymns and shorter sermon' },
  { value: 'second', label: '2nd Service (9:00 AM)', description: 'Contemporary worship with full band' },
  { value: 'both', label: 'Both Services', description: 'Attend whichever fits your schedule' },
]

const contributionType = ref('tithe')
const contributionOptions = [
  { value: 'tithe', label: 'Tithe' },
  { value: 'offering', label: 'Offering' },
  { value: 'building', label: 'Building Fund' },
  { value: 'missions', label: 'Missions' },
]

const registrationMethod = ref('online')
const registrationOptions = [
  { value: 'online', label: 'Online Registration' },
  { value: 'in-person', label: 'In-Person at Church' },
  { value: 'phone', label: 'Phone Registration', disabled: true },
]

const selectedEvent = ref<string | null>(null)
const eventOptions = [
  { value: 'sunday-service', label: 'Sunday Service', icon: Cross, description: 'Weekly worship gathering', meta: 'Every Sunday, 6:00 AM & 9:00 AM' },
  { value: 'bible-study', label: 'Bible Study', icon: BookOpen, description: 'Midweek teaching and discussion', meta: 'Wednesdays, 6:30 PM' },
  { value: 'youth-meeting', label: 'Youth Fellowship', icon: Users, description: 'Youth gathering and activities', meta: 'Fridays, 5:00 PM' },
]

// =============================================================================
// SECTION 4: ChSwitch
// =============================================================================

const notificationsEnabled = ref(true)
const emailAlerts = ref(true)
const smsAlerts = ref(false)
const pushNotifications = ref(true)

const smallSwitch = ref(false)
const mediumSwitch = ref(true)
const largeSwitch = ref(false)

const archivedMode = ref(false)

const themeSettings = reactive({
  darkMode: false,
  highContrast: false,
  reduceMotion: false,
  compactView: false,
})

// =============================================================================
// SECTION 5: ChSlider
// =============================================================================

const volumeLevel = ref(50)
const budgetPercentage = ref(25)
const budgetSteps = [
  { value: 0, label: '0%' },
  { value: 25, label: '25%' },
  { value: 50, label: '50%' },
  { value: 75, label: '75%' },
  { value: 100, label: '100%' },
]

const attendanceGoal = ref(150)
const contributionAmount = ref(500)
const legacySetting = ref(75)

// =============================================================================
// SECTION 6: ChFileUpload
// =============================================================================

const profilePhoto = ref<File[]>([])
const documentFiles = ref<File[]>([])
const documentTypes = '.pdf,.doc,.docx,.xls,.xlsx'
const eventPhotos = ref<File[]>([])

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// =============================================================================
// SECTION 7: ChDatePicker
// =============================================================================

const eventDate = ref<Date | null>(null)
const dateRange = ref<{ start: Date | null; end: Date | null }>({ start: null, end: null })

const scheduledDate = ref<Date | null>(null)
const today = new Date()
const minDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 7)
const maxDate = new Date(today.getFullYear() + 1, today.getMonth(), today.getDate())

const blackoutDate = ref<Date | null>(null)
const disabledDates = [
  new Date(today.getFullYear(), today.getMonth(), 15),
  new Date(today.getFullYear(), today.getMonth(), 25),
]

function formatDate(date: Date): string {
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

// =============================================================================
// SECTION 8: ChModal
// =============================================================================

const basicModalOpen = ref(false)
const sizeModal = ref(false)
const sizeModalContent = ref('')
const currentModalSize = ref<'sm' | 'md' | 'lg' | 'xl' | 'full'>('md')

function openSizeModal(size: 'sm' | 'md' | 'lg' | 'xl' | 'full') {
  currentModalSize.value = size
  if (size === 'sm') {
    sizeModalContent.value = 'Small modals are ideal for quick confirmations or simple forms.'
  } else if (size === 'lg') {
    sizeModalContent.value = 'Large modals work well for detailed forms with multiple sections.'
  } else if (size === 'xl') {
    sizeModalContent.value = 'Extra large modals are perfect for complex workflows or extensive content.'
  } else if (size === 'full') {
    sizeModalContent.value = 'Full-screen modals are used for immersive experiences or complex data entry.'
  } else {
    sizeModalContent.value = 'Medium modals offer a balanced size for most use cases.'
  }
  sizeModal.value = true
}

const confirmModalOpen = ref(false)
const confirmAction = ref<'delete' | 'archive' | null>(null)
const confirmTitle = ref('')
const confirmMessage = ref('')

function openConfirmDialog(action: 'delete' | 'archive') {
  confirmAction.value = action
  if (action === 'delete') {
    confirmTitle.value = 'Delete Member Record'
    confirmMessage.value = 'Are you sure you want to permanently delete this member record? This action cannot be undone.'
  } else {
    confirmTitle.value = 'Archive Member Record'
    confirmMessage.value = 'Archiving will move this member to the inactive archive. You can restore them at any time.'
  }
  confirmModalOpen.value = true
}

const formModalOpen = ref(false)
const memberForm = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  address: '',
  dateOfBirth: null as Date | null,
  membershipDate: null as Date | null,
  group: '',
})

function openMemberForm() {
  Object.assign(memberForm, { firstName: '', lastName: '', email: '', phone: '', address: '', dateOfBirth: null, membershipDate: null, group: '' })
  formModalOpen.value = true
}

function submitMemberForm() {
  formModalOpen.value = false
  alert('Member added successfully!')
}

const asyncModalOpen = ref(false)
const isProcessing = ref(false)
const processingComplete = ref(false)

async function openAsyncModal() {
  asyncModalOpen.value = true
  isProcessing.value = true
  processingComplete.value = false
  await new Promise(resolve => setTimeout(resolve, 2000))
  isProcessing.value = false
  processingComplete.value = true
  setTimeout(() => {
    asyncModalOpen.value = false
    processingComplete.value = false
  }, 1500)
}

const scrollModalOpen = ref(false)
const longContent = Array.from({ length: 10 }, (_, i) => ({
  title: `Church Policy Section ${i + 1}`,
  content: `This is the content for section ${i + 1} of the church bylaws and policies.`
}))

const parentModalOpen = ref(false)
const childModalOpen = ref(false)

function openParentModal() { parentModalOpen.value = true }
function openChildModal() { childModalOpen.value = true }

// =============================================================================
// SECTION 9: ChStepperWizard - New Member Registration
// =============================================================================

const registrationStepper = ref(0)
const isSubmitting = ref(false)
const isSubmitted = ref(false)

const step1Data = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  dateOfBirth: null as Date | null,
})

const step2Data = reactive({
  address: '',
  city: '',
  region: '',
  postalCode: '',
  emergencyContact: '',
  emergencyPhone: '',
})

const step3Data = reactive({
  membershipType: 'full',
  groups: [] as string[],
  howDidYouHear: '',
  salvationDate: null as Date | null,
  baptized: false,
  notes: '',
})

const step4Data = reactive({
  agreedToRules: false,
  agreedToTithe: false,
  confirmed: false,
})

function canProceedFromStep(step: number): boolean {
  if (step === 0) return !!(step1Data.firstName && step1Data.lastName && step1Data.email)
  if (step === 1) return !!(step2Data.address && step2Data.city)
  if (step === 3) return step4Data.agreedToRules && step4Data.confirmed
  return true
}

function nextStep() {
  if (canProceedFromStep(registrationStepper.value) && registrationStepper.value < 3) {
    registrationStepper.value++
  }
}

function prevStep() {
  if (registrationStepper.value > 0) {
    registrationStepper.value--
  }
}

async function submitRegistration() {
  if (!canProceedFromStep(3)) return
  isSubmitting.value = true
  await new Promise(resolve => setTimeout(resolve, 2000))
  isSubmitting.value = false
  isSubmitted.value = true
}

function resetWizard() {
  registrationStepper.value = 0
  isSubmitted.value = false
  Object.assign(step1Data, { firstName: '', lastName: '', email: '', phone: '', dateOfBirth: null })
  Object.assign(step2Data, { address: '', city: '', region: '', postalCode: '', emergencyContact: '', emergencyPhone: '' })
  Object.assign(step3Data, { membershipType: 'full', groups: [], howDidYouHear: '', salvationDate: null, baptized: false, notes: '' })
  Object.assign(step4Data, { agreedToRules: false, agreedToTithe: false, confirmed: false })
}

const registrationSteps = [
  { id: 'personal', label: 'Personal Info', icon: User },
  { id: 'contact', label: 'Contact Details', icon: Phone },
  { id: 'membership', label: 'Membership', icon: Heart },
  { id: 'review', label: 'Review & Submit', icon: Clipboard },
]

// =============================================================================
// SECTION 10: ChTimeline
// =============================================================================

const eventPlanningTimeline = [
  { id: 1, title: 'Event Planning Initiated', description: 'Initial meeting with pastoral team', timestamp: new Date(2024, 0, 15), variant: 'success' as const, icon: CalendarCheck, isCurrent: false },
  { id: 2, title: 'Venue Booking Confirmed', description: 'Main auditorium reserved', timestamp: new Date(2024, 0, 22), variant: 'success' as const, icon: Building, isCurrent: false },
  { id: 3, title: 'Speaker Confirmation Pending', description: 'Awaiting confirmation from guest speaker', timestamp: new Date(2024, 1, 5), variant: 'warning' as const, icon: UserCircle, isCurrent: true },
  { id: 4, title: 'Budget Review Scheduled', description: 'Finance committee meeting', timestamp: new Date(2024, 1, 10), variant: 'info' as const, icon: Banknote, isCurrent: false },
  { id: 5, title: 'Volunteer Recruitment', description: 'Open sign-ups for volunteers', timestamp: new Date(2024, 1, 15), variant: 'info' as const, icon: Users, isCurrent: false },
  { id: 6, title: 'Marketing & Promotion', description: 'Launch social media campaign', timestamp: new Date(2024, 1, 20), variant: 'info' as const, icon: Bell, isCurrent: false },
  { id: 7, title: 'Event Execution', description: 'Fellowship Weekend 2024', timestamp: new Date(2024, 2, 1), variant: 'primary' as const, icon: HeartHandshake, isCurrent: false },
  { id: 8, title: 'Post-Event Evaluation', description: 'Review meeting', timestamp: new Date(2024, 2, 8), variant: 'info' as const, icon: Award, isCurrent: false },
]

const memberJourneyTimeline = [
  { id: 1, title: 'First Visit', description: 'Attended Sunday service as visitor', timestamp: new Date(2023, 5, 10), variant: 'info' as const, icon: UserPlus },
  { id: 2, title: 'New Believers Class', description: 'Enrolled in foundational class', timestamp: new Date(2023, 6, 1), variant: 'primary' as const, icon: GraduationCap },
  { id: 3, title: 'Water Baptism', description: 'Public declaration of faith', timestamp: new Date(2023, 8, 15), variant: 'success' as const, icon: Heart },
  { id: 4, title: 'Membership Class', description: 'Completed orientation', timestamp: new Date(2023, 9, 1), variant: 'primary' as const, icon: Award },
  { id: 5, title: 'Full Member', description: 'Received as member', timestamp: new Date(2023, 9, 15), variant: 'success' as const, icon: UserCheck },
  { id: 6, title: 'Volunteer Service', description: 'Joined ushers team', timestamp: new Date(2023, 11, 1), variant: 'info' as const, icon: Handshake },
]

// =============================================================================
// CODE EXAMPLES
// =============================================================================

const codeExamples = {
  selectBasic: `<ChSelect
  v-model="selectedMember"
  :options="memberOptions"
  placeholder="Select a member..."
/>`,
  selectMultiple: `<ChSelect
  v-model="selectedGroups"
  :options="groupOptions"
  multiple
  searchable
  placeholder="Select groups..."
/>`,
  checkboxGroup: `<ChCheckbox
  v-model="selectedMinistries"
  :options="ministryOptions"
  label="Ministry Involvement"
/>`,
  switchBasic: `<ChSwitch
  v-model="notificationsEnabled"
  label="Enable Notifications"
/>`,
  sliderBasic: `<ChSlider
  v-model="contributionAmount"
  :min="0"
  :max="5000"
  :step="100"
  show-value
  prefix="$"
/>`,
  fileUpload: `<ChFileUpload
  v-model="profilePhoto"
  accept="image/*"
  :max-size="5 * 1024 * 1024"
  label="Profile Photo"
/>`,
  datePickerRange: `<ChDatePicker
  v-model="dateRange"
  :range="true"
  placeholder-start="Start Date"
  placeholder-end="End Date"
/>`,
  modalConfirmation: `<ChModal
  v-model:open="confirmOpen"
  title="Delete Record"
  size="sm"
>
  <p>Are you sure you want to delete?</p>
  <template #footer>
    <ChButton @click="confirmOpen = false">Cancel</ChButton>
    <ChButton variant="danger" @click="confirmDelete">Delete</ChButton>
  </template>
</ChModal>`,
  stepperWizard: `<ChStepperWizard>
  <ChStepperStep step-id="personal">...</ChStepperStep>
  <ChStepperStep step-id="contact">...</ChStepperStep>
  <ChStepperStep step-id="review">...</ChStepperStep>
</ChStepperWizard>`,
  timeline: `<ChTimeline>
  <ChTimelineItem
    title="Event Started"
    timestamp="2024-01-01"
    variant="success"
  >
    Initial planning completed.
  </ChTimelineItem>
</ChTimeline>`,
}

const showCodeKey = ref<string | null>(null)
const isCodeModalOpen = ref(false)

function showCode(key: keyof typeof codeExamples) {
  showCodeKey.value = key
  isCodeModalOpen.value = true
}

function closeCodeModal() {
  isCodeModalOpen.value = false
  showCodeKey.value = null
}
</script>

<template>
  <div class="doc-page">
    <!-- Page Header -->
    <header class="page-header">
      <h1 class="page-title">Forms & Flows</h1>
      <p class="page-desc">
        Comprehensive documentation for all form components and flow patterns.
        Features extreme focus contrast and flattened bounding boxes for a brutalist aesthetic.
      </p>
    </header>

    <!-- ======================================================================= -->
    <!-- SECTION 1: ChSelect -->
    <!-- ======================================================================= -->
    <section class="doc-section">
      <div class="section-header">
        <h2 class="doc-section-title">
          <span class="section-icon">
            <ChevronDown :size="20" />
          </span>
          ChSelect - Dropdown Select
        </h2>
        <p class="section-description">
          A fully custom dropdown select with search, single/multi-select, grouped options,
          disabled states, and icon support.
        </p>
      </div>

      <!-- Basic Single Select -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Basic Single Select</h3>
          <button class="code-toggle" @click="showCode('selectBasic')">
            <FileText :size="14" /> View Code
          </button>
        </div>
        <p class="demo-description">Single selection with member data showing subtitle and icon.</p>
        <div class="demo-content">
          <div class="form-row">
            <div class="form-field">
              <label class="field-label">Select Member</label>
              <ChSelect v-model="selectedMember" :options="memberOptions" placeholder="Choose a church member..." />
            </div>
            <div class="form-field">
              <label class="field-label">Selected Value</label>
              <div class="value-display">{{ selectedMember || 'None selected' }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Multiple Select with Groups -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Multiple Select with Groups</h3>
          <button class="code-toggle" @click="showCode('selectMultiple')">
            <FileText :size="14" /> View Code
          </button>
        </div>
        <p class="demo-description">Multi-select dropdown with grouped options by category.</p>
        <div class="demo-content">
          <div class="form-row">
            <div class="form-field">
              <label class="field-label">Ministry Groups</label>
              <ChSelect v-model="selectedGroups" :options="groupOptions" multiple searchable
                placeholder="Select ministry groups..." />
            </div>
            <div class="form-field">
              <label class="field-label">Selected Groups</label>
              <div class="value-display tags-display">
                <span v-for="group in selectedGroups" :key="group" class="tag">{{ group }}</span>
                <span v-if="selectedGroups.length === 0" class="muted">None selected</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Searchable Select -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Searchable Select</h3>
        </div>
        <p class="demo-description">Type to filter options. Great for long lists like departments.</p>
        <div class="demo-content">
          <div class="form-row">
            <div class="form-field">
              <label class="field-label">Department</label>
              <ChSelect v-model="searchableSelect" :options="departmentOptions" searchable
                placeholder="Search departments..." />
            </div>
            <div class="form-field">
              <label class="field-label">Selected Value</label>
              <div class="value-display">{{ searchableSelect || 'None selected' }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Disabled Options -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">With Disabled Options</h3>
        </div>
        <p class="demo-description">Some options can be disabled to prevent selection.</p>
        <div class="demo-content">
          <div class="form-row">
            <div class="form-field">
              <label class="field-label">Member Status</label>
              <ChSelect v-model="selectedStatus" :options="statusOptions" placeholder="Select status..." />
            </div>
            <div class="form-field">
              <label class="field-label">Selected Value</label>
              <div class="value-display">{{ selectedStatus || 'None selected' }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Size Variations -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Size Variations</h3>
        </div>
        <p class="demo-description">Select components come in different sizes.</p>
        <div class="demo-content sizes-demo">
          <div class="size-item">
            <label class="field-label">Small</label>
            <ChSelect v-model="smallSelect"
              :options="[{ value: '1', label: 'Option 1' }, { value: '2', label: 'Option 2' }]" size="sm"
              placeholder="Small" />
          </div>
          <div class="size-item">
            <label class="field-label">Medium (Default)</label>
            <ChSelect v-model="mediumSelect"
              :options="[{ value: '1', label: 'Option 1' }, { value: '2', label: 'Option 2' }]" size="md"
              placeholder="Medium" />
          </div>
          <div class="size-item">
            <label class="field-label">Large</label>
            <ChSelect v-model="largeSelect"
              :options="[{ value: '1', label: 'Option 1' }, { value: '2', label: 'Option 2' }]" size="lg"
              placeholder="Large" />
          </div>
        </div>
      </div>
    </section>

    <!-- ======================================================================= -->
    <!-- SECTION 2: ChCheckbox -->
    <!-- ======================================================================= -->
    <section class="doc-section">
      <div class="section-header">
        <h2 class="doc-section-title">
          <span class="section-icon">
            <CheckSquare :size="20" />
          </span>
          ChCheckbox - Checkbox Component
        </h2>
        <p class="section-description">
          Styled checkbox supporting checked, unchecked, indeterminate, and disabled states.
        </p>
      </div>

      <!-- Basic Checkbox -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Basic Checkboxes</h3>
        </div>
        <p class="demo-description">Simple boolean checkboxes for yes/no decisions.</p>
        <div class="demo-content inline-group">
          <ChCheckbox v-model="acceptTerms" label="I accept the terms and conditions" />
          <ChCheckbox v-model="isActiveMember" label="Active Member Status" />
        </div>
      </div>

      <!-- Checkbox with Helper Text -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">With Label and Helper Text</h3>
        </div>
        <p class="demo-description">Checkboxes can include helper/hint text below the label.</p>
        <div class="demo-content checkbox-stack">
          <ChCheckbox v-model="emailNotifications" label="Email Notifications" hint="Receive updates via email" />
          <ChCheckbox v-model="smsNotifications" label="SMS Notifications"
            hint="Get text messages for urgent announcements" />
          <ChCheckbox v-model="newsletterSubscription" label="Monthly Newsletter" hint="Subscribe to our newsletter" />
        </div>
      </div>

      <!-- Checkbox Group -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Checkbox Group (Array Binding)</h3>
          <button class="code-toggle" @click="showCode('checkboxGroup')">
            <FileText :size="14" /> View Code
          </button>
        </div>
        <p class="demo-description">Multiple checkboxes bound to the same array.</p>
        <div class="demo-content">
          <ChCheckbox v-model="selectedMinistries" :options="ministryOptions" label="Ministry Involvement" />
          <div class="value-display mt-2">Selected: <strong>{{ selectedMinistries.join(', ') || 'None' }}</strong></div>
        </div>
      </div>

      <!-- Indeterminate State -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Indeterminate State (Parent/Child)</h3>
        </div>
        <p class="demo-description">Parent checkbox shows indeterminate when some children are selected.</p>
        <div class="demo-content">
          <div class="checkbox-tree">
            <ChCheckbox :model-value="selectAllVolunteers" :indeterminate="isIndeterminate"
              label="Select All Volunteer Areas" @update:model-value="toggleAllVolunteers" />
            <div class="checkbox-children">
              <ChCheckbox v-model="volunteerCategories.worship" label="Worship & Music" />
              <ChCheckbox v-model="volunteerCategories.teaching" label="Teaching & Education" />
              <ChCheckbox v-model="volunteerCategories.pastoral" label="Pastoral Care" />
              <ChCheckbox v-model="volunteerCategories.administrative" label="Administration" />
            </div>
          </div>
        </div>
      </div>

      <!-- Disabled Checkbox -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Disabled States</h3>
        </div>
        <p class="demo-description">Checkboxes can be disabled in both checked and unchecked states.</p>
        <div class="demo-content inline-group">
          <ChCheckbox :model-value="true" label="Disabled (checked)" disabled />
          <ChCheckbox :model-value="false" label="Disabled (unchecked)" disabled />
          <ChCheckbox v-model="disabledCheckbox" label="Disabled with v-model" disabled />
        </div>
      </div>

      <!-- Inline Checkboxes -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Inline Checkboxes</h3>
        </div>
        <p class="demo-description">Checkboxes can be arranged horizontally for compact layouts.</p>
        <div class="demo-content">
          <div class="inline-checkbox-group">
            <span v-for="day in availableDays" :key="day.value" class="inline-checkbox">
              <ChCheckbox v-model="selectedDays" :value="day.value" :label="day.label" />
            </span>
          </div>
          <div class="value-display mt-2">Selected days: <strong>{{ selectedDays.join(', ') || 'None' }}</strong></div>
        </div>
      </div>
    </section>

    <!-- ======================================================================= -->
    <!-- SECTION 3: ChRadio -->
    <!-- ======================================================================= -->
    <section class="doc-section">
      <div class="section-header">
        <h2 class="doc-section-title">
          <span class="section-icon">
            <Circle :size="20" />
          </span>
          ChRadio - Radio Button Component
        </h2>
        <p class="section-description">
          Radio buttons for single-choice selection within a group.
        </p>
      </div>

      <!-- Basic Radio Group -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Basic Radio Group</h3>
        </div>
        <p class="demo-description">Standard radio buttons for mutually exclusive options.</p>
        <div class="demo-content">
          <ChRadio v-model="membershipType" :value="'full'" :options="membershipOptions" label="Membership Type" />
          <div class="value-display mt-2">Selected: <strong>{{ membershipType }}</strong></div>
        </div>
      </div>

      <!-- Radio with Descriptions -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Radio with Descriptions</h3>
        </div>
        <p class="demo-description">Each option can include a description for better context.</p>
        <div class="demo-content">
          <div class="radio-options">
            <div v-for="option in serviceOptions" :key="option.value" class="radio-option"
              :class="{ 'radio-option--selected': servicePreference === option.value }">
              <input type="radio" :value="option.value" v-model="servicePreference" :id="`service-${option.value}`"
                class="radio-input" />
              <label :for="`service-${option.value}`" class="radio-label">
                <span class="radio-title">{{ option.label }}</span>
                <span class="radio-description">{{ option.description }}</span>
              </label>
            </div>
          </div>
          <div class="value-display mt-2">Selected: <strong>{{ servicePreference }}</strong></div>
        </div>
      </div>

      <!-- Inline Radio -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Inline Radio Buttons</h3>
        </div>
        <p class="demo-description">Radio buttons can be arranged horizontally for compact layouts.</p>
        <div class="demo-content">
          <label class="field-label">Contribution Type</label>
          <div class="inline-radio-group">
            <ChRadio v-for="option in contributionOptions" :key="option.value" v-model="contributionType"
              :value="option.value" :label="option.label" />
          </div>
          <div class="value-display mt-2">Selected: <strong>{{ contributionType }}</strong></div>
        </div>
      </div>

      <!-- Disabled Radio -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Disabled State</h3>
        </div>
        <p class="demo-description">Individual radio options can be disabled when not available.</p>
        <div class="demo-content">
          <div class="radio-options">
            <div v-for="option in registrationOptions" :key="option.value" class="radio-option"
              :class="{ 'radio-option--disabled': option.disabled }">
              <input type="radio" :value="option.value" v-model="registrationMethod" :id="`reg-${option.value}`"
                class="radio-input" :disabled="option.disabled" />
              <label :for="`reg-${option.value}`" class="radio-label" :class="{ 'disabled': option.disabled }">
                {{ option.label }}
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Card-Style Radio -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Card-Style Radio (Selectable Cards)</h3>
        </div>
        <p class="demo-description">Radio options styled as selectable cards with icons and metadata.</p>
        <div class="demo-content">
          <label class="field-label">Select Event to Register</label>
          <div class="card-radio-group">
            <div v-for="event in eventOptions" :key="event.value" class="card-radio"
              :class="{ 'card-radio--selected': selectedEvent === event.value }" @click="selectedEvent = event.value">
              <input type="radio" :value="event.value" v-model="selectedEvent" :id="`event-${event.value}`"
                class="sr-only" />
              <div class="card-radio-icon">
                <component :is="event.icon" :size="24" />
              </div>
              <div class="card-radio-content">
                <label :for="`event-${event.value}`" class="card-radio-label">{{ event.label }}</label>
                <p class="card-radio-description">{{ event.description }}</p>
                <span class="card-radio-meta">{{ event.meta }}</span>
              </div>
              <div class="card-radio-check">
                <CheckCircle v-if="selectedEvent === event.value" :size="20" />
                <Circle v-else :size="20" />
              </div>
            </div>
          </div>
          <div class="value-display mt-2">Selected: <strong>{{ selectedEvent || 'None' }}</strong></div>
        </div>
      </div>
    </section>

    <!-- ======================================================================= -->
    <!-- SECTION 4: ChSwitch -->
    <!-- ======================================================================= -->
    <section class="doc-section">
      <div class="section-header">
        <h2 class="doc-section-title">
          <span class="section-icon">
            <Power :size="20" />
          </span>
          ChSwitch - Toggle Switch Component
        </h2>
        <p class="section-description">
          Toggle switches for binary on/off state. Semantically similar to checkboxes but with a distinct visual
          metaphor.
        </p>
      </div>

      <!-- Basic Switch -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Basic Toggle</h3>
          <button class="code-toggle" @click="showCode('switchBasic')">
            <FileText :size="14" /> View Code
          </button>
        </div>
        <p class="demo-description">Simple on/off toggle switch.</p>
        <div class="demo-content inline-group">
          <ChSwitch v-model="notificationsEnabled" label="Enable Notifications" />
          <ChSwitch v-model="emailAlerts" label="Email Alerts" />
        </div>
      </div>

      <!-- Switch with Labels -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">With Labels and Hints</h3>
        </div>
        <p class="demo-description">Switches can include descriptive labels and hint text.</p>
        <div class="demo-content switch-stack">
          <ChSwitch v-model="emailAlerts" label="Email Notifications" hint="Receive weekly updates and announcements" />
          <ChSwitch v-model="smsAlerts" label="SMS Alerts" hint="Get text messages for urgent announcements" />
          <ChSwitch v-model="pushNotifications" label="Push Notifications"
            hint="Receive real-time updates on your device" />
        </div>
      </div>

      <!-- Size Variations -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Size Variations</h3>
        </div>
        <p class="demo-description">Switches come in small, medium, and large sizes.</p>
        <div class="demo-content sizes-demo">
          <ChSwitch v-model="smallSwitch" size="sm" label="Small Switch" />
          <ChSwitch v-model="mediumSwitch" size="md" label="Medium Switch (Default)" />
          <ChSwitch v-model="largeSwitch" size="md" label="Large Switch" />
        </div>
      </div>

      <!-- Disabled State -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Disabled States</h3>
        </div>
        <p class="demo-description">Switches can be disabled in both on and off states.</p>
        <div class="demo-content inline-group">
          <ChSwitch :model-value="true" label="Disabled (On)" disabled />
          <ChSwitch :model-value="false" label="Disabled (Off)" disabled />
          <ChSwitch v-model="archivedMode" label="Archived Mode" disabled />
        </div>
      </div>

      <!-- Settings Panel Example -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Settings Panel Example</h3>
        </div>
        <p class="demo-description">Real-world example: Notification and display settings.</p>
        <div class="demo-content">
          <div class="settings-panel">
            <div class="settings-section">
              <h4 class="settings-section-title">Notification Settings</h4>
              <ChSwitch v-model="themeSettings.darkMode" label="Dark Mode" hint="Use dark theme for the interface" />
              <ChSwitch v-model="themeSettings.highContrast" label="High Contrast"
                hint="Increase contrast for better visibility" />
            </div>
            <div class="settings-section">
              <h4 class="settings-section-title">Accessibility</h4>
              <ChSwitch v-model="themeSettings.reduceMotion" label="Reduce Motion"
                hint="Minimize animations and transitions" />
              <ChSwitch v-model="themeSettings.compactView" label="Compact View"
                hint="Show more content in less space" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ======================================================================= -->
    <!-- SECTION 5: ChSlider -->
    <!-- ======================================================================= -->
    <section class="doc-section">
      <div class="section-header">
        <h2 class="doc-section-title">
          <span class="section-icon">
            <Percent :size="20" />
          </span>
          ChSlider - Range Slider Component
        </h2>
        <p class="section-description">
          Range sliders for selecting numeric values within a min/max range.
        </p>
      </div>

      <!-- Basic Slider -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Basic Slider</h3>
        </div>
        <p class="demo-description">Simple slider for adjusting values.</p>
        <div class="demo-content">
          <label class="field-label">Volume Level</label>
          <ChSlider v-model="volumeLevel" :min="0" :max="100" />
          <div class="value-display">Value: {{ volumeLevel }}</div>
        </div>
      </div>

      <!-- Slider with Min/Max/Step -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">With Min/Max/Step</h3>
          <button class="code-toggle" @click="showCode('sliderBasic')">
            <FileText :size="14" /> View Code
          </button>
        </div>
        <p class="demo-description">Configure minimum, maximum, and step values for precise control.</p>
        <div class="demo-content">
          <label class="field-label">Budget Allocation ({{ budgetPercentage }}%)</label>
          <ChSlider v-model="budgetPercentage" :min="0" :max="100" :step="25" />
          <div class="slider-labels">
            <span v-for="step in budgetSteps" :key="step.value" class="slider-label">{{ step.label }}</span>
          </div>
        </div>
      </div>

      <!-- Slider with Value Display -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">With Value Display</h3>
        </div>
        <p class="demo-description">Display the current value with optional prefix.</p>
        <div class="demo-content">
          <label class="field-label">Contribution Amount</label>
          <ChSlider v-model="contributionAmount" :min="0" :max="5000" :step="100" show-value prefix="$" />
          <div class="value-display">Monthly contribution: ${{ contributionAmount.toLocaleString() }}</div>
        </div>
      </div>

      <!-- Attendance Goal -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Attendance Goal</h3>
        </div>
        <p class="demo-description">Set a target attendance goal for an event.</p>
        <div class="demo-content">
          <label class="field-label">Target Attendance: {{ attendanceGoal }}</label>
          <ChSlider v-model="attendanceGoal" :min="50" :max="500" :step="10" show-value />
          <div class="goal-display">
            <div class="goal-progress" :style="{ width: `${(attendanceGoal / 500) * 100}%` }"></div>
          </div>
        </div>
      </div>

      <!-- Disabled Slider -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Disabled State</h3>
        </div>
        <p class="demo-description">Slider can be disabled to prevent interaction.</p>
        <div class="demo-content">
          <label class="field-label">Legacy Setting (Disabled)</label>
          <ChSlider v-model="legacySetting" :min="0" :max="100" disabled />
          <div class="value-display muted">Value: {{ legacySetting }} (disabled)</div>
        </div>
      </div>
    </section>

    <!-- ======================================================================= -->
    <!-- SECTION 6: ChFileUpload -->
    <!-- ======================================================================= -->
    <section class="doc-section">
      <div class="section-header">
        <h2 class="doc-section-title">
          <span class="section-icon">
            <Upload :size="20" />
          </span>
          ChFileUpload - File Upload Component
        </h2>
        <p class="section-description">
          File upload drop zone with drag-and-drop support, file type validation, and file removal.
        </p>
      </div>

      <!-- Basic File Upload -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Basic File Upload</h3>
          <button class="code-toggle" @click="showCode('fileUpload')">
            <FileText :size="14" /> View Code
          </button>
        </div>
        <p class="demo-description">Single file upload with image preview.</p>
        <div class="demo-content">
          <ChFileUpload v-model="profilePhoto" accept="image/*" :max-size="5 * 1024 * 1024" label="Profile Photo"
            hint="Upload a clear photo of yourself" />
          <div v-if="profilePhoto.length > 0" class="upload-preview">
            <img v-if="profilePhoto[0]" :src="browserURL.createObjectURL(profilePhoto[0])" alt="Profile preview"
              class="preview-image" />
            <div class="preview-info">
              <strong>{{ profilePhoto[0]?.name }}</strong>
              <span>{{ formatFileSize(profilePhoto[0]?.size || 0) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Multiple Files Upload -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Multiple Files Upload</h3>
        </div>
        <p class="demo-description">Upload multiple documents with file type restrictions.</p>
        <div class="demo-content">
          <ChFileUpload v-model="documentFiles" :multiple="true" :accept="documentTypes" :max-size="10 * 1024 * 1024"
            label="Document Attachments" hint="Accepts PDF, Word, and Excel files up to 10MB" />
          <div v-if="documentFiles.length > 0" class="file-list">
            <div v-for="(file, index) in documentFiles" :key="index" class="file-item">
              <FileText :size="16" />
              <span class="file-name">{{ file.name }}</span>
              <span class="file-size">{{ formatFileSize(file.size) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Image Upload with Preview -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Image Gallery Upload</h3>
        </div>
        <p class="demo-description">Multiple image upload with thumbnail previews.</p>
        <div class="demo-content">
          <ChFileUpload v-model="eventPhotos" :multiple="true" accept="image/*" :max-size="5 * 1024 * 1024"
            label="Event Photos" hint="Upload photos from the church event" />
          <div v-if="eventPhotos.length > 0" class="image-gallery">
            <div v-for="(file, index) in eventPhotos" :key="index" class="gallery-item">
              <img :src="browserURL.createObjectURL(file)" :alt="`Photo ${index + 1}`" />
              <button class="remove-btn" @click="eventPhotos.splice(index, 1)">
                <X :size="14" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ======================================================================= -->
    <!-- SECTION 7: ChDatePicker -->
    <!-- ======================================================================= -->
    <section class="doc-section">
      <div class="section-header">
        <h2 class="doc-section-title">
          <span class="section-icon">
            <CalendarDays :size="20" />
          </span>
          ChDatePicker - Date Selection Component
        </h2>
        <p class="section-description">
          Calendar popup date picker with optional date range mode and min/max date constraints.
        </p>
      </div>

      <!-- Basic Date Picker -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Basic Date Picker</h3>
        </div>
        <p class="demo-description">Single date selection with calendar popup.</p>
        <div class="demo-content">
          <div class="form-row">
            <div class="form-field">
              <label class="field-label">Select Event Date</label>
              <ChDatePicker v-model="eventDate" placeholder="Choose a date..." />
            </div>
            <div class="form-field">
              <label class="field-label">Selected Date</label>
              <div class="value-display">{{ eventDate ? formatDate(eventDate) : 'No date selected' }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Date Range Picker -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Date Range Picker</h3>
          <button class="code-toggle" @click="showCode('datePickerRange')">
            <FileText :size="14" /> View Code
          </button>
        </div>
        <p class="demo-description">Select a range of dates for events spanning multiple days.</p>
        <div class="demo-content">
          <ChDatePicker v-model="dateRange" :range="true" placeholder-start="Start Date" placeholder-end="End Date" label="Event Period" />
          <div class="value-display mt-2">
            <span v-if="dateRange.start && dateRange.end">{{ formatDate(dateRange.start) }} - {{ formatDate(dateRange.end) }}</span>
            <span v-else>No range selected</span>
          </div>
        </div>
      </div>

      <!-- With Min/Max Dates -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">With Min/Max Date Constraints</h3>
        </div>
        <p class="demo-description">Restrict date selection to a specific range.</p>
        <div class="demo-content">
          <label class="field-label">Schedule Date <span class="field-hint">(Must be between {{ formatDate(minDate) }}
              and {{ formatDate(maxDate) }})</span></label>
          <ChDatePicker v-model="scheduledDate" :min-date="minDate" :max-date="maxDate"
            placeholder="Select a future date..." />
          <div class="value-display mt-2">{{ scheduledDate ? formatDate(scheduledDate) : 'No date selected' }}</div>
        </div>
      </div>

      <!-- Disabled Dates -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Disabled Specific Dates</h3>
        </div>
        <p class="demo-description">Disable specific dates (e.g., holidays, blacked-out days).</p>
        <div class="demo-content">
          <label class="field-label">Select Date (Some dates disabled)</label>
          <ChDatePicker v-model="blackoutDate" :disabled-dates="disabledDates" placeholder="Select a date..." />
          <div class="value-display mt-2 muted">Disabled dates: {{disabledDates.map(d => formatDate(d)).join(', ')}}
          </div>
        </div>
      </div>
    </section>

    <!-- ======================================================================= -->
    <!-- SECTION 8: ChModal -->
    <!-- ======================================================================= -->
    <section class="doc-section">
      <div class="section-header">
        <h2 class="doc-section-title">
          <span class="section-icon">
            <Eye :size="20" />
          </span>
          ChModal - Modal Dialog Component
        </h2>
        <p class="section-description">
          Accessible dialog modals with focus trapping, scroll lock, and multiple sizes.
        </p>
      </div>

      <!-- Basic Modal -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Basic Modal</h3>
          <button class="code-toggle" @click="showCode('modalConfirmation')">
            <FileText :size="14" /> View Code
          </button>
        </div>
        <p class="demo-description">Open and close a basic modal dialog.</p>
        <div class="demo-content">
          <ChButton @click="basicModalOpen = true">
            <template #icon>
              <Eye :size="16" />
            </template>
            Open Basic Modal
          </ChButton>
        </div>
      </div>

      <!-- Modal Sizes -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Modal Sizes</h3>
        </div>
        <p class="demo-description">Choose from multiple sizes: sm, md, lg, xl, and full.</p>
        <div class="demo-content button-group">
          <ChButton size="sm" @click="openSizeModal('sm')">Small Modal</ChButton>
          <ChButton @click="openSizeModal('md')">Medium Modal</ChButton>
          <ChButton size="lg" @click="openSizeModal('lg')">Large Modal</ChButton>
          <ChButton size="lg" @click="openSizeModal('xl')">Extra Large</ChButton>
          <ChButton variant="outline" @click="openSizeModal('full')">Full Screen</ChButton>
        </div>
      </div>

      <!-- Confirmation Dialog -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Confirmation Dialog (Danger)</h3>
        </div>
        <p class="demo-description">Modal for destructive actions requiring confirmation.</p>
        <div class="demo-content button-group">
          <ChButton variant="danger" @click="openConfirmDialog('delete')">
            <template #icon>
              <Trash :size="16" />
            </template>
            Delete Record
          </ChButton>
          <ChButton variant="outline" @click="openConfirmDialog('archive')">
            <template #icon>
              <FileText :size="16" />
            </template>
            Archive Record
          </ChButton>
        </div>
      </div>

      <!-- Form Inside Modal -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Form Inside Modal</h3>
        </div>
        <p class="demo-description">Modal containing a complete form for data entry.</p>
        <div class="demo-content">
          <ChButton @click="openMemberForm">
            <template #icon>
              <UserPlus :size="16" />
            </template>
            Add New Member
          </ChButton>
        </div>
      </div>

      <!-- Async Action Modal -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Modal with Async Action</h3>
        </div>
        <p class="demo-description">Modal showing loading state during async operations.</p>
        <div class="demo-content">
          <ChButton @click="openAsyncModal">
            <template #icon>
              <Settings :size="16" />
            </template>
            Process Data
          </ChButton>
        </div>
      </div>

      <!-- Scrollable Content -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Scrollable Content</h3>
        </div>
        <p class="demo-description">Modal with long scrollable content.</p>
        <div class="demo-content">
          <ChButton @click="scrollModalOpen = true">
            <template #icon>
              <BookOpen :size="16" />
            </template>
            View Church Bylaws
          </ChButton>
        </div>
      </div>

      <!-- Nested Modals -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Nested Modals</h3>
        </div>
        <p class="demo-description">Modal that opens another modal on top.</p>
        <div class="demo-content">
          <ChButton @click="openParentModal">
            <template #icon>
              <Settings :size="16" />
            </template>
            Open Settings
          </ChButton>
        </div>
      </div>
    </section>

    <!-- ======================================================================= -->
    <!-- SECTION 9: ChStepperWizard -->
    <!-- ======================================================================= -->
    <section class="doc-section">
      <div class="section-header">
        <h2 class="doc-section-title">
          <span class="section-icon">
            <Clipboard :size="20" />
          </span>
          ChStepperWizard - Multi-Step Workflow
        </h2>
        <p class="section-description">
          Multi-step wizard for complex forms and workflows.
          Demonstrating a real-world New Member Registration process.
        </p>
      </div>

      <div class="demo-block full-width">
        <div class="demo-header">
          <h3 class="demo-title">New Member Registration Wizard</h3>
          <button class="code-toggle" @click="showCode('stepperWizard')">
            <FileText :size="14" /> View Code
          </button>
        </div>

        <!-- Success State -->
        <div v-if="isSubmitted" class="success-state">
          <div class="success-icon">
            <CheckCircle :size="64" />
          </div>
          <h3>Registration Complete!</h3>
          <p>Welcome to the church family, {{ step1Data.firstName }}!</p>
          <p class="muted">Your information has been submitted for review.</p>
          <ChButton @click="resetWizard">Register Another Member</ChButton>
        </div>

        <!-- Wizard Steps Navigation -->
        <div v-else class="wizard-container">
          <!-- Step Indicators -->
          <div class="wizard-steps">
            <div v-for="(step, index) in registrationSteps" :key="step.id" class="wizard-step"
              :class="{ 'wizard-step--active': index === registrationStepper, 'wizard-step--completed': index < registrationStepper }">
              <div class="step-indicator">
                <Check v-if="index < registrationStepper" :size="16" />
                <component v-else :is="step.icon" :size="16" />
              </div>
              <span class="step-label">{{ step.label }}</span>
              <div v-if="index < registrationSteps.length - 1" class="step-connector"></div>
            </div>
          </div>

          <!-- Step Content -->
          <div class="wizard-content">
            <!-- Step 1: Personal Information -->
            <div v-if="registrationStepper === 0" class="wizard-step-content">
              <h4>Personal Information</h4>
              <p class="step-description">Enter the member's basic personal details.</p>
              <div class="form-grid">
                <div class="form-field">
                  <label class="field-label required">First Name</label>
                  <ChInput v-model="step1Data.firstName" placeholder="Enter first name" />
                </div>
                <div class="form-field">
                  <label class="field-label required">Last Name</label>
                  <ChInput v-model="step1Data.lastName" placeholder="Enter last name" />
                </div>
                <div class="form-field full-width">
                  <label class="field-label required">Email Address</label>
                  <ChInput v-model="step1Data.email" type="email" placeholder="email@example.com" />
                </div>
                <div class="form-field">
                  <label class="field-label">Phone Number</label>
                  <ChInput v-model="step1Data.phone" type="tel" placeholder="+233 XX XXX XXXX" />
                </div>
                <div class="form-field">
                  <label class="field-label">Date of Birth</label>
                  <ChDatePicker v-model="step1Data.dateOfBirth" placeholder="Select date of birth" />
                </div>
              </div>
            </div>

            <!-- Step 2: Contact Details -->
            <div v-if="registrationStepper === 1" class="wizard-step-content">
              <h4>Contact Details</h4>
              <p class="step-description">Provide the member's address and emergency contact.</p>
              <div class="form-grid">
                <div class="form-field full-width">
                  <label class="field-label required">Street Address</label>
                  <ChInput v-model="step2Data.address" placeholder="House number and street" />
                </div>
                <div class="form-field">
                  <label class="field-label required">City/Town</label>
                  <ChInput v-model="step2Data.city" placeholder="Enter city" />
                </div>
                <div class="form-field">
                  <label class="field-label">Region</label>
                  <ChInput v-model="step2Data.region" placeholder="Enter region" />
                </div>
                <div class="form-field">
                  <label class="field-label">Emergency Contact Name</label>
                  <ChInput v-model="step2Data.emergencyContact" placeholder="Contact person name" />
                </div>
                <div class="form-field">
                  <label class="field-label">Emergency Contact Phone</label>
                  <ChInput v-model="step2Data.emergencyPhone" type="tel" placeholder="+233 XX XXX XXXX" />
                </div>
              </div>
            </div>

            <!-- Step 3: Membership Preferences -->
            <div v-if="registrationStepper === 2" class="wizard-step-content">
              <h4>Membership Preferences</h4>
              <p class="step-description">Select membership type and ministry involvement.</p>
              <div class="form-grid">
                <div class="form-field full-width">
                  <label class="field-label">Membership Type</label>
                  <div class="radio-options">
                    <label class="radio-option"
                      :class="{ 'radio-option--selected': step3Data.membershipType === 'full' }">
                      <input type="radio" value="full" v-model="step3Data.membershipType" />
                      <span>Full Member</span>
                    </label>
                    <label class="radio-option"
                      :class="{ 'radio-option--selected': step3Data.membershipType === 'associate' }">
                      <input type="radio" value="associate" v-model="step3Data.membershipType" />
                      <span>Associate Member</span>
                    </label>
                  </div>
                </div>
                <div class="form-field full-width">
                  <label class="field-label">How did you hear about us?</label>
                  <ChSelect v-model="step3Data.howDidYouHear"
                    :options="[{ value: 'friend', label: 'Friend or Family' }, { value: 'social', label: 'Social Media' }, { value: 'search', label: 'Search Engine' }, { value: 'event', label: 'Church Event' }, { value: 'other', label: 'Other' }]"
                    placeholder="Select option..." />
                </div>
                <div class="form-field">
                  <label class="field-label">Date of Salvation (if applicable)</label>
                  <ChDatePicker v-model="step3Data.salvationDate" placeholder="Select date" />
                </div>
                <div class="form-field">
                  <ChCheckbox v-model="step3Data.baptized" label="Water baptized" />
                </div>
              </div>
            </div>

            <!-- Step 4: Review & Submit -->
            <div v-if="registrationStepper === 3" class="wizard-step-content">
              <h4>Review & Submit</h4>
              <p class="step-description">Please review the information before submitting.</p>

              <div class="review-sections">
                <div class="review-section">
                  <h5>Personal Information</h5>
                  <div class="review-grid">
                    <div class="review-item"><span class="review-label">Name</span><span class="review-value">{{
                      step1Data.firstName }} {{ step1Data.lastName }}</span></div>
                    <div class="review-item"><span class="review-label">Email</span><span class="review-value">{{
                      step1Data.email }}</span></div>
                    <div class="review-item"><span class="review-label">Phone</span><span class="review-value">{{
                      step1Data.phone || 'Not provided' }}</span></div>
                  </div>
                </div>
                <div class="review-section">
                  <h5>Contact Details</h5>
                  <div class="review-grid">
                    <div class="review-item"><span class="review-label">Address</span><span class="review-value">{{
                      step2Data.address }}, {{ step2Data.city }}</span></div>
                    <div class="review-item"><span class="review-label">Emergency Contact</span><span
                        class="review-value">{{ step2Data.emergencyContact || 'Not provided' }}</span></div>
                  </div>
                </div>
                <div class="review-section">
                  <h5>Membership</h5>
                  <div class="review-grid">
                    <div class="review-item"><span class="review-label">Type</span><span class="review-value">{{
                      step3Data.membershipType === 'full' ? 'Full Member' : 'Associate Member' }}</span></div>
                    <div class="review-item"><span class="review-label">Baptized</span><span class="review-value">{{
                      step3Data.baptized ? 'Yes' : 'No' }}</span></div>
                  </div>
                </div>
              </div>

              <div class="agreements">
                <ChCheckbox v-model="step4Data.agreedToRules"
                  label="I agree to abide by the church constitution and bylaws" />
                <ChCheckbox v-model="step4Data.agreedToTithe" label="I understand the biblical principle of tithing" />
                <ChCheckbox v-model="step4Data.confirmed" label="I confirm that all information provided is accurate" />
              </div>
            </div>
          </div>

          <!-- Wizard Navigation -->
          <div class="wizard-navigation">
            <ChButton v-if="registrationStepper > 0" variant="outline" @click="prevStep">
              <template #icon>
                <ArrowLeft :size="16" />
              </template>
              Back
            </ChButton>
            <div v-else></div>

            <ChButton v-if="registrationStepper < 3" @click="nextStep"
              :disabled="!canProceedFromStep(registrationStepper)">
              Next
              <template #trailingIcon>
                <ArrowRight :size="16" />
              </template>
            </ChButton>
            <ChButton v-else variant="primary" :loading="isSubmitting" @click="submitRegistration"
              :disabled="!canProceedFromStep(3)">
              <template #icon>
                <Check :size="16" />
              </template>
              Submit Registration
            </ChButton>
          </div>
        </div>
      </div>
    </section>

    <!-- ======================================================================= -->
    <!-- SECTION 10: ChTimeline -->
    <!-- ======================================================================= -->
    <section class="doc-section">
      <div class="section-header">
        <h2 class="doc-section-title">
          <span class="section-icon">
            <CalendarCheck :size="20" />
          </span>
          ChTimeline - Timeline Component
        </h2>
        <p class="section-description">
          Vertical timeline for displaying chronological events, audit logs, and activity feeds.
        </p>
      </div>

      <!-- Event Planning Timeline -->
      <div class="demo-block full-width">
        <div class="demo-header">
          <h3 class="demo-title">Event Planning Timeline</h3>
          <button class="code-toggle" @click="showCode('timeline')">
            <FileText :size="14" /> View Code
          </button>
        </div>
        <p class="demo-description">Real-world example showing the stages of planning a church fellowship weekend.</p>

        <div class="timeline-container">
          <ChTimeline>
            <ChTimelineItem v-for="item in eventPlanningTimeline" :key="item.id" :title="item.title"
              :timestamp="formatDate(item.timestamp)" :variant="item.variant" :is-current="item.isCurrent">
              <template #icon>
                <component :is="item.icon" :size="14" />
              </template>
              {{ item.description }}
            </ChTimelineItem>
          </ChTimeline>
        </div>
      </div>

      <!-- Member Journey Timeline -->
      <div class="demo-block full-width">
        <div class="demo-header">
          <h3 class="demo-title">Member Journey Timeline</h3>
        </div>
        <p class="demo-description">Alternative example showing a member's journey from first visit to full membership.
        </p>

        <div class="timeline-container">
          <ChTimeline>
            <ChTimelineItem v-for="item in memberJourneyTimeline" :key="item.id" :title="item.title"
              :timestamp="formatDate(item.timestamp)" :variant="item.variant">
              <template #icon>
                <component :is="item.icon" :size="14" />
              </template>
              {{ item.description }}
            </ChTimelineItem>
          </ChTimeline>
        </div>
      </div>

      <!-- Timeline Variants -->
      <div class="demo-block">
        <div class="demo-header">
          <h3 class="demo-title">Timeline Variants</h3>
        </div>
        <p class="demo-description">Different color variants for status indication.</p>

        <div class="variants-grid">
          <div class="variant-item">
            <ChTimeline>
              <ChTimelineItem title="Success Event" timestamp="2024-01-15" variant="success">Task completed successfully
              </ChTimelineItem>
            </ChTimeline>
          </div>
          <div class="variant-item">
            <ChTimeline>
              <ChTimelineItem title="Warning Event" timestamp="2024-01-15" variant="warning">Attention needed
              </ChTimelineItem>
            </ChTimeline>
          </div>
          <div class="variant-item">
            <ChTimeline>
              <ChTimelineItem title="Danger Event" timestamp="2024-01-15" variant="danger">Critical issue
              </ChTimelineItem>
            </ChTimeline>
          </div>
          <div class="variant-item">
            <ChTimeline>
              <ChTimelineItem title="Info Event" timestamp="2024-01-15" variant="info">General information
              </ChTimelineItem>
            </ChTimeline>
          </div>
        </div>
      </div>
    </section>

    <!-- ======================================================================= -->
    <!-- MODALS -->
    <!-- ======================================================================= -->

    <!-- Basic Modal -->
    <ChModal v-model:open="basicModalOpen" title="Basic Modal" subtitle="A simple modal dialog example">
      <p>This is a basic modal with a title, subtitle, and content area. Modals are used to focus user attention on a
        specific task.</p>
      <p class="mt-2">Click outside the modal or use the buttons below to close it.</p>
      <template #footer>
        <ChButton variant="ghost" @click="basicModalOpen = false">Cancel</ChButton>
        <ChButton variant="primary" @click="basicModalOpen = false">Got It</ChButton>
      </template>
    </ChModal>

    <!-- Size Modal -->
    <ChModal v-model:open="sizeModal" :title="`${currentModalSize.toUpperCase()} Modal`"
      :subtitle="`Size: ${currentModalSize}`" :size="currentModalSize">
      <p>{{ sizeModalContent }}</p>
      <template #footer>
        <ChButton variant="ghost" @click="sizeModal = false">Close</ChButton>
      </template>
    </ChModal>

    <!-- Confirmation Modal -->
    <ChModal v-model:open="confirmModalOpen" :title="confirmTitle" size="sm">
      <div class="confirm-content">
        <AlertTriangle v-if="confirmAction === 'delete'" class="confirm-icon danger" :size="48" />
        <FileText v-else class="confirm-icon warning" :size="48" />
        <p>{{ confirmMessage }}</p>
      </div>
      <template #footer>
        <ChButton variant="ghost" @click="confirmModalOpen = false">Cancel</ChButton>
        <ChButton :variant="confirmAction === 'delete' ? 'danger' : 'primary'" @click="confirmModalOpen = false">
          {{ confirmAction === 'delete' ? 'Delete' : 'Archive' }}
        </ChButton>
      </template>
    </ChModal>

    <!-- Member Form Modal -->
    <ChModal v-model:open="formModalOpen" title="Add New Member" subtitle="Enter member information" size="lg">
      <div class="modal-form">
        <div class="form-grid">
          <div class="form-field"><label class="field-label required">First Name</label>
            <ChInput v-model="memberForm.firstName" placeholder="Enter first name" />
          </div>
          <div class="form-field"><label class="field-label required">Last Name</label>
            <ChInput v-model="memberForm.lastName" placeholder="Enter last name" />
          </div>
          <div class="form-field"><label class="field-label required">Email</label>
            <ChInput v-model="memberForm.email" type="email" placeholder="email@example.com" />
          </div>
          <div class="form-field"><label class="field-label">Phone</label>
            <ChInput v-model="memberForm.phone" type="tel" placeholder="+233 XX XXX XXXX" />
          </div>
          <div class="form-field full-width"><label class="field-label">Address</label>
            <ChInput v-model="memberForm.address" placeholder="Enter address" />
          </div>
          <div class="form-field"><label class="field-label">Date of Birth</label>
            <ChDatePicker v-model="memberForm.dateOfBirth" placeholder="Select date" />
          </div>
          <div class="form-field"><label class="field-label">Membership Date</label>
            <ChDatePicker v-model="memberForm.membershipDate" placeholder="Select date" />
          </div>
        </div>
      </div>
      <template #footer>
        <ChButton variant="ghost" @click="formModalOpen = false">Cancel</ChButton>
        <ChButton variant="primary" @click="submitMemberForm">Add Member</ChButton>
      </template>
    </ChModal>

    <!-- Async Action Modal -->
    <ChModal v-model:open="asyncModalOpen" title="Processing Data" size="sm" :hide-close="isProcessing">
      <div class="async-content">
        <div v-if="isProcessing" class="processing-state">
          <Loader class="spinner" :size="48" />
          <p>Processing your request...</p>
        </div>
        <div v-else-if="processingComplete" class="success-state">
          <CheckCircle class="success-icon-small" :size="48" />
          <p>Process completed successfully!</p>
        </div>
      </div>
    </ChModal>

    <!-- Scrollable Content Modal -->
    <ChModal v-model:open="scrollModalOpen" title="Church Bylaws & Policies"
      subtitle="Version 2.0 - Last Updated January 2024" size="lg">
      <div class="scrollable-content">
        <div v-for="(section, index) in longContent" :key="index" class="policy-section">
          <h4>{{ section.title }}</h4>
          <p>{{ section.content }}</p>
          <ChDivider v-if="index < longContent.length - 1" />
        </div>
      </div>
      <template #footer>
        <ChButton variant="ghost" @click="scrollModalOpen = false">Close</ChButton>
        <ChButton variant="primary">Accept Policies</ChButton>
      </template>
    </ChModal>

    <!-- Parent Modal -->
    <ChModal v-model:open="parentModalOpen" title="Settings" subtitle="Configure your preferences" size="md">
      <p>Configure application settings here.</p>
      <div class="settings-preview">
        <ChSwitch v-model="themeSettings.darkMode" label="Dark Mode" />
        <ChSwitch v-model="themeSettings.highContrast" label="High Contrast" />
      </div>
      <template #footer>
        <ChButton variant="ghost" @click="parentModalOpen = false">Cancel</ChButton>
        <ChButton variant="outline" @click="openChildModal">Advanced Settings</ChButton>
        <ChButton variant="primary" @click="parentModalOpen = false">Save</ChButton>
      </template>
    </ChModal>

    <!-- Child Modal (Nested) -->
    <ChModal v-model:open="childModalOpen" title="Advanced Settings" subtitle="Fine-tune application behavior"
      size="sm">
      <p>Advanced configuration options.</p>
      <div class="settings-preview">
        <ChSwitch v-model="themeSettings.reduceMotion" label="Reduce Motion" />
        <ChSwitch v-model="themeSettings.compactView" label="Compact View" />
      </div>
      <template #footer>
        <ChButton variant="ghost" @click="childModalOpen = false">Back</ChButton>
        <ChButton variant="primary" @click="childModalOpen = false">Apply</ChButton>
      </template>
    </ChModal>

    <!-- Code Example Modal -->
    <ChModal v-model:open="isCodeModalOpen" title="Code Example" size="lg">
      <div v-if="showCodeKey" class="code-display">
        <pre><code>{{ codeExamples[showCodeKey as keyof typeof codeExamples] }}</code></pre>
      </div>
      <template #footer>
        <ChButton variant="ghost" @click="closeCodeModal">Close</ChButton>
      </template>
    </ChModal>
  </div>
</template>

<style scoped>
/* ============================================================
   PAGE LAYOUT
   ============================================================ */

.doc-page {
  padding: var(--ch-space-8, 2rem);
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: var(--ch-space-8, 2rem);
  padding-bottom: var(--ch-space-6, 1.5rem);
  border-bottom: 2px solid var(--ch-color-border-strong, #000);
}

.page-title {
  font-family: var(--ch-font-display, system-ui);
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: var(--ch-space-2, 0.5rem);
  text-transform: uppercase;
  letter-spacing: -0.02em;
}

.page-desc {
  font-size: 1.1rem;
  color: var(--ch-color-text-muted, #666);
  max-width: 700px;
}

/* ============================================================
   SECTIONS
   ============================================================ */

.doc-section {
  margin-bottom: var(--ch-space-12, 3rem);
}

.section-header {
  margin-bottom: var(--ch-space-6, 1.5rem);
  padding-bottom: var(--ch-space-4, 1rem);
  border-bottom: 1px solid var(--ch-color-border, #ddd);
}

.doc-section-title {
  font-family: var(--ch-font-display, system-ui);
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: var(--ch-space-2, 0.5rem);
  display: flex;
  align-items: center;
  gap: var(--ch-space-2, 0.5rem);
}

.section-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: var(--ch-color-primary, #000);
  color: var(--ch-color-bg, #fff);
}

.section-description {
  color: var(--ch-color-text-muted, #666);
  font-size: 0.95rem;
}

/* ============================================================
   DEMO BLOCKS
   ============================================================ */

.demo-block {
  background: var(--ch-color-surface, #fff);
  border: 2px solid var(--ch-color-border-strong, #000);
  padding: var(--ch-space-6, 1.5rem);
  margin-bottom: var(--ch-space-4, 1rem);
  box-shadow: 4px 4px 0 var(--ch-color-border-strong, #000);
}

.demo-block.full-width {
  grid-column: 1 / -1;
}

.demo-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--ch-space-3, 0.75rem);
  padding-bottom: var(--ch-space-3, 0.75rem);
  border-bottom: 1px solid var(--ch-color-border, #ddd);
}

.demo-title {
  font-size: 1.1rem;
  font-weight: 600;
}

.demo-description {
  color: var(--ch-color-text-muted, #666);
  font-size: 0.9rem;
  margin-bottom: var(--ch-space-4, 1rem);
}

.demo-content {
  padding: var(--ch-space-4, 1rem) 0;
}

/* ============================================================
   FORM LAYOUT
   ============================================================ */

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--ch-space-4, 1rem);
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-1, 0.25rem);
}

.form-field.full-width {
  grid-column: 1 / -1;
}

.field-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--ch-color-text, #000);
}

.field-label.required::after {
  content: ' *';
  color: var(--ch-color-danger, #dc2626);
}

.field-hint {
  font-weight: 400;
  color: var(--ch-color-text-muted, #666);
  font-size: 0.8rem;
}

.value-display {
  padding: var(--ch-space-2, 0.5rem);
  background: var(--ch-color-bg-subtle, #f5f5f5);
  border: 1px solid var(--ch-color-border, #ddd);
  font-family: monospace;
  font-size: 0.875rem;
}

.value-display.muted {
  color: var(--ch-color-text-muted, #666);
}

.value-display.mt-2 {
  margin-top: var(--ch-space-2, 0.5rem);
}

.tags-display {
  display: flex;
  flex-wrap: wrap;
  gap: var(--ch-space-1, 0.25rem);
}

.tag {
  display: inline-block;
  padding: var(--ch-space-1, 0.25rem) var(--ch-space-2, 0.5rem);
  background: var(--ch-color-primary, #000);
  color: var(--ch-color-bg, #fff);
  font-size: 0.75rem;
  text-transform: uppercase;
}

.sizes-demo {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4, 1rem);
}

/* ============================================================
   CHECKBOX STYLES
   ============================================================ */

.checkbox-stack {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-3, 0.75rem);
}

.inline-group {
  display: flex;
  flex-wrap: wrap;
  gap: var(--ch-space-4, 1rem);
  align-items: flex-start;
}

.checkbox-tree {
  padding-left: var(--ch-space-4, 1rem);
  border-left: 2px solid var(--ch-color-border, #ddd);
}

.checkbox-children {
  padding-left: var(--ch-space-6, 1.5rem);
  margin-top: var(--ch-space-2, 0.5rem);
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2, 0.5rem);
}

.inline-checkbox-group {
  display: flex;
  flex-wrap: wrap;
  gap: var(--ch-space-4, 1rem);
}

.mt-2 {
  margin-top: var(--ch-space-2, 0.5rem);
}

/* ============================================================
   RADIO STYLES
   ============================================================ */

.radio-options {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2, 0.5rem);
}

.radio-option {
  display: flex;
  align-items: flex-start;
  gap: var(--ch-space-2, 0.5rem);
  padding: var(--ch-space-3, 0.75rem);
  border: 2px solid var(--ch-color-border, #ddd);
  cursor: pointer;
  transition: all 0.15s;
}

.radio-option:hover {
  border-color: var(--ch-color-border-strong, #000);
}

.radio-option--selected {
  border-color: var(--ch-color-primary, #000);
  background: var(--ch-color-bg-subtle, #f5f5f5);
}

.radio-option--disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.radio-input {
  margin-top: var(--ch-space-1, 0.25rem);
  accent-color: var(--ch-color-primary, #000);
}

.radio-label {
  flex: 1;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-1, 0.25rem);
}

.radio-label.disabled {
  color: var(--ch-color-text-muted, #666);
  cursor: not-allowed;
}

.radio-title {
  font-weight: 500;
}

.radio-description {
  font-size: 0.875rem;
  color: var(--ch-color-text-muted, #666);
}

.inline-radio-group {
  display: flex;
  flex-wrap: wrap;
  gap: var(--ch-space-4, 1rem);
}

/* Card Radio */
.card-radio-group {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-3, 0.75rem);
}

.card-radio {
  display: flex;
  align-items: center;
  gap: var(--ch-space-4, 1rem);
  padding: var(--ch-space-4, 1rem);
  border: 2px solid var(--ch-color-border, #ddd);
  cursor: pointer;
  transition: all 0.15s;
}

.card-radio:hover {
  border-color: var(--ch-color-border-strong, #000);
}

.card-radio--selected {
  border-color: var(--ch-color-primary, #000);
  background: var(--ch-color-bg-subtle, #f5f5f5);
}

.card-radio-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ch-color-primary-muted, #e5e5e5);
  flex-shrink: 0;
}

.card-radio--selected .card-radio-icon {
  background: var(--ch-color-primary, #000);
  color: var(--ch-color-bg, #fff);
}

.card-radio-content {
  flex: 1;
}

.card-radio-label {
  font-weight: 600;
  display: block;
  cursor: pointer;
}

.card-radio-description {
  font-size: 0.875rem;
  color: var(--ch-color-text-muted, #666);
  margin: var(--ch-space-1, 0.25rem) 0;
}

.card-radio-meta {
  font-size: 0.75rem;
  color: var(--ch-color-text-subtle, #999);
}

.card-radio-check {
  color: var(--ch-color-primary, #000);
  flex-shrink: 0;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* ============================================================
   SWITCH STYLES
   ============================================================ */

.switch-stack {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4, 1rem);
}

.settings-panel {
  border: 2px solid var(--ch-color-border, #ddd);
}

.settings-section {
  padding: var(--ch-space-4, 1rem);
  border-bottom: 1px solid var(--ch-color-border, #ddd);
}

.settings-section:last-child {
  border-bottom: none;
}

.settings-section-title {
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: var(--ch-space-3, 0.75rem);
  color: var(--ch-color-text-muted, #666);
}

/* ============================================================
   SLIDER STYLES
   ============================================================ */

.slider-labels {
  display: flex;
  justify-content: space-between;
  margin-top: var(--ch-space-2, 0.5rem);
  font-size: 0.75rem;
  color: var(--ch-color-text-muted, #666);
}

.goal-display {
  height: 8px;
  background: var(--ch-color-border, #ddd);
  margin-top: var(--ch-space-3, 0.75rem);
}

.goal-progress {
  height: 100%;
  background: var(--ch-color-primary, #000);
  transition: width 0.3s ease;
}

/* ============================================================
   FILE UPLOAD STYLES
   ============================================================ */

.upload-preview {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3, 0.75rem);
  margin-top: var(--ch-space-3, 0.75rem);
  padding: var(--ch-space-3, 0.75rem);
  background: var(--ch-color-bg-subtle, #f5f5f5);
  border: 1px solid var(--ch-color-border, #ddd);
}

.preview-image {
  width: 64px;
  height: 64px;
  object-fit: cover;
  border: 2px solid var(--ch-color-border, #ddd);
}

.preview-info {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-1, 0.25rem);
}

.file-list {
  margin-top: var(--ch-space-3, 0.75rem);
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2, 0.5rem);
}

.file-item {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2, 0.5rem);
  padding: var(--ch-space-2, 0.5rem);
  background: var(--ch-color-bg-subtle, #f5f5f5);
  border: 1px solid var(--ch-color-border, #ddd);
}

.file-name {
  flex: 1;
  font-weight: 500;
}

.file-size {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted, #666);
}

.image-gallery {
  display: flex;
  flex-wrap: wrap;
  gap: var(--ch-space-2, 0.5rem);
  margin-top: var(--ch-space-3, 0.75rem);
}

.gallery-item {
  position: relative;
  width: 100px;
  height: 100px;
}

.gallery-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border: 2px solid var(--ch-color-border, #ddd);
}

.gallery-item .remove-btn {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--ch-color-danger, #dc2626);
  color: white;
  border: 2px solid var(--ch-color-surface, #fff);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ============================================================
   BUTTON STYLES
   ============================================================ */

.code-toggle {
  display: inline-flex;
  align-items: center;
  gap: var(--ch-space-1, 0.25rem);
  padding: var(--ch-space-1, 0.25rem) var(--ch-space-2, 0.5rem);
  background: var(--ch-color-bg-subtle, #f5f5f5);
  border: 1px solid var(--ch-color-border, #ddd);
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.15s;
}

.code-toggle:hover {
  background: var(--ch-color-border, #ddd);
}

.button-group {
  display: flex;
  flex-wrap: wrap;
  gap: var(--ch-space-2, 0.5rem);
}

/* ============================================================
   MODAL STYLES
   ============================================================ */

.confirm-content {
  text-align: center;
  padding: var(--ch-space-4, 1rem);
}

.confirm-icon {
  margin-bottom: var(--ch-space-4, 1rem);
}

.confirm-icon.danger {
  color: var(--ch-color-danger, #dc2626);
}

.confirm-icon.warning {
  color: var(--ch-color-warning, #f59e0b);
}

.modal-form {
  padding: var(--ch-space-2, 0.5rem) 0;
}

.async-content {
  text-align: center;
  padding: var(--ch-space-6, 1.5rem);
}

.processing-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--ch-space-4, 1rem);
}

.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

.scrollable-content {
  max-height: 400px;
  overflow-y: auto;
}

.policy-section {
  padding: var(--ch-space-4, 1rem) 0;
}

.policy-section h4 {
  margin-bottom: var(--ch-space-2, 0.5rem);
}

.settings-preview {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-3, 0.75rem);
  margin-top: var(--ch-space-4, 1rem);
}

.code-display {
  background: var(--ch-color-bg-subtle, #f5f5f5);
  padding: var(--ch-space-4, 1rem);
  overflow-x: auto;
}

.code-display pre {
  margin: 0;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.code-display code {
  font-family: 'Courier New', monospace;
  font-size: 0.875rem;
}

/* ============================================================
   STEPPER WIZARD STYLES
   ============================================================ */

.wizard-container {
  padding: var(--ch-space-4, 1rem) 0;
}

.wizard-steps {
  display: flex;
  justify-content: space-between;
  margin-bottom: var(--ch-space-8, 2rem);
  position: relative;
}

.wizard-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--ch-space-2, 0.5rem);
  position: relative;
  flex: 1;
}

.step-indicator {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--ch-color-bg-subtle, #f5f5f5);
  border: 2px solid var(--ch-color-border, #ddd);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
  transition: all 0.2s;
}

.wizard-step--active .step-indicator {
  background: var(--ch-color-primary, #000);
  border-color: var(--ch-color-primary, #000);
  color: var(--ch-color-bg, #fff);
}

.wizard-step--completed .step-indicator {
  background: var(--ch-color-success, #16a34a);
  border-color: var(--ch-color-success, #16a34a);
  color: var(--ch-color-bg, #fff);
}

.step-label {
  font-size: 0.75rem;
  font-weight: 500;
  text-align: center;
  color: var(--ch-color-text-muted, #666);
}

.wizard-step--active .step-label {
  color: var(--ch-color-text, #000);
  font-weight: 600;
}

.step-connector {
  position: absolute;
  top: 20px;
  left: calc(50% + 20px);
  width: calc(100% - 40px);
  height: 2px;
  background: var(--ch-color-border, #ddd);
  z-index: 0;
}

.wizard-step--completed .step-connector {
  background: var(--ch-color-success, #16a34a);
}

.wizard-content {
  min-height: 300px;
  padding: var(--ch-space-6, 1.5rem);
  background: var(--ch-color-bg-subtle, #f5f5f5);
  border: 2px solid var(--ch-color-border, #ddd);
  margin-bottom: var(--ch-space-4, 1rem);
}

.wizard-step-content h4 {
  margin-bottom: var(--ch-space-2, 0.5rem);
}

.step-description {
  color: var(--ch-color-text-muted, #666);
  margin-bottom: var(--ch-space-6, 1.5rem);
}

.wizard-navigation {
  display: flex;
  justify-content: space-between;
  padding-top: var(--ch-space-4, 1rem);
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--ch-space-4, 1rem);
}

/* Review Section */
.review-sections {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4, 1rem);
  margin-bottom: var(--ch-space-6, 1.5rem);
}

.review-section {
  background: var(--ch-color-surface, #fff);
  border: 1px solid var(--ch-color-border, #ddd);
  padding: var(--ch-space-4, 1rem);
}

.review-section h5 {
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: var(--ch-space-3, 0.75rem);
  color: var(--ch-color-text-muted, #666);
}

.review-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--ch-space-3, 0.75rem);
}

.review-item {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-1, 0.25rem);
}

.review-label {
  font-size: 0.75rem;
  color: var(--ch-color-text-muted, #666);
}

.review-value {
  font-weight: 500;
}

.agreements {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-3, 0.75rem);
  padding-top: var(--ch-space-4, 1rem);
  border-top: 1px solid var(--ch-color-border, #ddd);
}

/* Success State */
.success-state {
  text-align: center;
  padding: var(--ch-space-8, 2rem);
}

.success-icon {
  color: var(--ch-color-success, #16a34a);
  margin-bottom: var(--ch-space-4, 1rem);
}

.success-icon-small {
  color: var(--ch-color-success, #16a34a);
}

/* ============================================================
   TIMELINE STYLES
   ============================================================ */

.timeline-container {
  padding: var(--ch-space-4, 1rem) 0;
}

.variants-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: var(--ch-space-4, 1rem);
}

.variant-item {
  min-width: 0;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .review-grid {
    grid-template-columns: 1fr;
  }

  .variants-grid {
    grid-template-columns: 1fr;
  }

  .wizard-steps {
    flex-direction: column;
    gap: var(--ch-space-4, 1rem);
  }

  .step-connector {
    display: none;
  }

  .wizard-step {
    flex-direction: row;
    gap: var(--ch-space-3, 0.75rem);
  }

  .step-label {
    text-align: left;
  }
}
</style>