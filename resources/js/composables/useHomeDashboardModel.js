import { computed } from 'vue';
import { formatMoney } from '../utils/formatters';

const pluralize = (count, singular, plural) => `${count} ${count === 1 ? singular : plural}`;

const formatAttendancePercent = (rate) => {
    if (typeof rate !== 'number' || Number.isNaN(rate)) {
        return null;
    }

    return Math.round(rate * 1000) / 10;
};

export function useHomeDashboardModel({ appointmentReport, userName, isAdmin }) {
    const attendancePercent = computed(() => formatAttendancePercent(appointmentReport.summary.attendanceRate));

    const dashboardHero = computed(() => ({
        kicker: 'Painel do consultório',
        title: `Olá, ${userName.value}`,
        description:
            appointmentReport.summary.totalSessions > 0
                ? `Você tem ${pluralize(appointmentReport.summary.totalSessions, 'sessão registrada', 'sessões registradas')} no período acompanhado.`
                : 'Acompanhe a rotina clínica e financeira assim que houver sessões no período.',
    }));

    const dashboardMetrics = computed(() => [
        {
            id: 'sessions',
            label: 'Sessões no período',
            value: appointmentReport.summary.totalSessions,
            detail: pluralize(appointmentReport.summary.uniquePatients, 'paciente único', 'pacientes únicos'),
            icon: 'CalendarCheck2',
            accent: 'primary',
        },
        {
            id: 'attendance',
            label: 'Comparecimento',
            value: attendancePercent.value === null ? 'Sem dados' : `${attendancePercent.value.toFixed(1)}%`,
            detail: `${appointmentReport.appointments.done} concluídas, ${appointmentReport.appointments.missed} faltas`,
            icon: 'UserCheck',
            accent: 'success',
        },
        {
            id: 'ticket',
            label: 'Ticket médio',
            value: formatMoney(appointmentReport.summary.avgTicket),
            detail: 'Média dos atendimentos pagos',
            icon: 'BadgeDollarSign',
            accent: 'tertiary',
        },
        {
            id: 'pending',
            label: 'A receber',
            value: formatMoney(appointmentReport.payments.pending.value),
            detail: pluralize(appointmentReport.payments.pending.appointments, 'sessão pendente', 'sessões pendentes'),
            icon: 'Clock3',
            accent: 'warning',
        },
    ]);

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

    const dashboardQuickLinks = computed(() => [
        {
            id: 'patients',
            label: 'Pacientes',
            title: 'Base de pacientes',
            description: 'Cadastro, histórico e prontuários',
            icon: 'UsersRound',
            to: { name: 'patients' },
            accent: 'primary',
        },
        {
            id: 'schedule',
            label: 'Agenda',
            title: 'Sessões e horários',
            description: 'Planejamento da rotina clínica',
            icon: 'CalendarClock',
            to: { name: 'schedule' },
            accent: 'success',
        },
        {
            id: 'reports',
            label: 'Relatórios',
            title: 'Indicadores',
            description: 'Compare presença, receita e faltas',
            icon: 'ChartColumn',
            to: { name: 'reports' },
            accent: 'warning',
        },
        {
            id: 'finance',
            label: 'Financeiro',
            title: 'Recebimentos',
            description: 'Cobranças, Pix e recibos',
            icon: 'WalletCards',
            to: { name: 'finance' },
            accent: 'primary',
        },
        {
            id: 'exports',
            label: 'Exportação',
            title: 'Arquivos clínicos',
            description: 'Exporte dados para conferência',
            icon: 'FileArchive',
            to: { name: 'exports' },
            accent: 'error',
        },
    ]);

    const dashboardInsightCards = computed(() => [
        {
            id: 'clinical',
            label: 'Rotina clínica',
            title: pluralize(appointmentReport.summary.totalSessions, 'sessão no período', 'sessões no período'),
            description:
                attendancePercent.value === null
                    ? 'Sem dados suficientes para calcular comparecimento.'
                    : `${attendancePercent.value.toFixed(1)}% de comparecimento nas sessões concluídas ou com falta.`,
            icon: 'Stethoscope',
            accent: 'primary',
        },
        {
            id: 'finance',
            label: 'Recebimentos',
            title: formatMoney(appointmentReport.payments.paid.value),
            description: `${pluralize(appointmentReport.payments.paid.appointments, 'sessão paga', 'sessões pagas')} e ${pluralize(appointmentReport.payments.pending.appointments, 'pendência', 'pendências')}.`,
            icon: 'ReceiptText',
            accent: 'success',
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

    return {
        dashboardHero,
        dashboardMetrics,
        dashboardPrimaryActions,
        dashboardQuickLinks,
        dashboardInsightCards,
        shellNavigationItems,
    };
}
