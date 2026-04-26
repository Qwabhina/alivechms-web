<script setup lang="ts">
/**
 * ExpenseListView — Full implementation.
 * Displays paginated expenses with stats, filters, sortable table, and delete flow.
 */
import { useRouter } from 'vue-router'
import { expenseService } from '@/services/expense.service'
import { lookupService } from '@/services/finance.service'
import type { FinanceLookupData } from '@/services/finance.service'
import { useToast, ChPageHeader } from '@/design-system'
import type { Expense, ExpenseListFilters, ExpenseCategory } from '@/types/finance'
import { Plus, Trash2, Eye, TrendingDown, BarChart2, CheckCircle, Clock } from 'lucide-vue-next'

const router = useRouter()
const toast = useToast()

// ── State ─────────────────────────────────────────────────────────────────────

const expenses = ref<Expense[]>([])
const total = ref(0)
const page = ref(1)
const isLoading = ref(false)
const lookupData = ref<FinanceLookupData | null>(null)
const categories = ref<ExpenseCategory[]>([])

// ── Filters ───────────────────────────────────────────────────────────────────

const search = ref('')
const categoryFilter = ref<string | number>('')
const statusFilter = ref<string>('')
const startDate = ref<Date | null>(null)
const endDate = ref<Date | null>(null)
const sortBy = ref('ExpenseDate')
const sortDir = ref<'ASC' | 'DESC'>('DESC')

// ── Delete flow ───────────────────────────────────────────────────────────────

const showDeleteModal = ref(false)
const expenseToDelete = ref<Expense | null>(null)
const isDeleting = ref(false)

// ── Computed stats ────────────────────────────────────────────────────────────

const totalAmount = computed(() => {
  return expenses.value.reduce((sum, e) => sum + (e.Amount || 0), 0)
})

const pendingCount = computed(() => {
  return expenses.value.filter(e => !e.ApprovedBy).length
})

// ── Table columns ─────────────────────────────────────────────────────────────

const columns = [
  { key: 'CategoryName', label: 'Category' },
  { key: 'Description', label: 'Description', type: 'slot' as const },
  {
    key: 'Amount',
    label: 'Amount',
    align: 'right' as const,
    sortable: true,
    type: 'slot' as const,
  },
  { key: 'ExpenseDate', label: 'Date', sortable: true, type: 'slot' as const },
  { key: 'status', label: 'Status', type: 'slot' as const },
  { key: 'actions', label: '', type: 'slot' as const, exportable: false, align: 'right' as const },
]

// ── Watchers ──────────────────────────────────────────────────────────────────

let searchTimer: ReturnType<typeof setTimeout> | null = null

watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    loadExpenses()
  }, 500)
})

watch([categoryFilter, statusFilter, startDate, endDate, page], () => loadExpenses())

// ── Data loaders ──────────────────────────────────────────────────────────────

async function loadExpenses() {
  isLoading.value = true
  try {
    const filters: ExpenseListFilters = {
      category_id: categoryFilter.value !== '' ? Number(categoryFilter.value) : undefined,
      status: statusFilter.value || undefined,
      start_date: startDate.value ? startDate.value.toISOString().slice(0, 10) : undefined,
      end_date: endDate.value ? endDate.value.toISOString().slice(0, 10) : undefined,
      sort_by: sortBy.value,
      sort_dir: sortDir.value,
    }
    const response = await expenseService.list(page.value, 25, filters)
    const paginatedData = response?.data
    if (paginatedData && paginatedData.data && Array.isArray(paginatedData.data)) {
      expenses.value = paginatedData.data as Expense[]
      total.value = paginatedData.pagination?.total || 0
    }
  } catch {
    toast.error('Failed to load expenses.')
  } finally {
    isLoading.value = false
  }
}

