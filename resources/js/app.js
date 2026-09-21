import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { useAuthStore } from './stores/auth';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

const auth = useAuthStore(pinia);
auth.initialize();

router.beforeEach((to) => {
    const normalizedPath = to.path.replace(/^\/+/, '/');

    if (normalizedPath !== to.path) {
        return {
            path: normalizedPath,
            query: to.query,
            hash: to.hash,
            replace: true,
        };
    }

    if (!to.meta?.public && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: `/${to.fullPath.replace(/^\/+/, '')}` } };
    }

    if (auth.isAuthenticated && auth.requiresEmailVerification && to.name !== 'email-verification') {
        return { name: 'email-verification' };
    }

    if (auth.isAuthenticated && !auth.requiresEmailVerification && to.name === 'email-verification') {
        return { name: 'home' };
    }

    if (to.meta?.guest && auth.isAuthenticated) {
        return { name: 'home' };
    }

    return true;
});

app.mount('#app');
