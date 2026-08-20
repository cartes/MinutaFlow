import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { useAuthStore } from './stores/auth';

const app = createApp(App);
app.use(createPinia());
app.use(router);

// Recupera la sesión antes del primer render para evitar parpadeo de rutas protegidas.
const auth = useAuthStore();
auth.fetchMe().finally(() => app.mount('#app'));
