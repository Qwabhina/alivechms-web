<script setup lang="ts">
/**
 * MemberDetailView — Stub. Phase 3.
 */
import { ArrowLeft, Pencil } from 'lucide-vue-next'
import { useRouter, useRoute } from 'vue-router'
const router = useRouter()
const route = useRoute()
</script>

<template>
  <div class="view">
    <div class="view-header">
      <ChButton variant="ghost" size="sm" @click="router.push('/members')">
        <template #icon><ArrowLeft :size="16" /></template>
        Members
      </ChButton>
      <ChButton variant="outline" size="sm" @click="router.push(`/members/${route.params.id}/edit`)">
        <template #icon><Pencil :size="14" /></template>
        Edit
      </ChButton>
    </div>

    <ChCard shadow="sm">
      <div class="coming-soon">
        <p>Member profile for ID #{{ route.params.id }} — coming in Phase 3.</p>
      </div>
    </ChCard>
  </div>
</template>

<style scoped>
.view { display: flex; flex-direction: column; gap: var(--ch-space-4); max-width: 900px; }
.view-header { display: flex; align-items: center; justify-content: space-between; }
.coming-soon { padding: var(--ch-space-12) 0; text-align: center; color: var(--ch-color-text-subtle); font-size: var(--ch-text-sm); }
</style>
