<script setup lang="ts">
import { useRouter } from 'vue-router'
import { memberService } from '@/services/member.service'
import {
  useToast,
  ChPageHeader,
  useStepperWizard,
  ChStepperWizard,
  ChStepperStep,
  ChCard,
  ChFormField,
  ChInput,
  ChSelect,
  ChDatePicker,
  ChTextarea,
  ChFileUpload,
  ChButton,
} from '@/design-system'
import type { MemberCreate, MemberLookupData } from '@/types/member'
import { ArrowLeft, Plus, Trash2 } from '@lucide/vue'
import { ref, reactive, onMounted } from 'vue'

// ─── Helpers ──────────────────────────────────────────────────────────────────

function toIsoDate(date: Date | null): string | undefined {
  if (!date) return undefined
  return date.toISOString().slice(0, 10)
}

function getApiError(err: unknown, fallback: string): string {
  if (err !== null && typeof err === 'object' && 'response' in err) {
    const resp = (err as { response?: { data?: { message?: string } } }).response
    if (resp?.data?.message) return resp.data.message
  }
  return fallback
}

// ─── Setup ────────────────────────────────────────────────────────────────────

const router = useRouter()
const toast = useToast()

// ─── Form state ───────────────────────────────────────────────────────────────

const form = reactive({
  // Personal Info
  first_name: '',
  family_name: '',
  other_names: '',
  gender: null as 'Male' | 'Female' | 'Other' | null,
  date_of_birth: null as Date | null,
  occupation: '',
  
  // Contact Info
  email_address: '',
  address: '',
  
  // Membership
  marital_status_id: null as number | null,
  education_level_id: null as number | null,
  membership_status_id: null as number | null,
  branch_id: null as number | null,
  
  // System Access (optional)
  create_account: false,
  username: '',
  password: '',
})

const profileFiles = ref<File[]>([])
const phones = ref([{ number: '', type_id: null as number | null, is_primary: true }])
const isSubmitting = ref(false)
const lookupData = ref<MemberLookupData | null>(null)

// Per-step errors
const stepErrors = reactive({
  personal: {} as Record<string, string>,
  contact: {} as Record<string, string>,
  membership: {} as Record<string, string>,
  access: {} as Record<string, string>,
})

// ─── Phone management ─────────────────────────────────────────────────────────

function addPhone() {
  phones.value.push({ number: '', type_id: null, is_primary: false })
}

function removePhone(i: number) {
  if (phones.value.length > 1) phones.value.splice(i, 1)
}

function setPrimary(i: number) {
  phones.value.forEach((p, idx) => {
    p.is_primary = idx === i
  })
}

// ─── Step Validations ─────────────────────────────────────────────────────────

function clearStepErrors(step: keyof typeof stepErrors) {
  Object.keys(stepErrors[step]).forEach((k) => delete stepErrors[step][k])
}

function validatePersonalStep(): boolean | string {
  clearStepErrors('personal')
  let valid = true
  
  if (!form.first_name.trim()) {
    stepErrors.personal.first_name = 'First name is required.'
    valid = false
  }
  if (!form.family_name.trim()) {
    stepErrors.personal.family_name = 'Family name is required.'
    valid = false
  }
  
  return valid || 'Please complete all required fields.'
}

function validateContactStep(): boolean | string {
  clearStepErrors('contact')
  let valid = true
  
  if (!form.email_address.trim()) {
    stepErrors.contact.email_address = 'Email address is required.'
    valid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email_address)) {
    stepErrors.contact.email_address = 'Please enter a valid email address.'
    valid = false
  }
  
  // At least one phone number is required
  const hasValidPhone = phones.value.some(p => p.number.trim())
  if (!hasValidPhone) {
    stepErrors.contact.phone = 'At least one phone number is required.'
    valid = false
  }
  
  return valid || 'Please complete all required fields.'
}

function validateMembershipStep(): boolean | string {
  clearStepErrors('membership')
  // All membership fields are optional
  return true
}

