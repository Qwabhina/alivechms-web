<script setup lang="ts">
/**
 * @component ChDataList
 * @path /frontend/src/design-system/components/data/ChDataList.vue
 * @description A structured label/value list for displaying record details.
 *
 * Used on detail pages to present an entity's fields in a clean,
 * scannable two-column layout. Think of it as a visual representation
 * of a database row — each item has a label and a value.
 *
 * ─── Layout modes ────────────────────────────────────────────────────────────
 * - `horizontal` (default) → label on the left, value on the right (wide screens)
 * - `vertical`             → label above value, stacked (narrow cards/panels)
 *
 * ─── Use cases ───────────────────────────────────────────────────────────────
 * - Member profile panel (Name, DOB, Phone, Email, Join Date, Membership Type)
 * - Event detail sidebar (Date, Location, Organizer, Group, Capacity)
 * - Contribution receipt (Amount, Type, Date, Method, Reference)
 * - Expense record (Category, Vendor, Amount, Approved By, Receipt)
 *
 * @example From data array
 * <ChDataList :items="[
 *   { label: 'Full Name',   value: 'John Addo' },
 *   { label: 'Phone',       value: '+233 24 000 0000' },
 *   { label: 'Member Since', value: 'March 2019' },
 *   { label: 'Status',       value: 'Active', type: 'badge', variant: 'success' },
 * ]" />
 *
 * @example Custom value slot
 * <ChDataList :items="memberFields">
 *   <template #value-avatar="{ item }">
 *     <ChAvatar :src="item.value" :name="member.name" size="sm" />
 *   </template>
 * </ChDataList>
 */

import { computed } from 'vue'
import ChBadge from '../core/ChBadge.vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * The "type" of value to render.
 * - `text`  → plain string (default)
 * - `badge` → render value inside a ChBadge
 * - `slot`  → use a named slot for custom rendering: `#value-{item.slotName}`
 */
type ItemType = 'text' | 'badge' | 'slot'

/** A single label/value pair in the list */
export interface DataListItem {
  /** The field label. e.g. "Date of Birth" */
  label:     string

  /**
   * The field value. e.g. "12 January 1990"
   * For `type: 'slot'`, this value is passed to the slot as context.
   */
  value:     string | number | null | undefined

  /** How to render the value. Default: 'text' */
  type?:     ItemType

  /**
   * Variant for badge type. Maps to ChBadge's variant prop.
   * Only used when `type: 'badge'`.
   */
  variant?:  'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'

  /**
   * Name for the custom slot when `type: 'slot'`.
   * The slot name is `value-{slotName}`.
   * e.g. `slotName: 'avatar'` → use `<template #value-avatar="{ item }">`
   */
  slotName?: string

  /**
   * When true, this item spans the full width.
   * Useful for long values like addresses, notes, or descriptions.
   */
  fullWidth?: boolean
}

interface Props {
  /** Array of label/value items to display */
  items:       DataListItem[]

  /**
   * Layout direction:
   * - `horizontal` → label left, value right (default — better for wide containers)
   * - `vertical`   → label on top, value below (better for narrow panels/cards)
   */
  layout?:     'horizontal' | 'vertical'

  /**
   * String to display when a value is null, undefined, or empty string.
   * Default: "—" (em dash — conventional for "no value" in data tables)
   */
  emptyText?:  string

  /** Shows a subtle separator line between items. Default: true */
  dividers?:   boolean

  /** Loading state — replaces all items with skeleton rows */
  loading?:    boolean

  /** Number of skeleton rows to show when loading. Default: 4 */
  skeletonRows?: number
}

const props = withDefaults(defineProps<Props>(), {
  layout:      'horizontal',
  emptyText:   '—',
  dividers:    true,
  loading:     false,
  skeletonRows: 4,
})

// ─── Computed ─────────────────────────────────────────────────────────────────

/**
 * Normalizes a raw value to a display string.
 * Returns `emptyText` for falsy values (null, undefined, empty string).
 */
function formatValue(val: string | number | null | undefined): string {
  if (val === null || val === undefined || val === '') return props.emptyText
  return String(val)
}

/** Generates an array of skeleton row indices for the loading state */
const skeletonArray = computed(() =>
  Array.from({ length: props.skeletonRows }, (_, i) => i)
)

const rootClasses = computed(() => [
  'ch-data-list',
  `ch-data-list--${props.layout}`,
  { 'ch-data-list--dividers': props.dividers },
])
</script>

