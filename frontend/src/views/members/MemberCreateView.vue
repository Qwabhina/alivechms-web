<script setup lang="ts">
/**
 * MemberCreateView — Stub. Phase 3.
 */
import { ArrowLeft } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
const router = useRouter()
</script>

<template>
  <div class="view">
    <div class="view-header">
      <ChButton variant="ghost" size="sm" @click="router.back()">
        <template #icon><ArrowLeft :size="16" /></template>
        Back
      </ChButton>
      <h1 class="view-title">Add New Member</h1>
    </div>

    <ChCard shadow="sm">
      <div class="coming-soon">
        <p>Member registration form — coming in Phase 3.</p>
      </div>
    </ChCard>
  </div>
</template>

<style scoped>
.view { display: flex; flex-direction: column; gap: var(--ch-space-4); max-width: 800px; }
.view-header { display: flex; align-items: center; gap: var(--ch-space-3); }
.view-title { font-size: var(--ch-text-xl); font-weight: var(--ch-font-bold); font-family: var(--ch-font-display); color: var(--ch-color-text); margin: 0; }
.coming-soon { padding: var(--ch-space-12) 0; text-align: center; color: var(--ch-color-text-subtle); font-size: var(--ch-text-sm); }
</style>
