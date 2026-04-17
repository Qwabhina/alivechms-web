/**
 * @file router/index.ts
 * @description Application router with lazy-loaded views and auth guards.
 *
 * Route meta convention:
 *   requiresAuth: boolean  — route needs a valid session
 *   permission:   string   — required RBAC permission name
 *   title:        string   — human-readable page title (topbar + <title>)
 *   icon:         Component — Lucide icon component shown beside the title
 */

import { createRouter, createWebHashHistory } from 'vue-router'
import { registerGuards } from './guards'
import {
  LayoutDashboard,
  Users,
  UserCircle,
  Wallet,
  CalendarDays,
  Settings,
} from 'lucide-vue-next'

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
          meta: {
            permission: 'reports.view',
            title: 'Dashboard',
            icon: LayoutDashboard,
          },
        },

        /* Members */
        {
          path: 'members',
          name: 'members',
          component: () => import('@/views/members/MembersDashboardView.vue'),
          meta: {
            permission: 'members.view',
            title: 'Members & People',
            icon: Users,
          },
        },
        {
          path: 'members/directory',
          name: 'members-directory',
          component: () => import('@/views/members/MemberListView.vue'),
          meta: {
            permission: 'members.view',
            title: 'Members Directory',
            icon: Users,
          },
        },
        {
          path: 'members/create',
          name: 'members-create',
          component: () => import('@/views/members/MemberCreateView.vue'),
          meta: {
            permission: 'members.view',
            title: 'Add Member',
            icon: Users,
          },
        },
        {
          path: 'members/:id',
          name: 'members-detail',
          component: () => import('@/views/members/MemberDetailView.vue'),
          meta: {
            permission: 'members.view',
            title: 'Member Profile',
            icon: Users,
          },
        },
        {
          path: 'members/:id/edit',
          name: 'members-edit',
          component: () => import('@/views/members/MemberEditView.vue'),
          meta: {
            permission: 'members.edit',
            title: 'Edit Member',
            icon: Users,
          },
        },

        /* Families */
        {
          path: 'families',
          name: 'families',
          component: () => import('@/views/families/FamilyListView.vue'),
          meta: {
            permission: 'members.view',
            title: 'Families',
            icon: UserCircle,
          },
        },
        {
          path: 'families/create',
          name: 'families-create',
          component: () => import('@/views/families/FamilyCreateView.vue'),
          meta: {
            permission: 'members.edit',
            title: 'Add Family',
            icon: UserCircle,
          },
        },
        {
          path: 'families/:id',
          name: 'families-detail',
          component: () => import('@/views/families/FamilyDetailView.vue'),
          meta: {
            permission: 'members.view',
            title: 'Family Profile',
            icon: UserCircle,
          },
        },
        {
          path: 'families/:id/edit',
          name: 'families-edit',
          component: () => import('@/views/families/FamilyEditView.vue'),
          meta: {
            permission: 'members.edit',
            title: 'Edit Family',
            icon: UserCircle,
          },
        },

        /* Groups */
        {
          path: 'groups',
          name: 'groups',
          component: () => import('@/views/groups/GroupListView.vue'),
          meta: {
            permission: 'groups.view',
            title: 'Groups',
            icon: Users,
          },
        },
        {
          path: 'groups/create',
          name: 'groups-create',
          component: () => import('@/views/groups/GroupCreateView.vue'),
          meta: {
            permission: 'groups.create',
            title: 'Create Group',
            icon: Users,
          },
        },
        {
          path: 'groups/:id',
          name: 'groups-detail',
          component: () => import('@/views/groups/GroupDetailView.vue'),
          meta: {
            permission: 'groups.view',
            title: 'Group Details',
            icon: Users,
          },
        },
        {
          path: 'groups/:id/edit',
          name: 'groups-edit',
          component: () => import('@/views/groups/GroupEditView.vue'),
          meta: {
            permission: 'groups.edit',
            title: 'Edit Group',
            icon: Users,
          },
        },

        /* Events */
        {
          path: 'events',
          name: 'events',
          component: () => import('@/views/events/EventListView.vue'),
          meta: {
            permission: 'events.view',
            title: 'Events',
            icon: CalendarDays,
          },
        },
        {
          path: 'events/create',
          name: 'events-create',
          component: () => import('@/views/events/EventCreateView.vue'),
          meta: {
            permission: 'events.create',
            title: 'Create Event',
            icon: CalendarDays,
          },
        },
        {
          path: 'events/:id',
          name: 'events-detail',
          component: () => import('@/views/events/EventDetailView.vue'),
          meta: {
            permission: 'events.view',
            title: 'Event Details',
            icon: CalendarDays,
          },
        },
        {
          path: 'events/:id/edit',
          name: 'events-edit',
          component: () => import('@/views/events/EventEditView.vue'),
          meta: {
            permission: 'events.edit',
            title: 'Edit Event',
            icon: CalendarDays,
          },
        },

        /* Finance — Contributions */
        {
          path: 'finance/contributions',
          name: 'contributions',
          component: () => import('@/views/finance/ContributionListView.vue'),
          meta: {
            permission: 'finances.view',
            title: 'Contributions',
            icon: Wallet,
          },
        },
        {
          path: 'finance/contributions/create',
          name: 'contributions-create',
          component: () => import('@/views/finance/ContributionCreateView.vue'),
          meta: {
            permission: 'contributions.create',
            title: 'Record Contribution',
            icon: Wallet,
          },
        },

        /* Settings */
        {
          path: 'settings',
          name: 'settings',
          component: () => import('@/views/settings/GeneralSettingsView.vue'),
          meta: {
            permission: 'settings.view',
            title: 'Settings',
            icon: Settings,
          },
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
    /** Human-readable page name shown in the topbar and browser tab. */
    title?: string
    /** Lucide icon component rendered beside the title in the topbar. */
    icon?: Component
  }
}