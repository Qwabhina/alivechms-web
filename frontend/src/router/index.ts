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
      ]
    }
  ],
})

export default router
