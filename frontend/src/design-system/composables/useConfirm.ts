import { useModal } from './useModal'

export type ConfirmOptions = {
  title?: string
  message: string
  confirmLabel?: string
  cancelLabel?: string
}

// Shared singleton confirm modal instance
// Use a permissive data shape — the modal will carry the display options
// while close(true|false) will resolve the waitForClose promise.
export const confirmModal = useModal<any>()

/**
 * Open a confirmation dialog and await the user's decision.
 * Returns true when the user confirmed, false otherwise.
 */
export async function confirm(options: ConfirmOptions): Promise<boolean> {
  // Open the modal with the provided text payload
  confirmModal.open(options as any)
  const result = await confirmModal.waitForClose()
  // If the modal was closed without an explicit boolean, treat as false
  return result === true
}
