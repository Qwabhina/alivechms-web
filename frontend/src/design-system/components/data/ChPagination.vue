<script setup lang="ts">
/**
 * @component ChPagination
 * @path /frontend/src/design-system/components/data/ChPagination.vue
 * @description Standalone pagination control for navigating through paged data.
 *
 * Extracted as a separate component so pagination can be used outside of
 * ChTable — e.g. in a member card grid, a photo gallery, or any list view
 * that isn't tabular.
 *
 * ─── Page model ──────────────────────────────────────────────────────────────
 * Pages are 1-indexed. The component displays:
 *   1. A summary: "1–10 of 248"
 *   2. Page number buttons with ellipsis for large page counts
 *   3. Previous / Next arrow buttons
 *
 * ─── Smart ellipsis ──────────────────────────────────────────────────────────
 * When there are ≤ 7 pages, all page numbers are shown.
 * When there are > 7 pages, ellipsis (…) is inserted between distant pages:
 *   [1] … [4] [5] [6] … [20]
 *
 * @example Basic usage
 * <ChPagination
 *   :page="currentPage"
 *   :total="totalItems"
 *   :page-size="20"
 *   @update:page="currentPage = $event"
 * />
 *
 * @example With v-model
 * <ChPagination v-model:page="page" :total="500" :page-size="25" />
 */

import { computed } from 'vue'
import ChButton from '../core/ChButton.vue'

// ─── Props ────────────────────────────────────────────────────────────────────
interface Props {
  /** Current page number (1-indexed). Use v-model:page for two-way binding. */
  page:      number

  /** Total number of items across all pages. */
  total:     number

  /** Number of items per page. Default: 10 */
  pageSize?: number
}

const props = withDefaults(defineProps<Props>(), {
  pageSize: 10,
})

const emit = defineEmits<{
  /** Emitted when the user navigates to a different page. */
  'update:page': [page: number]
}>()

// ─── Computed ─────────────────────────────────────────────────────────────────

const totalPages = computed(() =>
  props.total ? Math.ceil(props.total / props.pageSize) : 0
)

/**
 * Builds the page number array with ellipsis markers (represented as 0).
 * Shows all pages when ≤ 7, otherwise shows first, last, and a window
 * of ±1 around the current page with ellipsis gaps.
 */
const pageNumbers = computed(() => {
  const total = totalPages.value
  const cur = props.page

  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

  const pages: (number | 0)[] = [1]
  if (cur > 3) pages.push(0) // left ellipsis
  for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) {
    pages.push(i)
  }
  if (cur < total - 2) pages.push(0) // right ellipsis
  pages.push(total)

  return pages
})

/** Summary text: "1–10 of 248" */
const summaryText = computed(() => {
  const start = ((props.page - 1) * props.pageSize + 1).toLocaleString()
  const end = Math.min(props.page * props.pageSize, props.total).toLocaleString()
  const total = props.total.toLocaleString()
  return `${start}–${end} of ${total}`
})

// ─── Navigation ───────────────────────────────────────────────────────────────

function goToPage(p: number) {
  if (p >= 1 && p <= totalPages.value && p !== props.page) {
    emit('update:page', p)
  }
}
</script>

<template>
  <div
    v-if="total && totalPages > 1"
    class="ch-pagination"
    role="navigation"
    aria-label="Pagination"
  >
    <!-- Summary: "1–10 of 248" -->
    <span class="ch-pagination__summary">{{ summaryText }}</span>

    <!-- Page buttons -->
    <div class="ch-pagination__pages">
      <!-- Previous -->
      <ChButton
        variant="ghost"
        size="sm"
        :disabled="page <= 1"
        :icon-only="true"
        aria-label="Previous page"
        @click="goToPage(page - 1)"
      >
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M10 12L6 8l4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </ChButton>

      <!-- Page numbers with ellipsis -->
      <template v-for="(p, i) in pageNumbers" :key="i">
        <span v-if="p === 0" class="ch-pagination__ellipsis" aria-hidden="true">…</span>
        <ChButton
          v-else
          :variant="p === page ? 'primary' : 'ghost'"
          size="sm"
          :aria-label="`Page ${p}`"
          :aria-current="p === page ? 'page' : undefined"
          @click="goToPage(p)"
        >{{ p }}</ChButton>
      </template>

      <!-- Next -->
      <ChButton
        variant="ghost"
        size="sm"
        :disabled="page >= totalPages"
        :icon-only="true"
        aria-label="Next page"
        @click="goToPage(page + 1)"
      >
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </ChButton>
    </div>
  </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-pagination {
  display:         flex;
  align-items:     center;
  justify-content: space-between;
  flex-wrap:       wrap;
  gap:             var(--ch-space-3);
}

/* ─── Summary text ────────────────────────────────────────────────────────── */
.ch-pagination__summary {
  font-size: var(--ch-text-sm);
  color:     var(--ch-color-text-muted);
}

/* ─── Page buttons ────────────────────────────────────────────────────────── */
.ch-pagination__pages {
  display:     flex;
  align-items: center;
  gap:         var(--ch-space-1);
}

/* ─── Ellipsis ────────────────────────────────────────────────────────────── */
.ch-pagination__ellipsis {
  padding:     0 var(--ch-space-1);
  color:       var(--ch-color-text-subtle);
  font-size:   var(--ch-text-sm);
  user-select: none;
}
</style>