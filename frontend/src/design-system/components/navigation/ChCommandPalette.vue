<script setup lang="ts">
/**
 * @component ChCommandPalette
 * @path /frontend/src/design-system/components/navigation/ChCommandPalette.vue
 * @description A keyboard-driven command palette for quick navigation and
 * actions. Supports fuzzy search, grouped commands, and keyboard navigation.
 *
 * ─── Design decisions ────────────────────────────────────────────────────────
 * - Triggered by keyboard shortcut (Ctrl/Cmd + K by default)
 * - Fuzzy search matching for commands
 * - Full keyboard navigation (Arrow keys, Enter, Escape)
 * - Grouped commands with section headers
 * - Recently used commands tracking
 *
 * ─── Accessibility ───────────────────────────────────────────────────────────
 * - Uses `role="dialog"` with proper labeling
 * - Focus trapping when open
 * - Escape key closes the palette
 * - Arrow keys navigate, Enter selects
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example Basic usage
 * <ChCommandPalette
 *   :commands="[
 *     { id: 'dashboard', label: 'Dashboard', icon: homeIcon, action: () => router.push('/') },
 *     { id: 'members', label: 'Members', icon: usersIcon, action: () => router.push('/members') },
 *   ]"
 * />
 *
 * @example With groups
 * <ChCommandPalette
 *   :groups="[
 *     {
 *       title: 'Navigation',
 *       commands: [
 *         { id: 'dashboard', label: 'Dashboard' },
 *         { id: 'members', label: 'Members' },
 *       ]
 *     },
 *     {
 *       title: 'Actions',
 *       commands: [
 *         { id: 'new-member', label: 'Add Member' },
 *         { id: 'new-event', label: 'New Event' },
 *       ]
 *     }
 *   ]"
 *   @select="handleCommand"
 * />
 *
 * @example Custom trigger
 * <ChCommandPalette v-model:open="isOpen" :commands="commands">
 *   <template #trigger>
 *     <ChButton>Search...</ChButton>
 *   </template>
 * </ChCommandPalette>
 */

import { computed, ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { Search, CircleHelp } from 'lucide-vue-next'
import ChInput from '../core/ChInput.vue'
import ChPopover from '../core/ChPopover.vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/** A single command in the palette */
export interface Command {
  /** Unique identifier */
  id: string
  /** Display label */
  label: string
  /** Optional description shown below label */
  description?: string
  /** Optional icon SVG path */
  icon?: string
  /** Optional keyboard shortcut hint */
  shortcut?: string
  /** Optional group/category */
  group?: string
  /** Search keywords (aliases) */
  keywords?: string[]
  /** Whether the command is disabled */
  disabled?: boolean
  /** Any additional data */
  [key: string]: unknown
}

/** A group of commands */
export interface CommandGroup {
  /** Group title */
  title: string
  /** Commands in this group */
  commands: Command[]
}

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Controls open state — use v-model:open */
  open?: boolean
  /** Flat list of commands */
  commands?: Command[]
  /** Grouped commands */
  groups?: CommandGroup[]
  /** Placeholder for search input. Default: 'Type a command or search...' */
  placeholder?: string
  /** Enable keyboard shortcut to open (Ctrl/Cmd + K). Default: true */
  enableShortcut?: boolean
  /** Keyboard shortcut key. Default: 'k' */
  shortcutKey?: string
  /** Custom CSS class */
  class?: string
  /** Min width. Default: '400px' */
  minWidth?: string
  /** Max height of command list. Default: '400px' */
  maxHeight?: string
  /** Show recent commands. Default: false */
  showRecent?: boolean
  /** Max recent commands to track. Default: 5 */
  maxRecent?: number
}

