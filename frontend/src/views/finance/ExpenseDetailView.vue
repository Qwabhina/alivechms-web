<script setup lang="ts">
/**
 * ExpenseDetailView — View a single expense with details.
 */
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { expenseService } from '@/services/expense.service'
import { lookupService } from '@/services/finance.service'
import type { FinanceLookupData } from '@/services/finance.service'
import { useToast, confirm } from '@/design-system'
import type { ExpenseDetail } from '@/types/finance'
import { ArrowLeft, Edit, Trash2, DollarSign, Receipt, CheckCircle, XCircle, Clock } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const expenseId = Number(route.params.id)

// ── State ─────────────────────────────────────────────────────────────────────

const loading = ref(true)
const expense = ref<ExpenseDetail | null>(null)
const lookupData = ref<FinanceLookupData | null>(null)
const isDeleting = ref(false)

// ── Data loaders ──────────────────────────────────────────────────────────────

async function loadExpense() {
  loading.value = true
  try {
    const [expenseRes, lookupRes] = await Promise.all([
      expenseService.get(expenseId),
      lookupService.getAll(),
    ])
    
    if (expenseRes?.data) {
      expense.value = expenseRes.data
    } else {
      toast.error('Expense not found')
      router.push('/finance/expenses')
    }
    
    if (lookupRes?.data?.data) {
      lookupData.value = lookupRes.data.data as FinanceLookupData
    }
  } catch {
    toast.error('Failed to load expense details')
    router.push('/finance/expenses')
  } finally {
    loading.value = false
  }
}

// ── Actions ───────────────────────────────────────────────────────────────────

function navigateToEdit() {
  router.push(`/finance/expenses/${expenseId}/edit`)
}

async function confirmDelete() {
  if (!expense.value) return
  
  const confirmed = await confirm({
    title: 'Delete Expense',
    message: `Are you sure you want to delete this expense of ${formatCurrency(expense.value.Amount)}?`,
    confirmLabel: 'Delete',
    cancelLabel: 'Cancel',
  })
  
  if (!confirmed) return
  
  isDeleting.value = true
  try {
    await expenseService.delete(expenseId)
    toast.success('Expense deleted successfully')
    router.push('/finance/expenses')
  } catch {
    toast.error('Failed to delete expense')
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
    month: 'long',
    year: 'numeric',
  })
}

