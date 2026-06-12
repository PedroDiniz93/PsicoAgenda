import { defineStore } from 'pinia';
import axios from 'axios';

const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';
const EXPIRES_AT_KEY = 'auth_expires_at';
const REMEMBER_KEY = 'auth_remember';

const storageTargets = [localStorage, sessionStorage];

const readStoredSession = () => {
    for (const storage of storageTargets) {
        const token = storage.getItem(TOKEN_KEY);
        const user = storage.getItem(USER_KEY);
        const expiresAt = storage.getItem(EXPIRES_AT_KEY);

        if (token) {
            return { storage, token, user, expiresAt };
        }
    }

    return { storage: null, token: null, user: null, expiresAt: null };
};

const clearStoredSession = () => {
    storageTargets.forEach((storage) => {
        storage.removeItem(TOKEN_KEY);
        storage.removeItem(USER_KEY);
        storage.removeItem(EXPIRES_AT_KEY);
        storage.removeItem(REMEMBER_KEY);
    });
};

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: null,
        user: null,
        requiresEmailVerification: false,
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

            const { token, user, expiresAt } = readStoredSession();

            if (expiresAt && new Date(expiresAt).getTime() <= Date.now()) {
                this.clearSession();
                this.initialized = true;
                return;
            }

            if (token) {
                this.token = token;
                axios.defaults.headers.common.Authorization = `Bearer ${token}`;
            }

            if (user) {
                try {
                    this.user = JSON.parse(user);
                    this.requiresEmailVerification = this.user?.role === 'psychologist' && !this.user?.email_verified_at;
                } catch (_) {
                    clearStoredSession();
                }
            }

            this.initialized = true;
        },
        async login(credentials) {
            this.loading = true;
            this.error = null;

            try {
                const { data } = await axios.post('/api/auth/login', credentials);
                const remember = Boolean(data.remember ?? credentials.remember);
                this.setSession(data.token, data.user, { remember, expiresAt: data.expires_at });
                this.requiresEmailVerification = Boolean(data.requires_email_verification);
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
        async verifyEmail(code) {
            this.loading = true;
            this.error = null;

            try {
                const { data } = await axios.post('/api/auth/email-verification/verify', { code });
                this.setUser(data.user);
                this.requiresEmailVerification = false;
                return data.message;
            } catch (error) {
                const message = this.extractErrorMessage(error);
                this.error = message;
                throw new Error(message);
            } finally {
                this.loading = false;
            }
        },
        async resendEmailVerification() {
            this.loading = true;
            this.error = null;

            try {
                const { data } = await axios.post('/api/auth/email-verification/resend');
                this.setUser(data.user);
                this.requiresEmailVerification = this.user?.role === 'psychologist' && !this.user?.email_verified_at;
                return data.message;
            } catch (error) {
                const message = this.extractErrorMessage(error);
                this.error = message;
                throw new Error(message);
            } finally {
                this.loading = false;
            }
        },
        setSession(token, user, options = {}) {
            const remember = Boolean(options.remember);
            const storage = remember ? localStorage : sessionStorage;
            const expiresAt = remember ? (options.expiresAt ?? new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString()) : null;

            clearStoredSession();
            this.token = token;
            axios.defaults.headers.common.Authorization = `Bearer ${token}`;
            storage.setItem(TOKEN_KEY, token);
            storage.setItem(REMEMBER_KEY, remember ? '1' : '0');

            if (expiresAt) {
                storage.setItem(EXPIRES_AT_KEY, expiresAt);
            }

            this.setUser(user, storage);
        },
        setUser(user, storage = null) {
            this.user = user;
            this.requiresEmailVerification = user?.role === 'psychologist' && !user?.email_verified_at;

            const targetStorage = storage ?? readStoredSession().storage ?? localStorage;
            targetStorage.setItem(USER_KEY, JSON.stringify(user));
        },
        clearSession() {
            this.token = null;
            this.user = null;
            this.requiresEmailVerification = false;
            delete axios.defaults.headers.common.Authorization;
            clearStoredSession();
        },
        extractErrorMessage(error) {
            if (error?.response?.data?.message) {
                return error.response.data.message;
            }

            return 'Não foi possível acessar. Tente novamente.';
        },
    },
});
