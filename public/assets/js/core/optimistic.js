/**
 * Optimistic Updates Utility
 * 
 * Provides optimistic UI updates for better user experience.
 * Updates UI immediately, then syncs with server.
 * Automatically rolls back on failure.
 * 
 * @package  AliveChMS
 * @version  1.0.0
 * @author   Benjamin Ebo Yankson
 * @since    2026-January
 */

class OptimisticUpdates {
   constructor() {
      this.pendingUpdates = new Map();
      this.rollbackCallbacks = new Map();
   }

   /**
    * Perform optimistic update
    * 
    * @param {string} key - Unique identifier for this update
    * @param {Function} optimisticFn - Function to update UI immediately
    * @param {Function} serverFn - Async function to sync with server
    * @param {Function} rollbackFn - Function to rollback UI on failure
    * @param {Object} options - Additional options
    * @returns {Promise} Server response
    */
   async update(key, optimisticFn, serverFn, rollbackFn, options = {}) {
      const {
         showLoading = false,
         loadingMessage = 'Updating...',
         successMessage = null,
         errorMessage = null,
         onSuccess = null,
         onError = null
      } = options;

      // Check if update is already pending
      if (this.pendingUpdates.has(key)) {
         Config.warn(`Optimistic update already pending for key: ${key}`);
         return this.pendingUpdates.get(key);
      }

      try {
         // Step 1: Apply optimistic update immediately
         Config.log(`Optimistic update: ${key} - Applying UI changes`);
         optimisticFn();

         // Store rollback function
         this.rollbackCallbacks.set(key, rollbackFn);

         // Step 2: Show optional loading indicator
         if (showLoading) {
            Alerts.loading(loadingMessage);
         }

         // Step 3: Sync with server
         const serverPromise = serverFn();
         this.pendingUpdates.set(key, serverPromise);

         const result = await serverPromise;

         // Step 4: Success - cleanup
         this.pendingUpdates.delete(key);
         this.rollbackCallbacks.delete(key);

         if (showLoading) {
            Alerts.closeLoading();
         }

         if (successMessage) {
            Alerts.success(successMessage);
         }

         if (onSuccess) {
            onSuccess(result);
         }

         Config.log(`Optimistic update: ${key} - Server confirmed`);
         return result;

      } catch (error) {
         // Step 5: Failure - rollback
         Config.error(`Optimistic update: ${key} - Server rejected, rolling back`, error);

         if (rollbackFn) {
            rollbackFn();
         }

         this.pendingUpdates.delete(key);
         this.rollbackCallbacks.delete(key);

         if (showLoading) {
            Alerts.closeLoading();
         }

         if (errorMessage) {
            Alerts.error(errorMessage);
         } else if (error.message) {
            Alerts.error(error.message);
         }

         if (onError) {
            onError(error);
         }

         throw error;
      }
   }

   /**
    * Perform optimistic delete
    * 
    * @param {string} key - Unique identifier
    * @param {HTMLElement} element - Element to remove
    * @param {Function} serverFn - Async function to delete on server
    * @param {Object} options - Additional options
    * @returns {Promise} Server response
    */
   async delete(key, element, serverFn, options = {}) {
      const {
         animationDuration = 300,
         successMessage = 'Deleted successfully',
         errorMessage = 'Delete failed'
      } = options;

      // Store original state for rollback
      const parent = element.parentNode;
      const nextSibling = element.nextSibling;
      const originalHTML = element.outerHTML;

      const optimisticFn = () => {
         // Fade out animation
         element.style.transition = `opacity ${animationDuration}ms ease-out`;
         element.style.opacity = '0';
         
         setTimeout(() => {
            element.remove();
         }, animationDuration);
      };

      const rollbackFn = () => {
         // Restore element
         const temp = document.createElement('div');
         temp.innerHTML = originalHTML;
         const restoredElement = temp.firstChild;

         if (nextSibling) {
            parent.insertBefore(restoredElement, nextSibling);
         } else {
            parent.appendChild(restoredElement);
         }

         // Fade in animation
         restoredElement.style.opacity = '0';
         setTimeout(() => {
            restoredElement.style.transition = `opacity ${animationDuration}ms ease-in`;
            restoredElement.style.opacity = '1';
         }, 10);
      };

      return this.update(
         key,
         optimisticFn,
         serverFn,
         rollbackFn,
         { ...options, successMessage, errorMessage }
      );
   }

