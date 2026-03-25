import { createRouter, createWebHashHistory } from 'vue-router'
import DocLayout from '../layouts/DocLayout.vue'

const router = createRouter({
  history: createWebHashHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/docs/foundation'
    },
    {
      path: '/docs',
      component: DocLayout,
      children: [
        {
          path: 'foundation',
          name: 'docs-foundation',
          component: () => import('../views/docs/FoundationView.vue'),
        },
        {
          path: 'core',
          name: 'docs-core',
          component: () => import('../views/docs/CoreView.vue'),
        },
        {
          path: 'forms',
          name: 'docs-forms',
          component: () => import('../views/docs/FormsView.vue'),
        },
        {
          path: 'data',
          name: 'docs-data',
          component: () => import('../views/docs/DataView.vue'),
        },
        {
          path: 'navigation',
          name: 'docs-navigation',
          component: () => import('../views/docs/NavigationView.vue'),
        },
        {
          path: 'feedback',
          name: 'docs-feedback',
          component: () => import('../views/docs/FeedbackView.vue'),
        }
      ]
    }
  ],
})

export default router
