import { toast } from '@/components/ui/sonner'
import Swal from 'sweetalert2'

export const APP_CONFIG = {
  TOAST_DURATION: 3000,
  SWAL_CONFIG: {
    confirmButtonColor: '#00028a', // Updated to match our brand primary
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Confirm',
    cancelButtonText: 'Cancel',
    showCancelButton: true,
    reverseButtons: true,
  },
}

export const Alerts = {
  /**
   * Show toast notification using Sonner (Standardized through UI layer)
   */
  toast(message: string, optionsOrType: any = 'info') {
    if (typeof optionsOrType === 'string') {
      const type = optionsOrType as 'success' | 'error' | 'warning' | 'info'
      const options = {
        duration: type === 'error' ? APP_CONFIG.TOAST_DURATION + 2000 : APP_CONFIG.TOAST_DURATION,
      }

      switch (type) {
        case 'success': toast.success(message, options); break
        case 'error': toast.error(message, options); break
        case 'warning': toast.warning(message, options); break
        default: toast.info(message, options)
      }
    } else {
      // Direct pass-through for rich Sonner options
      toast(message, {
        duration: APP_CONFIG.TOAST_DURATION,
        ...optionsOrType
      })
    }
  },

  /**
   * Promise toast for async operations
   */
  promise(promise: Promise<any>, options: { loading: string; success: any; error: any }) {
    return toast.promise(promise, options)
  },

  success(message: string) {
    this.toast(message, 'success')
  },

  error(message: string) {
    this.toast(message, 'error')
  },

  warning(message: string) {
    this.toast(message, 'warning')
  },

  info(message: string) {
    this.toast(message, 'info')
  },

  /**
   * Show SweetAlert confirmation modal
   */
  async confirm(options: any = {}) {
    const result = await Swal.fire({
      ...APP_CONFIG.SWAL_CONFIG,
      title: 'Are you sure?',
      text: 'This action cannot be undone.',
      icon: 'warning',
      reverseButtons: true,
      ...options,
    })
    return result.isConfirmed
  },

  async confirmDelete(itemName = 'this item') {
    return await this.confirm({
      title: 'Delete Confirmation',
      text: `<p>Are you sure you want to delete <strong>${itemName}</strong>?</p><p class="text-xs text-slate-500 mt-2">This action cannot be undone and may affect related records.</p>`,
      icon: 'warning',
      confirmButtonText: 'Yes, delete it',
      confirmButtonColor: '#dc3545', // Match legacy Bootstrap danger
    })
  },

  /**
   * Show input dialog (Missing in previous version)
   */
  async input(options: any = {}) {
    const result = await Swal.fire({
      ...APP_CONFIG.SWAL_CONFIG,
      title: 'Enter value',
      input: 'text',
      inputPlaceholder: 'Type here...',
      showCancelButton: true,
      confirmButtonText: 'Submit',
      reverseButtons: true,
      inputValidator: (value) => {
        if (!value) {
          return 'This field is required'
        }
      },
      ...options,
    })
    return result.isConfirmed ? result.value : null
  },

  /**
   * Loading state modal
   */
  loading(title = 'Processing...', text = 'Please wait') {
    Swal.fire({
      title,
      text,
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading()
      },
    })
  },

  closeLoading() {
    Swal.close()
  },

  /**
   * Success modal
   */
  async successModal(title: string, text = '') {
    return await Swal.fire({
      icon: 'success',
      title,
      text,
      confirmButtonText: 'OK',
      confirmButtonColor: APP_CONFIG.SWAL_CONFIG.confirmButtonColor,
    })
  },

  /**
   * Error modal
   */
  async errorModal(title: string, text = '') {
    return await Swal.fire({
      icon: 'error',
      title,
      text,
      confirmButtonText: 'OK',
      confirmButtonColor: APP_CONFIG.SWAL_CONFIG.confirmButtonColor,
    })
  },

  /**
   * Handle API error and show appropriate message (Sync with legacy behavior)
   */
  handleApiError(error: any, defaultMessage = 'An error occurred. Please try again.') {
    console.error('[API Error]', error)
    
    let message = defaultMessage

    if (error.response) {
      const data = error.response.data
      const status = error.response.status

      if (data && data.message) {
        message = data.message
      }

      // Handle specific error codes per legacy logic
      if (status === 401) {
        message = 'Session expired. Please login again.'
      } else if (status === 403) {
        message = 'You do not have permission to perform this action.'
      } else if (status === 404) {
        message = 'The requested resource was not found.'
      } else if (status === 422) {
        // Validation errors - Format with breaks like legacy
        if (data && data.errors) {
          const errors = Object.values(data.errors).flat()
          if (errors.length > 1) {
            // Show as bulleted list in swal or joined string for toast
            message = errors.join('\n')
          } else {
            message = errors[0] as string
          }
        }
      } else if (status === 429) {
        message = 'Too many requests. Please try again later.'
      } else if (status >= 500) {
        message = 'Server error. Please contact support if the problem persists.'
      }
    } else if (error.message) {
      message = error.message
      if (message.includes('Network Error')) {
        message = 'Network error. Please check your internet connection.'
      }
    }

    // Use error modal for high-severity or structural errors, toast for others
    if (error.response?.status === 422 || error.response?.status === 403) {
      this.errorModal('Action Failed', message)
    } else {
      this.error(message)
    }
  },
}
