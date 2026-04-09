/**
 * @file useLocalStorage.ts
 * @path /frontend/src/design-system/composables/useLocalStorage.ts
 * @description Composable for reactive localStorage management with automatic
 * serialization, cross-tab synchronization, and SSR safety.
 *
 * ─── Architecture ────────────────────────────────────────────────────────────
 * - Reactive state synced with localStorage
 * - Automatic JSON serialization/deserialization
 * - Cross-tab synchronization via storage events
 * - SSR-safe (checks for window availability)
 * - Supports any JSON-serializable type
 * - Optional TTL (time-to-live) for expiring data
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example Basic usage
 * const [theme, setTheme] = useLocalStorage('theme', 'light')
 *
 * // In template: {{ theme }}
 * // Calls: setTheme('dark')
 *
 * @example With complex object
 * interface UserSettings {
 *   notifications: boolean
 *   language: string
 *   theme: 'light' | 'dark'
 * }
 *
 * const [settings, setSettings] = useLocalStorage<UserSettings>(
 *   'user-settings',
 *   { notifications: true, language: 'en', theme: 'light' }
 * )
 *
 * @example Remove item
 * const [value, setValue, removeValue] = useLocalStorage('key', 'default')
 * removeValue() // Deletes from localStorage
 *
 * @example Check if key exists
 * const exists = useLocalStorage.exists('my-key')
 *
 * @example Clear all storage
 * useLocalStorage.clear()
 *
 * @example Get storage info
 * const info = useLocalStorage.getInfo()
 * console.log(info.usage) // bytes used
 * console.log(info.quota) // estimated quota
 */

import { ref, watch, type Ref, shallowRef } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/** Options for useLocalStorage */
export interface UseLocalStorageOptions<T> {
  /** Serializer function. Default: JSON.stringify */
  serialize?: (value: T) => string
  /** Deserializer function. Default: JSON.parse */
  deserialize?: (value: string) => T
  /** Listen to cross-tab storage events. Default: true */
  listenToStorageEvents?: boolean
  /** Time-to-live in milliseconds. If set, value expires after this duration */
  ttl?: number
}

/** Storage info returned by getInfo() */
export interface StorageInfo {
  /** Current usage in bytes (approximate) */
  usage: number
  /** Estimated quota in bytes (approximate) */
  quota: number
  /** Number of items stored */
  itemCount: number
  /** Percentage of quota used */
  usagePercent: number
}

/** Stored value with metadata for TTL support */
interface StoredValue<T> {
  /** The actual value */
  value: T
  /** Timestamp when the value was stored */
  timestamp: number
  /** Optional TTL in milliseconds */
  ttl?: number
}

// ─── Storage helpers ─────────────────────────────────────────────────────────

/** Check if we're in a browser environment */
function isClient(): boolean {
  return typeof window !== 'undefined' && typeof localStorage !== 'undefined'
}

/**
 * Safely get item from localStorage
 */
function getItem(key: string): string | null {
  if (!isClient()) return null
  try {
    return localStorage.getItem(key)
  } catch (e) {
    console.error(`[useLocalStorage] Error reading key "${key}":`, e)
    return null
  }
}

/**
 * Safely set item in localStorage
 */
function setItem(key: string, value: string): void {
  if (!isClient()) return
  try {
    localStorage.setItem(key, value)
  } catch (e) {
    console.error(`[useLocalStorage] Error writing key "${key}":`, e)
    // Storage might be full
    if (e instanceof DOMException && e.name === 'QuotaExceededError') {
      console.warn('[useLocalStorage] Storage quota exceeded. Consider clearing old data.')
    }
  }
}

/**
 * Safely remove item from localStorage
 */
function removeItem(key: string): void {
  if (!isClient()) return
  try {
    localStorage.removeItem(key)
  } catch (e) {
    console.error(`[useLocalStorage] Error removing key "${key}":`, e)
  }
}

/**
 * Safely clear all localStorage
 */
function clearStorage(): void {
  if (!isClient()) return
  try {
    localStorage.clear()
  } catch (e) {
    console.error('[useLocalStorage] Error clearing storage:', e)
  }
}

/**
 * Get storage size estimate
 */
function getStorageSize(key?: string): number {
  if (!isClient()) return 0
  try {
    if (key) {
      const item = localStorage.getItem(key)
      return item ? key.length + item.length : 0
    }
    // Calculate total size
    let total = 0
    for (let i = 0; i < localStorage.length; i++) {
      const k = localStorage.key(i)
      if (k) {
        const v = localStorage.getItem(k)
        total += k.length + (v ? v.length : 0)
      }
    }
    return total
  } catch {
    return 0
  }
}

