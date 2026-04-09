<script setup lang="ts">
/**
 * @component ChCommandPalette
 * @path /frontend/src/design-system/components/navigation/ChCommandPalette.vue
 * @description A keyboard-driven command palette for quick navigation and
 * actions. Supports fuzzy search, grouped commands, and keyboard navigation.
 *
 * ─── Design decisions ────────────────────────────────────────────────────────
 * - Rendered via <Teleport> as a centered viewport modal, not a popover.
 *   Command palettes are not anchored to a trigger — they float in the center.
 * - selectedIndex tracks a flat position into filteredCommands so selection
 *   is always unambiguous regardless of how many groups exist.
 * - groupedResults uses a Map to preserve the original group order from props.
 * - The search container div is used to focus the inner input via querySelector
 *   so ChInput doesn't need to expose a focus() method.
 *
 * ─── Accessibility ───────────────────────────────────────────────────────────
 * - role="dialog" + aria-modal on the panel
 * - role="listbox" + role="option" + aria-selected on items
 * - Escape closes; Arrow keys navigate; Enter selects
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example Basic
 * <ChCommandPalette
 *   :commands="[
 *     { id: 'home', label: 'Dashboard', action: () => router.push('/') },
 *     { id: 'members', label: 'Members', action: () => router.push('/members') },
 *   ]"
 * />
 *
 * @example With groups
 * <ChCommandPalette
 *   :groups="[
 *     { title: 'Navigation', commands: [{ id: 'home', label: 'Dashboard' }] },
 *     { title: 'Actions',    commands: [{ id: 'new',  label: 'Add Member' }] },
 *   ]"
 *   @select="handleCommand"
 * />
 *
 * @example Custom trigger with v-model
 * <ChCommandPalette v-model:open="isOpen" :commands="commands">
 *   <template #trigger>
 *     <ChButton>Search...</ChButton>
 *   </template>
 * </ChCommandPalette>
 */

import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { Search, CircleHelp } from 'lucide-vue-next'
import ChInput from '../core/ChInput.vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/** A single command in the palette */
export interface Command {
  /** Unique identifier */
  id: string
  /** Display label */
  label: string
  /** Optional description shown below the label */
  description?: string
  /** Optional icon SVG path */
  icon?: string
  /** Optional keyboard shortcut hint */
  shortcut?: string
  /** Group/category — populated automatically when using the groups prop */
  group?: string
  /** Search aliases */
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
  /** Flat list of commands (use either this or groups, not both) */
  commands?: Command[]
  /** Grouped commands — group order is preserved */
  groups?: CommandGroup[]
  /** Placeholder for the search input */
  placeholder?: string
  /** Enable the global Ctrl/Cmd + K shortcut. Default: true */
  enableShortcut?: boolean
  /** Which key to combine with Ctrl/Cmd. Default: 'k' */
  shortcutKey?: string
  /** CSS class applied to the trigger wrapper */
  paletteClass?: string
  /** Min width of the command dialog. Default: '400px' */
  minWidth?: string
  /** Max height of the command list before scrolling. Default: '400px' */
  maxHeight?: string
  /** Show recently used commands when no query is active. Default: false */
  showRecent?: boolean
  /** Max number of recent commands to track. Default: 5 */
  maxRecent?: number
}