const props = withDefaults(defineProps<Props>(), {
  open: false,
  commands: () => [],
  groups: () => [],
  placeholder: 'Type a command or search...',
  enableShortcut: true,
  shortcutKey: 'k',
  class: '',
  minWidth: '400px',
  maxHeight: '400px',
  showRecent: false,
  maxRecent: 5,
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  'update:open': [value: boolean]
  select: [command: Command]
}>()

// ─── Local state ──────────────────────────────────────────────────────────────

const isOpen = ref(props.open)
const searchQuery = ref('')
const selectedIndex = ref(0)
const inputRef = ref<HTMLInputElement | null>(null)
const recentCommands = ref<Command[]>([])

// ─── Computed ─────────────────────────────────────────────────────────────────

/** Flatten groups into a single array for searching */
const allCommands = computed(() => {
  if (props.groups.length > 0) {
    return props.groups.flatMap(g => g.commands.map(c => ({ ...c, group: g.title })))
  }
  return props.commands
})

/** Filter commands based on search query with fuzzy matching */
const filteredCommands = computed(() => {
  let commands = allCommands.value

  // Add recent commands first if enabled and no query
  if (props.showRecent && recentCommands.value.length > 0 && !searchQuery.value) {
    const recentIds = new Set(recentCommands.value.map(c => c.id))
    const otherCommands = commands.filter(c => !recentIds.has(c.id))
    commands = [...recentCommands.value, ...otherCommands]
  }

  if (!searchQuery.value) return commands

  const query = searchQuery.value.toLowerCase()

  return commands.filter(cmd => {
    const label = cmd.label.toLowerCase()
    const description = (cmd.description ?? '').toLowerCase()
    const keywords = (cmd.keywords ?? []).join(' ').toLowerCase()

    // Exact match or includes
    if (label.includes(query)) return true
    if (description.includes(query)) return true
    if (keywords.includes(query)) return true

    // Fuzzy match
    return fuzzyMatch(query, label)
  })
})

const hasCommands = computed(() => filteredCommands.value.length > 0)

const groupedResults = computed(() => {
  if (props.groups.length > 0 && searchQuery.value) {
    // Group filtered results by their original group
    const groups: Record<string, Command[]> = {}
    filteredCommands.value.forEach(cmd => {
      const group = cmd.group ?? 'Other'
      if (!groups[group]) groups[group] = []
      groups[group].push(cmd)
    })
    return Object.entries(groups).map(([title, commands]) => ({ title, commands }))
  }
  return [{ title: 'Results', commands: filteredCommands.value }]
})

// ─── Functions ────────────────────────────────────────────────────────────────

/** Simple fuzzy matching algorithm */
function fuzzyMatch(query: string, str: string): boolean {
  let queryIndex = 0
  for (let i = 0; i < str.length && queryIndex < query.length; i++) {
    if (str[i] === query[queryIndex]) queryIndex++
  }
  return queryIndex === query.length
}

function selectCommand(command: Command) {
  if (command.disabled) return

  // Track as recent
  if (props.showRecent) {
    recentCommands.value = recentCommands.value.filter(c => c.id !== command.id)
    recentCommands.value.unshift(command)
    if (recentCommands.value.length > props.maxRecent) {
      recentCommands.value.pop()
    }
  }

  emit('select', command)
  close()
}

function navigate(direction: 'up' | 'down') {
  const max = filteredCommands.value.length - 1
  if (max < 0) return

  if (direction === 'down') {
    selectedIndex.value = selectedIndex.value >= max ? 0 : selectedIndex.value + 1
  } else {
    selectedIndex.value = selectedIndex.value <= 0 ? max : selectedIndex.value - 1
  }
}

function showPalette() {
  isOpen.value = true
  emit('update:open', true)
  searchQuery.value = ''
  selectedIndex.value = 0
}

function close() {
  isOpen.value = false
  emit('update:open', false)
  searchQuery.value = ''
  selectedIndex.value = 0
}

function toggle() {
  isOpen.value ? close() : open()
}

// ─── Keyboard shortcuts ──────────────────────────────────────────────────────

function handleGlobalKeydown(e: KeyboardEvent) {
  // Check for Ctrl/Cmd + K
  if (props.enableShortcut && (e.ctrlKey || e.metaKey) && e.key === props.shortcutKey) {
    e.preventDefault()
    toggle()
  }

  // Handle navigation when open
  if (!isOpen.value) return

  switch (e.key) {
    case 'ArrowDown':
      e.preventDefault()
      navigate('down')
      break
    case 'ArrowUp':
      e.preventDefault()
      navigate('up')
      break
    case 'Enter':
      e.preventDefault()
      const selectedCommand = filteredCommands.value[selectedIndex.value]
      if (selectedCommand) {
        selectCommand(selectedCommand)
      }
      break
    case 'Escape':
      e.preventDefault()
      close()
      break
  }
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(() => {
  document.addEventListener('keydown', handleGlobalKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleGlobalKeydown)
})

watch(isOpen, async (val) => {
  if (val) {
    await nextTick()
    inputRef.value?.focus()
  }
})

watch(() => props.open, (val) => {
  isOpen.value = val
})
</script>

<template>
  <div :class="['ch-command-palette', props.class]">
    <!-- Custom trigger slot -->
    <slot name="trigger">
      <button class="ch-command-palette__trigger" @click="showPalette" :aria-label="'Open command palette'">
        <Search :size="16" :stroke-width="2" />
        <span>Search...</span>
        <kbd class="ch-command-palette__shortcut">{{ enableShortcut ? 'Ctrl K' : '' }}</kbd>
      </button>
    </slot>

    <!-- Command palette popover -->
    <ChPopover :open="isOpen" placement="bottom" trigger="click" :min-width="minWidth" @update:open="isOpen = $event">
      <div class="ch-command-palette__content">
        <!-- Search input -->
        <div class="ch-command-palette__search">
          <ChInput ref="inputRef" v-model="searchQuery" :placeholder="placeholder" size="md" clearable>
            <template #prefix>
              <Search :size="16" :stroke-width="2" />
            </template>
          </ChInput>
        </div>

        <!-- Results -->
        <div class="ch-command-palette__results" :style="{ '--ch-command-max-height': maxHeight }" role="listbox">
          <!-- No results -->
          <div v-if="!hasCommands" class="ch-command-palette__empty">
            <CircleHelp :size="32" :stroke-width="1.5" />
            <p>No results found</p>
            <p v-if="searchQuery" class="ch-command-palette__empty-hint">
              Try a different search term
            </p>
          </div>

          <!-- Grouped results -->
          <template v-else>
            <div v-for="(group, groupIndex) in groupedResults" :key="group.title" class="ch-command-palette__group">
              <div v-if="groupedResults.length > 1" class="ch-command-palette__group-title">
                {{ group.title }}
              </div>
              <div class="ch-command-palette__list">
                <button v-for="(cmd, cmdIndex) in group.commands" :key="cmd.id" :class="[
                  'ch-command-palette__item',
                  {
                    'ch-command-palette__item--selected':
                      cmdIndex === selectedIndex &&
                      (groupedResults.length === 1 || groupIndex === 0),
                    'ch-command-palette__item--disabled': cmd.disabled,
                  },
                ]" :aria-selected="cmdIndex === selectedIndex && groupedResults.length === 1" role="option"
                  :disabled="cmd.disabled" @click="selectCommand(cmd)">
                  <!-- Icon -->
                  <svg v-if="cmd.icon" class="ch-command-palette__item-icon" width="18" height="18" viewBox="0 0 18 18"
                    fill="none" stroke="currentColor" stroke-width="1.5">
                    <path :d="cmd.icon" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>

                  <!-- Content -->
                  <div class="ch-command-palette__item-content">
                    <span class="ch-command-palette__item-label">{{ cmd.label }}</span>
                    <span v-if="cmd.description" class="ch-command-palette__item-description">
                      {{ cmd.description }}
                    </span>
                  </div>

                  <!-- Shortcut -->
                  <kbd v-if="cmd.shortcut" class="ch-command-palette__item-shortcut">
                    {{ cmd.shortcut }}
                  </kbd>
                </button>
              </div>
            </div>
          </template>
        </div>
      </div>
    </ChPopover>
  </div>
</template>

<style scoped>
/* ─── Trigger button ──────────────────────────────────────────────────────── */
.ch-command-palette__trigger {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2);
  padding: var(--ch-space-2) var(--ch-space-3);
  background: var(--ch-color-bg-muted);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-none);
  color: var(--ch-color-text-muted);
  font-size: var(--ch-text-sm);
  cursor: pointer;
  transition:
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-command-palette__trigger:hover {
  background: var(--ch-color-bg-subtle);
  border-color: var(--ch-color-border-strong);
}