function validateAccessStep(): boolean | string {
  clearStepErrors('access')
  
  // Step is optional, skip validation if not creating account
  if (!form.create_account) return true
  
  let valid = true
  
  if (!form.username.trim()) {
    stepErrors.access.username = 'Username is required when creating an account.'
    valid = false
  }
  if (!form.password) {
    stepErrors.access.password = 'Password is required when creating an account.'
    valid = false
  } else if (form.password.length < 8) {
    stepErrors.access.password = 'Password must be at least 8 characters.'
    valid = false
  }
  
  return valid || 'Please complete all required fields or skip this step.'
}

// ─── Stepper Wizard ───────────────────────────────────────────────────────────

const wizard = useStepperWizard([
  { id: 'personal', label: 'Personal Info', validate: validatePersonalStep },
  { id: 'contact', label: 'Contact Info', validate: validateContactStep },
  { id: 'membership', label: 'Membership', validate: validateMembershipStep },
  { id: 'access', label: 'System Access', validate: validateAccessStep, optional: true },
])

// ─── Submit ───────────────────────────────────────────────────────────────────

async function handleFinish() {
  isSubmitting.value = true
  try {
    const phoneNumbers = phones.value
      .filter((p) => p.number.trim())
      .map((p) => ({
        number: p.number.trim(),
        type_id: p.type_id ?? undefined,
        is_primary: p.is_primary,
      }))

    const profileFile = profileFiles.value[0] ?? null

    const basePayload: MemberCreate = {
      first_name: form.first_name,
      family_name: form.family_name,
      email_address: form.email_address,
      other_names: form.other_names || undefined,
      gender: form.gender ?? undefined,
      date_of_birth: toIsoDate(form.date_of_birth),
      occupation: form.occupation || undefined,
      address: form.address || undefined,
      marital_status_id: form.marital_status_id ?? undefined,
      education_level_id: form.education_level_id ?? undefined,
      membership_status_id: form.membership_status_id ?? undefined,
      branch_id: form.branch_id ?? undefined,
      phone_numbers: phoneNumbers.length ? phoneNumbers : undefined,
      // Add authentication details if creating account
      ...(form.create_account && {
        username: form.username,
        password: form.password,
      }),
    }

    if (profileFile) {
      const fd = new FormData()
      fd.append('first_name', basePayload.first_name)
      fd.append('family_name', basePayload.family_name)
      fd.append('email_address', basePayload.email_address)
      if (basePayload.other_names) fd.append('other_names', basePayload.other_names)
      if (basePayload.gender) fd.append('gender', basePayload.gender)
      if (basePayload.date_of_birth) fd.append('date_of_birth', basePayload.date_of_birth)
      if (basePayload.occupation) fd.append('occupation', basePayload.occupation)
      if (basePayload.address) fd.append('address', basePayload.address)
      if (basePayload.marital_status_id) fd.append('marital_status_id', String(basePayload.marital_status_id))
      if (basePayload.education_level_id) fd.append('education_level_id', String(basePayload.education_level_id))
      if (basePayload.membership_status_id) fd.append('membership_status_id', String(basePayload.membership_status_id))
      if (basePayload.branch_id) fd.append('branch_id', String(basePayload.branch_id))
      if (basePayload.phone_numbers) fd.append('phone_numbers', JSON.stringify(basePayload.phone_numbers))
      if (basePayload.username) fd.append('username', basePayload.username)
      if (basePayload.password) fd.append('password', basePayload.password)
      fd.append('profile_picture', profileFile)
      
      const res = await memberService.createWithPhoto(fd)
      toast.success('Member added successfully.')
      if (res?.data?.mbr_id) router.push(`/members/${res.data.mbr_id}`)
    } else {
      const res = await memberService.create(basePayload)
      toast.success('Member added successfully.')
      if (res?.data?.mbr_id) router.push(`/members/${res.data.mbr_id}`)
    }
  } catch (err: unknown) {
    toast.error(getApiError(err, 'Failed to add member. Please try again.'))
  } finally {
    isSubmitting.value = false
  }
}

function handleCancel() {
  router.push('/members')
}

// ─── Lookup data ──────────────────────────────────────────────────────────────

async function loadLookupData() {
  try {
    const res = await memberService.getLookupData()
    if (res?.data) lookupData.value = res.data
  } catch {
    toast.error('Failed to load form data.')
  }
}

