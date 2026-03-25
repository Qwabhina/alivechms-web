<script setup lang="ts">
/**
 * @component ChDivider
 * @path /frontend/src/design-system/components/core/ChDivider.vue
 * @description A visual separator between content sections.
 *
 * Dividers serve a single purpose: to communicate that two adjacent areas
 * of content are distinct. They should be used sparingly — overusing dividers
 * creates visual noise. Prefer whitespace (spacing) for soft separation and
 * reserve dividers for cases where a clear boundary is needed.
 *
 * ─── When to use ─────────────────────────────────────────────────────────────
 * ✓ Between card header/footer and body (already handled inside ChCard)
 * ✓ Between major sections in a settings or profile page
 * ✓ Between items in a dropdown menu
 * ✓ To separate a form section's fields from its action buttons
 * ✓ With a `label` to create an "or" separator between form options
 *
 * ─── When NOT to use ─────────────────────────────────────────────────────────
 * ✗ Between every list item (use spacing/alternating row bg instead)
 * ✗ To add decoration between headings
 *
 * @example Basic horizontal divider
 * <ChDivider />
 *
 * @example With label (e.g. login "or" separator)
 * <ChDivider label="or continue with" />
 *
 * @example Vertical divider between two actions
 * <div style="display: flex; height: 24px; align-items: center">
 *   <button>Edit</button>
 *   <ChDivider orientation="vertical" />
 *   <button>Delete</button>
 * </div>
 *
 * @example Dashed style (for optional/secondary separators)
 * <ChDivider variant="dashed" spacing="lg" />
 */

// No imports needed — this is a purely presentational component
// with no reactive state or computed values.

type Orientation = 'horizontal' | 'vertical'
type Variant     = 'solid' | 'dashed' | 'dotted'

interface Props {
  /**
   * Direction of the dividing line.
   * - `horizontal` → full-width line (default, most common)
   * - `vertical`   → full-height line (for use inside a flex row)
   */
  orientation?: Orientation

  /**
   * The border-style of the line.
   * - `solid`  → clean edge (default, for clear separation)
   * - `dashed` → optional/soft separation (e.g. between optional form sections)
   * - `dotted` → decorative (use very sparingly)
   */
  variant?:     Variant

  /**
   * Optional label text centered on a horizontal divider.
   * Creates the classic "or" separator pattern.
   * Ignored for vertical dividers.
   */
  label?:       string

  /**
   * Vertical margin (for horizontal) or horizontal margin (for vertical).
   * Controls breathing room around the divider.
   */
  spacing?:     'sm' | 'md' | 'lg'
}

withDefaults(defineProps<Props>(), {
  orientation: 'horizontal',
  variant:     'solid',
  spacing:     'md',
})
</script>

<template>
  <!--
    `role="separator"` is the ARIA role for a visual divider between sections.
    Screen readers may announce this as a structural boundary.

    `aria-orientation` communicates the axis to assistive technologies
    (especially important for vertical dividers inside toolbars/menus).
  -->
  <div
    :class="[
      'ch-divider',
      `ch-divider--${orientation}`,
      `ch-divider--${variant}`,
      `ch-divider--spacing-${spacing}`,
    ]"
    role="separator"
    :aria-orientation="orientation"
  >
    <!--
      Label text — only shown on horizontal dividers (a vertical divider
      with a label would be confusing and rare).
      The label sits between two line segments created by ::before and ::after
      pseudo-elements in CSS, creating the split-line effect.
    -->
    <span v-if="label && orientation === 'horizontal'" class="ch-divider__label">
      {{ label }}
    </span>
  </div>
</template>

<style scoped>
/* ─── Base ────────────────────────────────────────────────────────────────── */
.ch-divider {
  display:     flex;
  align-items: center;
  color:       var(--ch-color-text-subtle); /* label text color */
  font-size:   var(--ch-text-xs);
  font-family: var(--ch-font-sans);
}

/* ─── Horizontal ──────────────────────────────────────────────────────────── */
.ch-divider--horizontal {
  width:           100%;      /* span the full width of its container */
  flex-direction:  row;       /* label and lines in a horizontal row */
}

/*
 * The ::before and ::after pseudo-elements create the actual lines.
 * They are flex children with `flex: 1`, so they each take up equal
 * space on either side of the label (or fill the full width if no label).
 *
 * `border-top-style` uses a CSS custom property `--_border-style`
 * to allow the variant modifier classes to change the style without
 * re-declaring all border properties. This is the "CSS custom property
 * cascade" pattern — set it once, override in a single place.
 */
.ch-divider--horizontal::before,
.ch-divider--horizontal::after {
  content:           '';       /* required for pseudo-elements to render */
  flex:              1;        /* equal width lines on both sides of label */
  border-top-width:  1px;
  border-top-style:  var(--_border-style, solid); /* defaults to solid */
  border-top-color:  var(--ch-color-border-strong);
}

/* Label sits between the two lines with horizontal padding */
.ch-divider--horizontal .ch-divider__label {
  padding:     0 var(--ch-space-3); /* 12px breathing room on each side of the text */
  white-space: nowrap;               /* prevent the label from wrapping to two lines */
  color:       var(--ch-color-text);
  font-weight: var(--ch-font-semibold); /* make text sharp and visible */
}

/* ─── Vertical ────────────────────────────────────────────────────────────── */
.ch-divider--vertical {
  flex-direction: column; /* stack content vertically */
  align-self:     stretch; /* fill the height of the parent flex container */
  width:          auto;    /* shrink to content width (not full-width like horizontal) */
}

/*
 * For vertical, only ::before is needed (a single vertical line).
 * `flex: 1` makes it fill the full height of the element.
 */
.ch-divider--vertical::before {
  content:           '';
  flex:              1;
  border-left-width: 1px;
  border-left-style: var(--_border-style, solid);
  border-left-color: var(--ch-color-border-strong);
}

/* ─── Border Style Variants ───────────────────────────────────────────────── */
/*
 * Each variant sets the `--_border-style` custom property.
 * This propagates down to the ::before/::after pseudo-elements above.
 * The property is scoped to this component via `scoped`, so there's no risk
 * of it affecting other elements on the page.
 */
.ch-divider--solid  { --_border-style: solid; }
.ch-divider--dashed { --_border-style: dashed; }
.ch-divider--dotted { --_border-style: dotted; }

/* ─── Spacing (margin around the divider) ─────────────────────────────────── */
/*
 * Horizontal → top and bottom margin (vertical rhythm).
 * Vertical   → left and right margin (horizontal rhythm).
 *
 * The `sm`, `md`, `lg` sizes map to small, comfortable, and generous spacing.
 */
.ch-divider--horizontal.ch-divider--spacing-sm { margin: var(--ch-space-2) 0; } /*  8px top/bottom */
.ch-divider--horizontal.ch-divider--spacing-md { margin: var(--ch-space-4) 0; } /* 16px top/bottom */
.ch-divider--horizontal.ch-divider--spacing-lg { margin: var(--ch-space-8) 0; } /* 32px top/bottom */

.ch-divider--vertical.ch-divider--spacing-sm   { margin: 0 var(--ch-space-2); } /*  8px left/right */
.ch-divider--vertical.ch-divider--spacing-md   { margin: 0 var(--ch-space-4); } /* 16px left/right */
.ch-divider--vertical.ch-divider--spacing-lg   { margin: 0 var(--ch-space-8); } /* 32px left/right */
</style>
