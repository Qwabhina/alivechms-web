<script setup lang="ts">
import { useRouter, useRoute } from 'vue-router'
import { memberService } from '@/services/member.service'
import { useToast } from '@/design-system'
import type { MemberLookupData, MemberUpdate } from '@/types/member'
import { ArrowLeft, Save, Plus, Trash2 } from 'lucide-vue-next'

// ─── Helpers ──────────────────────────────────────────────────────────────────

function toIsoDate(date: Date | null): string | undefined {
  if (!date) return undefined
  return date.toISOString().slice(0, 10)
}

function isNotFound(err: unknown): boolean {
  if (err !== null && typeof err === 'object' && 'response' in err) {
    const resp = (err as { response?: { status?: number } }).response
    return resp?.status === 404
  }
  return false
}

function getApiError(err: unknown, fallback: string): string {
  if (err !== null && typeof err === 'object' && 'response' in err) {
    const resp = (err as { response?: { data?: { message?: string } } }).response
    if (resp?.data?.message) return resp.data.message
  }
  return fallback
}

// ─── Setup ────────────────────────────────────────────────────────────────────

const route = useRoute()
const router = useRouter()
const toast = useToast()

const memberId = computed(() => Number(route.params.id))

// ─── Form state ───────────────────────────────────────────────────────────────

const form = reactive({
  first_name: '',
  family_name: '',
  other_names: '',
  email_address: '',
  gender: null as 'Male' | 'Female' | 'Other' | null,
  occupation: '',
  address: '',
  marital_status_id: null as number | null,
  education_level_id: null as number | null,
  membership_status_id: null as number | null,
  branch_id: null as number | null,
})

const dobDate = ref<Date | null>(null)
const profileFiles = ref<File[]>([])
const currentProfilePicture = ref<string | null>(null)
const currentMemberName = ref('')
const phones = ref([{ number: '', type_id: null as number | null, is_primary: true }])
const isLoading = ref(true)
const isSubmitting = ref(false)
const lookupData = ref<MemberLookupData | null>(null)
const errors = reactive<Record<string, string>>({})

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

// ─── Validation ───────────────────────────────────────────────────────────────

function validate(): boolean {
  Object.keys(errors).forEach((k) => delete errors[k])
  if (!form.first_name.trim()) errors.first_name = 'First name is required.'
  if (!form.family_name.trim()) errors.family_name = 'Family name is required.'
  if (!form.email_address.trim()) {
    errors.email_address = 'Email address is required.'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email_address)) {
    errors.email_address = 'Please enter a valid email address.'
  }
  return Object.keys(errors).length === 0
}

// ─── Load data ────────────────────────────────────────────────────────────────

async function loadData() {
  isLoading.value = true
  try {
    const [memberRes, lookupRes] = await Promise.all([
      memberService.getById(memberId.value),
      memberService.getLookupData(),
    ])

    const m = memberRes?.data
    const lu = lookupRes?.data
    if (!m || !lu) {
      toast.error('Failed to load member data.')
      router.push('/members')
      return
    }
    lookupData.value = lu

    // Track current name for display
    currentMemberName.value = `${m.MbrFirstName} ${m.MbrFamilyName}`

    // Populate form fields
    form.first_name = m.MbrFirstName
    form.family_name = m.MbrFamilyName
    form.other_names = m.MbrOtherNames ?? ''
    form.email_address = m.MbrEmailAddress
    form.gender = m.MbrGender ?? null
    form.occupation = m.MbrOccupation ?? ''
    form.address = m.MbrResidentialAddress ?? ''
    form.marital_status_id = m.MbrMaritalStatusID
    form.education_level_id = m.MbrEducationLevelID
    form.membership_status_id = m.MbrMembershipStatusID
    form.branch_id = m.BranchID

    // Date of birth
    dobDate.value = m.MbrDateOfBirth ? new Date(m.MbrDateOfBirth) : null

    // Store current profile picture URL for preview
    currentProfilePicture.value = m.MbrProfilePicture

    // Populate phones
    if (m.phones && m.phones.length > 0) {
      phones.value = m.phones.map((p: any) => ({
        number: p.PhoneNumber,
        type_id: p.PhoneTypeID ?? null,
        is_primary: p.IsPrimary === 1,
      }))
    }
  } catch (err: unknown) {
    if (isNotFound(err)) {
      toast.error('Member not found.')
      router.push('/members')
    } else {
      toast.error('Failed to load member data.')
    }
  } finally {
    isLoading.value = false
  }
}