onMounted(loadLookupData)
</script>

<template>
  <div class="view">
    <ChPageHeader title="Add New Member" subtitle="Register a new member into the church directory.">
      <template #leading>
        <ChButton variant="ghost" size="sm" @click="handleCancel">
          <template #icon><ArrowLeft :size="16" /></template>
          Members
        </ChButton>
      </template>
    </ChPageHeader>

    <ChStepperWizard
      :wizard="wizard"
      finish-label="Add Member"
      back-label="Back"
      next-label="Next"
      :show-progress="true"
      @finish="handleFinish"
      @cancel="handleCancel"
    >
      <template #cancel>
        <ChButton variant="ghost" @click="handleCancel">Cancel</ChButton>
      </template>

      <!-- Step 1: Personal Information -->
      <ChStepperStep step-id="personal" :wizard="wizard">
        <ChCard shadow="sm">
          <template #header>
            <span class="section-title">Personal Information</span>
          </template>

          <div class="form-grid">
            <!-- First name -->
            <ChFormField
              label="First Name"
              input-id="first-name"
              :required="true"
              :error="stepErrors.personal.first_name"
            >
              <ChInput
                id="first-name"
                v-model="form.first_name"
                placeholder="Enter first name"
                :error="!!stepErrors.personal.first_name"
              />
            </ChFormField>

            <!-- Family name -->
            <ChFormField
              label="Family Name"
              input-id="family-name"
              :required="true"
              :error="stepErrors.personal.family_name"
            >
              <ChInput
                id="family-name"
                v-model="form.family_name"
                placeholder="Enter family name"
                :error="!!stepErrors.personal.family_name"
              />
            </ChFormField>

            <!-- Other names -->
            <ChFormField label="Other Names" input-id="other-names" class="full-width">
              <ChInput
                id="other-names"
                v-model="form.other_names"
                placeholder="Middle name or nickname (optional)"
              />
            </ChFormField>

            <!-- Gender -->
            <ChFormField label="Gender" input-id="gender">
              <ChSelect
                v-model="form.gender"
                :options="[
                  { value: 'Male', label: 'Male' },
                  { value: 'Female', label: 'Female' },
                  { value: 'Other', label: 'Other' },
                ]"
                placeholder="Select gender"
                size="md"
              />
            </ChFormField>

            <!-- Date of birth -->
            <ChFormField label="Date of Birth" input-id="dob">
              <ChDatePicker
                v-model="form.date_of_birth"
                placeholder="Select date of birth"
                display-format="dd/mm/yyyy"
              />
            </ChFormField>

            <!-- Occupation -->
            <ChFormField label="Occupation" input-id="occupation" class="full-width">
              <ChInput
                id="occupation"
                v-model="form.occupation"
                placeholder="Enter occupation (optional)"
              />
            </ChFormField>
          </div>
        </ChCard>
      </ChStepperStep>

      <!-- Step 2: Contact Information -->
      <ChStepperStep step-id="contact" :wizard="wizard">
        <ChCard shadow="sm">
          <template #header>
            <span class="section-title">Contact Information</span>
          </template>

          <div class="form-grid">
            <!-- Email -->
            <ChFormField
              label="Email Address"
              input-id="email"
              :required="true"
              :error="stepErrors.contact.email_address"
              class="full-width"
            >
              <ChInput
                id="email"
                v-model="form.email_address"
                type="email"
                placeholder="name@example.com"
                :error="!!stepErrors.contact.email_address"
              />
            </ChFormField>

            <!-- Address -->
            <ChFormField label="Residential Address" input-id="address" class="full-width">
              <ChTextarea v-model="form.address" placeholder="Enter residential address" :rows="3" />
            </ChFormField>
          </div>

          <!-- Phone numbers -->
          <div class="phone-section">
            <div class="phone-section__header">
              <span class="phone-section__label">Phone Numbers</span>
              <ChButton type="button" size="sm" variant="ghost" @click="addPhone">
                <template #icon><Plus :size="14" /></template>
                Add Phone
              </ChButton>
            </div>

            <div v-if="stepErrors.contact.phone" class="step-error">
              {{ stepErrors.contact.phone }}
            </div>

            <div class="phone-list">
              <div v-for="(phone, i) in phones" :key="i" class="phone-row">
                <div class="phone-row__number">
                  <ChInput v-model="phone.number" type="tel" placeholder="+233 24 000 0000" />
                </div>

                <div class="phone-row__type">
                  <ChSelect
                    v-model="phone.type_id"
                    :options="
                      lookupData?.phone_types.map((t) => ({ value: t.id, label: t.name })) ?? []
                    "
                    placeholder="Type"
                    size="md"
                  />
                </div>

                <div class="phone-row__actions">
                  <ChButton
                    type="button"
                    size="sm"
                    :variant="phone.is_primary ? 'primary' : 'ghost'"
                    @click="setPrimary(i)"
                  >
                    {{ phone.is_primary ? 'Primary' : 'Set Primary' }}
                  </ChButton>
                  <ChButton
                    type="button"
                    size="sm"
                    variant="ghost"
                    :disabled="phones.length === 1"
                    @click="removePhone(i)"
                  >
                    <Trash2 :size="14" />
                  </ChButton>
                </div>
              </div>
            </div>
          </div>
        </ChCard>
      </ChStepperStep>

      <!-- Step 3: Membership Details -->
      <ChStepperStep step-id="membership" :wizard="wizard">
        <ChCard shadow="sm">
          <template #header>
            <span class="section-title">Membership Details</span>
          </template>

          <div class="form-grid">
            <!-- Membership status -->
            <ChFormField label="Membership Status" input-id="membership-status">
              <ChSelect
                v-model="form.membership_status_id"
                :options="
                  lookupData?.membership_statuses.map((s) => ({ value: s.id, label: s.name })) ?? []
                "
                placeholder="Select status"
                :searchable="true"
                size="md"
              />
            </ChFormField>

            <!-- Branch -->
            <ChFormField label="Branch" input-id="branch">
              <ChSelect
                v-model="form.branch_id"
                :options="lookupData?.branches.map((b) => ({ value: b.id, label: b.name })) ?? []"
                placeholder="Select branch"
                :searchable="true"
                size="md"
              />
            </ChFormField>

            <!-- Marital status -->
            <ChFormField label="Marital Status" input-id="marital-status">
              <ChSelect
                v-model="form.marital_status_id"
                :options="
                  lookupData?.marital_statuses.map((s) => ({ value: s.id, label: s.name })) ?? []
                "
                placeholder="Select marital status"
                size="md"
              />
            </ChFormField>

            <!-- Education level -->
            <ChFormField label="Education Level" input-id="education-level">
              <ChSelect
                v-model="form.education_level_id"
                :options="
                  lookupData?.education_levels.map((e) => ({ value: e.id, label: e.name })) ?? []
                "
                placeholder="Select education level"
                size="md"
              />
            </ChFormField>
          </div>
        </ChCard>

        <!-- Profile Picture -->
        <ChCard shadow="sm" class="mt-4">
          <template #header>
            <span class="section-title">Profile Picture</span>
          </template>

          <ChFileUpload
            v-model="profileFiles"
            accept="image/jpeg,image/png,image/webp"
            :max-size="2 * 1024 * 1024"
            label="Profile Picture"
            drop-text="Drag & drop a photo here"
            sub-text="JPEG, PNG or WebP — max 2 MB"
            button-text="Select Photo"
          />
        </ChCard>
      </ChStepperStep>

      <!-- Step 4: System Access (Optional) -->
      <ChStepperStep step-id="access" :wizard="wizard">
        <ChCard shadow="sm">
          <template #header>
            <span class="section-title">System Access (Optional)</span>
          </template>

          <div class="access-intro">
            <p class="access-description">
              Create login credentials for this member to access the system. 
              This step is optional — you can skip it and the member will be registered without system access.
            </p>
            
            <div class="create-account-toggle">
              <label class="toggle-label">
                <input
                  v-model="form.create_account"
                  type="checkbox"
                  class="toggle-input"
                />
                <span class="toggle-text">Create system account for this member</span>
              </label>
            </div>
          </div>

          <div v-if="form.create_account" class="form-grid mt-4">
            <!-- Username -->
            <ChFormField
              label="Username"
              input-id="username"
              :required="form.create_account"
              :error="stepErrors.access.username"
            >
              <ChInput
                id="username"
                v-model="form.username"
                placeholder="Enter username"
                :error="!!stepErrors.access.username"
              />
            </ChFormField>

            <!-- Password -->
            <ChFormField
              label="Password"
              input-id="password"
              :required="form.create_account"
              :error="stepErrors.access.password"
            >
              <ChInput
                id="password"
                v-model="form.password"
                type="password"
                placeholder="Enter password (min 8 characters)"
                :error="!!stepErrors.access.password"
              />
            </ChFormField>
          </div>
        </ChCard>
      </ChStepperStep>
    </ChStepperWizard>

    <!-- Loading overlay for submission -->
    <div v-if="isSubmitting" class="loading-overlay">
      <div class="loading-content">
        <span class="loading-spinner"></span>
        <span>Adding member...</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Layout ─────────────────────────────────────────────────────────────── */
