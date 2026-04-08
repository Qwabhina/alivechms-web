/**
 * @file router/guards.ts
 * @description Navigation guards for authentication and authorization.
 */

import type { Router } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

export function registerGuards(router: Router) {
  router.beforeEach(async (to) => {
    const auth = useAuthStore()

    // 1. Attempt session restore on first navigation (app boot)
    if (!auth.initialized) {
      await auth.tryRestoreSession()
    }

    // 2. Protected routes — must be authenticated
    if (to.meta.requiresAuth && !auth.isAuthenticated) {
      return {
        path: '/login',
        query: { redirect: to.fullPath },
      }
    }

    // 3. Permission-gated routes
    if (to.meta.permission && typeof to.meta.permission === 'string') {
      if (!auth.hasPermission(to.meta.permission)) {
        // Redirect to dashboard with a subtle denial
        return { path: '/dashboard' }
      }
    }

    // 4. Prevent authenticated users from visiting login
    if (to.path === '/login' && auth.isAuthenticated) {
      return { path: '/dashboard' }
    }
  })
}
