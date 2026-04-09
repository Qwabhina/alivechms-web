<!--
  Module-level counter — must live in a plain <script> block, NOT inside
  <script setup>. Code inside <script setup> runs once per component INSTANCE,
  so a counter defined there resets to 0 every mount and every modal gets the
  same ID ("ch-modal-title-1"). A plain <script> block is evaluated once when
  the module is first imported, making the counter truly shared across all
  instances.
-->
<script lang="ts">
let _modalCounter = 0
</script>

<script setup lang="ts">
/**
 * @component ChModal
 * @path /frontend/src/design-system/components/forms/ChModal.vue
 * @description An accessible dialog modal with focus trapping, scroll lock,
 * keyboard dismissal, and a clean header/body/footer slot structure.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example Basic confirmation dialog
 * <ChModal v-model:open="showConfirm" title="Delete Member" size="sm">
 *   <p>Are you sure you want to delete <strong>Kwame Asante</strong>?</p>
 *   <template #footer>
 *     <ChButton variant="ghost" @click="showConfirm = false">Cancel</ChButton>
 *     <ChButton variant="danger" @click="confirmDelete">Delete</ChButton>
 *   </template>
 * </ChModal>
 *
 * @example Large form modal
 * <ChModal v-model:open="editOpen" title="Edit Member" size="lg"
 *          subtitle="Update personal and contact information">
 *   <MemberForm :member="member" @saved="editOpen = false" />
 * </ChModal>
 *
 * ─── Focus trap ──────────────────────────────────────────────────────────────
 * Tab and Shift+Tab cycle focus only within the modal while it is open.
 * Focus is restored to the triggering element when the modal closes.
 *
 * ─── Scroll lock ─────────────────────────────────────────────────────────────
 * Opening adds overflow:hidden to <body> and sets --ch-scrollbar-width on
 * :root so that fixed headers/sidebars can self-compensate the missing
 * scrollbar width with `padding-right: var(--ch-scrollbar-width, 0px)`.
 */

import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

type ModalSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'full'

// ─── Unique IDs per instance ──────────────────────────────────────────────────

const _instanceId = ++_modalCounter
const titleId = `ch-modal-title-${_instanceId}`
const subtitleId = `ch-modal-subtitle-${_instanceId}`

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  open: boolean
  title?: string
  subtitle?: string
  size?: ModalSize
  /**
   * When true, clicking the backdrop does NOT close the modal.
   * The panel plays a shake animation as tactile feedback instead.
   */
  persistent?: boolean
  /**
   * Hides the default × close button in the header.
   * Use when the footer provides explicit Cancel / Close actions.
   */
  hideClose?: boolean
  /**
   * When true, the modal body gets its own scrollbar and the header/footer
   * stay fixed. Recommended for long-form content. Default: true.
   */
  scrollable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  persistent: false,
  hideClose: false,
  scrollable: true,
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  'update:open': [value: boolean]
  close: []
}>()

// ─── Refs ─────────────────────────────────────────────────────────────────────

const panelRef = ref<HTMLElement | null>(null)
/** Element focused before the modal opened — restored on close */
const triggerEl = ref<HTMLElement | null>(null)
/** Drives the shake animation on persistent-mode backdrop clicks */
const isShaking = ref(false)

// ─── Computed ─────────────────────────────────────────────────────────────────

/**
 * Wire the subtitle to aria-describedby so screen readers announce it as
 * context for the dialog, not just as incidental body text.
 */
const ariaDescribedby = computed(() => (props.subtitle ? subtitleId : undefined))

// ─── Focus trap ───────────────────────────────────────────────────────────────

const FOCUSABLE = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',')

function getFocusable(): HTMLElement[] {
  return panelRef.value ? Array.from(panelRef.value.querySelectorAll<HTMLElement>(FOCUSABLE)) : []
}

function trapFocus(e: KeyboardEvent) {
  if (e.key !== 'Tab') return
  const focusable = getFocusable()
  if (focusable.length === 0) return

  const first = focusable[0]!
  const last = focusable[focusable.length - 1]!

  if (e.shiftKey) {
    if (document.activeElement === first) {
      e.preventDefault()
      last.focus()
    }
  } else {
    if (document.activeElement === last) {
      e.preventDefault()
      first.focus()
    }
  }
}

function onEsc(e: KeyboardEvent) {
  if (e.key === 'Escape' && !props.persistent) close()
}

// ─── Scroll lock ──────────────────────────────────────────────────────────────

let _savedOverflow = ''
let _savedPaddingRight = ''

function lockScroll() {
  // Measure scrollbar width BEFORE hiding it.
  // window.innerWidth includes the scrollbar; documentElement.clientWidth does not.
  const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth

  _savedOverflow = document.body.style.overflow
  _savedPaddingRight = document.body.style.paddingRight

  document.body.style.overflow = 'hidden'

  if (scrollbarWidth > 0) {
    document.body.style.paddingRight = `${scrollbarWidth}px`
    // Expose as a CSS variable so fixed headers/sidebars can compensate too:
    //   .my-fixed-header { padding-right: var(--ch-scrollbar-width, 0px); }
    document.documentElement.style.setProperty('--ch-scrollbar-width', `${scrollbarWidth}px`)
  }
}

