<script setup lang="ts">
/**
 * ContributionListView — Full implementation.
 * Displays paginated contributions with stats, filters, sortable table, and delete flow.
 */
import { useRouter } from 'vue-router'
import { contributionService, lookupService } from '@/services/finance.service'
import type { FinanceLookupData } from '@/services/finance.service'
import { useToast, ChPageHeader } from '@/design-system'
import type { Contribution, ContributionFilters, ContributionStats } from '@/types/finance'
import { Plus, Trash2, Eye, TrendingUp, Wallet, BarChart2 } from '@lucide/vue'

const router = useRouter()
const toast = useToast()

// ── State ─────────────────────────────────────────────────────────────────────

const contributions = ref<Contribution[]>([])
const total = ref(0)
const page = ref(1)
const isLoading = ref(false)
const lookupData = ref<FinanceLookupData | null>(null)
const stats = ref<ContributionStats | null>(null)

// ── Filters ───────────────────────────────────────────────────────────────────

const search = ref('')
const typeFilter = ref<string | number>('')
const startDate = ref<Date | null>(null)
const endDate = ref<Date | null>(null)
const sortBy = ref('ContributionDate')
const sortDir = ref<'ASC' | 'DESC'>('DESC')

// ── Delete flow ───────────────────────────────────────────────────────────────

const showDeleteModal = ref(false)
const contributionToDelete = ref<Contribution | null>(null)
const isDeleting = ref(false)

// ── Table columns ─────────────────────────────────────────────────────────────

const columns = [
  { key: 'MemberName', label: 'Member', sortable: true },
  { key: 'ContributionTypeName', label: 'Type' },
  {
    key: 'ContributionAmount',
    label: 'Amount',
    align: 'right' as const,
    sortable: true,
    type: 'slot' as const,
  },
  { key: 'ContributionDate', label: 'Date', sortable: true, type: 'slot' as const },
  { key: 'PaymentMethodName', label: 'Method' },
  { key: 'PaymentReference', label: 'Reference' },
  { key: 'actions', label: '', type: 'slot' as const, exportable: false, align: 'right' as const },
]

// ── Watchers ──────────────────────────────────────────────────────────────────

let searchTimer: ReturnType<typeof setTimeout> | null = null

watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    loadContributions()
  }, 500)
})

watch([typeFilter, startDate, endDate, page], () => loadContributions())

// ── Data loaders ──────────────────────────────────────────────────────────────

async function loadContributions() {
  isLoading.value = true
  try {
    const filters: ContributionFilters = {
      search: search.value || undefined,
      contribution_type_id: typeFilter.value !== '' ? Number(typeFilter.value) : undefined,
      start_date: startDate.value ? startDate.value.toISOString().slice(0, 10) : undefined,
      end_date: endDate.value ? endDate.value.toISOString().slice(0, 10) : undefined,
      sort_by: sortBy.value,
      sort_dir: sortDir.value,
    }
    const { data } = await contributionService.getAll(page.value, 25, filters)
    contributions.value = data.data
    total.value = data.pagination.total
  } catch {
    toast.error('Failed to load contributions.')
  } finally {
    isLoading.value = false
  }
}

async function loadStats() {
  try {
    const { data } = await contributionService.getStats()
    stats.value = data.data!
  } catch {
    /* silent — stats are non-critical */
  }
}

async function loadLookups() {
  try {
    const { data } = await lookupService.getAll()
    lookupData.value = data.data as FinanceLookupData
  } catch {
    /* silent */
  }
}

// ── Handlers ──────────────────────────────────────────────────────────────────

function handleSort(key: string, dir: 'asc' | 'desc' | null) {
  sortBy.value = key
  sortDir.value = dir === 'asc' ? 'ASC' : 'DESC'
  page.value = 1
  loadContributions()
}

function openDeleteModal(c: Contribution) {
  contributionToDelete.value = c
  showDeleteModal.value = true
}

async function confirmDelete() {
  if (!contributionToDelete.value) return
  isDeleting.value = true
  try {
    await contributionService.delete(contributionToDelete.value.ContributionID)
    toast.success('Contribution deleted.')
    showDeleteModal.value = false
    loadContributions()
    loadStats()
  } catch {
    toast.error('Failed to delete contribution.')
  } finally {
    isDeleting.value = false
  }
}

// ── Formatters ────────────────────────────────────────────────────────────────

function formatCurrency(amount: number): string {
  return (
    'GH₵ ' + amount.toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  )
}

function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(() => {
  loadLookups()
  loadStats()
  loadContributions()
})
</script>

