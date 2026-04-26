<script setup lang="ts">
/**
 * ContributionDetailView — View a single contribution with details.
 */
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { contributionService, lookupService } from '@/services/finance.service'
import type { FinanceLookupData } from '@/services/finance.service'
import { useToast, confirm } from '@/design-system'
import type { Contribution } from '@/types/finance'
import { ArrowLeft, Edit, Trash2, DollarSign } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const contributionId = Number(route.params.id)

// ── State ─────────────────────────────────────────────────────────────────────

const loading = ref(true)
const contribution = ref<Contribution | null>(null)
const lookupData = ref<FinanceLookupData | null>(null)
const isDeleting = ref(false)

// ── Data loaders ──────────────────────────────────────────────────────────────

async function loadContribution() {
  loading.value = true
  try {
    const [contribRes, lookupRes] = await Promise.all([
      contributionService.getById(contributionId),
      lookupService.getAll(),
    ])
    
    if (contribRes?.data?.data) {
      contribution.value = contribRes.data.data
    } else {
      toast.error('Contribution not found')
      router.push('/finance/contributions')
    }
    
    if (lookupRes?.data?.data) {
      lookupData.value = lookupRes.data.data as FinanceLookupData
    }
  } catch {
    toast.error('Failed to load contribution details')
    router.push('/finance/contributions')
  } finally {
    loading.value = false
  }
}

// ── Actions ───────────────────────────────────────────────────────────────────

function navigateToEdit() {
  router.push(`/finance/contributions/${contributionId}/edit`)
}

async function confirmDelete() {
  const confirmed = await confirm({
    title: 'Delete Contribution',
    message: `Are you sure you want to delete this contribution of ${formatCurrency(contribution.value?.ContributionAmount || 0)} from ${contribution.value?.MemberName}?`,
    confirmLabel: 'Delete',
    cancelLabel: 'Cancel',
  })
  
  if (!confirmed) return
  
  isDeleting.value = true
  try {
    await contributionService.delete(contributionId)
    toast.success('Contribution deleted successfully')
    router.push('/finance/contributions')
  } catch {
    toast.error('Failed to delete contribution')
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

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(() => {
  loadContribution()
})
</script>

<template>
  <div class="view">
    <!-- ── Header ─────────────────────────────────────────────────────────────── -->
    <ChPageHeader :title="contribution ? `Contribution #${contribution.ReceiptNumber || contribution.ContributionID}` : 'Contribution Details'">
      <template #leading>
        <ChButton variant="ghost" size="sm" @click="router.push('/finance/contributions')">
          <template #icon><ArrowLeft :size="16" /></template>
          Contributions
        </ChButton>
      </template>
      <template #actions v-if="contribution && !loading">
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
      <span>Loading contribution details...</span>
    </div>

    <!-- ── Content ────────────────────────────────────────────────────────────── -->
    <template v-else-if="contribution">
      <div class="detail-grid">
        <!-- Main Info Card -->
        <ChCard shadow="sm" class="main-card">
          <template #header>
            <div class="card-header">
              <DollarSign :size="24" class="header-icon" />
              <h2 class="card-title">Contribution Information</h2>
            </div>
          </template>
          
          <div class="amount-display">
            <span class="amount-label">Amount</span>
            <span class="amount-value">{{ formatCurrency(contribution.ContributionAmount) }}</span>
          </div>

          <ChDivider />

          <ChDataList
            :items="[
              { label: 'Member', value: contribution.MemberName || 'Unknown' },
              { label: 'Contribution Type', value: contribution.ContributionTypeName || '—' },
              { label: 'Contribution Date', value: formatDate(contribution.ContributionDate) },
              { label: 'Payment Method', value: contribution.PaymentMethodName || '—' },
              { label: 'Fiscal Year', value: contribution.FiscalYearName || '—' },
              { label: 'Receipt Number', value: contribution.ReceiptNumber || '—' },
            ]"
          />
        </ChCard>

        <!-- Notes Card -->
        <ChCard v-if="contribution.Notes" shadow="sm" class="notes-card">
          <template #header>
            <h2 class="card-title">Notes</h2>
          </template>
          <p class="notes-text">{{ contribution.Notes }}</p>
        </ChCard>

        <!-- Metadata Card -->
        <ChCard shadow="sm" class="meta-card">
          <template #header>
            <h2 class="card-title">Record Information</h2>
          </template>
          <ChDataList
            :items="[
              { label: 'Contribution ID', value: `#${contribution.ContributionID}` },
              { label: 'Created At', value: formatDateTime(contribution.CreatedAt) },
              { label: 'Record Status', value: contribution.Deleted ? 'Deleted' : 'Active' },
            ]"
          />
        </ChCard>
      </div>
    </template>

    <!-- ── Not Found State ─────────────────────────────────────────────────────── -->
    <div v-else class="empty-state">
      <ChEmptyState
        icon="search"
        title="Contribution not found"
        description="The contribution you're looking for doesn't exist or has been removed."
      >
        <ChButton variant="primary" @click="router.push('/finance/contributions')">
          Back to Contributions
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
  color: var(--ch-color-primary);
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
  background: linear-gradient(135deg, var(--ch-color-primary-subtle), var(--ch-color-primary-muted));
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
  color: var(--ch-color-primary);
  font-variant-numeric: tabular-nums;
}

.notes-card .notes-text {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text);
  line-height: var(--ch-leading-relaxed);
  white-space: pre-wrap;
  margin: 0;
}

.meta-card {
  background: var(--ch-color-bg-subtle);
}
</style>
