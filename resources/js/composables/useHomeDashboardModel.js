import { computed, ref } from 'vue';
import axios from 'axios';

export function useHomeDashboardModel({ userName, isAdmin }) {
    const dashboardLoading = ref(false);
    const dashboardError = ref('');
    const dashboardHero = ref({
        title: `Olá, ${userName.value}`,
        description: 'Acompanhe a rotina clínica e financeira assim que houver sessões no período.',
    });
    const dashboardMetrics = ref([]);
    const dashboardNextPatients = ref([]);
    const dashboardWeeklyAttendances = ref({
        label: 'Semana atual',
        from: '',
        to: '',
        total: 0,
        average_daily: 0,
        days: [],
    });

    const dashboardPrimaryActions = computed(() => [
        {
            id: 'new-appointment',
            label: 'Nova sessão',
            icon: 'CalendarPlus2',
            to: { name: 'schedule' },
            variant: 'primary',
        },
        {
            id: 'new-patient',
            label: 'Novo paciente',
            icon: 'UserPlus',
            to: { name: 'patients' },
            variant: 'secondary',
        },
    ]);

    const shellNavigationItems = computed(() => [
        {
            id: 'dashboard',
            label: 'Dashboard',
            icon: 'LayoutDashboard',
            kind: 'tab',
            tab: 'overview',
        },
        {
            id: 'patients',
            label: 'Pacientes',
            icon: 'UsersRound',
            kind: 'route',
            to: { name: 'patients' },
        },
        {
            id: 'schedule',
            label: 'Agenda',
            icon: 'CalendarDays',
            kind: 'route',
            to: { name: 'schedule' },
        },
        {
            id: 'reports',
            label: 'Relatórios',
            icon: 'ChartColumn',
            kind: 'route',
            to: { name: 'reports' },
        },
        {
            id: 'finance',
            label: 'Financeiro',
            icon: 'WalletCards',
            kind: 'route',
            to: { name: 'finance' },
        },
        {
            id: 'exports',
            label: 'Exportação',
            icon: 'FolderArchive',
            kind: 'route',
            to: { name: 'exports' },
        },
        {
            id: 'profile',
            label: 'Perfil',
            icon: 'UserRoundCog',
            kind: 'tab',
            tab: 'profile',
        },
        {
            id: 'settings',
            label: 'Configurações',
            icon: 'Settings',
            kind: 'tab',
            tab: 'settings',
        },
        ...(isAdmin.value
            ? [
                {
                    id: 'admin',
                    label: 'Admin',
                    icon: 'ShieldCheck',
                    kind: 'tab',
                    tab: 'admin',
                },
            ]
            : []),
    ]);

    const applyDashboard = (payload = {}) => {
        const hero = payload.hero ?? {};
        dashboardHero.value = {
            title: hero.title ?? `Olá, ${userName.value}`,
            description: hero.description ?? 'Acompanhe a rotina clínica e financeira assim que houver sessões no período.',
        };
        dashboardMetrics.value = Array.isArray(payload.metrics) ? payload.metrics : [];
        dashboardNextPatients.value = Array.isArray(payload.next_patients) ? payload.next_patients : [];
        dashboardWeeklyAttendances.value = {
            label: payload.weekly_attendances?.label ?? 'Semana atual',
            from: payload.weekly_attendances?.from ?? '',
            to: payload.weekly_attendances?.to ?? '',
            total: Number(payload.weekly_attendances?.total ?? 0),
            average_daily: Number(payload.weekly_attendances?.average_daily ?? 0),
            days: Array.isArray(payload.weekly_attendances?.days) ? payload.weekly_attendances.days : [],
        };
    };

    const fetchDashboard = async () => {
        dashboardLoading.value = true;
        dashboardError.value = '';

        try {
            const { data } = await axios.get('/api/home/dashboard');
            applyDashboard(data ?? {});
        } catch (error) {
            dashboardError.value = error?.response?.data?.message ?? 'Não foi possível carregar a dashboard.';
        } finally {
            dashboardLoading.value = false;
        }
    };

    return {
        dashboardLoading,
        dashboardError,
        dashboardHero,
        dashboardMetrics,
        dashboardPrimaryActions,
        dashboardNextPatients,
        dashboardWeeklyAttendances,
        fetchDashboard,
        shellNavigationItems,
    };
}
