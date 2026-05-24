<script setup lang="ts">
import { useRouter } from 'vue-router'
import { memberService } from '@/services/member.service'
import { useToast, ChPageHeader } from '@/design-system'
import type { Member, MemberFilters, MemberLookupData } from '@/types/member'
import { UserPlus, Search, Trash2 } from '@lucide/vue'
import { normalizeProfileImage } from '@/utils/image'

// ─── Helpers ──────────────────────────────────────────────────────────────────

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

const router = useRouter()
const toast = useToast()

// ─── State ────────────────────────────────────────────────────────────────────

const members = ref<Member[]>([])
const total = ref(0)
const page = ref(1)
const isLoading = ref(false)
const search = ref('')
const statusFilter = ref<string | number | null>(null)
const sortBy = ref('MbrRegistrationDate')
const sortDir = ref<'ASC' | 'DESC'>('DESC')
const lookupData = ref<MemberLookupData | null>(null)

// Delete flow
const showDeleteModal = ref(false)
const memberToDelete = ref<Member | null>(null)
const isDeleting = ref(false)

// ─── Table columns ────────────────────────────────────────────────────────────

const columns = [
  {
    key: 'avatar',
    label: '',
    type: 'slot' as const,
    width: '48px',
    exportable: false,
    align: 'center' as const,
  },
  { key: 'name', label: 'Member', sortable: true, type: 'slot' as const },
  {
    key: 'MembershipStatusName',
    label: 'Status',
    type: 'badge' as const,
    badgeVariant: (v: unknown) => statusVariant(v),
  },
  { key: 'BranchName', label: 'Branch' },
  { key: 'MbrRegistrationDate', label: 'Joined', sortable: true, type: 'slot' as const },
  {
    key: 'actions',
    label: '',
    type: 'slot' as const,
    exportable: false,
    align: 'right' as const,
  },
]

// ─── Status filter options ────────────────────────────────────────────────────

const statusOptions = computed(() => [
  { value: '', label: 'All Statuses' },
  ...(lookupData.value?.membership_statuses.map((s) => ({ value: s.id, label: s.name })) ?? []),
])

// ─── Data loading ─────────────────────────────────────────────────────────────

async function loadMembers() {
  isLoading.value = true
  try {
    const filters: MemberFilters = {
      search: search.value || undefined,
      status: statusFilter.value ? String(statusFilter.value) : undefined,
      sort_by: sortBy.value,
      sort_dir: sortDir.value,
    }
    const res = await memberService.getAll(page.value, 25, filters)
    if (!res?.data || !res.data.data) throw new Error('No members data')
    members.value = res.data.data
    total.value = res.data.pagination?.total ?? 0
  } catch {
    toast.error('Failed to load members.')
  } finally {
    isLoading.value = false
  }
}

async function loadLookupData() {
  try {
    const res = await memberService.getLookupData()
    if (res?.data) lookupData.value = res.data
  } catch {
    /* silent */
  }
}

// ─── Watchers ─────────────────────────────────────────────────────────────────

let searchTimer: ReturnType<typeof setTimeout> | null = null

watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    loadMembers()
  }, 500)
})

watch([statusFilter, page], () => loadMembers())

// ─── Sort ─────────────────────────────────────────────────────────────────────

function handleSort(key: string, dir: 'asc' | 'desc' | null) {
  sortBy.value = key
  sortDir.value = dir === 'asc' ? 'ASC' : 'DESC'
  page.value = 1
  loadMembers()
}

// ─── Delete ───────────────────────────────────────────────────────────────────

function openDeleteModal(member: Member) {
  memberToDelete.value = member
  showDeleteModal.value = true
}

async function confirmDelete() {
  if (!memberToDelete.value) return
  isDeleting.value = true
  try {
    await memberService.delete(memberToDelete.value.MbrID)
    toast.success(
      `${memberToDelete.value.MbrFirstName} ${memberToDelete.value.MbrFamilyName} removed.`,
    )
    showDeleteModal.value = false
    loadMembers()
  } catch {
    toast.error('Failed to delete member.')
  } finally {
    isDeleting.value = false
  }
}

// ─── Init ─────────────────────────────────────────────────────────────────────

