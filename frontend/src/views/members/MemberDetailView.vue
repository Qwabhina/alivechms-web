<script setup lang="ts">
import { useRouter, useRoute } from 'vue-router'
import { memberService } from '@/services/member.service'
import { useToast } from '@/design-system'
import type { Member } from '@/types/member'
import { ArrowLeft, Pencil, Trash2, Phone, Mail, MapPin } from 'lucide-vue-next'

// ─── Helpers ──────────────────────────────────────────────────────────────────

function isNotFound(err: unknown): boolean {
  if (err !== null && typeof err === 'object' && 'response' in err) {
    const resp = (err as { response?: { status?: number } }).response
    return resp?.status === 404
  }
  return false
}

function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function statusVariant(status: unknown): 'success' | 'default' | 'warning' | 'danger' | 'info' {
  const map: Record<string, 'success' | 'default' | 'warning' | 'danger' | 'info'> = {
    Active: 'success',
    Inactive: 'default',
    Pending: 'warning',
    Suspended: 'danger',
    Visitor: 'info',
  }
  return map[String(status)] ?? 'default'
}

// ─── Setup ────────────────────────────────────────────────────────────────────

const route = useRoute()
const router = useRouter()
const toast = useToast()

// ─── State ────────────────────────────────────────────────────────────────────

const member = ref<Member | null>(null)
const isLoading = ref(true)
const activeTab = ref('overview')
const showDeleteModal = ref(false)
const isDeleting = ref(false)

// ─── Data loading ─────────────────────────────────────────────────────────────

async function loadMember() {
  isLoading.value = true
  try {
    const id = Number(route.params.id)
    const { data } = await memberService.getById(id)
    member.value = data.data!
  } catch (err: unknown) {
    if (isNotFound(err)) {
      toast.error('Member not found.')
      router.push('/members')
    } else {
      toast.error('Failed to load member profile.')
    }
  } finally {
    isLoading.value = false
  }
}

// ─── Delete ───────────────────────────────────────────────────────────────────

async function confirmDelete() {
  if (!member.value) return
  isDeleting.value = true
  try {
    await memberService.delete(member.value.MbrID)
    toast.success('Member deleted.')
    router.push('/members')
  } catch {
    toast.error('Failed to delete.')
  } finally {
    isDeleting.value = false
  }
}

// ─── Init ─────────────────────────────────────────────────────────────────────

onMounted(loadMember)
</script>