function unlockScroll() {
  document.body.style.overflow = _savedOverflow
  document.body.style.paddingRight = _savedPaddingRight
  document.documentElement.style.removeProperty('--ch-scrollbar-width')
}

// ─── Open / Close lifecycle ───────────────────────────────────────────────────

function focusFirstInPanel() {
  const focusable = getFocusable()
  if (focusable.length > 0) focusable[0]!.focus()
}

function onOpen() {
  // Capture the focused element for restoration on close.
  // Guard against document.body — it's not a meaningful restore target.
  const active = document.activeElement
  triggerEl.value = active instanceof HTMLElement && active !== document.body ? active : null

  lockScroll()
  document.addEventListener('keydown', trapFocus)
  document.addEventListener('keydown', onEsc)

  // When called from the immediate watch (open: true on mount), panelRef is
  // null so this is a no-op — onMounted runs focusFirstInPanel afterwards.
  // For subsequent opens panelRef exists and the nextTick resolves correctly.
  nextTick(focusFirstInPanel)
}

function onClose() {
  unlockScroll()
  document.removeEventListener('keydown', trapFocus)
  document.removeEventListener('keydown', onEsc)
  nextTick(() => triggerEl.value?.focus())
}

// immediate: true handles modals that mount with open: true
watch(
  () => props.open,
  (isOpen) => {
    isOpen ? onOpen() : onClose()
  },
  { immediate: true },
)

onMounted(() => {
  // When open: true on mount, the immediate watch fires before panelRef
  // exists so focusFirstInPanel() was a no-op. Run it now that the DOM is ready.
  if (props.open) focusFirstInPanel()
})

onUnmounted(() => {
  if (props.open) {
    unlockScroll()
    document.removeEventListener('keydown', trapFocus)
    document.removeEventListener('keydown', onEsc)
  }
})

// ─── Actions ──────────────────────────────────────────────────────────────────

function close() {
  emit('update:open', false)
  emit('close')
}

function onBackdropClick() {
  if (props.persistent) {
    // Shake the panel to signal it cannot be dismissed this way.
    // Reset first so clicking again while shaking re-triggers the animation.
    isShaking.value = false
    nextTick(() => {
      isShaking.value = true
      setTimeout(() => {
        isShaking.value = false
      }, 400)
    })
  } else {
    close()
  }
}
</script>

<template>
  <Teleport to="body">
    <!--
      Single <Transition> on the backdrop.
      The panel's enter/leave animation is expressed through the parent
      transition's CSS classes (.ch-modal-fade-enter-active .ch-modal, etc.)
      rather than a nested <Transition>.

      A nested <Transition> with no v-if on the child is a no-op — the child
      never enters or leaves the DOM independently of its parent, so neither
      enter-from nor leave-to are ever applied.
    -->
    <Transition name="ch-modal-fade">
      <div v-if="open" class="ch-modal-backdrop" @click.self="onBackdropClick">
        <div
          ref="panelRef"
          class="ch-modal"
          :class="[
            `ch-modal--${size}`,
            {
              'ch-modal--scrollable': scrollable,
              'ch-modal--shaking': isShaking,
            },
          ]"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="title ? titleId : undefined"
          :aria-describedby="ariaDescribedby"
        >
          <!-- ── Header ── -->
          <div class="ch-modal__header">
            <div class="ch-modal__heading">
              <h2 v-if="title" :id="titleId" class="ch-modal__title">{{ title }}</h2>
              <p v-if="subtitle" :id="subtitleId" class="ch-modal__subtitle">{{ subtitle }}</p>
            </div>
            <div v-if="$slots.header" class="ch-modal__header-extra">
              <slot name="header" />
            </div>
            <button
              v-if="!hideClose"
              type="button"
              class="ch-modal__close"
              aria-label="Close dialog"
              @click="close"
            >
              <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                <path
                  d="M13.5 4.5l-9 9M4.5 4.5l9 9"
                  stroke="currentColor"
                  stroke-width="1.5"
                  stroke-linecap="round"
                />
              </svg>
            </button>
          </div>

          <!-- ── Body ── -->
          <div class="ch-modal__body">
            <slot />
          </div>

          <!-- ── Footer (optional) ── -->
          <div v-if="$slots.footer" class="ch-modal__footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* ─── Backdrop ────────────────────────────────────────────────────────────── */
.ch-modal-backdrop {
  position: fixed;
  inset: 0;
  background: var(--ch-color-overlay);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: var(--ch-z-modal);
  padding: var(--ch-space-4);
  overflow-y: auto;
}

