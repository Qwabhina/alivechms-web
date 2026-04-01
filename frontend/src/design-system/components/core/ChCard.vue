<script setup lang="ts">
/**
 * @component ChCard
 * @path /frontend/src/design-system/components/core/ChCard.vue
 * @description A versatile surface container used to group related content.
 *
 * Cards are one of the most fundamental layout components — they create
 * visual separation between content areas and establish hierarchy on the page.
 *
 * ─── Slot structure ──────────────────────────────────────────────────────────
 * ChCard has three optional sections:
 *   - `header` slot → top section with border-bottom (title, actions)
 *   - default slot  → main content body (always present)
 *   - `footer` slot → bottom section with border-top and muted background
 *
 * You don't need to use all three — just the default slot is fine for
 * simple content cards.
 *
 * ─── The `as` prop (polymorphic component) ───────────────────────────────────
 * The `as` prop lets you change the rendered HTML element. By default it
 * renders a `<div>`, but you can pass `'article'`, `'section'`, `'li'`, etc.
 * for semantically correct markup.
 *
 * ─── Clickable cards and accessibility ───────────────────────────────────────
 * When `clickable` is true, the card receives `role="button"` and becomes
 * keyboard-focusable. Both Enter AND Space trigger the click (matching native
 * button behavior — Space is commonly forgotten).
 *
 * IMPORTANT: provide `aria-label` on clickable cards whenever the card's
 * inner content would make a poor button label. Screen readers will announce
 * the entire card content as the button name if no label is given. Example:
 *
 *   <ChCard clickable aria-label="View John Addo's profile">
 *     <MemberSummary :member="member" />
 *   </ChCard>
 *
 * @example Simple content card
 * <ChCard>
 *   <p>Member count: 142</p>
 * </ChCard>
 *
 * @example Card with header and footer
 * <ChCard>
 *   <template #header>
 *     <h3>Upcoming Events</h3>
 *     <ChButton variant="ghost" size="sm">View all</ChButton>
 *   </template>
 *   <EventList />
 *   <template #footer>
 *     <ChButton variant="primary">Add event</ChButton>
 *   </template>
 * </ChCard>
 *
 * @example Clickable card (e.g. member profile card)
 * <ChCard :clickable="true" aria-label="View profile" @click="openProfile(member.id)">
 *   <MemberSummary :member="member" />
 * </ChCard>
 */

import { computed } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * Controls internal padding of the card body.
 * `none` is useful when you need full-bleed content (e.g. a map, image, or
 * table that should touch the card edges without whitespace).
 * Note: header and footer always retain their own padding regardless of
 * this prop — it only affects the body slot.
 */
type Padding = 'none' | 'sm' | 'md' | 'lg'

/**
 * Controls the card's box-shadow depth, communicating how "elevated"
 * the card appears above the page surface.
 * Use heavier shadows sparingly — they draw the eye.
 */
type Shadow = 'none' | 'sm' | 'md' | 'lg'

// ─── Props ────────────────────────────────────────────────────────────────────
interface Props {
  /** Internal body padding size. Default: 'md' (20px) */
  padding?:   Padding

  /** Box shadow depth. Default: 'sm' */
  shadow?:    Shadow

  /** Whether to show a 1px border around the card. Default: true */
  bordered?:  boolean

  /**
   * When true, adds a hover shadow + subtle lift transform.
   * Use for cards that are meaningful to hover but NOT directly clickable
   * (e.g. a card that reveals extra info on hover).
   */
  hoverable?: boolean

  /**
   * When true, makes the card fully interactive — shows pointer cursor,
   * emits `click` events, and responds to both keyboard Enter and Space
   * (matching native button behavior).
   * Automatically enables `hoverable` behavior too.
   * Use for cards that navigate or trigger actions when clicked.
   */
  clickable?: boolean

  /**
   * Accessible name for clickable cards used with `role="button"`.
   * Without this, screen readers will announce the entire card content as
   * the button label, which is often verbose and confusing.
   * Strongly recommended whenever `clickable` is true.
   *
   * e.g. aria-label="View John Addo's profile"
   */
  ariaLabel?: string

  /**
   * The HTML element tag to render as. Default: 'div'.
   * Change to 'article', 'section', 'li', etc. for correct semantics.
   */
  as?: string
}

const props = withDefaults(defineProps<Props>(), {
  padding:   'md',
  shadow:    'sm',
  bordered:  true,
  hoverable: false,
  clickable: false,
  as:        'div',
})

// ─── Emits ────────────────────────────────────────────────────────────────────
const emit = defineEmits<{
  /**
   * Fired when `clickable` is true and the user clicks, presses Enter, or
   * presses Space. The event is `MouseEvent | KeyboardEvent` because both
   * input methods can trigger it.
   */
  click: [event: MouseEvent | KeyboardEvent]
}>()

// ─── Computed ─────────────────────────────────────────────────────────────────

/**
 * Builds the class list for the root element.
 * `hoverable` is implicitly enabled for clickable cards.
 */
const classes = computed(() => [
  'ch-card',
  `ch-card--padding-${props.padding}`,
  `ch-card--shadow-${props.shadow}`,
  {
    'ch-card--bordered':  props.bordered,
    'ch-card--hoverable': props.hoverable || props.clickable,
    'ch-card--clickable': props.clickable,
  },
])

// ─── Handlers ─────────────────────────────────────────────────────────────────

function handleClick(e: MouseEvent) {
  if (props.clickable) emit('click', e)
}

/**
 * Handles keyboard activation of clickable cards.
 * Both Enter and Space should trigger the action — this matches native
 * <button> behavior. Space also scrolls the page by default, so we call
 * `preventDefault()` to suppress that when inside a clickable card.
 */
