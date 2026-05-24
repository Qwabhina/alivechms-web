<script setup lang="ts">
/**
 * DashboardView — Live overview of key church metrics.
 * Fetches data from the dashboard API on mount and renders:
 *   - 4 stat cards (members, new members, net balance, upcoming events)
 *   - Recent members list with avatar rows and profile navigation
 *   - Finance overview mini-stats (income, expenses, net balance)
 */

import { useRouter } from 'vue-router'
import { dashboardService } from '@/services/dashboard.service'
import { useToast, ChPageHeader } from '@/design-system'
import type { DashboardOverview } from '@/types/operations'
import { Users, Wallet, TrendingUp, CalendarDays, ArrowRight, DollarSign, LayoutDashboardIcon } from '@lucide/vue'
import { normalizeProfileImage } from '@/utils/image'

// ── Router & Toast ────────────────────────────────────────────────────────────
const router = useRouter()
const toast = useToast()

// ── State ─────────────────────────────────────────────────────────────────────
const isLoading = ref(true)
const overview = ref<DashboardOverview | null>(null)
const errorMessage = ref<string | null>(null)

// ── Helpers ───────────────────────────────────────────────────────────────────

/** Format a number as Ghanaian Cedi currency string. e.g. "GH₵ 24,500.00" */
function formatCurrency(amount: number): string {
  return (
    'GH₵ ' +
    amount.toLocaleString('en-GH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  )
}

/** Format an ISO date string to a human-readable date. e.g. "12 Jan 2024" */
function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

// ── Computed ──────────────────────────────────────────────────────────────────

/** Net balance card variant — green when ≥ 0, red when negative. */
const netBalanceVariant = computed<'success' | 'danger'>(() =>
  (overview.value?.finance.net_balance ?? 0) >= 0 ? 'success' : 'danger',
)

/** Net balance icon background class, used in the finance overview card. */
const netBalanceIconClass = computed(() =>
  netBalanceVariant.value === 'success'
    ? 'finance-stat__icon--income'
    : 'finance-stat__icon--expense',
)

/** Net balance value class for coloured text in the finance overview card. */
const netBalanceValueClass = computed(() =>
  netBalanceVariant.value === 'success'
    ? 'finance-stat__value--income'
    : 'finance-stat__value--expense',
)

/** The first 5 entries of recent_members (API returns them newest-first). */
const recentMembers = computed(() => overview.value?.recent_members.slice(0, 5) ?? [])

// ── Data fetch ────────────────────────────────────────────────────────────────

onMounted(async () => {
  try {
    const response = await dashboardService.getOverview()
    overview.value = response.data.data ?? null
  } catch (err: unknown) {
    const message =
      err instanceof Error ? err.message : 'Failed to load dashboard data. Please try again.'
    errorMessage.value = message
    toast.error(message, { title: 'Dashboard Error' })
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="dashboard">
    <ChPageHeader title="Dashboard" subtitle="Your church at a glance." titleTag="h2">
      <template #icon>
        <LayoutDashboardIcon :size="22" aria-hidden="true" />
      </template>
    </ChPageHeader>
    <!-- ── Error alert ────────────────────────────────────────────────── -->
    <Transition name="alert-fade">
      <ChAlert
        v-if="errorMessage"
        variant="danger"
        title="Unable to load dashboard"
        dismissible
        @dismiss="errorMessage = null"
      >
        {{ errorMessage }}
      </ChAlert>
    </Transition>

    <!-- ── Stat cards ─────────────────────────────────────────────────── -->
    <div class="stat-grid">
      <!-- Total Members -->
      <ChStatCard
        label="Total Members"
        :value="overview?.members.total ?? '—'"
        :loading="isLoading"
        variant="primary"
      >
        <template #icon>
          <Users :size="20" />
        </template>
      </ChStatCard>

      <!-- New This Month -->
      <ChStatCard
        label="New This Month"
        :value="overview?.members.new_this_month ?? '—'"
        :loading="isLoading"
        variant="info"
      >
        <template #icon>
          <TrendingUp :size="20" />
        </template>
      </ChStatCard>

      <!-- Net Balance — variant flips based on sign -->
      <ChStatCard
        label="Net Balance"
        :value="overview ? formatCurrency(overview.finance.net_balance) : '—'"
        :loading="isLoading"
        :variant="netBalanceVariant"
      >
        <template #icon>
          <Wallet :size="20" />
        </template>
      </ChStatCard>

      <!-- Upcoming Events -->
      <ChStatCard
        label="Upcoming Events"
        :value="overview?.events.upcoming ?? '—'"
        :loading="isLoading"
        variant="warning"
      >
        <template #icon>
          <CalendarDays :size="20" />
        </template>
      </ChStatCard>
    </div>

    <!-- ── Bottom grid: Recent Members + Finance Overview ─────────────── -->
    <div class="bottom-grid">
      <!-- Recent Members ──────────────────────────────────────────────── -->
      <ChCard shadow="sm">
        <template #header>
          <span class="card-title">Recent Members</span>
          <ChButton variant="ghost" size="sm" @click="router.push('/members')"> View all </ChButton>
        </template>

        <!-- Loading: centered spinner -->
        <div v-if="isLoading" class="card-spinner" aria-busy="true">
          <ChSpinner size="md" label="Loading members…" />
        </div>

        <!-- Empty state -->
        <div v-else-if="recentMembers.length === 0" class="empty-state" role="status">
          <Users :size="36" class="empty-state__icon" aria-hidden="true" />
          <p class="empty-state__text">No members found.</p>
          <ChButton variant="outline" size="sm" @click="router.push('/members/create')">
            Add first member
          </ChButton>
        </div>

        <!-- Member rows -->
        <ul v-else class="recent-list" role="list">
          <li v-for="member in recentMembers" :key="member.MbrID" class="recent-row">
            <!-- Avatar -->
            <ChAvatar
              :name="`${member.MbrFirstName} ${member.MbrFamilyName}`"
              :src="normalizeProfileImage(member.MbrProfilePicture)"
              size="sm"
            />

            <!-- Name + date -->
            <div class="recent-row__info">
              <span class="recent-row__name">
                {{ member.MbrFirstName }} {{ member.MbrFamilyName }}
              </span>
              <span class="recent-row__date">
                Joined {{ formatDate(member.MbrRegistrationDate) }}
              </span>
            </div>

            <!-- Navigate to member detail -->
            <ChButton
              variant="ghost"
              size="sm"
              icon-only
              :aria-label="`View ${member.MbrFirstName} ${member.MbrFamilyName}'s profile`"
              @click="router.push(`/members/${member.MbrID}`)"
            >
              <ArrowRight :size="16" />
            </ChButton>
          </li>
        </ul>
      </ChCard>

      <!-- Finance Overview ────────────────────────────────────────────── -->
      <ChCard shadow="sm">
        <template #header>
          <span class="card-title">Finance Overview</span>
          <ChButton variant="ghost" size="sm" @click="router.push('/finance/contributions')">
            View all
          </ChButton>
        </template>

        <!-- Loading: centered spinner -->
        <div v-if="isLoading" class="card-spinner" aria-busy="true">
          <ChSpinner size="md" label="Loading finance data…" />
        </div>

        <!-- Finance stats -->
        <div v-else class="finance-stats">
          <!-- Total Income -->
          <div class="finance-stat">
            <div class="finance-stat__icon finance-stat__icon--income" aria-hidden="true">
              <DollarSign :size="18" />
            </div>
            <div class="finance-stat__content">
              <span class="finance-stat__label">Total Income</span>
              <span class="finance-stat__value finance-stat__value--income">
                {{ overview ? formatCurrency(overview.finance.total_income) : '—' }}
              </span>
            </div>
          </div>

          <div class="finance-divider" role="separator" />

          <!-- Total Expenses -->
          <div class="finance-stat">
            <div class="finance-stat__icon finance-stat__icon--expense" aria-hidden="true">
              <DollarSign :size="18" />
            </div>
            <div class="finance-stat__content">
              <span class="finance-stat__label">Total Expenses</span>
              <span class="finance-stat__value finance-stat__value--expense">
                {{ overview ? formatCurrency(overview.finance.total_expenses) : '—' }}
              </span>
            </div>
          </div>

          <div class="finance-divider" role="separator" />

          <!-- Net Balance — dynamically coloured -->
          <div class="finance-stat">
            <div class="finance-stat__icon" :class="netBalanceIconClass" aria-hidden="true">
              <Wallet :size="18" />
            </div>
            <div class="finance-stat__content">
              <span class="finance-stat__label">Net Balance</span>
              <span class="finance-stat__value" :class="netBalanceValueClass">
                {{ overview ? formatCurrency(overview.finance.net_balance) : '—' }}
              </span>
            </div>
          </div>
        </div>
      </ChCard>
    </div>
  </div>
</template>

<style scoped>
/* ── Root ─────────────────────────────────────────────────────────────────── */
.dashboard {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
  /* max-width: 1200px; */
}

/* ── Page heading ─────────────────────────────────────────────────────────── */
.dashboard__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.dashboard__title {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-2xl);
  font-weight: var(--ch-font-bold);
  color: var(--ch-color-text);
  line-height: var(--ch-leading-tight);
  margin: 0;
}

.dashboard__subtitle {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin: var(--ch-space-1) 0 0;
}

/* ── Alert transition ─────────────────────────────────────────────────────── */
.alert-fade-enter-active,
.alert-fade-leave-active {
  transition:
    opacity var(--ch-duration-fast) var(--ch-ease-out),
    transform var(--ch-duration-fast) var(--ch-ease-out);
}

.alert-fade-enter-from,
.alert-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* ── Stat cards grid ──────────────────────────────────────────────────────── */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: var(--ch-space-5);
}


/* ── Bottom two-column grid ───────────────────────────────────────────────── */
.bottom-grid {
  display: grid;
  grid-template-columns: 3fr 2fr;
  gap: var(--ch-space-5);
  align-items: start;
}

@media (max-width: 768px) {
  .bottom-grid {
    grid-template-columns: 1fr;
  }
}

/* ── Shared card header typography ───────────────────────────────────────── */
.card-title {
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

/* ── Spinner centering wrapper (used in both cards) ───────────────────────── */
.card-spinner {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--ch-space-10) var(--ch-space-5);
}

/* ── Empty state ──────────────────────────────────────────────────────────── */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--ch-space-3);
  padding: var(--ch-space-8) var(--ch-space-5);
  text-align: center;
}

.empty-state__icon {
  color: var(--ch-color-text-subtle);
  opacity: 0.5;
}

.empty-state__text {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin: 0;
}

/* ── Recent members list ──────────────────────────────────────────────────── */
.recent-list {
  display: flex;
  flex-direction: column;
  list-style: none;
  margin: 0;
  padding: 0;
}

.recent-row {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  padding: var(--ch-space-3) 0;
  border-bottom: 1px solid var(--ch-color-border);
  transition: background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.recent-row:first-child {
  padding-top: 0;
}

.recent-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.recent-row__info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-0_5);
  min-width: 0; /* allow text truncation in narrow containers */
}

