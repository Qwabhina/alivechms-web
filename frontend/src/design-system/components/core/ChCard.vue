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
 * <ChCard :clickable="true" @click="openProfile(member.id)">
 *   <MemberSummary :member="member" />
 * </ChCard>
 */

import { computed } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * Controls internal padding of the card body.
 * `none` is useful when you need full-bleed content (e.g. a map, image, or table
 * that should touch the card edges without whitespace).
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
   * emits `click` events, and responds to keyboard Enter.
   * Automatically enables `hoverable` behavior too.
   * Use for cards that navigate or trigger actions when clicked.
   */
  clickable?: boolean

  /**
   * The HTML element tag to render as. Default: 'div'.
   * Change to 'article', 'section', 'li', etc. for correct semantics.
   * Vue's `<component :is="tag">` renders any valid HTML tag.
   */
  as?:        string
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
  /** Fired when `clickable` is true and the user clicks or presses Enter */
  click: [event: MouseEvent]
}>()

// ─── Computed ─────────────────────────────────────────────────────────────────

/**
 * Builds the class list for the root element.
 * Note: hoverable is implicitly enabled for clickable cards.
 */
const classes = computed(() => [
  'ch-card',
  `ch-card--padding-${props.padding}`,  // e.g. 'ch-card--padding-md'
  `ch-card--shadow-${props.shadow}`,    // e.g. 'ch-card--shadow-sm'
  {
    'ch-card--bordered':  props.bordered,
    'ch-card--hoverable': props.hoverable || props.clickable, // clickable implies hoverable
    'ch-card--clickable': props.clickable,
  },
])
</script>

<template>
  <!--
    `<component :is="as">` is Vue's polymorphic element pattern.
    It renders whatever tag is in `as` — by default `<div>`, but
    the parent can pass `as="article"` for semantic HTML.

    `role="button"` and `tabindex="0"` are conditionally added when
    `clickable` is true to make non-button elements keyboard-accessible.
    Without `tabindex="0"`, a <div> can't receive keyboard focus.

    `@keydown.enter` — allows Enter key to trigger the click action,
    matching the behavior of a native <button>. We cast the KeyboardEvent
    to MouseEvent because our emit type is `[event: MouseEvent]`.
    This is a minor simplification — for full accuracy, you'd accept both.
  -->
  <component
    :is="as"
    :class="classes"
    :role="clickable ? 'button' : undefined"
    :tabindex="clickable ? 0 : undefined"
    @click="clickable && emit('click', $event)"
    @keydown.enter="clickable && emit('click', $event as unknown as MouseEvent)"
  >
    <!--
      Header slot — only rendered if the parent provides content.
      `v-if="$slots.header"` checks whether the named slot has content.
      The header gets a bottom border separating it from the body.
    -->
    <div v-if="$slots.header" class="ch-card__header">
      <slot name="header"></slot>
    </div>

    <!--
      Default (body) slot — always rendered.
      Padding is applied to this element, NOT the outer card,
      so the header and footer can be full-width (edge-to-edge).
    -->
    <div class="ch-card__body">
      <slot></slot>
    </div>

    <!--
      Footer slot — only rendered if the parent provides content.
      Gets a top border and slightly muted background to distinguish it
      from the body (commonly used for action buttons).
    -->
    <div v-if="$slots.footer" class="ch-card__footer">
      <slot name="footer"></slot>
    </div>
  </component>
</template>

<style scoped>
/* ─── Base ────────────────────────────────────────────────────────────────── */
/* ─── Base ────────────────────────────────────────────────────────────────── */
.ch-card {
  background-color: var(--ch-color-surface);
  border-radius:    var(--ch-radius-none); /* 0px */
  overflow:         hidden;

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

/* ─── Hover / Clickable ───────────────────────────────────────────────────── */
.ch-card--hoverable:hover {
  box-shadow:   var(--ch-shadow-lg);
  border-color: var(--ch-color-border-focus);
  transform:    translate(-2px, -2px);
}

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
 * Padding goes on `.ch-card__body`, NOT on `.ch-card` itself.
 * This allows the header and footer to be full-bleed (no padding)
 * while the body content has comfortable breathing room.
 *
 * We select the body through the parent class to keep all padding
 * decisions in one place per variant.
 */
.ch-card--padding-none .ch-card__body { padding: 0; }
.ch-card--padding-sm   .ch-card__body { padding: var(--ch-space-3); }  /* 12px */
.ch-card--padding-md   .ch-card__body { padding: var(--ch-space-5); }  /* 20px */
.ch-card--padding-lg   .ch-card__body { padding: var(--ch-space-8); }  /* 32px */

/* ─── Header ──────────────────────────────────────────────────────────────── */
.ch-card__header {
  /*
   * Flex row layout for: title on the left, actions on the right.
   * `justify-content: space-between` naturally handles this without
   * needing explicit margins on the title or buttons.
   */
  display:         flex;
  align-items:     center;
  justify-content: space-between;
  gap:             var(--ch-space-3);

  /* Default header padding (for md card) */
  padding:         var(--ch-space-4) var(--ch-space-5);
  border-bottom:   1px solid var(--ch-color-border);
}

/* Header padding shrinks/grows to match the body padding size */
.ch-card--padding-sm .ch-card__header { padding: var(--ch-space-3); }
.ch-card--padding-lg .ch-card__header { padding: var(--ch-space-5) var(--ch-space-8); }

/* ─── Footer ──────────────────────────────────────────────────────────────── */
.ch-card__footer {
  display:          flex;
  align-items:      center;
  gap:              var(--ch-space-3);
  padding:          var(--ch-space-4) var(--ch-space-5);
  border-top:       1px solid var(--ch-color-border);

  /*
   * Slightly muted background distinguishes the footer from the body.
   * This signals "secondary actions area" visually.
   */
  background-color: var(--ch-color-bg-subtle);
}

.ch-card--padding-sm .ch-card__footer { padding: var(--ch-space-3); }
.ch-card--padding-lg .ch-card__footer { padding: var(--ch-space-5) var(--ch-space-8); }
</style>
