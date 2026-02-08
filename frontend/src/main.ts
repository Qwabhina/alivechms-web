import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import router from './router';
import App from './App.vue';

// Styles
import 'primeicons/primeicons.css';
import './styles/theme.css';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(PrimeVue);

// Initialize auth store
import { useAuthStore } from './stores/authStore';
const authStore = useAuthStore();
authStore.initialize();

app.mount('#app');
