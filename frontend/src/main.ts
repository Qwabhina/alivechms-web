import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './assets/index.css'
import 'sweetalert2/dist/sweetalert2.min.css'
import App from './App.vue'
import { useSettingsStore } from './stores/settings'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Initialize settings
const settingsStore = useSettingsStore(pinia)
settingsStore.fetchSettings()

app.mount('#app')
