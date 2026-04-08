<script setup lang="ts">
/**
 * DashboardView — Skeleton overview. Phase 2 will wire up real API data.
 * Uses ChStatCard and ChCard from the design system.
 */
import { Users, Wallet, TrendingUp, CalendarDays } from 'lucide-vue-next'

// Loading state — true until real API data arrives (Phase 2)
const loading = true
</script>

<template>
  <div class="dashboard">

    <!-- Page heading -->
    <div class="dashboard__header">
      <div>
        <h1 class="dashboard__title">Dashboard</h1>
        <p class="dashboard__subtitle">Your church at a glance.</p>
      </div>
    </div>

    <!-- Stat cards row — ChStatCard with loading skeleton state -->
    <div class="stat-grid">
      <ChStatCard
        label="Total Members"
        value="—"
        :loading="loading"
        variant="primary"
      >
        <template #icon>
          <Users :size="20" />
        </template>
      </ChStatCard>

      <ChStatCard
        label="Monthly Contributions"
        value="—"
        :loading="loading"
        variant="success"
      >
        <template #icon>
          <Wallet :size="20" />
        </template>
      </ChStatCard>

      <ChStatCard
        label="New This Month"
        value="—"
        :loading="loading"
        variant="info"
      >
        <template #icon>
          <TrendingUp :size="20" />
        </template>
      </ChStatCard>

      <ChStatCard
        label="Upcoming Events"
        value="—"
        :loading="loading"
        variant="warning"
      >
        <template #icon>
          <CalendarDays :size="20" />
        </template>
      </ChStatCard>
    </div>

    <!-- Recent members card — ChCard -->
    <ChCard shadow="sm">
      <template #header>
        <span class="card-title">Recent Members</span>
        <ChButton variant="ghost" size="sm" @click="$router.push('/members')">
          View all
        </ChButton>
      </template>

      <!-- Skeleton rows while loading -->
      <div class="recent-list">
        <div v-for="i in 5" :key="i" class="recent-row">
          <div class="recent-row__avatar skeleton" />
          <div class="recent-row__info">
            <div class="skeleton skeleton--name" />
            <div class="skeleton skeleton--sub" />
          </div>
          <div class="skeleton skeleton--date" />
        </div>
      </div>
    </ChCard>

  </div>
</template>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-6);
  max-width: 1200px;
}

/* ── Header ────────────────────────────────────────────────────────────── */
.dashboard__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.dashboard__title {
  font-size: var(--ch-text-2xl);
  font-weight: var(--ch-font-bold);
  font-family: var(--ch-font-display);
  color: var(--ch-color-text);
  margin: 0;
}

.dashboard__subtitle {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin: var(--ch-space-1) 0 0;
}

/* ── Stat grid ─────────────────────────────────────────────────────────── */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: var(--ch-space-5);
}

/* ── Card header ───────────────────────────────────────────────────────── */
.card-title {
  font-size: var(--ch-text-base);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
}

/* ── Recent members skeleton ───────────────────────────────────────────── */
.recent-list {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-3);
}

.recent-row {
  display: flex;
  align-items: center;
  gap: var(--ch-space-3);
  padding: var(--ch-space-2) 0;
}

.recent-row__avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  flex-shrink: 0;
}

.recent-row__info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-1_5);
}

/* Skeleton shimmer */
.skeleton {
  background: linear-gradient(
    90deg,
    var(--ch-color-bg-muted) 0%,
    var(--ch-color-bg-subtle) 50%,
    var(--ch-color-bg-muted) 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.4s ease-in-out infinite;
  border-radius: var(--ch-radius-sm);
}

.skeleton--name { height: 14px; width: 55%; }
.skeleton--sub  { height: 12px; width: 35%; }
.skeleton--date { height: 12px; width: 80px; flex-shrink: 0; }

@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