// ─── useLocalStorage composable ──────────────────────────────────────────────

/**
 * Creates a reactive reference synced with localStorage.
 *
 * @template T - The type of value to store
 * @param key - The localStorage key
 * @param initialValue - The default value if key doesn't exist
 * @param options - Configuration options
 * @returns A tuple of [value, setValue, removeValue]
 */
export function useLocalStorage<T>(
  key: string,
  initialValue: T,
  options: UseLocalStorageOptions<T> = {}
): [Ref<T>, (value: T) => void, () => void] {
  const {
    serialize = JSON.stringify,
    deserialize = (v: string) => JSON.parse(v) as T,
    listenToStorageEvents = true,
    ttl,
  } = options

  // Create reactive ref
  const state = shallowRef<T>(initialValue)

  // Initialize from localStorage
  function init() {
    if (!isClient()) return

    const stored = getItem(key)
    if (stored) {
      try {
        // Check if it's a TTL-wrapped value
        const parsed = JSON.parse(stored) as StoredValue<T> | T

        // Handle TTL-wrapped values
        if (parsed && typeof parsed === 'object' && 'value' in parsed && 'timestamp' in parsed) {
          const storedValue = parsed as StoredValue<T>

          // Check if expired
          if (storedValue.ttl) {
            const age = Date.now() - storedValue.timestamp
            if (age > storedValue.ttl) {
              // Expired, remove and use default
              removeItem(key)
              state.value = initialValue
              return
            }
          }

          state.value = storedValue.value
        } else {
          // Regular value
          state.value = parsed
        }
      } catch (e) {
        console.error(`[useLocalStorage] Error parsing "${key}":`, e)
        state.value = initialValue
      }
    }
  }

  // Initialize on creation
  init()

  // Write to localStorage when value changes
  function setValue(value: T) {
    state.value = value

    if (!isClient()) return

    const storedValue: StoredValue<T> = {
      value,
      timestamp: Date.now(),
      ttl,
    }

    const serialized = serialize(value)
    const withMetadata = JSON.stringify(storedValue)

    // Store with metadata for TTL support
    setItem(key, withMetadata)

    // Also store plain serialized value for backward compatibility
    // This allows reading values from non-TTL usage
    if (!ttl) {
      setItem(key, serialized)
    }
  }

  // Remove from localStorage
  function removeValue() {
    removeItem(key)
    state.value = initialValue
  }

  // Listen to cross-tab storage events
  if (listenToStorageEvents && isClient()) {
    window.addEventListener('storage', (e: StorageEvent) => {
      if (e.key === key && e.newValue !== null) {
        try {
          const parsed = JSON.parse(e.newValue) as StoredValue<T> | T

          if (parsed && typeof parsed === 'object' && 'value' in parsed && 'timestamp' in parsed) {
            state.value = (parsed as StoredValue<T>).value
          } else {
            state.value = parsed
          }
        } catch {
          // Ignore parse errors from other tabs
        }
      } else if (e.key === key && e.newValue === null) {
        // Key was removed from another tab
        state.value = initialValue
      }
    })
  }

  // Watch for changes and update ref if localStorage changes externally
  if (isClient()) {
    watch(
      () => {
        // Force re-read on focus
        return document.visibilityState
      },
      () => {
        if (document.visibilityState === 'visible') {
          init()
        }
      }
    )
  }

  return [state, setValue, removeValue]
}

// ─── Static methods ──────────────────────────────────────────────────────────

/**
 * Check if a key exists in localStorage
 */
useLocalStorage.exists = (key: string): boolean => {
  if (!isClient()) return false
  const value = getItem(key)
  if (!value) return false

  // Check TTL if present
  try {
    const parsed = JSON.parse(value) as StoredValue<unknown>
    if (parsed && 'timestamp' in parsed && 'ttl' in parsed && parsed.ttl) {
      const age = Date.now() - parsed.timestamp
      return age <= parsed.ttl
    }
  } catch {
    // Not a TTL value, just check existence
  }

  return true
}

/**
 * Get a value from localStorage without creating a reactive reference
 */
useLocalStorage.get = <T>(key: string, defaultValue?: T): T | undefined => {
  const stored = getItem(key)
  if (!stored) return defaultValue

  try {
    const parsed = JSON.parse(stored) as StoredValue<T> | T

    if (parsed && typeof parsed === 'object' && 'value' in parsed && 'timestamp' in parsed) {
      const storedValue = parsed as StoredValue<T>
      if (storedValue.ttl) {
        const age = Date.now() - storedValue.timestamp
        if (age > storedValue.ttl) {
          removeItem(key)
          return defaultValue
        }
      }
      return storedValue.value
    }

    return parsed
  } catch {
    return defaultValue
  }
}