const props = withDefaults(defineProps<Props>(), {
  open: false,
  commands: () => [],
  groups: () => [],
  placeholder: 'Type a command or search...',
  enableShortcut: true,
  shortcutKey: 'k',
  paletteClass: '',
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

// ─── Refs ─────────────────────────────────────────────────────────────────────

/** Container div wrapping ChInput — used to focus the inner <input> via querySelector */
const searchContainerRef = ref<HTMLElement | null>(null)
/** Scrollable results list — used to scroll selected item into view */
const commandListRef = ref<HTMLElement | null>(null)

// ─── Local state ──────────────────────────────────────────────────────────────

const isOpen = ref(props.open)
const searchQuery = ref('')
const selectedIndex = ref(0)
const recentCommands = ref<Command[]>([])

// ─── Computed ─────────────────────────────────────────────────────────────────

/** All commands as a flat list, with group name stamped onto each item */
const allCommands = computed<Command[]>(() => {
  if (props.groups.length > 0) {
    return props.groups.flatMap((g) => g.commands.map((c) => ({ ...c, group: g.title })))
  }
  return props.commands
})

/** Commands filtered by the current search query */
const filteredCommands = computed<Command[]>(() => {
  let commands = allCommands.value

  if (props.showRecent && recentCommands.value.length > 0 && !searchQuery.value) {
    const recentIds = new Set(recentCommands.value.map((c) => c.id))
    commands = [...recentCommands.value, ...commands.filter((c) => !recentIds.has(c.id))]
  }

  if (!searchQuery.value) return commands

  const query = searchQuery.value.toLowerCase()
  return commands.filter((cmd) => {
    if (cmd.label.toLowerCase().includes(query)) return true
    if ((cmd.description ?? '').toLowerCase().includes(query)) return true
    if ((cmd.keywords ?? []).join(' ').toLowerCase().includes(query)) return true
    return fuzzyMatch(query, cmd.label.toLowerCase())
  })
})

const hasCommands = computed(() => filteredCommands.value.length > 0)

/**
 * Map from command id → flat index in filteredCommands.
 * This is the single source of truth for which item is selected —
 * avoids the per-group cmdIndex mismatch that would cause multiple items
 * across different groups to appear selected simultaneously.
 */
const flatIndexMap = computed<Map<string, number>>(() => {
  const map = new Map<string, number>()
  filteredCommands.value.forEach((cmd, i) => map.set(cmd.id, i))
  return map
})

/**
 * Grouped results for display.
 * Uses a Map to preserve the original group order from props.groups.
 * Empty groups (all commands filtered out) are omitted.
 */
const groupedResults = computed<CommandGroup[]>(() => {
  if (props.groups.length === 0) {
    return [{ title: '', commands: filteredCommands.value }]
  }

  // Pre-seed the Map with original group order so insertion order is preserved
  const grouped = new Map<string, Command[]>(props.groups.map((g) => [g.title, []]))

  filteredCommands.value.forEach((cmd) => {
    const key = cmd.group ?? 'Other'
    if (!grouped.has(key)) grouped.set(key, [])
    grouped.get(key)!.push(cmd)
  })

  return Array.from(grouped.entries())
    .filter(([, cmds]) => cmds.length > 0)
    .map(([title, commands]) => ({ title, commands }))
})

// ─── Functions ────────────────────────────────────────────────────────────────

/** Simple fuzzy match — every character in query must appear in order in str */
function fuzzyMatch(query: string, str: string): boolean {
  let qi = 0
  for (let i = 0; i < str.length && qi < query.length; i++) {
    if (str[i] === query[qi]) qi++
  }
  return qi === query.length
}

function selectCommand(command: Command) {
  if (command.disabled) return

  if (props.showRecent) {
    recentCommands.value = [
      command,
      ...recentCommands.value.filter((c) => c.id !== command.id),
    ].slice(0, props.maxRecent)
  }

  emit('select', command)
  close()
}

async function navigate(direction: 'up' | 'down') {
  const max = filteredCommands.value.length - 1
  if (max < 0) return

  selectedIndex.value =
    direction === 'down'
      ? selectedIndex.value >= max
        ? 0
        : selectedIndex.value + 1
      : selectedIndex.value <= 0
        ? max
        : selectedIndex.value - 1

  // Scroll selected item into view after Vue updates the DOM
  await nextTick()
  commandListRef.value
    ?.querySelector('[aria-selected="true"]')
    ?.scrollIntoView({ block: 'nearest' })
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
  isOpen.value ? close() : showPalette()
}

// ─── Watch ────────────────────────────────────────────────────────────────────

/** Reset selection whenever the filtered list changes due to typing */
watch(searchQuery, () => {
  selectedIndex.value = 0
})

/** Focus the search input whenever the palette opens */
watch(isOpen, async (val) => {
  if (val) {
    await nextTick()
    searchContainerRef.value?.querySelector('input')?.focus()
  }
})

/** Sync external v-model:open */
watch(
  () => props.open,
  (val) => {
    isOpen.value = val
  },
)

// ─── Keyboard shortcuts ───────────────────────────────────────────────────────

function handleGlobalKeydown(e: KeyboardEvent) {
  if (props.enableShortcut && (e.ctrlKey || e.metaKey) && e.key === props.shortcutKey) {
    e.preventDefault()
    toggle()
    return
  }

  if (!isOpen.value) return

  switch (e.key) {
    case 'ArrowDown': {
      e.preventDefault()
      navigate('down')
      break
    }
    case 'ArrowUp': {
      e.preventDefault()
      navigate('up')
      break
    }
    case 'Enter': {
      e.preventDefault()
      const selected = filteredCommands.value[selectedIndex.value]
      if (selected) selectCommand(selected)
      break
    }
    case 'Escape': {
      e.preventDefault()
      close()
      break
    }
  }
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(() => document.addEventListener('keydown', handleGlobalKeydown))
onUnmounted(() => document.removeEventListener('keydown', handleGlobalKeydown))
</script>

<template>
  <div :class="['ch-command-palette', paletteClass]">
    <!-- Trigger — slot or default search button -->
    <slot name="trigger">
      <button
        class="ch-command-palette__trigger"
        aria-label="Open command palette"
        @click="showPalette"
      >
        <Search :size="16" :stroke-width="2" />
        <span>Search...</span>
        <kbd v-if="enableShortcut" class="ch-command-palette__shortcut">⌘ K</kbd>
      </button>
    </slot>

    <!--
      The palette is a centered viewport modal, not a popover.
      <Teleport> ensures it renders above all stacking contexts.
      Clicking the overlay (not the dialog) closes the palette.
    -->
    <Teleport to="body">
      <Transition name="ch-command-palette">
        <div
          v-if="isOpen"
          class="ch-command-palette__overlay"
          aria-hidden="false"
          @click.self="close"
        >
          <div
            class="ch-command-palette__dialog"
            :style="{ '--ch-command-min-width': minWidth }"
            role="dialog"
            aria-modal="true"
            aria-label="Command palette"
          >
            <!-- Search input -->
            <div ref="searchContainerRef" class="ch-command-palette__search">
              <ChInput v-model="searchQuery" :placeholder="placeholder" size="md" clearable>
                <template #prefix>
                  <Search :size="16" :stroke-width="2" />
                </template>
              </ChInput>
            </div>

            <!-- Results -->
            <div
              ref="commandListRef"
              class="ch-command-palette__results"
              :style="{ '--ch-command-max-height': maxHeight }"
              role="listbox"
              aria-label="Commands"
            >
              <!-- Empty state -->
              <div v-if="!hasCommands" class="ch-command-palette__empty">
                <CircleHelp :size="32" :stroke-width="1.5" />
                <p>No results found</p>
                <p v-if="searchQuery" class="ch-command-palette__empty-hint">
                  Try a different search term
                </p>
              </div>

              <!-- Grouped results -->
              <template v-else>
                <div
                  v-for="group in groupedResults"
                  :key="group.title"
                  class="ch-command-palette__group"
                >
                  <!-- Group heading — hidden when there is only one group -->
                  <div
                    v-if="groupedResults.length > 1 && group.title"
                    class="ch-command-palette__group-title"
                  >
                    {{ group.title }}
                  </div>

                  <div class="ch-command-palette__list">
                    <button
                      v-for="cmd in group.commands"
                      :key="cmd.id"
                      :class="[
                        'ch-command-palette__item',
                        {
                          'ch-command-palette__item--selected':
                            flatIndexMap.get(cmd.id) === selectedIndex,
                          'ch-command-palette__item--disabled': cmd.disabled,
                        },
                      ]"
                      role="option"
                      :aria-selected="flatIndexMap.get(cmd.id) === selectedIndex"
                      :disabled="cmd.disabled || undefined"
                      @click="selectCommand(cmd)"
                    >
                      <!-- Icon -->
                      <svg
                        v-if="cmd.icon"
                        class="ch-command-palette__item-icon"
                        width="18"
                        height="18"
                        viewBox="0 0 18 18"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        aria-hidden="true"
                      >
                        <path :d="cmd.icon" stroke-linecap="round" stroke-linejoin="round" />
                      </svg>

                      <!-- Content -->
                      <div class="ch-command-palette__item-content">
                        <span class="ch-command-palette__item-label">{{ cmd.label }}</span>
                        <span v-if="cmd.description" class="ch-command-palette__item-description">
                          {{ cmd.description }}
                        </span>
                      </div>

                      <!-- Shortcut hint -->
                      <kbd v-if="cmd.shortcut" class="ch-command-palette__item-shortcut">
                        {{ cmd.shortcut }}
                      </kbd>
                    </button>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
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
  border-radius: var(--ch-radius-md);
  color: var(--ch-color-text-muted);
  font-size: var(--ch-text-sm);
  cursor: pointer;
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    border-color var(--ch-duration-fast) var(--ch-ease-out);
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

/* ─── Overlay ─────────────────────────────────────────────────────────────── */
.ch-command-palette__overlay {
  position: fixed;
  inset: 0;
  z-index: var(--ch-z-modal, 1000);
  background: var(--ch-color-overlay);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  /* Push dialog down slightly from the top for a classic command palette feel */
  padding: 15vh var(--ch-space-4) var(--ch-space-4);
}

/* ─── Dialog ──────────────────────────────────────────────────────────────── */
.ch-command-palette__dialog {
  width: 100%;
  min-width: var(--ch-command-min-width, 400px);
  max-width: 640px;
  display: flex;
  flex-direction: column;
  background: var(--ch-color-surface);
  border: 1px solid var(--ch-color-border-strong);
  border-radius: var(--ch-radius-lg);
  box-shadow: var(--ch-shadow-xl);
  overflow: hidden;
}

/* ─── Transition ──────────────────────────────────────────────────────────── */
.ch-command-palette-enter-active,
.ch-command-palette-leave-active {
  transition: opacity var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-command-palette-enter-active .ch-command-palette__dialog,
.ch-command-palette-leave-active .ch-command-palette__dialog {
  transition: transform var(--ch-duration-fast) var(--ch-ease-spring);
}

.ch-command-palette-enter-from,
.ch-command-palette-leave-to {
  opacity: 0;
}

.ch-command-palette-enter-from .ch-command-palette__dialog,
.ch-command-palette-leave-to .ch-command-palette__dialog {
  transform: translateY(-8px) scale(0.98);
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

.ch-command-palette__empty p {
  margin: 0;
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
