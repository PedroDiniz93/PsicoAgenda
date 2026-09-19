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
const SettingsView = () => import('../views/SettingsView.vue');
const ProfileView = () => import('../views/ProfileView.vue');
const GameKitView = () => import('../views/GameKitView.vue');
const GameKitPlayerView = () => import('../views/GameKitPlayerView.vue');
const GameKitMemoryView = () => import('../views/GameKitMemoryView.vue');
const GameKitMemoryPlayerView = () => import('../views/GameKitMemoryPlayerView.vue');
const GameKitRoutineView = () => import('../views/GameKitRoutineView.vue');
const GameKitRoutinePlayerView = () => import('../views/GameKitRoutinePlayerView.vue');
const GameKitTicTacToeView = () => import('../views/GameKitTicTacToeView.vue');
const GameKitTicTacToePlayerView = () => import('../views/GameKitTicTacToePlayerView.vue');
const GameKitHangmanView = () => import('../views/GameKitHangmanView.vue');
const GameKitHangmanPlayerView = () => import('../views/GameKitHangmanPlayerView.vue');

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
            meta: { requiresAuth: true, title: 'Visão geral' },
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
            path: '/profile',
            name: 'profile',
            component: ProfileView,
            meta: { requiresAuth: true, title: 'Perfil' },
        },
        {
            path: '/finance',
            name: 'finance',
            component: FinanceView,
            meta: { requiresAuth: true, title: 'Financeiro' },
        },
        {
            path: '/settings',
            name: 'settings',
            component: SettingsView,
            meta: { requiresAuth: true, title: 'Configurações' },
        },
        {
            path: '/gamekit',
            name: 'gamekit',
            component: GameKitView,
            meta: { requiresAuth: true, title: 'GameKit Psi' },
        },
        {
            path: '/gamekit/memory',
            name: 'gamekit-memory',
            component: GameKitMemoryView,
            meta: { requiresAuth: true, title: 'Jogo da memória · GameKit Psi' },
        },
        {
            path: '/gamekit/play/:token',
            name: 'gamekit-player',
            component: GameKitPlayerView,
            meta: { public: true, title: 'GameKit Psi' },
        },
        {
            path: '/gamekit/memory/play/:token',
            name: 'gamekit-memory-player',
            component: GameKitMemoryPlayerView,
            meta: { public: true, title: 'Jogo da memória · GameKit Psi' },
        },
        {
            path: '/gamekit/routine',
            name: 'gamekit-routine',
            component: GameKitRoutineView,
            meta: { requiresAuth: true, title: 'Organizador de rotina · GameKit Psi' },
        },
        {
            path: '/gamekit/routine/play/:token',
            name: 'gamekit-routine-player',
            component: GameKitRoutinePlayerView,
            meta: { public: true, title: 'Rotina diária · GameKit Psi' },
        },
        { path: '/gamekit/tictactoe', name: 'gamekit-tictactoe', component: GameKitTicTacToeView, meta: { requiresAuth: true, title: 'Jogo da Velha · GameKit Psi' } },
        { path: '/gamekit/tictactoe/play/:token', name: 'gamekit-tictactoe-player', component: GameKitTicTacToePlayerView, meta: { public: true, title: 'Jogo da Velha · GameKit Psi' } },
        { path: '/gamekit/hangman', name: 'gamekit-hangman', component: GameKitHangmanView, meta: { requiresAuth: true, title: 'Jogo da Forca · GameKit Psi' } },
        { path: '/gamekit/hangman/play/:token', name: 'gamekit-hangman-player', component: GameKitHangmanPlayerView, meta: { public: true, title: 'Jogo da Forca · GameKit Psi' } },
    ],
});

router.afterEach((to) => {
    document.title = to.meta?.title ? `${to.meta.title} · ${defaultTitle}` : defaultTitle;
});

export default router;
