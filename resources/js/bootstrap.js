import axios from 'axios';

const apiBase = import.meta.env.VITE_API_BASE_URL ?? '/';

axios.defaults.baseURL = apiBase;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = localStorage.getItem('auth_token');
if (token) {
    axios.defaults.headers.common.Authorization = `Bearer ${token}`;
}

const UNAUTHORIZED_STATUSES = [401, 419];
const AUTH_IGNORED_ENDPOINTS = ['/api/auth/login', '/api/auth/logout'];
let handlingUnauthorizedResponse = false;
let handlingEmailVerificationResponse = false;

const shouldIgnoreUnauthorized = (requestUrl = '') =>
    AUTH_IGNORED_ENDPOINTS.some((endpoint) => requestUrl.includes(endpoint));

const redirectToLoginWithExpiredSession = () => {
    if (handlingUnauthorizedResponse) return;
    handlingUnauthorizedResponse = true;

    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');

    const loginUrl = new URL('/login', window.location.origin);
    loginUrl.searchParams.set('session', 'expired');
    window.location.assign(loginUrl.toString());
};

const redirectToEmailVerification = () => {
    if (handlingEmailVerificationResponse) return;
    handlingEmailVerificationResponse = true;

    window.location.assign(new URL('/email-verification', window.location.origin).toString());
};

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error?.response?.status;
        const requestUrl = error?.config?.url ?? '';

        if (UNAUTHORIZED_STATUSES.includes(status) && !shouldIgnoreUnauthorized(requestUrl)) {
            redirectToLoginWithExpiredSession();
        }

        if (status === 403 && error?.response?.data?.requires_email_verification) {
            redirectToEmailVerification();
        }

        return Promise.reject(error);
    }
);

window.axios = axios;
