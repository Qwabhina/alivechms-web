import { createApp } from 'vue'
import { createPinia } from 'pinia'

import { injectCSSVars } from '@/design-system'
import '@/design-system/styles/base.css'

import App from './App.vue'
import router from './router'

// Inject design system CSS custom properties
injectCSSVars()

const app = createApp(App)

// Pinia must be installed before any store is used (e.g. in route guards)
app.use(createPinia())
app.use(router)

app.mount('#app')