// ─── Submit ───────────────────────────────────────────────────────────────────

async function handleSubmit() {
  if (!validate()) return

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

    if (profileFile) {
      // Multipart update with new profile picture
      const fd = new FormData()
      fd.append('first_name', form.first_name)
      fd.append('family_name', form.family_name)
      fd.append('email_address', form.email_address)
      if (form.other_names) fd.append('other_names', form.other_names)
      if (form.gender) fd.append('gender', form.gender)
      if (dobDate.value) fd.append('date_of_birth', toIsoDate(dobDate.value)!)
      if (form.occupation) fd.append('occupation', form.occupation)
      if (form.address) fd.append('address', form.address)
      if (form.marital_status_id) fd.append('marital_status_id', String(form.marital_status_id))
      if (form.education_level_id) fd.append('education_level_id', String(form.education_level_id))
      if (form.membership_status_id)
        fd.append('membership_status_id', String(form.membership_status_id))
      if (form.branch_id) fd.append('branch_id', String(form.branch_id))
      if (phoneNumbers.length) fd.append('phone_numbers', JSON.stringify(phoneNumbers))
      fd.append('profile_picture', profileFile)
      await memberService.updateWithPhoto(memberId.value, fd)
    } else {
      // JSON update without picture change
      const payload: MemberUpdate = {
        first_name: form.first_name,
        family_name: form.family_name,
        email_address: form.email_address,
        other_names: form.other_names || undefined,
        gender: form.gender ?? undefined,
        date_of_birth: toIsoDate(dobDate.value),
        occupation: form.occupation || undefined,
        address: form.address || undefined,
        marital_status_id: form.marital_status_id ?? undefined,
        education_level_id: form.education_level_id ?? undefined,
        membership_status_id: form.membership_status_id ?? undefined,
        branch_id: form.branch_id ?? undefined,
        phone_numbers: phoneNumbers.length ? phoneNumbers : undefined,
      }
      await memberService.update(memberId.value, payload)
    }

    toast.success('Member updated successfully.')
    router.push(`/members/${memberId.value}`)
  } catch (err: unknown) {
    toast.error(getApiError(err, 'Failed to update member. Please try again.'))
  } finally {
    isSubmitting.value = false
  }
}

// ─── Init ─────────────────────────────────────────────────────────────────────

onMounted(loadData)
</script>

