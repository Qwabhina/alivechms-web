<script setup lang="ts">
/**
 * @component ChFileUpload
 * @path /frontend/src/design-system/components/forms/ChFileUpload.vue
 * @description A file upload drop zone with drag-and-drop support, file
 * type/size validation, and a selected-file list with individual removal.
 *
 * ─── Drag events note ────────────────────────────────────────────────────────
 * Drag events must be attached to the visible drop zone div, NOT to the hidden
 * file input (which has width:0/height:0 and never receives drag events).
 *
 * ─── Accept validation ───────────────────────────────────────────────────────
 * The native `accept` attribute on <input> provides browser-level filtering.
 * The `processFiles` function performs a secondary JS validation as a safety
 * net using the MIME type category (e.g. "image/") or extension matching,
 * NOT `String.match()` with the accept string as a regex (that would treat
 * `image/*` as a regex, where `*` means "zero or more of the preceding char").
 *
 * @example Single image upload
 * <ChFileUpload v-model="form.photo" accept="image/*" :max-size="5 * 1024 * 1024"
 *               label="Profile photo" />
 *
 * @example Multiple document upload
 * <ChFileUpload v-model="form.attachments" accept=".pdf,.doc,.docx"
 *               :multiple="true" :max-size="10 * 1024 * 1024"
 *               label="Supporting documents" />
 */

import { ref, watch } from 'vue'

interface Props {
   id?: string
   modelValue?: File[]
   label?: string
   hint?: string
   accept?: string
   multiple?: boolean
   /** Maximum file size in bytes. Default: 10 MB */
   maxSize?: number
   buttonText?: string
   /** Instruction shown in the drop zone at rest */
   dropText?: string
   /** Sub-instruction shown below the main drop text */
   subText?: string
   disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
   accept: '',
   multiple: false,
   maxSize: 10 * 1024 * 1024,
   disabled: false,
   buttonText: 'Select Files',
   dropText: 'Drag & drop files here',
   subText: 'or click to browse',
})

const emit = defineEmits<{
   'update:modelValue': [value: File[]]
   change: [value: File[]]
   error: [message: string]
}>()

// ─── State ────────────────────────────────────────────────────────────────────

const fileInputRef = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)
const errorMsg = ref('')
const files = ref<File[]>([])

// Sync external modelValue changes into local state
watch(() => props.modelValue, (val) => {
   if (val) files.value = [...val]
}, { immediate: true })

// Emit upward whenever local files change
watch(files, (next) => {
   emit('update:modelValue', next)
   emit('change', next)
})

// ─── Accept validation ────────────────────────────────────────────────────────

/**
 * Checks whether a file's MIME type or extension is permitted by the
 * `accept` prop. Works correctly with patterns like:
 *   "image/*"       → checks file.type starts with "image/"
 *   ".pdf,.doc"     → checks file.name ends with ".pdf" or ".doc"
 *   "application/pdf" → exact MIME type match
 *
 * Does NOT use String.match() with the accept string as a regex, which
 * would misinterpret `*` and `.` as regex metacharacters.
 */
function fileMatchesAccept(file: File): boolean {
   if (!props.accept) return true
   const patterns = props.accept.split(',').map(s => s.trim().toLowerCase())

   return patterns.some(pattern => {
      if (pattern.startsWith('.')) {
         // Extension match: ".pdf" → check filename ends with ".pdf"
         return file.name.toLowerCase().endsWith(pattern)
      }
      if (pattern.endsWith('/*')) {
         // Wildcard MIME category: "image/*" → check type starts with "image/"
         const category = pattern.slice(0, -1) // "image/*" → "image/"
         return file.type.toLowerCase().startsWith(category)
      }
      // Exact MIME type: "application/pdf"
      return file.type.toLowerCase() === pattern
   })
}

// ─── File processing ──────────────────────────────────────────────────────────

function processFiles(incoming: File[]) {
   errorMsg.value = ''

   const valid = incoming.filter(file => {
      if (!fileMatchesAccept(file)) {
         const msg = `File type not supported: ${file.name}`
         errorMsg.value = msg
         emit('error', msg)
         return false
      }
      if (file.size > props.maxSize) {
         const msg = `${file.name} exceeds the ${formatSize(props.maxSize)} limit`
         errorMsg.value = msg
         emit('error', msg)
         return false
      }
      return true
   })

   files.value = props.multiple
      ? [...files.value, ...valid]
      : valid.slice(0, 1)
}

function removeFile(index: number) {
   files.value = files.value.filter((_, i) => i !== index)
}

function formatSize(bytes: number): string {
   if (bytes === 0) return '0 B'
   const units = ['B', 'KB', 'MB', 'GB']
   const i = Math.floor(Math.log(bytes) / Math.log(1024))
   return `${parseFloat((bytes / 1024 ** i).toFixed(1))} ${units[i]}`
}

// ─── Event handlers ───────────────────────────────────────────────────────────

function onInputChange(e: Event) {
   const input = e.target as HTMLInputElement
   if (input.files?.length) processFiles(Array.from(input.files))
   // Reset input so the same file can be re-selected after removal
   input.value = ''
}

