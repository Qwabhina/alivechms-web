/**
 * @file router/index.ts
 * @description Application router with lazy-loaded views and auth guards.
 *
 * Route meta convention:
 *   requiresAuth: boolean — route needs a valid session
 *   permission: string    — required RBAC permission name
 */

import { createRouter, createWebHashHistory } from 'vue-router'
import { registerGuards } from './guards'

const router = createRouter({
  history: createWebHashHistory(import.meta.env.BASE_URL),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    /* ── Auth (public) ───────────────────────────────────────────── */
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { requiresAuth: false },
    },

    /* ── App shell (protected) ───────────────────────────────────── */
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          redirect: '/dashboard',
        },

        /* Dashboard */
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('@/views/dashboard/DashboardView.vue'),
          meta: { permission: 'reports.view' },
        },

        /* Members */
        {
          path: 'members',
          name: 'members',
          component: () => import('@/views/members/MemberListView.vue'),
          meta: { permission: 'members.view' },
        },
        {
          path: 'members/create',
          name: 'members-create',
          component: () => import('@/views/members/MemberCreateView.vue'),
          meta: { permission: 'members.view' },
        },
        {
          path: 'members/:id',
          name: 'members-detail',
          component: () => import('@/views/members/MemberDetailView.vue'),
          meta: { permission: 'members.view' },
        },
        {
          path: 'members/:id/edit',
          name: 'members-edit',
          component: () => import('@/views/members/MemberEditView.vue'),
          meta: { permission: 'members.edit' },
        },

        /* Finance — Contributions */
        {
          path: 'finance/contributions',
          name: 'contributions',
          component: () => import('@/views/finance/ContributionListView.vue'),
          meta: { permission: 'finances.view' },
        },
        {
          path: 'finance/contributions/create',
          name: 'contributions-create',
          component: () => import('@/views/finance/ContributionCreateView.vue'),
          meta: { permission: 'contributions.create' },
        },

        /* Settings */
        {
          path: 'settings',
          name: 'settings',
          component: () => import('@/views/settings/GeneralSettingsView.vue'),
          meta: { permission: 'settings.view' },
        },
      ],
    },

    /* ── 404 catch-all ───────────────────────────────────────────── */
    {
      path: '/:pathMatch(.*)*',
      redirect: '/login',
    },
  ],
})

// Register navigation guards
registerGuards(router)

export default router

/* ── Route meta type augmentation ────────────────────────────────── */

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    permission?: string
  }
}