function formatDateTime(dateStr: string | null | undefined): string {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getStatusIcon(status: string) {
  switch (status) {
    case 'approved': return CheckCircle
    case 'rejected': return XCircle
    case 'cancelled': return XCircle
    default: return Clock
  }
}

function getStatusVariant(status: string): 'success' | 'danger' | 'warning' | 'default' {
  switch (status) {
    case 'approved': return 'success'
    case 'rejected': return 'danger'
    case 'cancelled': return 'danger'
    default: return 'warning'
  }
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(() => {
  loadExpense()
})
</script>

<template>
  <div class="view">
    <!-- ── Header ─────────────────────────────────────────────────────────────── -->
    <ChPageHeader :title="expense ? `Expense #${expense.ExpenseID}` : 'Expense Details'">
      <template #leading>
        <ChButton variant="ghost" size="sm" @click="router.push('/finance/expenses')">
          <template #icon><ArrowLeft :size="16" /></template>
          Expenses
        </ChButton>
      </template>
      <template #actions v-if="expense && !loading">
        <ChButton variant="outline" @click="navigateToEdit">
          <template #icon><Edit :size="18" /></template>
          Edit
        </ChButton>
        <ChButton variant="danger" :loading="isDeleting" @click="confirmDelete">
          <template #icon><Trash2 :size="18" /></template>
          Delete
        </ChButton>
      </template>
    </ChPageHeader>

    <!-- ── Loading State ─────────────────────────────────────────────────────── -->
    <div v-if="loading" class="loading-state">
      <ChSpinner size="lg" />
      <span>Loading expense details...</span>
    </div>

    <!-- ── Content ────────────────────────────────────────────────────────────── -->
    <template v-else-if="expense">
      <div class="detail-grid">
        <!-- Main Info Card -->
        <ChCard shadow="sm" class="main-card">
          <template #header>
            <div class="card-header">
              <DollarSign :size="24" class="header-icon" />
              <h2 class="card-title">Expense Information</h2>
            </div>
          </template>
          
          <div class="amount-display">
            <span class="amount-label">Amount</span>
            <span class="amount-value">{{ formatCurrency(expense.Amount) }}</span>
          </div>

          <ChDivider />

          <ChDataList
            :items="[
              { label: 'Category', value: expense.CategoryName || '—' },
              { label: 'Description', value: expense.Description || '—' },
              { label: 'Expense Date', value: formatDate(expense.ExpenseDate) },
              { label: 'Fiscal Year', value: expense.FiscalYearID ? `FY #${expense.FiscalYearID}` : '—' },
            ]"
          />
        </ChCard>

        <!-- Status Card -->
        <ChCard shadow="sm" class="status-card">
          <template #header>
            <h2 class="card-title">Status</h2>
          </template>
          
          <div class="status-display">
            <ChBadge :variant="getStatusVariant(expense.Status || 'pending')" size="lg">
              <template #icon>
                <component :is="getStatusIcon(expense.Status || 'pending')" :size="16" />
              </template>
              {{ expense.Status || 'Pending' }}
            </ChBadge>
            
            <div v-if="expense.ApprovedByName" class="approval-info">
              <span class="approval-label">Approved by</span>
              <span class="approval-value">{{ expense.ApprovedByName }}</span>
              <span v-if="expense.ApprovedAt" class="approval-date">
                on {{ formatDateTime(expense.ApprovedAt) }}
              </span>
            </div>
            
            <div v-if="expense.RejectionReason" class="rejection-reason">
              <span class="rejection-label">Rejection Reason:</span>
              <p class="rejection-text">{{ expense.RejectionReason }}</p>
            </div>
          </div>
        </ChCard>

        <!-- Metadata Card -->
        <ChCard shadow="sm" class="meta-card">
          <template #header>
            <h2 class="card-title">Record Information</h2>
          </template>
          <ChDataList
            :items="[
              { label: 'Expense ID', value: `#${expense.ExpenseID}` },
              { label: 'Created By', value: expense.CreatedByName || '—' },
              { label: 'Created At', value: formatDateTime(expense.CreatedAt) },
              { label: 'Record Status', value: expense.Deleted ? 'Deleted' : 'Active' },
            ]"
          />
        </ChCard>

        <!-- Proof Files Card -->
        <ChCard v-if="expense.proofFiles && expense.proofFiles.length > 0" shadow="sm" class="files-card">
          <template #header>
            <h2 class="card-title">Supporting Documents</h2>
          </template>
          <div class="files-list">
            <a
              v-for="file in expense.proofFiles"
              :key="file.FileID"
              :href="file.FileUrl"
              target="_blank"
              class="file-link"
            >
              <Receipt :size="16" />
              <span class="file-name">{{ file.FileName }}</span>
              <span class="file-date">{{ formatDate(file.UploadedAt) }}</span>
            </a>
          </div>
        </ChCard>
      </div>
    </template>

    <!-- ── Not Found State ─────────────────────────────────────────────────────── -->
    <div v-else class="empty-state">
      <ChEmptyState
        icon="search"
        title="Expense not found"
        description="The expense you're looking for doesn't exist or has been removed."
      >
        <ChButton variant="primary" @click="router.push('/finance/expenses')">
          Back to Expenses
        </ChButton>
      </ChEmptyState>
    </div>
  </div>
</template>

<style scoped>
.view {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
  max-width: 900px;
  margin: 0 auto;
  padding-bottom: var(--ch-space-8);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--ch-space-4);
  padding: var(--ch-space-12);
  color: var(--ch-color-text-muted);
}

.empty-state {
  padding: var(--ch-space-12) 0;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--ch-space-6);
}

.card-header {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
}

.header-icon {
  color: var(--ch-color-danger);
}

.card-title {
  font-size: var(--ch-text-lg);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  margin: 0;
}

.amount-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: var(--ch-space-6);
  background: linear-gradient(135deg, var(--ch-color-danger-subtle), var(--ch-color-danger-muted));
  border-radius: var(--ch-radius-lg);
  margin-bottom: var(--ch-space-4);
}

.amount-label {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.amount-value {
  font-size: var(--ch-text-3xl);
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-danger);
  font-variant-numeric: tabular-nums;
}

.status-display {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-4);
  align-items: flex-start;
}

.approval-info {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-1);
  padding: var(--ch-space-3);
  background: var(--ch-color-bg-subtle);
  border-radius: var(--ch-radius-md);
  width: 100%;
}

.approval-label {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

.approval-value {
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
}

.approval-date {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
}

.rejection-reason {
  padding: var(--ch-space-3);
  background: var(--ch-color-danger-subtle);
  border-radius: var(--ch-radius-md);
  border-left: 3px solid var(--ch-color-danger);
  width: 100%;
}

.rejection-label {
  font-size: var(--ch-text-xs);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-danger);
  display: block;
  margin-bottom: var(--ch-space-1);
}

.rejection-text {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text);
  margin: 0;
}

.meta-card {
  background: var(--ch-color-bg-subtle);
}

.files-list {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-2);
}

.file-link {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  padding: var(--ch-space-3);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-md);
  text-decoration: none;
  transition: background-color 0.15s ease;
}

.file-link:hover {
  background: var(--ch-color-bg-subtle);
}

.file-link svg {
  color: var(--ch-color-primary);
  flex-shrink: 0;
}

.file-name {
  flex: 1;
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text);
}

.file-date {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}
</style>