function onBrowseClick() {
   fileInputRef.value?.click()
}

// Drag events must be on the visible drop zone div, not the hidden input
function onDragEnter(e: DragEvent) { e.preventDefault(); isDragging.value = true }
function onDragOver(e: DragEvent) { e.preventDefault(); isDragging.value = true }
function onDragLeave() { isDragging.value = false }
function onDrop(e: DragEvent) {
   e.preventDefault()
   isDragging.value = false
   if (e.dataTransfer?.files.length) processFiles(Array.from(e.dataTransfer.files))
}
</script>

<template>
   <div class="ch-file-upload" :class="{ 'ch-file-upload--disabled': disabled }">

      <!-- Label (above the drop zone, consistent with other form components) -->
      <label v-if="label" class="ch-file-upload__label-text" :for="`${id}-input`">
         {{ label }}
      </label>

      <!--
      Drop zone — this is the visible, interactive area.
      Drag events live here (not on the hidden input) so they actually fire.
    -->
      <div class="ch-file-upload__zone" :class="{
         'ch-file-upload__zone--dragging': isDragging,
         'ch-file-upload__zone--has-error': !!errorMsg,
      }" role="button" tabindex="0" :aria-label="`${label || 'File upload'}. Press Enter or Space to browse.`"
         @click="onBrowseClick" @keydown.enter.prevent="onBrowseClick" @keydown.space.prevent="onBrowseClick"
         @dragenter="onDragEnter" @dragover="onDragOver" @dragleave="onDragLeave" @drop="onDrop">
         <!-- Upload cloud icon -->
         <svg class="ch-file-upload__icon" width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor"
            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10.667 21.333A5.333 5.333 0 018 11a6.667 6.667 0 0112.933-2.267A5.333 5.333 0 0124 19.333" />
            <path d="M13.333 21.333l2.667-2.666 2.667 2.666" />
            <path d="M16 18.667V28" />
         </svg>

         <p class="ch-file-upload__drop-text">
            {{ isDragging ? 'Drop files here' : dropText }}
         </p>
         <p class="ch-file-upload__sub-text">{{ subText }}</p>

         <button type="button" class="ch-file-upload__browse-btn" :disabled="disabled" tabindex="-1"
            @click.stop="onBrowseClick">
            {{ buttonText }}
         </button>
      </div>

      <!-- Hidden native file input — opened programmatically via onBrowseClick -->
      <input :id="`${id}-input`" ref="fileInputRef" type="file" class="ch-file-upload__input" :accept="accept"
         :multiple="multiple" :disabled="disabled" @change="onInputChange" />

      <!-- Hint -->
      <p v-if="hint && !errorMsg" class="ch-file-upload__hint">{{ hint }}</p>

      <!-- Validation error -->
      <p v-if="errorMsg" class="ch-file-upload__error" role="alert" aria-live="polite">
         <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
            <circle cx="6" cy="6" r="5.25" stroke="currentColor" stroke-width="1.2" />
            <path d="M6 3.5v3M6 8.5v.25" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
         </svg>
         {{ errorMsg }}
      </p>

      <!-- Selected files list -->
      <ul v-if="files.length > 0" class="ch-file-upload__list" role="list">
         <li v-for="(file, i) in files" :key="`${file.name}-${i}`" class="ch-file-upload__file">
            <!-- File icon -->
            <svg class="ch-file-upload__file-icon" width="16" height="16" viewBox="0 0 16 16" fill="none"
               stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"
               aria-hidden="true">
               <path d="M9 2H4a1 1 0 00-1 1v10a1 1 0 001 1h8a1 1 0 001-1V6L9 2z" />
               <path d="M9 2v4h4" />
            </svg>

            <div class="ch-file-upload__file-info">
               <span class="ch-file-upload__file-name">{{ file.name }}</span>
               <span class="ch-file-upload__file-size">{{ formatSize(file.size) }}</span>
            </div>

            <button type="button" class="ch-file-upload__remove" :aria-label="`Remove ${file.name}`"
               :disabled="disabled" @click="removeFile(i)">
               <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                  <path d="M9 3L3 9M3 3l6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
               </svg>
            </button>
         </li>
      </ul>

   </div>
</template>

<style scoped>
/* ─── Root ────────────────────────────────────────────────────────────────── */
.ch-file-upload {
   display: flex;
   flex-direction: column;
   gap: var(--ch-space-1_5);
   width: 100%;
   font-family: var(--ch-font-sans);
}

.ch-file-upload--disabled {
   opacity: 0.5;
   pointer-events: none;
}

/* ─── Label (above the zone, consistent with ChFormField pattern) ─────────── */
.ch-file-upload__label-text {
   font-size: var(--ch-text-sm);
   font-weight: var(--ch-font-medium);
   color: var(--ch-color-text);
}

