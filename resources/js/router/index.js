import { createRouter, createWebHistory } from 'vue-router';

const LoginView = () => import('../views/LoginView.vue');
const ForgotPasswordView = () => import('../views/ForgotPasswordView.vue');
const ResetPasswordView = () => import('../views/ResetPasswordView.vue');
const EmailVerificationView = () => import('../views/EmailVerificationView.vue');
const HomeView = () => import('../views/HomeView.vue');
const ReportsView = () => import('../views/ReportsView.vue');
const FinanceView = () => import('../views/FinanceView.vue');
const PatientsView = () => import('../views/PatientsView.vue');
const ScheduleView = () => import('../views/ScheduleView.vue');
const PatientRecordView = () => import('../views/PatientRecordView.vue');
const ExportsView = () => import('../views/ExportsView.vue');

const defaultTitle = document.title;

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: LoginView,
            meta: { public: true, guest: true, title: 'Entrar' },
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: ForgotPasswordView,
            meta: { public: true, guest: true, title: 'Recuperar senha' },
        },
        {
            path: '/reset-password',
            name: 'reset-password',
            component: ResetPasswordView,
            meta: { public: true, guest: true, title: 'Criar nova senha' },
        },
        {
            path: '/email-verification',
            name: 'email-verification',
            component: EmailVerificationView,
            meta: { requiresAuth: true, title: 'Validação de e-mail' },
        },
        {
            path: '/',
            name: 'home',
            component: HomeView,
            meta: { requiresAuth: true, title: 'Dashboard' },
        },
        {
            path: '/patients',
            name: 'patients',
            component: PatientsView,
            meta: { requiresAuth: true, title: 'Pacientes' },
        },
        {
            path: '/patients/:id',
            name: 'patient-records',
            component: PatientRecordView,
            meta: { requiresAuth: true, title: 'Prontuário' },
        },
        {
            path: '/schedule',
            name: 'schedule',
            component: ScheduleView,
            meta: { requiresAuth: true, title: 'Agenda' },
        },
        {
            path: '/exports',
            name: 'exports',
            component: ExportsView,
            meta: { requiresAuth: true, title: 'Exportação' },
        },
        {
            path: '/reports',
            name: 'reports',
            component: ReportsView,
            meta: { requiresAuth: true, title: 'Relatórios' },
        },
        {
            path: '/finance',
            name: 'finance',
            component: FinanceView,
            meta: { requiresAuth: true, title: 'Financeiro' },
        },
    ],
});

router.afterEach((to) => {
    document.title = to.meta?.title ? `${to.meta.title} · ${defaultTitle}` : defaultTitle;
});

export default router;