.recent-row__name {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.recent-row__date {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

/* ── Finance stats ────────────────────────────────────────────────────────── */
.finance-stats {
  display: flex;
  flex-direction: column;
}

.finance-stat {
  display: flex;
  align-items: center;
  gap: var(--ch-space-4);
  padding: var(--ch-space-4) 0;
}

.finance-stat:first-child {
  padding-top: 0;
}

.finance-stat:last-child {
  padding-bottom: 0;
}

.finance-divider {
  height: 1px;
  background-color: var(--ch-color-border);
  flex-shrink: 0;
}

/* Icon circle */
.finance-stat__icon {
  width: 40px;
  height: 40px;
  border-radius: var(--ch-radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.finance-stat__icon--income {
  background-color: var(--ch-color-success-bg);
  color: var(--ch-color-success);
}

.finance-stat__icon--expense {
  background-color: var(--ch-color-danger-bg);
  color: var(--ch-color-danger);
}

/* Stat text content */
.finance-stat__content {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-0_5);
  min-width: 0;
}

.finance-stat__label {
  font-size: var(--ch-text-xs);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text-muted);
  text-transform: uppercase;
  letter-spacing: var(--ch-tracking-wide);
}

.finance-stat__value {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-xl);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  line-height: var(--ch-leading-tight);
}

.finance-stat__value--income {
  color: var(--ch-color-success);
}

.finance-stat__value--expense {
  color: var(--ch-color-danger);
}
</style>
