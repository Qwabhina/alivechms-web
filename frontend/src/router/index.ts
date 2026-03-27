import { createRouter, createWebHashHistory } from 'vue-router'
import DocLayout from '../layouts/DocLayout.vue'

const router = createRouter({
  history: createWebHashHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/docs/introduction'
    },
    {
      path: '/docs',
      component: DocLayout,
      children: [
        {
          path: 'introduction',
          name: 'docs-introduction',
          component: () => import('../views/docs/IntroductionView.vue'),
        },
        {
          path: 'foundation',
          name: 'docs-foundation',
          component: () => import('../views/docs/FoundationView.vue'),
        },
        {
          path: 'installation',
          name: 'docs-installation',
          component: () => import('../views/docs/InstallationView.vue'),
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
        },
        {
          path: 'patterns/layout',
          name: 'docs-patterns-layout',
          component: () => import('../views/docs/LayoutPatternsView.vue'),
        },
        {
          path: 'patterns/forms',
          name: 'docs-patterns-forms',
          component: () => import('../views/docs/FormPatternsView.vue'),
        },
        {
          path: 'patterns/data',
          name: 'docs-patterns-data',
          component: () => import('../views/docs/DataPatternsView.vue'),
        },
        {
          path: 'resources/icons',
          name: 'docs-resources-icons',
          component: () => import('../views/docs/IconsView.vue'),
        },
        {
          path: 'resources/utilities',
          name: 'docs-resources-utilities',
          component: () => import('../views/docs/UtilitiesView.vue'),
        },
        {
          path: 'changelog',
          name: 'docs-changelog',
          component: () => import('../views/docs/ChangelogView.vue'),
        }
      ]
    }
  ],
})

export default router