<template>
  <div class="view">
    <!-- ── Top navigation row ────────────────────────────────────────────── -->
    <div class="view-nav">
      <ChButton variant="ghost" size="sm" @click="router.push('/members')">
        <template #icon><ArrowLeft :size="16" /></template>
        Members
      </ChButton>

      <div v-if="member && !isLoading" class="view-nav__actions">
        <ChButton variant="outline" size="sm" @click="router.push(`/members/${member.MbrID}/edit`)">
          <template #icon><Pencil :size="14" /></template>
          Edit
        </ChButton>
        <ChButton variant="danger" size="sm" @click="showDeleteModal = true">
          <template #icon><Trash2 :size="14" /></template>
          Delete
        </ChButton>
      </div>
    </div>

    <!-- ── Loading state ─────────────────────────────────────────────────── -->
    <div v-if="isLoading" class="loading-container">
      <ChSpinner size="lg" />
    </div>

    <!-- ── Profile content ───────────────────────────────────────────────── -->
    <template v-else-if="member">
      <!-- Profile header card -->
      <ChCard shadow="sm">
        <div class="profile-header">
          <ChAvatar
            :name="`${member.MbrFirstName} ${member.MbrFamilyName}`"
            :src="member.MbrProfilePicture || undefined"
            size="xl"
          />

          <div class="profile-header__info">
            <h1 class="profile-name">
              {{ member.MbrFirstName }}
              {{ member.MbrOtherNames ? member.MbrOtherNames + ' ' : '' }}{{ member.MbrFamilyName }}
            </h1>
            <p class="profile-id">
              {{ member.MbrUniqueID ?? `ID #${member.MbrID}` }}
            </p>
            <div class="profile-badges">
              <ChBadge
                v-if="member.MembershipStatusName"
                :variant="statusVariant(member.MembershipStatusName)"
                dot
              >
                {{ member.MembershipStatusName }}
              </ChBadge>
              <ChBadge v-if="member.BranchName" variant="default">
                {{ member.BranchName }}
              </ChBadge>
            </div>
          </div>

          <div class="profile-header__contact">
            <div class="contact-item">
              <Mail :size="14" class="contact-item__icon" />
              <span>{{ member.MbrEmailAddress }}</span>
            </div>
            <div v-if="member.PrimaryPhone" class="contact-item">
              <Phone :size="14" class="contact-item__icon" />
              <span>{{ member.PrimaryPhone }}</span>
            </div>
            <div v-if="member.MbrResidentialAddress" class="contact-item">
              <MapPin :size="14" class="contact-item__icon" />
              <span class="contact-item__address">{{ member.MbrResidentialAddress }}</span>
            </div>
          </div>
        </div>
      </ChCard>

      <!-- Tabs -->
      <ChTabs
        v-model="activeTab"
        :tabs="[
          { label: 'Overview', value: 'overview' },
          { label: 'Contact', value: 'contact' },
          { label: 'Membership', value: 'membership' },
          { label: 'Milestones', value: 'milestones' },
        ]"
      />

      <!-- ── Overview tab ───────────────────────────────────────────────── -->
      <ChCard v-show="activeTab === 'overview'" shadow="sm">
        <template #header>
          <span class="section-title">Personal Details</span>
        </template>
        <ChDataList
          :items="[
            { label: 'Date of Birth', value: formatDate(member.MbrDateOfBirth) },
            { label: 'Gender', value: member.MbrGender ?? '—' },
            { label: 'Marital Status', value: member.MaritalStatusName ?? '—' },
            { label: 'Education Level', value: member.EducationLevelName ?? '—' },
            { label: 'Occupation', value: member.MbrOccupation ?? '—' },
          ]"
        />
      </ChCard>

      <!-- ── Contact tab ────────────────────────────────────────────────── -->
      <ChCard v-show="activeTab === 'contact'" shadow="sm">
        <template #header>
          <span class="section-title">Contact Information</span>
        </template>

        <ChDataList
          :items="[
            { label: 'Email Address', value: member.MbrEmailAddress },
            {
              label: 'Residential Address',
              value: member.MbrResidentialAddress ?? '—',
              fullWidth: true,
            },
          ]"
        />

        <!-- Phone numbers list -->
        <template v-if="member.phones && member.phones.length > 0">
          <div class="phone-section-divider">
            <span class="phone-section-label">Phone Numbers</span>
          </div>
          <div class="phone-list">
            <div v-for="phone in member.phones" :key="phone.PhoneID" class="phone-item">
              <div class="phone-item__icon-wrap">
                <Phone :size="14" class="phone-item__icon" />
              </div>
              <div class="phone-item__details">
                <span class="phone-item__number">{{ phone.PhoneNumber }}</span>
                <span v-if="phone.PhoneTypeName" class="phone-item__type">
                  {{ phone.PhoneTypeName }}
                </span>
              </div>
              <ChBadge v-if="phone.IsPrimary" variant="primary" size="sm">Primary</ChBadge>
            </div>
          </div>
        </template>

        <div v-else class="no-phones">
          <Phone :size="15" class="no-phones__icon" />
          <span>No phone numbers recorded.</span>
        </div>
      </ChCard>

      <!-- ── Membership tab ─────────────────────────────────────────────── -->
      <ChCard v-show="activeTab === 'membership'" shadow="sm">
        <template #header>
          <span class="section-title">Membership Details</span>
        </template>
        <ChDataList
          :items="[
            {
              label: 'Membership Status',
              value: member.MembershipStatusName ?? '—',
              type: 'badge',
              variant: statusVariant(member.MembershipStatusName),
            },
            { label: 'Branch', value: member.BranchName ?? '—' },
            { label: 'Registration Date', value: formatDate(member.MbrRegistrationDate) },
            { label: 'Family Group', value: member.FamilyName ?? '—' },
            { label: 'Member ID', value: member.MbrUniqueID ?? `#${member.MbrID}` },
            {
              label: 'System Login',
              value: member.HasLogin
                ? member.IsActive
                  ? 'Enabled'
                  : 'Disabled'
                : 'No login assigned',
            },
          ]"
        />
      </ChCard>

      <!-- ── Milestones tab ─────────────────────────────────────────────── -->
      <ChCard v-show="activeTab === 'milestones'" shadow="sm">
        <template #header>
          <span class="section-title">Faith Milestones</span>
        </template>

        <ChTimeline v-if="member.milestones && member.milestones.length > 0">
          <ChTimelineItem
            v-for="m in member.milestones"
            :key="m.MilestoneID"
            :title="m.MilestoneTypeName ?? 'Milestone'"
            :timestamp="m.MilestoneDate"
            variant="primary"
          >
            <span v-if="m.Notes">{{ m.Notes }}</span>
          </ChTimelineItem>
        </ChTimeline>

        <ChEmptyState
          v-else
          icon="star"
          title="No milestones recorded"
          description="Faith milestones like baptism, confirmation, and membership will appear here."
          :compact="true"
        />
      </ChCard>
    </template>

    <!-- ── Delete confirmation modal ─────────────────────────────────────── -->
    <ChModal v-model:open="showDeleteModal" title="Delete Member" size="sm">
      <p class="delete-body">
        Are you sure you want to delete
        <strong>{{ member?.MbrFirstName }} {{ member?.MbrFamilyName }}</strong
        >? This action cannot be undone.
      </p>

      <template #footer>
        <ChButton variant="ghost" @click="showDeleteModal = false">Cancel</ChButton>
        <ChButton variant="danger" :loading="isDeleting" @click="confirmDelete">
          Delete Member
        </ChButton>
      </template>
    </ChModal>
  </div>