/**
 * Set a value in localStorage without creating a reactive reference
 */
useLocalStorage.set = <T>(key: string, value: T, ttl?: number): void => {
  const storedValue: StoredValue<T> = {
    value,
    timestamp: Date.now(),
    ttl,
  }
  setItem(key, JSON.stringify(storedValue))
  if (!ttl) {
    setItem(key, JSON.stringify(value))
  }
}

/**
 * Remove a value from localStorage
 */
useLocalStorage.remove = (key: string): void => {
  removeItem(key)
}

/**
 * Clear all localStorage data
 */
useLocalStorage.clear = (): void => {
  clearStorage()
}

/**
 * Get all keys in localStorage
 */
useLocalStorage.keys = (): string[] => {
  if (!isClient()) return []
  return Object.keys(localStorage)
}

/**
 * Get all values as an object
 */
useLocalStorage.getAll = (): Record<string, unknown> => {
  if (!isClient()) return {}
  const result: Record<string, unknown> = {}
  for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i)
    if (key) {
      result[key] = useLocalStorage.get(key)
    }
  }
  return result
}

/**
 * Get storage usage information
 */
useLocalStorage.getInfo = (): StorageInfo => {
  const usage = getStorageSize()
  // Estimate quota (localStorage typically has 5-10MB)
  const quota = 5 * 1024 * 1024 // 5MB conservative estimate
  const itemCount = isClient() ? localStorage.length : 0

  return {
    usage,
    quota,
    itemCount,
    usagePercent: (usage / quota) * 100,
  }
}

/**
 * Clean up expired TTL values
 */
useLocalStorage.cleanup = (): void => {
  if (!isClient()) return

  const keys = useLocalStorage.keys()
  keys.forEach((key) => {
    const stored = getItem(key)
    if (!stored) return

    try {
      const parsed = JSON.parse(stored) as StoredValue<unknown>
      if (parsed && 'timestamp' in parsed && 'ttl' in parsed && parsed.ttl) {
        const age = Date.now() - parsed.timestamp
        if (age > parsed.ttl) {
          removeItem(key)
        }
      }
    } catch {
      // Ignore parse errors
    }
  })
}

// ─── useSessionStorage (bonus) ───────────────────────────────────────────────

/**
 * Same API as useLocalStorage but uses sessionStorage instead.
 * Data is cleared when the tab/window is closed.
 */
export function useSessionStorage<T>(
  key: string,
  initialValue: T,
  options: Omit<UseLocalStorageOptions<T>, 'ttl'> = {}
): [Ref<T>, (value: T) => void, () => void] {
  const {
    serialize = JSON.stringify,
    deserialize = (v: string) => JSON.parse(v) as T,
    listenToStorageEvents = true,
  } = options

  const state = shallowRef<T>(initialValue)

  function isClient(): boolean {
    return typeof window !== 'undefined' && typeof sessionStorage !== 'undefined'
  }

  function getItem(k: string): string | null {
    if (!isClient()) return null
    try {
      return sessionStorage.getItem(k)
    } catch {
      return null
    }
  }

  function setItem(k: string, v: string): void {
    if (!isClient()) return
    try {
      sessionStorage.setItem(k, v)
    } catch (e) {
      console.error(`[useSessionStorage] Error writing key "${k}":`, e)
    }
  }

  function removeItem(k: string): void {
    if (!isClient()) return
    try {
      sessionStorage.removeItem(k)
    } catch {
      // ignore
    }
  }

  // Initialize
  const stored = getItem(key)
  if (stored) {
    try {
      state.value = deserialize(stored)
    } catch {
      state.value = initialValue
    }
  }

  // Write on change
  function setValue(value: T) {
    state.value = value
    if (!isClient()) return
    setItem(key, serialize(value))
  }

  function removeValue() {
    removeItem(key)
    state.value = initialValue
  }

  // Listen to storage events
  if (listenToStorageEvents && isClient()) {
    window.addEventListener('storage', (e: StorageEvent) => {
      if (e.key === key && e.newValue !== null) {
        try {
          state.value = deserialize(e.newValue)
        } catch {
          // ignore
        }
      } else if (e.key === key && e.newValue === null) {
        state.value = initialValue
      }
    })
  }

  return [state, setValue, removeValue]
}