/* ─── Panel ───────────────────────────────────────────────────────────────── */
.ch-modal {
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-lg);
  box-shadow: var(--ch-shadow-2xl);
  width: 100%;
  display: flex;
  flex-direction: column;
  /*
      100dvh (dynamic viewport height) accounts for retractable browser chrome on
      mobile — address bars and nav bars make 100vh taller than the visible area.
      The 100vh fallback serves browsers that don't yet support dvh units.
    */
  max-height: calc(100vh - var(--ch-space-8));
  max-height: calc(100dvh - var(--ch-space-8));
  position: relative;
  /*
      overflow: hidden clips slot content to the rounded corners.
      The footer needs no border-radius of its own as a result.
    */
  overflow: hidden;
}

/* ─── Sizes ───────────────────────────────────────────────────────────────── */
.ch-modal--xs {
  max-width: 360px;
}

.ch-modal--sm {
  max-width: 480px;
}

.ch-modal--md {
  max-width: 600px;
}

.ch-modal--lg {
  max-width: 768px;
}
.ch-modal--xl {
  max-width: 1024px;
}
.ch-modal--full {
  max-width: calc(100vw - var(--ch-space-8));
}

/* ─── Scrollable body ─────────────────────────────────────────────────────── */
.ch-modal--scrollable .ch-modal__body {
  overflow-y: auto;
  flex: 1 1 auto;
  min-height: 0;
}

/* ─── Persistent shake ────────────────────────────────────────────────────── */
@keyframes ch-modal-shake {
  0%,
  100% {
    transform: translateX(0);
  }

  20% {
    transform: translateX(-7px);
  }

  50% {
    transform: translateX(7px);
  }

  75% {
    transform: translateX(-4px);
  }

  90% {
    transform: translateX(2px);
  }
}

.ch-modal--shaking {
  animation: ch-modal-shake 0.4s var(--ch-ease-out);
}
/* ─── Header ──────────────────────────────────────────────────────────────── */
.ch-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--ch-space-4);
  padding: var(--ch-space-6) var(--ch-space-6) var(--ch-space-4);
  border-bottom: 1px solid var(--ch-color-border-strong);
  flex-shrink: 0;
}

.ch-modal__heading {
  flex: 1;
  min-width: 0;
}

.ch-modal__title {
  font-family: var(--ch-font-display);
  font-size: var(--ch-text-xl);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text);
  line-height: var(--ch-leading-tight);
  margin: 0;
}

.ch-modal__subtitle {
  font-size: var(--ch-text-sm);
  color: var(--ch-color-text-muted);
  margin: var(--ch-space-1) 0 0;
}

.ch-modal__header-extra {
  flex-shrink: 0;
}

.ch-modal__close {
  flex-shrink: 0;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--ch-color-text-subtle);
  padding: var(--ch-space-1);
  border-radius: var(--ch-radius-md);
  display: flex;
  align-items: center;
  transition:
    color var(--ch-duration-fast) var(--ch-ease-out),
    background-color var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-modal__close:hover {
  color: var(--ch-color-text);
  background-color: var(--ch-color-bg-muted);
}
.ch-modal__close:focus-visible {
  outline: 2px solid var(--ch-color-primary);
  outline-offset: 2px;
}

/* ─── Body ────────────────────────────────────────────────────────────────── */
.ch-modal__body {
  padding: var(--ch-space-6);
  flex-shrink: 0;
  color: var(--ch-color-text);
  font-size: var(--ch-text-sm);
  line-height: var(--ch-leading-relaxed);
}

/* ─── Footer ──────────────────────────────────────────────────────────────── */
.ch-modal__footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--ch-space-2);
  padding: var(--ch-space-4) var(--ch-space-6);
  border-top: 1px solid var(--ch-color-border-strong);
  background: var(--ch-color-bg-subtle);
  flex-shrink: 0;
  /* No border-radius needed — overflow: hidden on .ch-modal clips the corners */
}

/* ─── Transitions ─────────────────────────────────────────────────────────── */
/* Backdrop: fade */
.ch-modal-fade-enter-active {
  transition: opacity var(--ch-duration-normal) var(--ch-ease-out);
}

.ch-modal-fade-leave-active {
  transition: opacity var(--ch-duration-fast) var(--ch-ease-in);
}
.ch-modal-fade-enter-from,
.ch-modal-fade-leave-to {
  opacity: 0;
}

/*
  Panel: scale + slide, driven through the parent transition's classes.
  This replaces the previous nested <Transition> which was a no-op because
  the panel has no v-if — it never enters/leaves the DOM on its own.
*/
.ch-modal-fade-enter-active .ch-modal {
  transition:
    opacity var(--ch-duration-normal) var(--ch-ease-out),
    transform var(--ch-duration-normal) var(--ch-ease-spring);
}
.ch-modal-fade-leave-active .ch-modal {
  transition:
    opacity var(--ch-duration-fast) var(--ch-ease-in),
    transform var(--ch-duration-fast) var(--ch-ease-in);
}
.ch-modal-fade-enter-from .ch-modal,
.ch-modal-fade-leave-to .ch-modal {
  opacity: 0;
  transform: scale(0.95) translateY(8px);
}
</style>