<template>
  <!--
    `<dl>` is the semantic HTML element for a description/definition list.
    It's the correct element for label/value pairs — screen readers
    understand `<dt>` (term) and `<dd>` (description/detail) relationships.
  -->
  <dl :class="rootClasses">

    <!-- ── Loading state ── -->
    <template v-if="loading">
      <div
        v-for="i in skeletonArray"
        :key="i"
        class="ch-data-list__item ch-data-list__item--skeleton"
      >
        <!-- Skeleton label — short, about 30% width -->
        <div class="ch-data-list__skeleton ch-data-list__skeleton--label"></div>
        <!-- Skeleton value — longer, about 50% width, randomized slightly per row -->
        <div
          class="ch-data-list__skeleton ch-data-list__skeleton--value"
          :style="{ width: `${40 + (i % 3) * 15}%` }"
        ></div>
      </div>
    </template>

    <!-- ── Actual items ── -->
    <template v-else>
      <div
        v-for="(item, index) in items"
        :key="index"
        class="ch-data-list__item"
        :class="{ 'ch-data-list__item--full-width': item.fullWidth }"
      >
        <!--
          `<dt>` — the label/term.
          In horizontal layout, this appears on the left.
          In vertical layout, this appears above the value.
        -->
        <dt class="ch-data-list__label">{{ item.label }}</dt>

        <!--
          `<dd>` — the value/description.
          Rendering strategy depends on `item.type`:
            - 'text'  → plain text (default)
            - 'badge' → ChBadge component
            - 'slot'  → custom named slot
        -->
        <dd class="ch-data-list__value">

          <!-- text type (default): just display the formatted string -->
          <template v-if="!item.type || item.type === 'text'">
            {{ formatValue(item.value) }}
          </template>

          <!-- badge type: wrap value in a ChBadge -->
          <ChBadge
            v-else-if="item.type === 'badge'"
            :variant="item.variant ?? 'default'"
            size="sm"
          >
            {{ formatValue(item.value) }}
          </ChBadge>

          <!--
            slot type: render a named slot, passing the full item as context.
            The slot name is `value-{slotName}`.
            The parent can use: <template #value-avatar="{ item }">...</template>
            This gives the parent full control over how specific values are rendered.
          -->
          <slot
            v-else-if="item.type === 'slot' && item.slotName"
            :name="`value-${item.slotName}`"
            :item="item"
          >
            <!-- Fallback if the slot isn't provided -->
            {{ formatValue(item.value) }}
          </slot>

        </dd>
      </div>
    </template>

  </dl>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-data-list {
  /* Reset browser <dl> default margin */
  margin:  0;
  padding: 0;
  width:   100%;
}

/* ─── Item ────────────────────────────────────────────────────────────────── */
.ch-data-list__item {
  display: flex;
  padding: var(--ch-space-3) 0;
}

/* Divider between items — rendered as border-top on every item except the first */
.ch-data-list--dividers .ch-data-list__item + .ch-data-list__item {
  border-top: 1px solid var(--ch-color-border-strong);
}

/* ─── Horizontal layout ───────────────────────────────────────────────────── */
/*
 * Two-column: label on the left (fixed ~35% width), value fills the right.
 * `align-items: baseline` aligns multi-line values to the label's first line.
 */
.ch-data-list--horizontal .ch-data-list__item {
  flex-direction: row;
  align-items:    baseline;
  gap:            var(--ch-space-4);
}

.ch-data-list--horizontal .ch-data-list__label {
  flex:      0 0 35%;    /* fixed 35% width — label column */
  max-width: 35%;
}

.ch-data-list--horizontal .ch-data-list__value {
  flex: 1;               /* fill remaining space */
}

/* ─── Vertical layout ─────────────────────────────────────────────────────── */
/*
 * Stacked: label above, value below.
 * Better for narrow panels or mobile contexts.
 */
.ch-data-list--vertical .ch-data-list__item {
  flex-direction: column;
  gap:            var(--ch-space-0_5);
}

/* ─── Full-width item ─────────────────────────────────────────────────────── */
/*
 * For long values (notes, addresses), override horizontal label width
 * and stack them like vertical mode.
 */
.ch-data-list--horizontal .ch-data-list__item--full-width {
  flex-direction: column;
  gap:            var(--ch-space-1);
}

.ch-data-list--horizontal .ch-data-list__item--full-width .ch-data-list__label {
  flex:      none;
  max-width: none;
}

/* ─── Label (dt) ──────────────────────────────────────────────────────────── */
.ch-data-list__label {
  font-size:   var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color:       var(--ch-color-text-muted);   /* de-emphasized vs value */
  line-height: var(--ch-leading-normal);
  flex-shrink: 0;
}

/* ─── Value (dd) ──────────────────────────────────────────────────────────── */
.ch-data-list__value {
  /* Reset browser <dd> indent */
  margin:      0;
  font-size:   var(--ch-text-sm);
  font-weight: var(--ch-font-normal);
  color:       var(--ch-color-text);
  line-height: var(--ch-leading-normal);
  word-break:  break-word; /* prevent long strings (emails, URLs) from overflowing */
}

/* ─── Skeleton ────────────────────────────────────────────────────────────── */
.ch-data-list__item--skeleton {
  align-items: center;
}

.ch-data-list__skeleton {
  height:        14px;
  border-radius: var(--ch-radius-none);
  background:    linear-gradient(
    90deg,
    var(--ch-color-bg-muted)  0%,
    var(--ch-color-bg-subtle) 50%,
    var(--ch-color-bg-muted)  100%
  );
  background-size: 200% 100%;
  animation: ch-shimmer 1.4s var(--ch-ease-in-out) infinite;
}

.ch-data-list__skeleton--label { width: 28%; }
/* Value skeleton width is set via inline :style in the template for variation */
.ch-data-list__skeleton--value { height: 14px; }
</style>
