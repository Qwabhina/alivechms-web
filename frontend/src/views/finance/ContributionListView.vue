<script setup lang="ts">
/**
 * ContributionListView — Stub. Phase 4.
 */
import { Plus } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
const router = useRouter()
</script>

<template>
  <div class="view">
    <div class="view-header">
      <div>
        <h1 class="view-title">Contributions</h1>
        <p class="view-subtitle">Track and manage financial contributions.</p>
      </div>
      <ChButton variant="primary" @click="router.push('/finance/contributions/create')">
        <template #icon><Plus :size="18" /></template>
        Record Contribution
      </ChButton>
    </div>

    <ChCard shadow="sm">
      <template #header>
        <span class="card-label">All Contributions</span>
      </template>
      <div class="coming-soon">
        <p>Contribution list with filters, totals, and export — coming in Phase 4.</p>
      </div>
    </ChCard>
  </div>
</template>

<style scoped>
.view { display: flex; flex-direction: column; gap: var(--ch-space-6); max-width: 1200px; }
.view-header { display: flex; align-items: flex-start; justify-content: space-between; gap: var(--ch-space-4); }
.view-title { font-size: var(--ch-text-2xl); font-weight: var(--ch-font-bold); font-family: var(--ch-font-display); color: var(--ch-color-text); margin: 0; }
.view-subtitle { font-size: var(--ch-text-sm); color: var(--ch-color-text-muted); margin: var(--ch-space-1) 0 0; }
.card-label { font-size: var(--ch-text-base); font-weight: var(--ch-font-semibold); color: var(--ch-color-text); }
.coming-soon { padding: var(--ch-space-12) 0; text-align: center; color: var(--ch-color-text-subtle); font-size: var(--ch-text-sm); }
</style>
