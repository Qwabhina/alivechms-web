/**
 * @file useModal.ts
 * @path /frontend/src/design-system/composables/useModal.ts
 * @description Composable for managing a modal's open/close state and
 * passing data in and out of it — without prop-drilling.
 *
 * ─── Two usage patterns ───────────────────────────────────────────────────────
 *
 * Pattern 1 — Local (one modal per component, state lives in the component):
 *   const modal = useModal<Member>()
 *   modal.open({ id: 5, name: 'Kwame' })   // pass data to the modal
 *   const result = await modal.waitForClose() // wait for the user to finish
 *
 * Pattern 2 — Shared singleton (same modal controlled from multiple places):
 *   Create the modal instance at module level in a separate file:
 *   modals/editMemberModal.ts
 *   export const editMemberModal = useModal<Member>()
 *
 *   Open from a table action button:
 *   import { editMemberModal } from '@/modals/editMemberModal'
 *   editMemberModal.open(row)
 *
 *   Mount once in the parent page:
 *   <ChModal v-model:open="editMemberModal.isOpen.value" :title="'Edit Member'">
 *     <MemberForm :member="editMemberModal.data.value" />
 *   </ChModal>
 *
 * ─── Generic type parameter ───────────────────────────────────────────────────
 * `useModal<T>()` types the data payload. If your modal doesn't need data,
 * call `useModal()` with no type argument.
 *
 * @example Edit dialog
 * const modal = useModal<{ id: number; name: string }>()
 *
 * Open with data
 * modal.open({ id: row.id, name: row.name })
 *
 * In template
 * <ChModal v-model:open="modal.isOpen.value" title="Edit Member">
 *   <MemberForm :initial="modal.data.value" @saved="modal.close" />
 * </ChModal>
 */

import { ref, readonly } from 'vue'

export function useModal<TData = unknown>() {
  /** Whether the modal is currently open */
  const isOpen = ref(false)

  /**
   * The data payload passed when the modal was opened.
   * Typed to TData — null when the modal is closed or opened without data.
   */
  const data = ref<TData | null>(null) as ReturnType<typeof ref<TData | null>>

  /**
   * Internal resolver for waitForClose() — holds the Promise's resolve fn
   * so we can resolve it from close().
   */
  let _resolve: ((result: TData | null) => void) | null = null

  /**
   * Opens the modal, optionally with a data payload.
   * @param payload - Data to make available inside the modal via `modal.data`
   */
  function open(payload?: TData) {
    data.value  = payload ?? null
    isOpen.value = true
  }

  /**
   * Closes the modal and clears the data payload.
   * If `waitForClose()` is pending, resolves it with the optional result.
   * @param result - Optional return value (e.g. the saved record)
   */
  function close(result?: TData) {
    isOpen.value = false
    if (_resolve) {
      _resolve(result ?? null)
      _resolve = null
    }
    // Clear data after a short delay so exit animations don't see null
    setTimeout(() => { data.value = null }, 300)
  }

  /**
   * Returns a Promise that resolves when the modal is closed.
   * Useful for imperative "open and await result" patterns.
   *
   * @example
   * const confirmed = await confirmModal.waitForClose()
   * if (confirmed) deleteMember()
   */
  function waitForClose(): Promise<TData | null> {
    return new Promise(resolve => { _resolve = resolve })
  }

  return {
    /** Reactive boolean — bind to ChModal's v-model:open */
    isOpen,
    /** The data payload passed to open() */
    data: readonly(data),
    open,
    close,
    waitForClose,
  }
}