onMounted(() => {
  loadLookupData()
  loadMembers()
})
</script>

<template>
  <div class="view">
    <ChPageHeader title="Members" subtitle="Manage your church membership directory.">
      <template #actions>
        <ChButton variant="primary" @click="router.push('/members/create')">
          <template #icon><UserPlus :size="18" /></template>
          Add Member
        </ChButton>
      </template>
    </ChPageHeader>

    <!-- ── Filter bar ────────────────────────────────────────────────────── -->
    <div class="filter-bar">
      <div class="filter-bar__search">
        <ChInput v-model="search" placeholder="Search by name or email…" size="md">
          <template #leading>
            <Search :size="16" class="filter-icon" />
          </template>
        </ChInput>
      </div>

      <div class="filter-bar__status">
        <ChSelect
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="All Statuses"
          size="md"
        />
      </div>
    </div>

    <!-- ── Member table ──────────────────────────────────────────────────── -->
    <ChTable
      :columns="columns"
      :rows="members as Record<string, unknown>[]"
      :total="total"
      :page-size="25"
      v-model:page="page"
      :loading="isLoading"
      row-key="MbrID"
      :hoverable="true"
      :clickable="true"
      :exportable="true"
      title="Member Directory"
      @sort="handleSort"
      @row-click="(row) => router.push(`/members/${(row as unknown as Member).MbrID}`)"
    >
      <!-- Avatar cell -->
      <template #cell-avatar="{ row }">
        <ChAvatar
            :name="(row as any).MbrFirstName + ' ' + (row as any).MbrFamilyName"
            :src="normalizeProfileImage((row as any).MbrProfilePicture)"
          size="sm"
        />
      </template>

      <!-- Name + email cell -->
      <template #cell-name="{ row }">
        <div class="member-name">
          <span class="member-name__primary">
            {{ (row as any).MbrFirstName }} {{ (row as any).MbrFamilyName }}
          </span>
          <span class="member-name__secondary">{{ (row as any).MbrEmailAddress }}</span>
        </div>
      </template>

      <!-- Formatted join date -->
      <template #cell-MbrRegistrationDate="{ row }">
        {{ formatDate((row as any).MbrRegistrationDate) }}
      </template>

      <!-- Row actions -->
      <template #cell-actions="{ row }">
        <div class="row-actions">
          <ChButton
            size="sm"
            variant="ghost"
            @click.stop="router.push(`/members/${(row as any).MbrID}`)"
          >
            View
          </ChButton>
          <ChButton
            size="sm"
            variant="ghost"
            @click.stop="router.push(`/members/${(row as any).MbrID}/edit`)"
          >
            Edit
          </ChButton>
          <ChButton size="sm" variant="ghost" @click.stop="openDeleteModal(row as any)">
            <Trash2 :size="14" />
          </ChButton>
        </div>
      </template>
    </ChTable>

    <!-- ── Delete confirmation modal ────────────────────────────────────── -->
    <ChModal v-model:open="showDeleteModal" title="Delete Member" size="sm">
      <p class="delete-body">
        Are you sure you want to delete
        <strong>{{ memberToDelete?.MbrFirstName }} {{ memberToDelete?.MbrFamilyName }}</strong
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
/* ── Layout ─────────────────────────────────────────────────────────────── */
.view {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
  margin: 0 auto;

}

/* ── Filter bar ─────────────────────────────────────────────────────────── */
.filter-bar {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  flex-wrap: wrap;
}

.filter-bar__search {
  flex: 1;
  min-width: 200px;
  max-width: 400px;
}

.filter-icon {
  color: var(--ch-color-text-subtle);
}

.filter-bar__status {
  width: 200px;
}

/* ── Member name cell ───────────────────────────────────────────────────── */
.member-name {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.member-name__primary {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  line-height: var(--ch-leading-snug);
}

.member-name__secondary {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

/* ── Row action buttons ─────────────────────────────────────────────────── */
.row-actions {
  display: flex;
  align-items: center;
  gap: var(--ch-space-1);
  justify-content: flex-end;
}

/* ── Delete modal body ──────────────────────────────────────────────────── */
.delete-body {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  line-height: var(--ch-leading-relaxed);
  margin: 0;
}
</style>
