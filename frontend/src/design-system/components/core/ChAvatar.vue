<script setup lang="ts">
/**
 * @component ChAvatar
 * @path /frontend/src/design-system/components/core/ChAvatar.vue
 * @description Displays a user's identity visually — either via a profile photo
 * or, when no image is available, a colored circle with their initials.
 *
 * ─── Fallback strategy ───────────────────────────────────────────────────────
 * The component tries to display content in this order:
 *   1. `src` image (if provided AND loaded successfully)
 *   2. Initials derived from `name` (if image fails or isn't provided)
 *   3. '?' character (if neither src nor name is provided)
 *
 * The image failure case is handled by listening to the `@error` event on
 * the `<img>` element. If the image URL 404s or fails to load, the `imgError`
 * ref is set to true, which hides the `<img>` and shows the initials instead.
 * This is important for robustness — URLs become outdated, S3 buckets change.
 *
 * ─── Use cases ───────────────────────────────────────────────────────────────
 * - Member directory cards
 * - Comment/post authors
 * - Navigation "current user" display
 * - Attendance lists and check-in screens
 * - Message threads / group chats within the system
 *
 * @example With photo
 * <ChAvatar src="/uploads/john.jpg" name="John Addo" size="md" />
 *
 * @example Initials only (no photo available)
 * <ChAvatar name="Grace Mensah" size="lg" />
 *
 * @example With online status indicator
 * <ChAvatar src="/uploads/sarah.jpg" name="Sarah" size="md" status="online" />
 *
 * @example In a navigation bar
 * <ChAvatar :src="currentUser.avatar" :name="currentUser.name" size="sm" />
 */

import { computed, ref } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/**
 * Five sizes to fit various display contexts.
 * Named by t-shirt sizing for consistency with other components.
 */
type Size = 'xs' | 'sm' | 'md' | 'lg' | 'xl'

/**
 * Optional status indicator dot shown in the bottom-right corner.
 * Maps to the most common online presence states.
 */
type Status = 'online' | 'offline' | 'away' | 'busy'

// ─── Props ────────────────────────────────────────────────────────────────────
interface Props {
  /** URL of the user's profile image. If loading fails, falls back to initials. */
  src?: string

  /** Accessible text description of the image (used as aria-label) */
  alt?: string

  /**
   * The user's full name. Serves two purposes:
   * 1. Used to generate initials when no image is available
   * 2. Falls back as the aria-label if `alt` isn't provided
   */
  name?: string

  /** Display size. Default: 'md' (40×40px) */
  size?: Size

  /**
   * Optional presence/status dot.
   * Renders a small colored circle in the bottom-right corner.
   * If undefined, no dot is shown.
   */
  status?: Status

  /**
   * When true → fully circular (border-radius: 9999px)
   * When false → slightly rounded square (border-radius: xl = 12px)
   * Default: true. Use false for "avatar" in lists where circular feels disconnected.
   */
  rounded?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  rounded: true,
})

// ─── State ────────────────────────────────────────────────────────────────────

/**
 * Tracks whether the image failed to load.
 * Starts false (assume image will load).
 * Set to true by the `@error` handler on the `<img>` element.
 * Once true, the initials fallback is shown instead.
 */
const imgError = ref(false)

// ─── Computed ─────────────────────────────────────────────────────────────────

/**
 * Derives initials from the `name` prop.
 *
 * Algorithm:
 *   1. If no name → return '?' (anonymous/unknown user)
 *   2. Split name into words by whitespace
 *   3. If only one word → use just the first character
 *   4. If multiple words → use first char of first word + first char of last word
 *      (e.g. "John Addo" → "JA", "Mary Ama Boateng" → "MB")
 *
 * `.toUpperCase()` ensures consistency regardless of how the name is stored.
 */
const initials = computed(() => {
  if (!props.name) return '?'

  // Split by one or more whitespace characters (handles double spaces)
  const parts = props.name.trim().split(/\s+/)

  // Handle edge case: empty or whitespace-only name
  if (parts.length === 0 || !parts[0]) return '?'

  if (parts.length === 1) {
    // Single-word name → first character only (e.g. "Kwame" → "K")
    // Use optional chaining to handle empty string case
    return parts[0]?.[0]?.toUpperCase() ?? '?'
  }

  // Multi-word name → first + last word initials
  // `parts[parts.length - 1]` gets the last element (works for 2+ words)
  const firstChar = parts[0]?.[0] ?? ''
  const lastChar = parts[parts.length - 1]?.[0] ?? ''
  return (firstChar + lastChar).toUpperCase()
})

/**
 * Determines whether to show the `<img>` element.
 * Both conditions must be true: we have a src AND it hasn't errored.
 */
const showImage = computed(() => !!props.src && !imgError.value)

/**
 * Root element class list.
 * The `rounded` modifier switches between circular and square-ish shapes.
 */
const avatarClasses = computed(() => [
  'ch-avatar',
  `ch-avatar--${props.size}`,
  { 'ch-avatar--rounded': props.rounded },
])