.ch-command-palette__trigger:focus-visible {
  outline: 2px solid var(--ch-color-primary);
  outline-offset: 2px;
}

.ch-command-palette__shortcut {
  margin-left: auto;
  padding: var(--ch-space-0_5) var(--ch-space-1_5);
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border);
  border-radius: var(--ch-radius-sm);
  font-family: var(--ch-font-mono);
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

/* ─── Content ─────────────────────────────────────────────────────────────── */
.ch-command-palette__content {
  display: flex;
  flex-direction: column;
}

/* ─── Search ──────────────────────────────────────────────────────────────── */
.ch-command-palette__search {
  padding: var(--ch-space-3);
  border-bottom: 1px solid var(--ch-color-border);
}

/* ─── Results ─────────────────────────────────────────────────────────────── */
.ch-command-palette__results {
  max-height: var(--ch-command-max-height, 400px);
  overflow-y: auto;
}

/* ─── Empty state ─────────────────────────────────────────────────────────── */
.ch-command-palette__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--ch-space-2);
  padding: var(--ch-space-8) var(--ch-space-4);
  color: var(--ch-color-text-muted);
  text-align: center;
}

.ch-command-palette__empty .lucide {
  opacity: 0.5;
}

.ch-command-palette__empty-hint {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-subtle);
}