<template>
  <div class="view">
    <!-- ── Page header ───────────────────────────────────────────────────── -->
    <div class="view-header">
      <ChButton variant="ghost" size="sm" @click="router.push(`/members/${memberId}`)">
        <template #icon><ArrowLeft :size="16" /></template>
        View Profile
      </ChButton>

      <div class="view-header__title-block">
        <h1 class="view-title">Edit Member</h1>
        <p v-if="currentMemberName" class="view-subtitle">
          Editing profile for
          <strong class="view-subtitle__name">{{ currentMemberName }}</strong>
        </p>
        <p v-else class="view-subtitle">Update membership details and contact information.</p>
      </div>
    </div>

    <!-- ── Loading skeleton ──────────────────────────────────────────────── -->
    <template v-if="isLoading">
      <!-- Personal skeleton -->
      <ChCard shadow="sm">
        <template #header>
          <ChSkeleton shape="line" width="180px" height="18px" />
        </template>
        <div class="skeleton-grid">
          <ChSkeleton v-for="i in 6" :key="i" shape="block" height="58px" />
        </div>
      </ChCard>

      <!-- Contact skeleton -->
      <ChCard shadow="sm">
        <template #header>
          <ChSkeleton shape="line" width="180px" height="18px" />
        </template>
        <div class="skeleton-grid">
          <ChSkeleton shape="block" height="58px" class="skeleton-full" />
          <ChSkeleton shape="block" height="80px" class="skeleton-full" />
          <ChSkeleton shape="block" height="58px" />
          <ChSkeleton shape="block" height="58px" />
        </div>
      </ChCard>

      <!-- Membership skeleton -->
      <ChCard shadow="sm">
        <template #header>
          <ChSkeleton shape="line" width="180px" height="18px" />
        </template>
        <div class="skeleton-grid">
          <ChSkeleton v-for="i in 4" :key="i" shape="block" height="58px" />
        </div>
      </ChCard>
    </template>

    <!-- ── Edit form ─────────────────────────────────────────────────────── -->
    <form v-else class="form-layout" @submit.prevent="handleSubmit">
      <!-- ── Personal Information ────────────────────────────────────── -->
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
            :error="errors.first_name"
          >
            <ChInput
              id="first-name"
              v-model="form.first_name"
              placeholder="Enter first name"
              :error="!!errors.first_name"
            />
          </ChFormField>

          <!-- Family name -->
          <ChFormField
            label="Family Name"
            input-id="family-name"
            :required="true"
            :error="errors.family_name"
          >
            <ChInput
              id="family-name"
              v-model="form.family_name"
              placeholder="Enter family name"
              :error="!!errors.family_name"
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
              v-model="dobDate"
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

      <!-- ── Contact Information ─────────────────────────────────────── -->
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
            :error="errors.email_address"
            class="full-width"
          >
            <ChInput
              id="email"
              v-model="form.email_address"
              type="email"
              placeholder="name@example.com"
              :error="!!errors.email_address"
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

      <!-- ── Membership Details ──────────────────────────────────────── -->
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

      <!-- ── Profile Picture ─────────────────────────────────────────── -->
      <ChCard shadow="sm">
        <template #header>
          <span class="section-title">Profile Picture</span>
        </template>

        <!-- Current picture preview -->
        <div v-if="currentProfilePicture && profileFiles.length === 0" class="current-photo">
          <div class="current-photo__preview">
            <ChAvatar
              :src="currentProfilePicture"
              :name="currentMemberName"
              size="xl"
              :circle="false"
            />
            <div class="current-photo__info">
              <p class="current-photo__label">Current profile photo</p>
              <p class="current-photo__hint">
                Select a new photo below to replace it, or leave empty to keep the current one.
              </p>
            </div>
          </div>
        </div>

        <ChFileUpload
          v-model="profileFiles"
          accept="image/jpeg,image/png,image/webp"
          :max-size="2 * 1024 * 1024"
          :label="currentProfilePicture ? 'Replace Profile Picture' : 'Profile Picture'"
          :drop-text="
            currentProfilePicture ? 'Drag & drop a new photo here' : 'Drag & drop a photo here'
          "
          sub-text="JPEG, PNG or WebP — max 2 MB"
          button-text="Select Photo"
        />
      </ChCard>

      <!-- ── Form actions ────────────────────────────────────────────── -->
      <div class="form-actions">
        <ChButton type="button" variant="ghost" @click="router.push(`/members/${memberId}`)">
          Cancel
        </ChButton>
        <ChButton type="submit" variant="primary" :loading="isSubmitting">
          <template #icon><Save :size="16" /></template>
          Save Changes
        </ChButton>
      </div>
    </form>
  </div>
</template>

<style scoped>
/* ── Layout ──────────────────────────────────────────────────────────────── */
.view {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
  max-width: 860px;
}

/* ── Header ──────────────────────────────────────────────────────────────── */
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

.view-subtitle__name {
  color: var(--ch-color-text);
  font-weight: var(--ch-font-semibold);
}

/* ── Loading skeleton grid ───────────────────────────────────────────────── */
.skeleton-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--ch-space-4);
}

.skeleton-full {
  grid-column: 1 / -1;
}

/* ── Form layout ─────────────────────────────────────────────────────────── */
.form-layout {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-5);
}

/* ── Section header title ────────────────────────────────────────────────── */
.section-title {
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

/* ── Two-column form grid ────────────────────────────────────────────────── */
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

  .skeleton-grid {
    grid-template-columns: 1fr;
  }
}

/* ── Phone section ───────────────────────────────────────────────────────── */
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

/* ── Current photo preview ───────────────────────────────────────────────── */
.current-photo {
  margin-bottom: var(--ch-space-5);
  padding-bottom: var(--ch-space-5);
  border-bottom: 1px solid var(--ch-color-border-strong);
}

.current-photo__preview {
  display: flex;
  align-items: center;
  gap: var(--ch-space-4);
}

.current-photo__info {
  flex: 1;
}

.current-photo__label {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
  margin: 0 0 var(--ch-space-1);
}

.current-photo__hint {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  margin: 0;
  line-height: var(--ch-leading-relaxed);
}

/* ── Form actions bar ────────────────────────────────────────────────────── */
.form-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--ch-space-3);
  padding-bottom: var(--ch-space-6);
}
</style>