/**
 * The accessible label for the avatar element.
 * Priority: explicit `alt` prop → `name` prop → generic fallback.
 */
const ariaLabel = computed(() => props.alt ?? props.name ?? 'Avatar')
</script>

<template>
  <!--
    `role="img"` marks this as an image to assistive technologies.
    Even though it may contain text (initials), from an AT perspective
    this element represents a person's identity — an "image" concept.
    The `aria-label` provides the textual description read aloud.
  -->
  <span :class="avatarClasses" :aria-label="ariaLabel" role="img">
    <!--
      The profile image.
      `:src` and `:alt` bind reactively to the props.
      `@error="imgError = true"` — if the browser can't load the image
      (404, network failure, invalid URL), this fires and we fall back to initials.

      `v-if="showImage"` hides the img entirely once imgError is true,
      which removes the broken image icon the browser would otherwise show.
    -->
    <img v-if="showImage" :src="src" :alt="ariaLabel" class="ch-avatar__img" @error="imgError = true" />

    <!--
      Initials fallback — shown when no image is available or when image fails.
      `v-else` is logically equivalent to `v-if="!showImage"`.
      `aria-hidden="true"` — the text "JA" is decorative here; the
      meaningful label is already on the parent element via `aria-label`.
    -->
    <span v-else class="ch-avatar__initials" aria-hidden="true">
      {{ initials }}
    </span>

    <!--
      Status indicator dot — only rendered if `status` prop is provided.
      Positioned absolutely in the bottom-right corner via CSS.
      Uses `aria-label` to announce status to screen readers
      (e.g., "Status: online").
    -->
    <span v-if="status" :class="['ch-avatar__status', `ch-avatar__status--${status}`]"
      :aria-label="`Status: ${status}`"></span>
  </span>
</template>

<style scoped>
/* ─── Base ────────────────────────────────────────────────────────────────── */
.ch-avatar {
  position: relative;
  /* needed for absolute-positioned status dot */
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  /* never compress in a flex layout */

  /* Default background for initials-only display — brand-tinted */
  background-color: var(--ch-color-primary-muted);
  /* primary-100 */
  color: var(--ch-color-primary);
  /* primary-600 */

  font-family: var(--ch-font-sans);
  font-weight: var(--ch-font-semibold);
  /* initials should be bold enough to read */
  overflow: hidden;
  /* clip image to the border-radius */
  border-radius: var(--ch-radius-full);
  /* circular by default */
  user-select: none;
  /* can't accidentally select the initials text */
}

/* Square-ish mode: slightly rounded rectangle instead of circle */
.ch-avatar--rounded {
  border-radius: var(--ch-radius-xl);
  /* 12px */
}

/* ─── Sizes ───────────────────────────────────────────────────────────────── */
/*
 * Each size sets both `width` and `height` explicitly (square aspect ratio)
 * and a `font-size` for the initials text that's proportional to the avatar size.
 * There's no token for these specific font sizes — they're purpose-fit values.
 */
.ch-avatar--xs {
  width: 24px;
  height: 24px;
  font-size: 0.625rem;
}

/* 10px */
.ch-avatar--sm {
  width: 32px;
  height: 32px;
  font-size: var(--ch-text-xs);
}

/* 12px */
.ch-avatar--md {
  width: 40px;
  height: 40px;
  font-size: var(--ch-text-sm);
}

/* 14px */
.ch-avatar--lg {
  width: 48px;
  height: 48px;
  font-size: var(--ch-text-base);
}

/* 16px */
.ch-avatar--xl {
  width: 64px;
  height: 64px;
  font-size: var(--ch-text-xl);
}

/* 20px */

/* ─── Profile Image ───────────────────────────────────────────────────────── */
.ch-avatar__img {
  width: 100%;
  /* fill the avatar container completely */
  height: 100%;
  object-fit: cover;
  /* crop the image to fill the square without distortion */
}

/* ─── Status Dot ──────────────────────────────────────────────────────────── */
.ch-avatar__status {
  position: absolute;
  bottom: 0;
  right: 0;

  /*
   * The dot scales proportionally with the avatar.
   * `28%` of the avatar width/height — roughly correct across all sizes.
   * `min-width/height: 7px` ensures it's always visible even at xs size.
   */
  width: 28%;
  height: 28%;
  min-width: 7px;
  min-height: 7px;

  border-radius: 50%;
  /* always circular */

  /*
   * A white border separates the dot from the avatar image,
   * making it readable against any avatar content.
   * Uses `var(--ch-color-bg)` so it blends with whatever surface the avatar sits on.
   */
  border: 2px solid var(--ch-color-bg);
}

/* Status colors — each maps to a semantic meaning */
.ch-avatar__status--online {
  background-color: var(--ch-color-success);
}

/* green */
.ch-avatar__status--offline {
  background-color: var(--ch-color-text-subtle);
}

/* gray */
.ch-avatar__status--away {
  background-color: var(--ch-color-warning);
}

/* amber */
.ch-avatar__status--busy {
  background-color: var(--ch-color-danger);
}

/* red */
</style>