</template>

<style scoped>
/* ── Layout ──────────────────────────────────────────────────────────────── */
.view {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-5);
  max-width: 900px;
}

/* ── Top nav row ─────────────────────────────────────────────────────────── */
.view-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--ch-space-3);
}

.view-nav__actions {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

/* ── Loading ─────────────────────────────────────────────────────────────── */
.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--ch-space-12) 0;
}

/* ── Profile header ──────────────────────────────────────────────────────── */
.profile-header {
  display: flex;
  align-items: flex-start;
  gap: var(--ch-space-5);
  flex-wrap: wrap;
}

.profile-header__info {
  flex: 1;
  min-width: 180px;
}

.profile-name {
  font-size: var(--ch-text-2xl);
  font-weight: var(--ch-font-bold);
  font-family: var(--ch-font-display);
  color: var(--ch-color-text);
  margin: 0 0 var(--ch-space-1);
  line-height: var(--ch-leading-snug);
}

.profile-id {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-subtle);
  font-family: var(--ch-font-sans);
  margin: 0 0 var(--ch-space-3);
}

.profile-badges {
  display: flex;
  flex-wrap: wrap;
  gap: var(--ch-space-2);
}

/* ── Quick contact sidebar ───────────────────────────────────────────────── */
.profile-header__contact {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2);
  padding: var(--ch-space-1) var(--ch-space-5);
  border-left: 1px solid var(--ch-color-border-strong);
  min-width: 200px;
  max-width: 280px;
}

@media (max-width: 640px) {
  .profile-header__contact {
    border-left: none;
    border-top: 1px solid var(--ch-color-border-strong);
    padding: var(--ch-space-4) 0 0;
    max-width: 100%;
    width: 100%;
  }
}

.contact-item {
  display: flex;
  align-items: flex-start;
  gap: var(--ch-space-2);
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  line-height: var(--ch-leading-snug);
}

.contact-item__icon {
  flex-shrink: 0;
  margin-top: 2px;
  color: var(--ch-color-text-subtle);
}

.contact-item__address {
  word-break: break-word;
}

/* ── Section title ───────────────────────────────────────────────────────── */
.section-title {
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

/* ── Phone list (contact tab) ────────────────────────────────────────────── */
.phone-section-divider {
  margin-top: var(--ch-space-5);
  padding-top: var(--ch-space-4);
  border-top: 1px solid var(--ch-color-border-strong);
  margin-bottom: var(--ch-space-3);
}

.phone-section-label {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text-muted);
}

.phone-list {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2);
}

.phone-item {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  padding: var(--ch-space-3) var(--ch-space-4);
  background: var(--ch-color-bg-subtle);
  border-radius: var(--ch-radius-lg);
  border: 1px solid var(--ch-color-border-strong);
}

.phone-item__icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: var(--ch-radius-lg);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  flex-shrink: 0;
}

.phone-item__icon {
  color: var(--ch-color-text-subtle);
}

.phone-item__details {
  flex: 1;
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  flex-wrap: wrap;
}

.phone-item__number {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
}

.phone-item__type {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
}

.no-phones {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-subtle);
  padding: var(--ch-space-4) 0;
}

.no-phones__icon {
  flex-shrink: 0;
}

/* ── Delete modal body ───────────────────────────────────────────────────── */
.delete-body {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  line-height: var(--ch-leading-relaxed);
  margin: 0;
}
</style>