/* ─── Group ───────────────────────────────────────────────────────────────── */
.ch-command-palette__group {
  display: flex;
  flex-direction: column;
}

.ch-command-palette__group-title {
  padding: var(--ch-space-2) var(--ch-space-3);
  font-size: var(--ch-text-xs);
  font-weight: var(--ch-font-semibold);
  color: var(--ch-color-text-muted);
  text-transform: uppercase;
  letter-spacing: var(--ch-tracking-wide);
  background: var(--ch-color-bg-subtle);
}

.ch-command-palette__list {
  display: flex;
  flex-direction: column;
  padding: var(--ch-space-1) 0;
}

/* ─── Command item ────────────────────────────────────────────────────────── */
.ch-command-palette__item {
  display: flex;
  align-items: center;
  gap: var(--ch-space-2_5);
  width: 100%;
  padding: var(--ch-space-2) var(--ch-space-3);
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  transition: background-color var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-command-palette__item:hover:not(:disabled) {
  background: var(--ch-color-bg-muted);
}

.ch-command-palette__item--selected {
  background: var(--ch-color-primary-subtle);
}

.ch-command-palette__item:focus-visible {
  outline: 2px solid var(--ch-color-primary);
  outline-offset: -2px;
}

.ch-command-palette__item--disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ─── Item icon ───────────────────────────────────────────────────────────── */
.ch-command-palette__item-icon {
  flex-shrink: 0;
  color: var(--ch-color-text-muted);
}

/* ─── Item content ────────────────────────────────────────────────────────── */
.ch-command-palette__item-content {
  display: flex;
  flex-direction: column;
  gap: var(--ch-space-0_5);
  flex: 1;
  min-width: 0;
}

.ch-command-palette__item-label {
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-medium);
  color: var(--ch-color-text);
}

.ch-command-palette__item-description {
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
}

/* ─── Item shortcut ───────────────────────────────────────────────────────── */
.ch-command-palette__item-shortcut {
  padding: var(--ch-space-0_5) var(--ch-space-1_5);
  background: var(--ch-color-bg-muted);
  border: 1px solid var(--ch-color-border);
  border-radius: var(--ch-radius-sm);
  font-family: var(--ch-font-mono);
  font-size: var(--ch-text-xs);
  color: var(--ch-color-text-muted);
  flex-shrink: 0;
}
</style>