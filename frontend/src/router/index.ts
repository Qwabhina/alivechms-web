import { createRouter, createWebHistory } from 'vue-router';
import type { RouteRecordRaw, NavigationGuardNext, RouteLocationNormalized } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const routes: RouteRecordRaw[] = [
   {
      path: '/login',
      name: 'Login',
      component: () => import('@/modules/auth/views/LoginView.vue'),
      meta: { requiresAuth: false, layout: 'auth' },
   },
   {
      path: '/forgot-password',
      name: 'ForgotPassword',
      component: () => import('@/modules/auth/views/ForgotPasswordView.vue'),
      meta: { requiresAuth: false, layout: 'auth' },
   },
   {
      path: '/reset-password/:token',
      name: 'ResetPassword',
      component: () => import('@/modules/auth/views/ResetPasswordView.vue'),
      meta: { requiresAuth: false, layout: 'auth' },
   },
   {
      path: '/unauthorized',
      name: 'Unauthorized',
      component: () => import('@/modules/auth/views/UnauthorizedView.vue'),
      meta: { requiresAuth: false, layout: 'default' },
   },
   {
      path: '/',
      name: 'Dashboard',
      component: () => import('@/modules/dashboard/views/DashboardView.vue'),
      meta: { requiresAuth: true, requiredPermission: 'dashboard.view' },
   },
   // Member routes
   {
      path: '/members',
      name: 'MemberList',
      component: () => import('@/modules/members/views/MemberListView.vue'),
      meta: { requiresAuth: true, requiredPermission: 'members.view' },
   },
   {
      path: '/members/:id',
      name: 'MemberDetail',
      component: () => import('@/modules/members/views/MemberDetailView.vue'),
      meta: { requiresAuth: true, requiredPermission: 'members.view' },
   },
   // Catch-all 404
   {
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      component: () => import('@/modules/shared/views/NotFoundView.vue'),
      meta: { requiresAuth: false },
   },
];

const router = createRouter({
   history: createWebHistory(),
   routes,
});

// Navigation guards
router.beforeEach((to: RouteLocationNormalized, _from: RouteLocationNormalized, next: NavigationGuardNext) => {
   const authStore = useAuthStore();
   const requiresAuth = to.meta.requiresAuth !== false;
   const requiredPermission = to.meta.requiredPermission as string | undefined;

   // Check authentication
   if (requiresAuth && !authStore.isAuthenticated) {
      next({ name: 'Login', query: { redirect: to.fullPath } });
      return;
   }

   // Check permission
   if (requiredPermission && !authStore.can(requiredPermission)) {
      next({ name: 'Unauthorized' });
      return;
   }

   // Redirect to dashboard if already authenticated and trying to access login
   if (to.name === 'Login' && authStore.isAuthenticated) {
      next({ name: 'Dashboard' });
      return;
   }

   next();
});

export default router;