<template>
  <div class="view">
    <ChPageHeader title="Contributions" subtitle="Track and manage church finances.">
      <template #actions>
        <ChButton variant="primary" @click="router.push('/finance/contributions/create')">
          <template #icon><Plus :size="18" /></template>
          Record Contribution
        </ChButton>
      </template>
    </ChPageHeader>

    <!-- ── Stats Row ────────────────────────────────────────────────────────── -->
    <div class="stats-row">
      <ChStatCard
        label="Total Contributions"
        :value="stats ? formatCurrency(stats.total_amount) : '—'"
        :loading="!stats"
        variant="primary"
      >
        <template #icon><Wallet :size="20" /></template>
      </ChStatCard>

      <ChStatCard
        label="Total Records"
        :value="stats?.total_count ?? '—'"
        :loading="!stats"
        variant="info"
      >
        <template #icon><BarChart2 :size="20" /></template>
      </ChStatCard>

      <ChStatCard
        label="Average Amount"
        :value="stats ? formatCurrency(stats.average_amount) : '—'"
        :loading="!stats"
        variant="success"
      >
        <template #icon><TrendingUp :size="20" /></template>
      </ChStatCard>
    </div>

    <!-- ── Filters ───────────────────────────────────────────────────────────── -->
    <ChCard shadow="sm">
      <div class="filters-row">
        <!-- Search -->
        <ChFormField
          label="Search"
          input-id="contribution-search"
          class="filter-item filter-item--wide"
        >
          <ChInput
            id="contribution-search"
            v-model="search"
            placeholder="Search by member name, receipt…"
            clearable
          />
        </ChFormField>

        <!-- Type filter -->
        <ChFormField label="Contribution Type" input-id="type-filter" class="filter-item">
          <ChSelect
            id="type-filter"
            v-model="typeFilter"
            :options="[
              { value: '', label: 'All Types' },
              ...(lookupData?.contribution_types?.map((t) => ({ value: t.id, label: t.name })) ??
                []),
            ]"
            placeholder="All Types"
          />
        </ChFormField>

        <!-- Date range -->
        <ChFormField label="From Date" class="filter-item">
          <ChDatePicker v-model="startDate" placeholder="Start date" display-format="dd/mm/yyyy" />
        </ChFormField>

        <ChFormField label="To Date" class="filter-item">
          <ChDatePicker v-model="endDate" placeholder="End date" display-format="dd/mm/yyyy" />
        </ChFormField>
      </div>
    </ChCard>

    <!-- ── Contributions Table ───────────────────────────────────────────────── -->
    <ChTable
      :columns="columns"
      :rows="contributions as unknown as Record<string, unknown>[]"
      :total="total"
      :page="page"
      :page-size="25"
      :loading="isLoading"
      row-key="ContributionID"
      hoverable
      exportable
      title="Contributions"
      @sort="handleSort"
      @update:page="page = $event"
    >
      <!-- Amount cell: formatted currency, right-aligned -->
      <template #cell-ContributionAmount="{ row }">
        <span class="amount-cell">
          {{ formatCurrency((row as unknown as Contribution).ContributionAmount) }}
        </span>
      </template>

      <!-- Date cell: human-readable -->
      <template #cell-ContributionDate="{ row }">
        {{ formatDate((row as unknown as Contribution).ContributionDate) }}
      </template>

      <!-- Actions cell: view + delete -->
      <template #cell-actions="{ row }">
        <div class="actions-cell">
          <ChButton
            size="sm"
            variant="ghost"
            :icon-only="true"
            title="View contribution"
            @click="
              router.push(
                `/finance/contributions/${(row as unknown as Contribution).ContributionID}`,
              )
            "
          >
            <Eye :size="15" />
          </ChButton>
          <ChButton
            size="sm"
            variant="ghost"
            :icon-only="true"
            title="Delete contribution"
            @click="openDeleteModal(row as unknown as Contribution)"
          >
            <Trash2 :size="15" />
          </ChButton>
        </div>
      </template>
    </ChTable>

    <!-- ── Delete Confirmation Modal ────────────────────────────────────────── -->
    <ChModal
      :open="showDeleteModal"
      title="Delete Contribution"
      subtitle="This action cannot be undone."
      size="sm"
      @update:open="showDeleteModal = $event"
    >
      <p class="delete-message">
        Are you sure you want to delete the contribution from
        <strong>{{ contributionToDelete?.MemberName ?? 'this member' }}</strong>
        for
        <strong>{{
          contributionToDelete ? formatCurrency(contributionToDelete.ContributionAmount) : ''
        }}</strong>
        on
        <span class="delete-message__date">{{
          formatDate(contributionToDelete?.ContributionDate)
        }}</span
        >?
      </p>

      <template #footer>
        <ChButton variant="ghost" :disabled="isDeleting" @click="showDeleteModal = false">
          Cancel
        </ChButton>
        <ChButton variant="danger" :loading="isDeleting" @click="confirmDelete"> Delete </ChButton>
      </template>
    </ChModal>
  </div>
</template>

<style scoped>
/* ─── Page shell ──────────────────────────────────────────────────────────── */
.view {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
  max-width: 1200px;
  margin: 0 auto;
}

/* ─── Stats row ───────────────────────────────────────────────────────────── */
.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--ch-space-4);
}

@media (max-width: 768px) {
  .stats-row {
    grid-template-columns: 1fr;
  }
}

/* ─── Filters ─────────────────────────────────────────────────────────────── */
.filters-row {
  display: flex;
  flex-wrap: wrap;
  gap: var(--ch-space-4);
  align-items: flex-end;
}

.filter-item {
  flex: 1 1 180px;
  min-width: 160px;
}

.filter-item--wide {
  flex: 2 1 260px;
  min-width: 220px;
}

/* ─── Table actions cell ──────────────────────────────────────────────────── */
.actions-cell {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--ch-space-1);
}

/* ─── Amount cell ─────────────────────────────────────────────────────────── */
.amount-cell {
  font-variant-numeric: tabular-nums;
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

/* ─── Delete modal ────────────────────────────────────────────────────────── */
.delete-message {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text);
  line-height: var(--ch-leading-relaxed);
  margin: 0;
}

.delete-message strong {
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

.delete-message__date {
  color: var(--ch-color-text-muted);
}
</style>
