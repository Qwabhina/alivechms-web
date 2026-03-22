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
 * When open, Tab and Shift+Tab cycle focus only within the modal.
 * Focus is restored to the triggering element on close.
 *
 * ─── Scroll lock ─────────────────────────────────────────────────────────────
 * Opens: adds overflow:hidden to <body> so the page behind doesn't scroll.
 * Closes: restores the original overflow value.
 */

import { ref, watch, nextTick, onUnmounted } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────
type ModalSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'full'

interface Props {
  open:       boolean
  title?:     string
  subtitle?:  string
  size?:      ModalSize
  /**
   * When true, clicking the backdrop does NOT close the modal.
   * Use for critical confirmations or multi-step forms where accidental
   * dismissal would lose the user's work.
   */
  persistent?: boolean
  /**
   * Hides the default close (×) button in the header.
   * Use when the footer has explicit Cancel / Close actions.
   */
  hideClose?:  boolean
  /**
   * Scrollable body — when true, the modal body gets its own scrollbar
   * and the header/footer stay fixed. Use for long-form content.
   * Default: true
   */
  scrollable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size:       'md',
  persistent: false,
  hideClose:  false,
  scrollable: true,
})

const emit = defineEmits<{
  'update:open': [value: boolean]
  close: []
}>()

// ─── Refs ─────────────────────────────────────────────────────────────────────
const panelRef   = ref<HTMLElement | null>(null)
const triggerEl  = ref<Element | null>(null)  // element focused before modal opened

// ─── Focus trap helpers ───────────────────────────────────────────────────────

