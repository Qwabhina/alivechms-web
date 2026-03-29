<script setup lang="ts">
/**
 * @component ChFileUpload
 * @path /frontend/src/design-system/components/forms/ChFileUpload.vue
 * @description A file upload drop zone with drag-and-drop support, file
 * type/size validation, and a selected-file list with individual removal.
 *
 * ─── Drag events ─────────────────────────────────────────────────────────────
 * Drag events are on the visible drop zone div, NOT the hidden file input
 * (which has width/height 0 and never receives drag events).
 *
 * A drag counter is used instead of a boolean flag to prevent isDragging from
 * flickering false every time the cursor crosses into a child element — the
 * browser fires dragleave/dragenter pairs at every child boundary.
 *
 * ─── Accept validation ───────────────────────────────────────────────────────
 * The native `accept` attribute provides browser-level filtering.
 * `fileMatchesAccept` performs a JS safety-net check using MIME category
 * matching or extension matching — NOT String.match() with accept as a regex
 * (which would misinterpret `*` and `.` as regex metacharacters).
 *
 * ─── Emission strategy ───────────────────────────────────────────────────────
 * update:modelValue and change are emitted directly from processFiles and
 * removeFile — NOT from a watch on the local files ref. This avoids the
 * watch(modelValue) → set files → watch(files) → emit circular loop that
 * would fire change on mount whenever a modelValue is provided.
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

import { ref, computed, watch } from 'vue'

// ─── Props ────────────────────────────────────────────────────────────────────

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

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
   'update:modelValue': [value: File[]]
   change: [value: File[]]
   error: [messages: string[]]
}>()

// ─── Refs ─────────────────────────────────────────────────────────────────────

const fileInputRef = ref<HTMLInputElement | null>(null)

// ─── State ────────────────────────────────────────────────────────────────────

const files = ref<File[]>([])
const errorMsgs = ref<string[]>([])

/**
 * Drag counter instead of a boolean flag.
 * dragenter increments, dragleave decrements. isDragging is true when > 0.
 * This prevents the visual state from flickering every time the cursor
 * crosses from the zone into a child element (which fires a dragleave/
 * dragenter pair at each boundary).
 */
let dragCounter = 0
const isDragging = ref(false)

// ─── Sync external modelValue into local state ────────────────────────────────

/**
 * One-way sync: parent → local.
 * We do NOT watch `files` to emit back — that would create a circular loop
 * (modelValue changes → files updated → watch fires → emit → modelValue
 * changes again). Instead, we emit directly from user-action handlers only.
 *
 * When the parent clears the model, also clear any lingering error messages.
 */
watch(
   () => props.modelValue,
   (val) => {
      files.value = val ? [...val] : []
      if (!val || val.length === 0) errorMsgs.value = []
   },
   { immediate: true },
)

// ─── Computed ─────────────────────────────────────────────────────────────────

/** IDs for accessible label association — undefined when no id prop given */
const inputId = computed(() => props.id ? `${props.id}-input` : undefined)
const errorId = computed(() => props.id ? `${props.id}-error` : undefined)

// ─── Accept validation ────────────────────────────────────────────────────────

/**
 * Checks whether a file's MIME type or extension is permitted.
 * Works correctly with:
 *   "image/*"         → type starts with "image/"
 *   ".pdf,.doc"       → name ends with ".pdf" or ".doc"
 *   "application/pdf" → exact MIME type match
 */
function fileMatchesAccept(file: File): boolean {
   if (!props.accept) return true
   const patterns = props.accept.split(',').map(s => s.trim().toLowerCase())
   return patterns.some(pattern => {
     if (pattern.startsWith('.')) return file.name.toLowerCase().endsWith(pattern)
     if (pattern.endsWith('/*')) return file.type.toLowerCase().startsWith(pattern.slice(0, -1))
     return file.type.toLowerCase() === pattern
  })
}

// ─── File deduplication ───────────────────────────────────────────────────────

/**
 * Returns only files that are not already in the current list,
 * matched by name + size + lastModified to avoid duplicate entries.
 */
function deduplicateFiles(incoming: File[]): File[] {
   return incoming.filter(
      f => !files.value.some(
         existing => existing.name === f.name &&
            existing.size === f.size &&
            existing.lastModified === f.lastModified,
      ),
   )
}

// ─── File processing ──────────────────────────────────────────────────────────

/**
 * Validate and add incoming files. Collects all validation errors rather than
 * stopping at the first failure, so the user sees every problem at once.
 *
 * Emits update:modelValue and change directly — no reactive watch needed.
 */
function processFiles(incoming: File[]) {
   errorMsgs.value = []
   const errors: string[] = []

   const valid = deduplicateFiles(incoming).filter(file => {
      if (!fileMatchesAccept(file)) {
        errors.push(`File type not supported: ${file.name}`)
        return false
     }
     if (file.size > props.maxSize) {
        errors.push(`${file.name} exceeds the ${formatSize(props.maxSize)} limit`)
        return false
     }
     return true
  })

   if (errors.length > 0) {
      errorMsgs.value = errors
      emit('error', errors)
   }

   const next = props.multiple ? [...files.value, ...valid] : valid.slice(0, 1)
   files.value = next
   emit('update:modelValue', next)
   emit('change', next)
}

function removeFile(index: number) {
   const next = files.value.filter((_, i) => i !== index)
   files.value = next
   errorMsgs.value = []
   emit('update:modelValue', next)
   emit('change', next)
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
   // Reset so the same file can be re-selected after removal
   input.value = ''
}

