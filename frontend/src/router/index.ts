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
            // Members & People
            {
               path: 'members',
               name: 'members',
               component: () => import('../views/dashboard/MembersView.vue'),
            },
            {
               path: 'families',
               name: 'families',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            {
               path: 'groups',
               name: 'groups',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            {
               path: 'volunteers',
               name: 'volunteers',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            {
               path: 'milestones',
               name: 'milestones',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            // Finance
            {
               path: 'contributions',
               name: 'contributions',
               component: () => import('../views/dashboard/ContributionsView.vue'),
            },
            {
               path: 'pledges',
               name: 'pledges',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            {
               path: 'expenses',
               name: 'expenses',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            {
               path: 'budgets',
               name: 'budgets',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            {
               path: 'financial-reports',
               name: 'financial-reports',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            // Events & Activities
            {
               path: 'events',
               name: 'events',
               component: () => import('../views/dashboard/EventsView.vue'),
            },
            {
               path: 'attendance',
               name: 'attendance',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            // Communication
            {
               path: 'messages',
               name: 'messages',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            {
               path: 'announcements',
               name: 'announcements',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            // Assets
            {
               path: 'assets',
               name: 'assets',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            {
               path: 'asset-categories',
               name: 'asset-categories',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            // Settings
            {
               path: 'settings',
               name: 'settings',
               component: () => import('../views/dashboard/SettingsView.vue'),
               props: true
            },
            {
               path: 'users',
               name: 'users',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            {
               path: 'roles-permission',
               name: 'roles',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            {
               path: 'branches',
               name: 'branches',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            {
               path: 'fiscal-years',
               name: 'fiscal-years',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            {
               path: 'audit-log',
               name: 'audit-log',
               component: () => import('../views/dashboard/PlaceholderView.vue'),
            },
            {
               path: 'expenses',
               name: 'expenses',
               component: () => import('../views/dashboard/ExpensesView.vue'),
            },
            {
               path: 'pledges',
               name: 'pledges',
               component: () => import('../views/dashboard/PledgesView.vue'),
            },
            {
               path: 'budgets',
               name: 'budgets',
               component: () => import('../views/dashboard/BudgetsView.vue'),
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