.view {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
  max-width: 860px;
  margin: 0 auto;
}

/* ── Header ─────────────────────────────────────────────────────────────── */
.view-header {
  display: flex;
  align-items: flex-start;
  gap: var(--ch-space-4);
  flex-wrap: wrap;
}

.view-header__title-block {
  flex: 1;
}

.view-title {
  font-size: var(--ch-text-2xl);
  font-weight: var(--ch-font-bold);
  font-family: var(--ch-font-display);
  color: var(--ch-color-text);
  margin: 0;
}

.view-subtitle {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin: var(--ch-space-1) 0 0;
}

/* ── Form layout ────────────────────────────────────────────────────────── */
.form-layout {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-5);
}

/* ── Section header title ───────────────────────────────────────────────── */
.section-title {
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

/* ── Two-column form grid ───────────────────────────────────────────────── */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--ch-space-4);
}

.form-grid .full-width {
  grid-column: 1 / -1;
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-grid .full-width {
    grid-column: 1;
  }
}

/* ── Phone section ──────────────────────────────────────────────────────── */
.phone-section {
  margin-top: var(--ch-space-5);
  border-top: 1px solid var(--ch-color-border-strong);
  padding-top: var(--ch-space-5);
}

.phone-section__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--ch-space-3);
}

.phone-section__label {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
}