const FOCUSABLE = [
  'a[href]', 'button:not([disabled])', 'input:not([disabled])',
  'select:not([disabled])', 'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',')

function getFocusable(): HTMLElement[] {
  if (!panelRef.value) return []
  return Array.from(panelRef.value.querySelectorAll<HTMLElement>(FOCUSABLE))
}

function trapFocus(e: KeyboardEvent) {
  if (e.key !== 'Tab') return
  const focusable = getFocusable()
  if (focusable.length === 0) return

  const first = focusable[0]!
  const last = focusable[focusable.length - 1]!

  if (e.shiftKey) {
    if (document.activeElement === first) { e.preventDefault(); last.focus() }
  } else {
    if (document.activeElement === last)  { e.preventDefault(); first.focus() }
  }
}

function onEsc(e: KeyboardEvent) {
  if (e.key === 'Escape' && !props.persistent) close()
}

// ─── Scroll lock ──────────────────────────────────────────────────────────────

let _savedOverflow = ''

function lockScroll() {
  _savedOverflow = document.body.style.overflow
  document.body.style.overflow = 'hidden'
}

function unlockScroll() {
  document.body.style.overflow = _savedOverflow
}

// ─── Open / Close lifecycle ───────────────────────────────────────────────────

watch(() => props.open, async (isOpen) => {
  if (isOpen) {
    // Save the currently focused element to restore on close
    triggerEl.value = document.activeElement

    lockScroll()
    document.addEventListener('keydown', trapFocus)
    document.addEventListener('keydown', onEsc)

    // Move focus into the modal after it renders
    await nextTick()
    const focusable = getFocusable()
    if (focusable.length > 0) focusable[0]!.focus()
  } else {
    unlockScroll()
    document.removeEventListener('keydown', trapFocus)
    document.removeEventListener('keydown', onEsc)

    // Return focus to the element that opened the modal
    await nextTick()
    if (triggerEl.value instanceof HTMLElement) triggerEl.value.focus()
  }
})

onUnmounted(() => {
  unlockScroll()
  document.removeEventListener('keydown', trapFocus)
  document.removeEventListener('keydown', onEsc)
})

function close() {
  emit('update:open', false)
  emit('close')
}

function onBackdropClick() {
  if (!props.persistent) close()
}
</script>

<template>
  <Teleport to="body">
    <Transition name="ch-modal-fade">
      <div
        v-if="open"
        class="ch-modal-backdrop"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="title ? 'ch-modal-title' : undefined"
        @click.self="onBackdropClick"
      >
        <Transition name="ch-modal-scale">
          <div
            v-if="open"
            ref="panelRef"
            class="ch-modal"
            :class="[`ch-modal--${size}`, { 'ch-modal--scrollable': scrollable }]"
          >
            <!-- ── Header ── -->
            <div class="ch-modal__header">
              <div class="ch-modal__heading">
                <h2 v-if="title" id="ch-modal-title" class="ch-modal__title">{{ title }}</h2>
                <p v-if="subtitle" class="ch-modal__subtitle">{{ subtitle }}</p>
              </div>
              <!-- Optional header slot for extra content (e.g. a step indicator) -->
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
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                  <path d="M13.5 4.5l-9 9M4.5 4.5l9 9"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
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
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* ─── Backdrop ────────────────────────────────────────────────────────────── */
.ch-modal-backdrop {
  position:        fixed;
  inset:           0;
  background:      rgb(0 0 0 / 0.5);
  display:         flex;
  align-items:     center;
  justify-content: center;
  z-index:         var(--ch-z-modal);
  padding:         var(--ch-space-4);
  overflow-y:      auto;
}

/* ─── Panel ───────────────────────────────────────────────────────────────── */
.ch-modal {
  background:     var(--ch-color-surface);
  border:         1px solid var(--ch-color-border);
  border-radius:  var(--ch-radius-2xl);
  box-shadow:     var(--ch-shadow-xl);
  width:          100%;
  display:        flex;
  flex-direction: column;
  max-height:     calc(100vh - var(--ch-space-8));
  position:       relative;
}

/* ─── Sizes ───────────────────────────────────────────────────────────────── */
.ch-modal--xs   { max-width: 360px; }
.ch-modal--sm   { max-width: 480px; }
.ch-modal--md   { max-width: 600px; }
.ch-modal--lg   { max-width: 768px; }
.ch-modal--xl   { max-width: 1024px; }
.ch-modal--full { max-width: calc(100vw - var(--ch-space-8)); }

/* ─── Scrollable body ─────────────────────────────────────────────────────── */
.ch-modal--scrollable .ch-modal__body {
  overflow-y: auto;
  /* flex-shrink allows body to shrink when viewport is short */
  flex:       1 1 auto;
  min-height: 0;
}

/* ─── Header ──────────────────────────────────────────────────────────────── */
.ch-modal__header {
  display:         flex;
  align-items:     flex-start;
  justify-content: space-between;
  gap:             var(--ch-space-4);
  padding:         var(--ch-space-6) var(--ch-space-6) var(--ch-space-4);
  border-bottom:   1px solid var(--ch-color-border);
  flex-shrink:     0;
}

.ch-modal__heading { flex: 1; min-width: 0; }

.ch-modal__title {
  font-family:  var(--ch-font-display);
  font-size:    var(--ch-text-xl);
  font-weight:  var(--ch-font-semibold);
  color:        var(--ch-color-text);
  line-height:  var(--ch-leading-tight);
  margin:       0;
}

.ch-modal__subtitle {
  font-size:   var(--ch-text-sm);
  color:       var(--ch-color-text-muted);
  margin:      var(--ch-space-1) 0 0;
}

.ch-modal__header-extra { flex-shrink: 0; }

.ch-modal__close {
  flex-shrink:   0;
  background:    none;
  border:        none;
  cursor:        pointer;
  color:         var(--ch-color-text-subtle);
  padding:       var(--ch-space-1);
  border-radius: var(--ch-radius-md);
  display:       flex;
  align-items:   center;
  transition:
    color            var(--ch-duration-fast) var(--ch-ease-out),
    background-color var(--ch-duration-fast) var(--ch-ease-out);
}
.ch-modal__close:hover {
  color:            var(--ch-color-text);
  background-color: var(--ch-color-bg-muted);
}

/* ─── Body ────────────────────────────────────────────────────────────────── */
.ch-modal__body {
  padding:    var(--ch-space-6);
  flex-shrink:0;
  color:      var(--ch-color-text);
  font-size:  var(--ch-text-sm);
  line-height:var(--ch-leading-relaxed);
}

/* ─── Footer ──────────────────────────────────────────────────────────────── */
.ch-modal__footer {
  display:         flex;
  align-items:     center;
  justify-content: flex-end;
  gap:             var(--ch-space-2);
  padding:         var(--ch-space-4) var(--ch-space-6);
  border-top:      1px solid var(--ch-color-border);
  background:      var(--ch-color-bg-subtle);
  border-radius:   0 0 var(--ch-radius-2xl) var(--ch-radius-2xl);
  flex-shrink:     0;
}

/* ─── Transitions ─────────────────────────────────────────────────────────── */
.ch-modal-fade-enter-active,
.ch-modal-fade-leave-active { transition: opacity var(--ch-duration-normal) var(--ch-ease-out); }
.ch-modal-fade-enter-from,
.ch-modal-fade-leave-to     { opacity: 0; }

.ch-modal-scale-enter-active {
  transition: opacity    var(--ch-duration-normal) var(--ch-ease-out),
              transform  var(--ch-duration-normal) var(--ch-ease-spring);
}
.ch-modal-scale-leave-active {
  transition: opacity    var(--ch-duration-fast) var(--ch-ease-in),
              transform  var(--ch-duration-fast) var(--ch-ease-in);
}
.ch-modal-scale-enter-from,
.ch-modal-scale-leave-to {
  opacity:   0;
  transform: scale(0.95) translateY(8px);
}
</style>