async function loadCategories() {
  try {
    const response = await expenseService.listCategories()
    if (response?.data) {
      categories.value = response.data
    }
  } catch {
    /* silent */
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
  loadExpenses()
}

function openDeleteModal(e: Expense) {
  expenseToDelete.value = e
  showDeleteModal.value = true
}

async function confirmDelete() {
  if (!expenseToDelete.value) return
  isDeleting.value = true
  try {
    await expenseService.delete(expenseToDelete.value.ExpenseID)
    toast.success('Expense deleted.')
    showDeleteModal.value = false
    loadExpenses()
  } catch {
    toast.error('Failed to delete expense.')
  } finally {
    isDeleting.value = false
  }
}

// ── Formatters ────────────────────────────────────────────────────────────────

function formatCurrency(amount: number): string {
  return 'GH₵ ' + amount.toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function getStatusVariant(expense: Expense): 'success' | 'warning' | 'danger' | 'default' {
  if (expense.ApprovedBy) return 'success'
  return 'warning'
}

function getStatusLabel(expense: Expense): string {
  if (expense.ApprovedBy) return 'Approved'
  return 'Pending'
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(() => {
  loadLookups()
  loadCategories()
  loadExpenses()
})
</script>

<template>
  <div class="view">
    <ChPageHeader title="Expenses" subtitle="Track and manage church expenses.">
      <template #actions>
        <ChButton variant="primary" @click="router.push('/finance/expenses/create')">
          <template #icon><Plus :size="18" /></template>
          Record Expense
        </ChButton>
      </template>
    </ChPageHeader>

    <!-- ── Stats Row ────────────────────────────────────────────────────────── -->
    <div class="stats-row">
      <ChStatCard
        label="Total Expenses"
        :value="formatCurrency(totalAmount)"
        :loading="isLoading"
        variant="danger"
      >
        <template #icon><TrendingDown :size="20" /></template>
      </ChStatCard>

      <ChStatCard
        label="Total Records"
        :value="total"
        :loading="isLoading"
        variant="info"
      >
        <template #icon><BarChart2 :size="20" /></template>
      </ChStatCard>

      <ChStatCard
        label="Pending Approval"
        :value="pendingCount"
        :loading="isLoading"
        variant="warning"
      >
        <template #icon><Clock :size="20" /></template>
      </ChStatCard>
    </div>

    <!-- ── Filters ───────────────────────────────────────────────────────────── -->
    <ChCard shadow="sm">
      <div class="filters-row">
        <!-- Search -->
        <ChFormField
          label="Search"
          input-id="expense-search"
          class="filter-item filter-item--wide"
        >
          <ChInput
            id="expense-search"
            v-model="search"
            placeholder="Search by description..."
            clearable
          />
        </ChFormField>

        <!-- Category filter -->
        <ChFormField label="Category" input-id="category-filter" class="filter-item">
          <ChSelect
            id="category-filter"
            v-model="categoryFilter"
            :options="[
              { value: '', label: 'All Categories' },
              ...(categories?.map((c) => ({ value: c.CategoryID, label: c.CategoryName })) ?? []),
            ]"
            placeholder="All Categories"
          />
        </ChFormField>

        <!-- Status filter -->
        <ChFormField label="Status" input-id="status-filter" class="filter-item">
          <ChSelect
            id="status-filter"
            v-model="statusFilter"
            :options="[
              { value: '', label: 'All Statuses' },
              { value: 'pending', label: 'Pending' },
              { value: 'approved', label: 'Approved' },
            ]"
            placeholder="All Statuses"
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

    <!-- ── Expenses Table ───────────────────────────────────────────────────── -->
    <ChTable
      :columns="columns"
      :rows="expenses as unknown as Record<string, unknown>[]"
      :total="total"
      :page="page"
      :page-size="25"
      :loading="isLoading"
      row-key="ExpenseID"
      hoverable
      exportable
      title="Expenses"
      @sort="handleSort"
      @update:page="page = $event"
    >
      <!-- Description cell -->
      <template #cell-Description="{ row }">
        <span class="description-cell">
          {{ (row as unknown as Expense).Description || '—' }}
        </span>
      </template>

      <!-- Amount cell: formatted currency, right-aligned -->
      <template #cell-Amount="{ row }">
        <span class="amount-cell">
          {{ formatCurrency((row as unknown as Expense).Amount) }}
        </span>
      </template>

      <!-- Date cell: human-readable -->
      <template #cell-ExpenseDate="{ row }">
        {{ formatDate((row as unknown as Expense).ExpenseDate) }}
      </template>

      <!-- Status cell -->
      <template #cell-status="{ row }">
        <ChBadge :variant="getStatusVariant(row as unknown as Expense)" size="sm">
          <template v-if="(row as unknown as Expense).ApprovedBy">
            <CheckCircle :size="12" />
          </template>
          <template v-else>
            <Clock :size="12" />
          </template>
          {{ getStatusLabel(row as unknown as Expense) }}
        </ChBadge>
      </template>

      <!-- Actions cell: view + delete -->
      <template #cell-actions="{ row }">
        <div class="actions-cell">
          <ChButton
            size="sm"
            variant="ghost"
            :icon-only="true"
            title="View expense"
            @click="router.push(`/finance/expenses/${(row as unknown as Expense).ExpenseID}`)"
          >
            <Eye :size="15" />
          </ChButton>
          <ChButton
            size="sm"
            variant="ghost"
            :icon-only="true"
            title="Delete expense"
            @click="openDeleteModal(row as unknown as Expense)"
          >
            <Trash2 :size="15" />
          </ChButton>
        </div>
      </template>
    </ChTable>

    <!-- ── Delete Confirmation Modal ────────────────────────────────────────── -->
    <ChModal
      v-model:open="showDeleteModal"
      title="Delete Expense"
      subtitle="This action cannot be undone."
      size="sm"
    >
      <p class="delete-message">
        Are you sure you want to delete the expense
        <strong>{{ expenseToDelete?.Description || 'this expense' }}</strong>
        for
        <strong>{{ expenseToDelete ? formatCurrency(expenseToDelete.Amount) : '' }}</strong>
        on
        <span class="delete-message__date">{{ formatDate(expenseToDelete?.ExpenseDate) }}</span>?
      </p>

      <template #footer>
        <ChButton variant="ghost" :disabled="isDeleting" @click="showDeleteModal = false">
          Cancel
        </ChButton>
        <ChButton variant="danger" :loading="isDeleting" @click="confirmDelete">
          Delete
        </ChButton>
      </template>
    </ChModal>
  </div>
</template>

<style scoped>
.view {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
  max-width: 1200px;
  margin: 0 auto;
}

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

.actions-cell {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--ch-space-1);
}

.amount-cell {
  font-variant-numeric: tabular-nums;
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

.description-cell {
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

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
