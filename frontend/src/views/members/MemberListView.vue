<script setup lang="ts">
/**
 * MemberListView — Stub view using design system components.
 * Full implementation in Phase 3.
 */
import { UserPlus } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
const router = useRouter()
</script>

<template>
  <div class="view">
    <div class="view-header">
      <div>
        <h1 class="view-title">Members</h1>
        <p class="view-subtitle">Manage your church membership directory.</p>
      </div>
      <ChButton variant="primary" @click="router.push('/members/create')">
        <template #icon><UserPlus :size="18" /></template>
        Add Member
      </ChButton>
    </div>

    <ChCard shadow="sm">
      <template #header>
        <span class="card-label">All Members</span>
      </template>
      <div class="coming-soon">
        <p>Member list with search, filters, and pagination — coming in Phase 3.</p>
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
