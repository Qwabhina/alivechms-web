import { createApp } from 'vue'
import { createPinia } from 'pinia'

import '@/design-system/styles/base.css'
// import '@/design-system/styles/page-header.css'

import App from './App.vue'
import router from './router'

const app = createApp(App)

// Pinia must be installed before any store is used (e.g. in route guards)
app.use(createPinia())
app.use(router)

app.mount('#app')