function onBrowseClick() {
   if (!props.disabled) fileInputRef.value?.click()
}

// Drag counter handlers — prevent child-crossing flicker
function onDragEnter(e: DragEvent) {
   e.preventDefault()
   dragCounter++
   isDragging.value = true
}

function onDragOver(e: DragEvent) {
   // preventDefault is required to allow dropping
   e.preventDefault()
}

function onDragLeave() {
   if (--dragCounter <= 0) {
      dragCounter = 0
      isDragging.value = false
   }
}

function onDrop(e: DragEvent) {
   e.preventDefault()
   dragCounter = 0
   isDragging.value = false
   if (e.dataTransfer?.files.length) processFiles(Array.from(e.dataTransfer.files))
}
</script>

<template>
  <div class="ch-file-upload" :class="{ 'ch-file-upload--disabled': disabled }">

    <!-- Label -->
      <label v-if="label" class="ch-file-upload__label-text" :for="inputId">
         {{ label }}
      </label>

    <!--
     Drop zone — visible interactive area.
      The inner browse <button> is the primary keyboard/click action.
      The outer div handles drag events and acts as an extended click target.
    -->
     <div class="ch-file-upload__zone" :class="{
         'ch-file-upload__zone--dragging': isDragging,
         'ch-file-upload__zone--has-error': errorMsgs.length > 0,
      }" :aria-describedby="errorMsgs.length > 0 ? errorId : undefined" @dragenter="onDragEnter" @dragover="onDragOver"
         @dragleave="onDragLeave" @drop="onDrop">
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


       <button type="button" class="ch-file-upload__browse-btn" :disabled="disabled"
            :aria-label="`${label ? `${label}: ` : ''}${buttonText}`" @click="onBrowseClick">
            {{ buttonText }}
         </button>
      </div>

    <!-- Hidden native file input — opened programmatically via browse button -->
      <input :id="inputId" ref="fileInputRef" type="file" class="ch-file-upload__input" :accept="accept"
         :multiple="multiple" :disabled="disabled" :aria-hidden="true" tabindex="-1" @change="onInputChange" />

    <!-- Hint (hidden when errors are showing) -->
      <p v-if="hint && errorMsgs.length === 0" class="ch-file-upload__hint">{{ hint }}</p>

    <!-- Validation errors — role="alert" alone (implies aria-live="assertive") -->
      <ul v-if="errorMsgs.length > 0" :id="errorId" class="ch-file-upload__errors" role="alert">
         <li v-for="(msg, i) in errorMsgs" :key="i" class="ch-file-upload__error">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
               <circle cx="6" cy="6" r="5.25" stroke="currentColor" stroke-width="1.2" />
               <path d="M6 3.5v3M6 8.5v.25" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
            </svg>
  
           {{ msg }}
         </li>
      </ul>

    <!-- Selected files list -->
      <ul v-if="files.length > 0" class="ch-file-upload__list" role="list">
 
        <li v-for="(file, i) in files" :key="`${file.name}-${file.size}-${file.lastModified}`"
            class="ch-file-upload__file">
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

/* ─── Label ───────────────────────────────────────────────────────────────── */
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
   border-radius: var(--ch-radius-md);
   background-color: var(--ch-color-bg-subtle);
cursor: default;
   text-align: center;
   transition:
   border-color var(--ch-duration-fast) var(--ch-ease-out),
      background-color var(--ch-duration-fast) var(--ch-ease-out);
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
   border-radius: var(--ch-radius-md);
   font-family: var(--ch-font-sans);
   font-size: var(--ch-text-sm);
   font-weight: var(--ch-font-medium);
   cursor: pointer;
   transition: background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-file-upload__browse-btn:hover:not(:disabled) {
background-color: var(--ch-color-primary-hover);
}

.ch-file-upload__browse-btn:focus-visible {
   outline: 2px solid var(--ch-color-primary);
   outline-offset: 2px;
}
.ch-file-upload__browse-btn:disabled {
opacity: 0.5;
cursor: not-allowed;
}

/* ─── Hidden native input ─────────────────────────────────────────────────── */
.ch-file-upload__input {
position: absolute;
opacity: 0;
   width: 1px;
   height: 1px;
   overflow: hidden;
clip: rect(0, 0, 0, 0);
}

/* ─── Hint ────────────────────────────────────────────────────────────────── */
.ch-file-upload__hint {
font-size: var(--ch-text-xs);
   color: var(--ch-color-text-subtle);
   line-height: var(--ch-leading-normal);
margin: 0;
}

/* ─── Errors list ─────────────────────────────────────────────────────────── */
.ch-file-upload__errors {
   list-style: none;
   margin: 0;
   padding: 0;
   display: flex;
   flex-direction: column;
   gap: var(--ch-space-0_5);
}

.ch-file-upload__error {
display: flex;
   align-items: center;
gap: var(--ch-space-1);
   font-size: var(--ch-text-xs);
   font-weight: var(--ch-font-medium);
color: var(--ch-color-danger);
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
   border-radius: var(--ch-radius-md);
}

.ch-file-upload__file-icon {
color: var(--ch-color-primary);
   flex-shrink: 0;
}

.ch-file-upload__file-info {
flex: 1;
   min-width: 0;
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
   border-radius: var(--ch-radius-md);
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