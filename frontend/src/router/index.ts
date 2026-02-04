import { createRouter, createWebHashHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const router = createRouter({
   history: createWebHashHistory(import.meta.env.BASE_URL),
   routes: [
      {
         path: '/login',
         name: 'login',
         component: () => import('../views/LoginView.vue'),
         meta: { guestOnly: true },
      },
      {
         path: '/',
         component: () => import('../layouts/DashboardLayout.vue'),
         meta: { requiresAuth: true },
         children: [
            {
               path: '',
               name: 'home',
               component: () => import('../views/dashboard/HomeView.vue'),
            },
            {
               path: 'members',
               name: 'members',
               component: () => import('../views/dashboard/MembersView.vue'),
            },
            {
               path: 'finance',
               name: 'finance',
               component: () => import('../views/dashboard/FinanceView.vue'),
            },
            {
               path: 'events',
               name: 'events',
               component: () => import('../views/dashboard/EventsView.vue'),
            },
            {
               path: 'settings',
               name: 'settings',
               component: () => import('../views/dashboard/SettingsView.vue'),
            },
         ],
      },
   ],
})

router.beforeEach(async (to, from, next) => {
   const authStore = useAuthStore()

   if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      next({ name: 'login' })
   } else if (to.meta.guestOnly && authStore.isAuthenticated) {
      next({ name: 'home' })
   } else {
      next()
   }
})

export default router
