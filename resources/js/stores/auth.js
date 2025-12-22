import { defineStore } from 'pinia';
import axios from 'axios';

const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: null,
        user: null,
        loading: false,
        error: null,
        initialized: false,
    }),
    getters: {
        isAuthenticated: (state) => Boolean(state.token),
    },
    actions: {
        initialize() {
            if (this.initialized) return;

            const token = localStorage.getItem(TOKEN_KEY);
            const user = localStorage.getItem(USER_KEY);

            if (token) {
                this.token = token;
                axios.defaults.headers.common.Authorization = `Bearer ${token}`;
            }

            if (user) {
                try {
                    this.user = JSON.parse(user);
                } catch (_) {
                    localStorage.removeItem(USER_KEY);
                }
            }

            this.initialized = true;
        },
        async login(credentials) {
            this.loading = true;
            this.error = null;

            try {
                const { data } = await axios.post('/api/auth/login', credentials);
                this.setSession(data.token, data.user);
            } catch (error) {
                const message = this.extractErrorMessage(error);
                this.error = message;
                throw new Error(message);
            } finally {
                this.loading = false;
            }
        },
        async logout() {
            try {
                await axios.post('/api/auth/logout');
            } catch (_) {
                // swallow network errors to always clean session client-side
            }

            this.clearSession();
        },
        setSession(token, user) {
            this.token = token;
            this.user = user;
            axios.defaults.headers.common.Authorization = `Bearer ${token}`;
            localStorage.setItem(TOKEN_KEY, token);
            localStorage.setItem(USER_KEY, JSON.stringify(user));
        },
        clearSession() {
            this.token = null;
            this.user = null;
            delete axios.defaults.headers.common.Authorization;
            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(USER_KEY);
        },
        extractErrorMessage(error) {
            if (error?.response?.data?.message) {
                return error.response.data.message;
            }

            return 'Não foi possível acessar. Tente novamente.';
        },
    },
});