.phone-list {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-3);
}

.phone-row {
  display: grid;
  grid-template-columns: 1fr 160px auto;
  gap: var(--ch-space-3);
  align-items: center;
}

@media (max-width: 640px) {
  .phone-row {
    grid-template-columns: 1fr;
  }
}

.phone-row__actions {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  flex-shrink: 0;
}

/* ── Form actions bar ───────────────────────────────────────────────────── */
.form-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--ch-space-3);
  padding-bottom: var(--ch-space-6);
}

/* ── Step error ─────────────────────────────────────────────────────────── */
.step-error {
  color: var(--ch-color-danger);
  font-size: var(--ch-text-sm);
  margin-bottom: var(--ch-space-3);
}

/* ── Access section ─────────────────────────────────────────────────────── */
.access-intro {
  margin-bottom: var(--ch-space-4);
}

.access-description {
  color: var(--ch-color-text-muted);
  font-size: var(--ch-text-sm);
  margin-bottom: var(--ch-space-4);
  line-height: 1.5;
}

.create-account-toggle {
  padding: var(--ch-space-3);
  background: var(--ch-color-surface-elevated);
  border-radius: var(--ch-radius-md);
  border: 1px solid var(--ch-color-border);
}

.toggle-label {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  cursor: pointer;
}

.toggle-input {
  width: 18px;
  height: 18px;
  accent-color: var(--ch-color-primary);
}

.toggle-text {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
}

.mt-4 {
  margin-top: var(--ch-space-4);
}

/* ── Loading overlay ─────────────────────────────────────────────────────── */
.loading-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.loading-content {
  background: var(--ch-color-surface);
  padding: var(--ch-space-6);
  border-radius: var(--ch-radius-lg);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--ch-space-3);
  box-shadow: var(--ch-shadow-lg);
}

.loading-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--ch-color-border);
  border-top-color: var(--ch-color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
