import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { useAuthStore } from './stores/auth';
import { initTheme } from './composables/useTheme';

initTheme();

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

const auth = useAuthStore(pinia);
auth.initialize();

router.beforeEach((to) => {
    if (!to.meta?.public && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta?.guest && auth.isAuthenticated) {
        return { name: 'home' };
    }

    return true;
});

app.mount('#app');
