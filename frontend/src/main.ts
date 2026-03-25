import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import { injectCSSVars } from "@/design-system";
import "@/design-system/styles/base.css";
import "@/design-system/styles/animations.css";

import App from './App.vue'
import router from './router'

injectCSSVars();
const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
