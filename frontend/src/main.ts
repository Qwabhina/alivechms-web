import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import { injectCSSVars } from "@/design-system";
import "@/design-system/styles/base.css";
import "@/design-system/styles/animations.css";

import App from './App.vue'
import router from './router'

injectCSSVars({
   '--ch-color-primary': '#00026D',
   '--ch-color-primary-hover': '#0003ae',
   '--ch-color-primary-active': '#0004ed',
   '--ch-color-primary-contrast': '#ffffff',
});
const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
