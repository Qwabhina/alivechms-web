<script setup lang="ts">
/**
 * @component ChBadge
 * @path /frontend/src/design-system/components/core/ChBadge.vue
 * @description A small, non-interactive label used to communicate status,
 * category, or metadata at a glance.
 *
 * Badges appear alongside other content — they're never standalone interactive
 * elements. They answer "what is this?" or "what's the state of this?".
 *
 * ─── Use cases in a church management system ─────────────────────────────────
 * - Member status: "Active", "Inactive", "Visitor"
 * - Event registration: "Registered", "Waitlisted", "Cancelled"
 * - Role labels: "Leader", "Volunteer", "Staff"
 * - Payment status: "Paid", "Pending", "Overdue"
 * - Notification counts: (use `dot` variant for unread indicators)
 *
 * ─── Variant selection guide ─────────────────────────────────────────────────
 * - `default`  → neutral/informational (role labels, categories)
 * - `primary`  → brand-colored highlight (featured, selected, new)
 * - `success`  → positive/complete states ("Active", "Paid", "Registered")
 * - `warning`  → needs attention ("Pending", "Due Soon", "Waitlisted")
 * - `danger`   → problematic/urgent ("Overdue", "Cancelled", "Blocked")
 * - `info`     → informational ("Draft", "Scheduled", "In Review")
 *
 * @example Status badge
 * <ChBadge variant="success" dot>Active</ChBadge>
 *
 * @example Role label
 * <ChBadge variant="default">Volunteer</ChBadge>
 *
 * @example Overdue payment
 * <ChBadge variant="danger" size="sm">Overdue</ChBadge>
 */

import { computed } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * Semantic color variants for the badge.
 * Each maps to a background + text color pair from the design tokens.
 */
type Variant = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'

/** Three sizes for use in different density contexts */
type Size    = 'sm' | 'md' | 'lg'

// ─── Props ────────────────────────────────────────────────────────────────────
interface Props {
  /** Semantic color variant. Default: 'default' */
  variant?: Variant

  /** Badge size. Default: 'md' */
  size?:    Size

  /**
   * When true, renders a small colored dot before the text.
   * Useful for status indicators (online/offline, active/inactive).
   * The dot color inherits from the variant's text color via `currentColor`.
   */
  dot?:     boolean

  /**
   * When true, uses `border-radius: full` (pill shape).
   * When false, uses `border-radius: md` (slightly rounded rectangle).
   * Default: true (pill is more common for badges).
   */
  pill?:    boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size:    'md',
  dot:     false,
  pill:    true,
})

// ─── Computed ─────────────────────────────────────────────────────────────────

/**
 * Builds the class list for the badge element.
 * Classes are additive — a badge always has all four class groups applied.
 */
const classes = computed(() => [
  'ch-badge',
  `ch-badge--${props.variant}`, // controls color scheme
  `ch-badge--${props.size}`,    // controls padding and font size
  {
    'ch-badge--dot':  props.dot,  // adds the dot span rendering logic in CSS
    'ch-badge--pill': props.pill, // toggles between pill and rounded-rect shape
  },
])
</script>

<template>
  <!--
    Badges use `<span>` (not `<div>`) because they're inline elements —
    they appear inside text or alongside other inline content.
    Using `<div>` would create an unwanted block-level context.
  -->
  <span :class="classes">

    <!--
      Status dot — a small colored circle rendered before the text.
      `v-if="dot"` only renders this when the `dot` prop is true.
      `aria-hidden="true"` hides it from screen readers — the badge text
      itself already communicates the status.
      The dot color is set to `currentColor` in CSS, so it automatically
      matches the variant's text color without needing extra tokens.
    -->
    <span v-if="dot" class="ch-badge__dot" aria-hidden="true" ></span>

    <!--
      The default slot holds the badge label text.
      e.g. <ChBadge>Active</ChBadge>
    -->
    <slot ></slot>

  </span>
</template>