function handleKeydown(e: KeyboardEvent) {
  if (!props.clickable) return
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault() // stop Space from scrolling the page
    emit('click', e)
  }
}
</script>

<template>
  <!--
    `<component :is="as">` renders whatever tag is in `as` (default: `<div>`).

    `role="button"` + `tabindex="0"` make non-button elements keyboard-accessible
    when `clickable` is true. Without `tabindex`, a <div> can't receive focus.

    `aria-label` gives screen readers a concise button name instead of reading
    out the full card content. Passed through via v-bind for flexibility.
  -->
  <component
    :is="as"
    :class="classes"
    :role="clickable ? 'button' : undefined"
    :tabindex="clickable ? 0 : undefined"
    :aria-label="clickable ? ariaLabel : undefined" @click="handleClick" @keydown="handleKeydown"
  >
    <!--
      Header slot — only rendered if the parent provides content.
      Gets a bottom border separating it from the body.
    -->
    <div v-if="$slots.header" class="ch-card__header">
      <slot name="header"></slot>
    </div>

    <!--
      Default (body) slot — always rendered.
      Padding is applied here, NOT on the outer card element, so header
      and footer remain full-bleed (edge-to-edge).
    -->
    <div class="ch-card__body">
      <slot></slot>
    </div>

    <!--
      Footer slot — only rendered if the parent provides content.
      Gets a top border and slightly muted background (signals "actions area").
    -->
    <div v-if="$slots.footer" class="ch-card__footer">
      <slot name="footer"></slot>
    </div>
  </component>
</template>

<style scoped>
/* ─── Base ────────────────────────────────────────────────────────────────── */
.ch-card {
  background-color: var(--ch-color-surface);
  border-radius: var(--ch-radius-lg);
  overflow: hidden;

  transition:
    box-shadow   var(--ch-duration-fast) var(--ch-ease-out),
    transform    var(--ch-duration-fast) var(--ch-ease-out),
    border-color var(--ch-duration-fast) var(--ch-ease-out);
}

/* ─── Border ──────────────────────────────────────────────────────────────── */
.ch-card--bordered {
  border: 1px solid var(--ch-color-border-strong);
}

/* ─── Shadows ─────────────────────────────────────────────────────────────── */
.ch-card--shadow-none { box-shadow: none; }
.ch-card--shadow-sm   { box-shadow: var(--ch-shadow-sm); }
.ch-card--shadow-md   { box-shadow: var(--ch-shadow-md); }
.ch-card--shadow-lg   { box-shadow: var(--ch-shadow-lg); }

/* ─── Hover ───────────────────────────────────────────────────────────────── */
.ch-card--hoverable:hover {
  box-shadow: var(--ch-shadow-lg);
    transform: translate(-2px, -2px);
  }
  
  /*
     * Only transition the border color for bordered cards.
     * Non-bordered cards have no `border` property set, so applying
     * `border-color` on hover would flash a border from nothing —
     * a jarring appearance of a border that was never there.
     */
  .ch-card--bordered.ch-card--hoverable:hover {
  border-color: var(--ch-color-border-focus);
}

/* ─── Clickable ───────────────────────────────────────────────────────────── */
.ch-card--clickable {
  cursor:      pointer;
  user-select: none;
}

.ch-card--clickable:active {
  transform:  translate(2px, 2px);
  box-shadow: none;
}

.ch-card--clickable:focus-visible {
  outline:        2px solid var(--ch-color-border-focus);
  outline-offset: 2px;
}

/* ─── Padding Variants ────────────────────────────────────────────────────── */
/*
 * Padding is applied to `.ch-card__body` only,
 not the root element. * This keeps header and footer full-bleed while the body has breathing room. * Header/footer manage their own padding independently below.
 */
.ch-card--padding-none .ch-card__body { padding: 0; }
.ch-card--padding-sm   .ch-card__body { padding: var(--ch-space-3); }  /* 12px */
.ch-card--padding-md   .ch-card__body { padding: var(--ch-space-5); }  /* 20px */
.ch-card--padding-lg   .ch-card__body { padding: var(--ch-space-8); }  /* 32px */

/* ─── Header ──────────────────────────────────────────────────────────────── */
.ch-card__header {
  /*
  * Flex row: title left, actions right. * `space-between` handles this without explicit margins.
   */
  display:         flex;
  align-items:     center;
  justify-content: space-between;
  gap:             var(--ch-space-3);

  padding: var(--ch-space-4) var(--ch-space-5);
    /* matches md body padding */
    border-bottom: 1px solid var(--ch-color-border);
}

/* Header padding tracks the body padding size */
.ch-card--padding-sm .ch-card__header { padding: var(--ch-space-3); }
.ch-card--padding-lg .ch-card__header { padding: var(--ch-space-5) var(--ch-space-8); }

/* ─── Footer ──────────────────────────────────────────────────────────────── */
.ch-card__footer {
  display: flex;
    align-items: center;
    gap: var(--ch-space-3);
  
    padding: var(--ch-space-4) var(--ch-space-5);
    /* matches md body padding */
    border-top: 1px solid var(--ch-color-border);

  /*
   * Slightly muted background distinguishes the footer from the body.
  * Signals "secondary actions area" visually.
   */
  background-color: var(--ch-color-bg-subtle);
}

/* Footer padding tracks the body padding size */
.ch-card--padding-sm .ch-card__footer { padding: var(--ch-space-3); }
.ch-card--padding-lg .ch-card__footer { padding: var(--ch-space-5) var(--ch-space-8); }
</style>