   /**
    * Perform optimistic table row update
    * 
    * @param {string} key - Unique identifier
    * @param {HTMLElement} row - Table row element
    * @param {Object} newData - New data to display
    * @param {Function} serverFn - Async function to update on server
    * @param {Object} options - Additional options
    * @returns {Promise} Server response
    */
   async updateTableRow(key, row, newData, serverFn, options = {}) {
      // Store original HTML for rollback
      const originalHTML = row.innerHTML;

      const optimisticFn = () => {
         // Update row with new data
         Object.keys(newData).forEach(field => {
            const cell = row.querySelector(`[data-field="${field}"]`);
            if (cell) {
               cell.textContent = newData[field];
            }
         });

         // Add visual feedback
         row.classList.add('table-warning');
         setTimeout(() => {
            row.classList.remove('table-warning');
            row.classList.add('table-success');
            setTimeout(() => {
               row.classList.remove('table-success');
            }, 1000);
         }, 300);
      };

      const rollbackFn = () => {
         row.innerHTML = originalHTML;
         row.classList.add('table-danger');
         setTimeout(() => {
            row.classList.remove('table-danger');
         }, 2000);
      };

      return this.update(key, optimisticFn, serverFn, rollbackFn, options);
   }

   /**
    * Perform optimistic counter update
    * 
    * @param {string} key - Unique identifier
    * @param {HTMLElement} element - Counter element
    * @param {number} delta - Change amount (+/-)
    * @param {Function} serverFn - Async function to sync with server
    * @param {Object} options - Additional options
    * @returns {Promise} Server response
    */
   async updateCounter(key, element, delta, serverFn, options = {}) {
      const originalValue = parseInt(element.textContent) || 0;

      const optimisticFn = () => {
         const newValue = originalValue + delta;
         element.textContent = newValue;

         // Add animation
         element.classList.add('counter-update');
         setTimeout(() => {
            element.classList.remove('counter-update');
         }, 500);
      };

      const rollbackFn = () => {
         element.textContent = originalValue;
         element.classList.add('counter-error');
         setTimeout(() => {
            element.classList.remove('counter-error');
         }, 1000);
      };

      return this.update(key, optimisticFn, serverFn, rollbackFn, options);
   }

   /**
    * Perform optimistic toggle (checkbox, switch)
    * 
    * @param {string} key - Unique identifier
    * @param {HTMLElement} element - Toggle element
    * @param {Function} serverFn - Async function to sync with server
    * @param {Object} options - Additional options
    * @returns {Promise} Server response
    */
   async toggle(key, element, serverFn, options = {}) {
      const originalState = element.checked;

      const optimisticFn = () => {
         element.checked = !originalState;
         element.disabled = true; // Prevent multiple clicks
      };

      const rollbackFn = () => {
         element.checked = originalState;
         element.disabled = false;
      };

      const successCallback = () => {
         element.disabled = false;
      };

      return this.update(
         key,
         optimisticFn,
         serverFn,
         rollbackFn,
         { ...options, onSuccess: successCallback }
      );
   }

   /**
    * Check if update is pending
    * 
    * @param {string} key - Update identifier
    * @returns {boolean} Is pending
    */
   isPending(key) {
      return this.pendingUpdates.has(key);
   }

   /**
    * Cancel pending update
    * 
    * @param {string} key - Update identifier
    */
   cancel(key) {
      if (this.pendingUpdates.has(key)) {
         const rollbackFn = this.rollbackCallbacks.get(key);
         if (rollbackFn) {
            rollbackFn();
         }
         this.pendingUpdates.delete(key);
         this.rollbackCallbacks.delete(key);
         Config.log(`Optimistic update cancelled: ${key}`);
      }
   }

   /**
    * Cancel all pending updates
    */
   cancelAll() {
      this.pendingUpdates.forEach((_, key) => {
         this.cancel(key);
      });
   }

   /**
    * Get count of pending updates
    * 
    * @returns {number} Pending count
    */
   getPendingCount() {
      return this.pendingUpdates.size;
   }
}

// Create singleton instance
const Optimistic = new OptimisticUpdates();

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
   .counter-update {
      animation: pulse 0.5s ease-in-out;
   }

   .counter-error {
      animation: shake 0.5s ease-in-out;
      color: #dc3545 !important;
   }

   @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
   }

   @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
   }

   .table-warning {
      background-color: #fff3cd !important;
      transition: background-color 0.3s ease;
   }

   .table-success {
      background-color: #d1e7dd !important;
      transition: background-color 0.3s ease;
   }

   .table-danger {
      background-color: #f8d7da !important;
      transition: background-color 0.3s ease;
   }
`;
document.head.appendChild(style);

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
   module.exports = { OptimisticUpdates, Optimistic };
}