<style scoped>
/* ─── Base ────────────────────────────────────────────────────────────────── */
.ch-badge {
  display:     inline-flex; /* inline so it sits alongside text */
  align-items: center;
  gap:         var(--ch-space-1); /* 4px gap between dot and text */

  font-family:  var(--ch-font-sans);
  font-weight:  var(--ch-font-medium); /* 500 — slightly heavy for legibility at small size */
  line-height:  1;                     /* no extra line height in a tight badge */
  white-space:  nowrap;                /* never wrap badge text onto two lines */

  /*
   * A transparent border is always present (even when not visually shown).
   * This prevents a 1px layout shift if a bordered variant is toggled dynamically.
   */
  border:        1px solid transparent;
  border-radius: var(--ch-radius-none); /* 0px by default */
}

/* ─── Pill Shape (Now Small Radius) ───────────────────────────────────────── */
/* Previously fully rounded sides, now a subtle 2px rounding to keep the sharp aesthetic */
.ch-badge--pill {
  border-radius: var(--ch-radius-sm); /* 2px */
}

/* ─── Sizes ───────────────────────────────────────────────────────────────── */
/*
 * Badges are tiny elements — their sizes are intentionally compact.
 * Font sizes go below `text-xs` (12px) for the `sm` variant to fit
 * in very dense contexts (table cells, list items, chips).
 *
 * Padding uses a non-token `0.1875rem` for sm/md because the gap between
 * the standard token steps is too large for these small elements.
 */

.ch-badge--sm {
  font-size: 0.6875rem;          /* 11px — smaller than text-xs */
  padding:   0.125rem 0.375rem;  /* 2px 6px */
}

.ch-badge--md {
  font-size: var(--ch-text-xs);  /* 12px */
  padding:   0.1875rem 0.5rem;   /* 3px 8px */
}

.ch-badge--lg {
  font-size: var(--ch-text-sm);              /* 14px */
  padding:   var(--ch-space-1) var(--ch-space-2_5); /* 4px 10px */
}

/* ─── Variants ────────────────────────────────────────────────────────────── */
/*
 * Each variant is a coordinated pair of background and text colors,
 * both sourced from semantic color tokens.
 *
 * The pattern:
 *   background → light pastel (e.g. success-bg = success-50)
 *   text       → dark shade   (e.g. success-fg = success-700)
 *
 * This gives good contrast on both light and dark pastel backgrounds
 * while keeping the badge visually soft (not as intense as buttons).
 */

/* DEFAULT — neutral gray. For categories, labels with no special status. */
.ch-badge--default {
  background-color: var(--ch-color-bg-muted);  /* light gray */
  border-color:     var(--ch-color-border);    /* visible edge for definition */
  color:            var(--ch-color-text-muted);
}

/* PRIMARY — brand-colored. For highlighted or selected states. */
.ch-badge--primary {
  background-color: var(--ch-color-primary-muted); /* light brand tint */
  border-color:     var(--ch-color-primary-muted);
  color:            var(--ch-color-primary);        /* brand text */
}

/* SUCCESS — green. For completed, active, confirmed states. */
.ch-badge--success {
  background-color: var(--ch-color-success-bg); /* green-50 */
  color:            var(--ch-color-success-fg); /* green-700 */
}

/* WARNING — amber. For pending, due-soon, needs-attention states. */
.ch-badge--warning {
  background-color: var(--ch-color-warning-bg);
  color:            var(--ch-color-warning-fg);
}

/* DANGER — red. For overdue, failed, cancelled, blocked states. */
.ch-badge--danger {
  background-color: var(--ch-color-danger-bg);
  color:            var(--ch-color-danger-fg);
}

/* INFO — blue. For draft, scheduled, in-progress states. */
.ch-badge--info {
  background-color: var(--ch-color-info-bg);
  color:            var(--ch-color-info-fg);
}

/* ─── Status Dot ──────────────────────────────────────────────────────────── */
.ch-badge__dot {
  width:        6px;
  height:       6px;
  border-radius: 50%; /* always circular */

  /*
   * `currentColor` inherits the text color of the variant.
   * So success badge dot = success-700 green.
   * This means we only need ONE dot style for ALL variants — no per-variant overrides.
   */
  background-color: currentColor;

  flex-shrink: 0; /* dot never compresses even in very narrow contexts */
}
</style>