/* ─── Drop zone ───────────────────────────────────────────────────────────── */
.ch-file-upload__zone {
   display: flex;
   flex-direction: column;
   align-items: center;
   gap: var(--ch-space-3);
   padding: var(--ch-space-8) var(--ch-space-6);
   border: 2px dashed var(--ch-color-border-strong);
   border-radius: var(--ch-radius-none);
   background-color: var(--ch-color-bg-subtle);
   cursor: pointer;
   text-align: center;
   /*
   * Specify individual properties rather than `transition: all` —
   * `all` causes every CSS property change to animate, including layout
   * recalculations, which is a performance hazard.
   */
   transition:
      border-color var(--ch-duration-fast) var(--ch-ease-out),
      background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-file-upload__zone:hover,
.ch-file-upload__zone:focus-visible {
   border-color: var(--ch-color-primary);
   background-color: var(--ch-color-primary-subtle);
   outline: none;
}

.ch-file-upload__zone--dragging {
   border-color: var(--ch-color-primary);
   background-color: var(--ch-color-primary-subtle);
   outline: 2px solid var(--ch-color-primary);
   outline-offset: -2px;
}

.ch-file-upload__zone--has-error {
   border-color: var(--ch-color-danger);
}

/* ─── Zone icon ───────────────────────────────────────────────────────────── */
.ch-file-upload__icon {
   color: var(--ch-color-primary);
}

/* ─── Zone text ───────────────────────────────────────────────────────────── */
.ch-file-upload__drop-text {
   font-size: var(--ch-text-sm);
   font-weight: var(--ch-font-semibold);
   color: var(--ch-color-text);
   margin: 0;
}

.ch-file-upload__sub-text {
   font-size: var(--ch-text-xs);
   color: var(--ch-color-text-subtle);
   margin: 0;
}

/* ─── Browse button ───────────────────────────────────────────────────────── */
.ch-file-upload__browse-btn {
   padding: var(--ch-space-1_5) var(--ch-space-4);
   background-color: var(--ch-color-primary);
   color: white;
   border: none;
   border-radius: var(--ch-radius-none);
   font-family: var(--ch-font-sans);
   font-size: var(--ch-text-sm);
   font-weight: var(--ch-font-medium);
   cursor: pointer;
   transition: background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-file-upload__browse-btn:hover:not(:disabled) {
   background-color: var(--ch-color-primary-hover);
}

.ch-file-upload__browse-btn:disabled {
   opacity: 0.5;
   cursor: not-allowed;
}

/* ─── Hidden native input ─────────────────────────────────────────────────── */
/* Visually hidden but kept in the accessibility tree for the label association */
.ch-file-upload__input {
   position: absolute;
   opacity: 0;
   width: 1px;
   height: 1px;
   overflow: hidden;
   clip: rect(0, 0, 0, 0);
}

/* ─── Hint / Error ────────────────────────────────────────────────────────── */
.ch-file-upload__hint {
   font-size: var(--ch-text-xs);
   color: var(--ch-color-text-subtle);
   line-height: var(--ch-leading-normal);
   margin: 0;
}

.ch-file-upload__error {
   display: flex;
   align-items: center;
   gap: var(--ch-space-1);
   font-size: var(--ch-text-xs);
   font-weight: var(--ch-font-medium);
   color: var(--ch-color-danger);
   margin: 0;
}

/* ─── Selected files list ─────────────────────────────────────────────────── */
.ch-file-upload__list {
   list-style: none;
   margin: 0;
   padding: 0;
   display: flex;
   flex-direction: column;
   gap: var(--ch-space-1_5);
}

.ch-file-upload__file {
   display: flex;
   align-items: center;
   gap: var(--ch-space-2_5);
   padding: var(--ch-space-2_5) var(--ch-space-3);
   background-color: var(--ch-color-bg-muted);
   border: 1px solid var(--ch-color-border-strong);
   border-radius: var(--ch-radius-none);
}

.ch-file-upload__file-icon {
   color: var(--ch-color-primary);
   flex-shrink: 0;
}

.ch-file-upload__file-info {
   flex: 1;
   min-width: 0;
   /* allow text truncation */
   display: flex;
   flex-direction: column;
   gap: var(--ch-space-0_5);
}

.ch-file-upload__file-name {
   font-size: var(--ch-text-sm);
   font-weight: var(--ch-font-medium);
   color: var(--ch-color-text);
   overflow: hidden;
   text-overflow: ellipsis;
   white-space: nowrap;
}

.ch-file-upload__file-size {
   font-size: var(--ch-text-xs);
   font-family: var(--ch-font-mono);
   color: var(--ch-color-text-subtle);
}

/* ─── Remove button ───────────────────────────────────────────────────────── */
.ch-file-upload__remove {
   flex-shrink: 0;
   background: none;
   border: none;
   padding: var(--ch-space-1);
   cursor: pointer;
   color: var(--ch-color-text-subtle);
   border-radius: var(--ch-radius-none);
   display: flex;
   align-items: center;
   transition:
      color var(--ch-duration-fast) var(--ch-ease-out),
      background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-file-upload__remove:hover {
   color: var(--ch-color-danger);
   background-color: var(--ch-color-danger-bg);
}

.ch-file-upload__remove:focus-visible {
   outline: 2px solid var(--ch-color-border-focus);
   outline-offset: 1px;
}
</style>