<script setup lang="ts">
import { useRouter } from 'vue-router'
import { memberService } from '@/services/member.service'
import { useToast, ChPageHeader } from '@/design-system'
import type { MemberCreate, MemberLookupData } from '@/types/member'
import { ArrowLeft, UserPlus, Plus, Trash2 } from 'lucide-vue-next'

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
const phones = ref([{ number: '', type_id: null as number | null, is_primary: true }])
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
      const res = await memberService.createWithPhoto(fd)
      toast.success('Member added successfully.')
      if (res?.data?.mbr_id) router.push(`/members/${res.data.mbr_id}`)
    } else {
      const payload: MemberCreate = {
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
      const res = await memberService.create(payload)
      toast.success('Member added successfully.')
      if (res?.data?.mbr_id) router.push(`/members/${res.data.mbr_id}`)
    }
  } catch (err: unknown) {
    toast.error(getApiError(err, 'Failed to add member. Please try again.'))
  } finally {
    isSubmitting.value = false
  }
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
        <ChButton variant="ghost" size="sm" @click="router.push('/members')">
          <template #icon><ArrowLeft :size="16" /></template>
          Members
        </ChButton>
      </template>
    </ChPageHeader>

    <form class="form-layout" @submit.prevent="handleSubmit">
      <!-- ── Personal Information ──────────────────────────────────────── -->
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

      <!-- ── Contact Information ───────────────────────────────────────── -->
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

      <!-- ── Membership ────────────────────────────────────────────────── -->
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

      <!-- ── Profile Picture ───────────────────────────────────────────── -->
      <ChCard shadow="sm">
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

      <!-- ── Form actions ──────────────────────────────────────────────── -->
      <div class="form-actions">
        <ChButton type="button" variant="ghost" @click="router.push('/members')"> Cancel </ChButton>
        <ChButton type="submit" variant="primary" :loading="isSubmitting">
          <template #icon><UserPlus :size="16" /></template>
          Add Member
        </ChButton>
      </div>
    </form>
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
</style>
