import { computed, reactive, ref } from 'vue';
import axios from 'axios';
import { formatMoney } from '../utils/formatters';

const statusLabels = {
    scheduled: 'Agendado',
    done: 'Concluído',
    missed: 'Falta',
    canceled: 'Cancelado',
};

const statusBadgeClasses = {
    scheduled: 'badge-neutral',
    done: 'badge-success',
    missed: 'badge-warning',
    canceled: 'badge-danger',
};

const createReportState = () => ({
    summary: {
        attendanceRate: null,
        avgSessionsPerPatient: 0,
        avgTicket: '0.00',
        totalSessions: 0,
        uniquePatients: 0,
    },
    payments: {
        paid: { appointments: 0, value: '0.00' },
        pending: { appointments: 0, value: '0.00' },
    },
    appointments: {
        done: 0,
        canceled: 0,
        missed: 0,
    },
    lists: {
        paid: [],
        pending: [],
        done: [],
        canceled: [],
        missed: [],
    },
});

export function useAppointmentReports() {
    const appointmentReport = reactive(createReportState());
    const reportFilters = reactive({ from: '', to: '' });
    const appliedReportFilters = reactive({ from: '', to: '' });
    const reportError = ref('');
    const reportLoading = ref(false);

    const hasReportFiltersFilled = computed(() => Boolean(reportFilters.from || reportFilters.to));

    const reportFiltersInfo = computed(() => {
        if (appliedReportFilters.from && appliedReportFilters.to) {
            return `Período aplicado: ${appliedReportFilters.from} até ${appliedReportFilters.to}.`;
        }
        if (appliedReportFilters.from) {
            return `Filtrando a partir de ${appliedReportFilters.from}.`;
        }
        if (appliedReportFilters.to) {
            return `Filtrando até ${appliedReportFilters.to}.`;
        }
        return 'Sem filtros de data aplicados.';
    });

    const attendanceRateDisplay = computed(() => {
        const rate = appointmentReport.summary.attendanceRate;
        if (typeof rate !== 'number' || Number.isNaN(rate)) {
            return { percent: 0, label: '—' };
        }
        const percent = Math.round(rate * 1000) / 10;
        return { percent, label: `${percent.toFixed(1)}%` };
    });

    const avgSessionsDisplay = computed(() => {
        const value = appointmentReport.summary.avgSessionsPerPatient;
        if (!value) return '0';
        return Number(value).toFixed(1);
    });

    const avgTicketDisplay = computed(() => formatMoney(appointmentReport.summary.avgTicket ?? '0.00'));

    const healthCards = computed(() => [
        {
            id: 'attendance',
            label: 'Taxa de comparecimento',
            description: 'Proporção de sessões concluídas em relação às faltas.',
            value: attendanceRateDisplay.value.label,
            accent: 'text-emerald-600',
            progress: attendanceRateDisplay.value.percent,
        },
        {
            id: 'sessions',
            label: 'Média de sessões por paciente',
            description: 'Volume médio de atendimentos por paciente no período filtrado.',
            value: avgSessionsDisplay.value,
            accent: 'text-blue-600',
            progress: Math.min(100, (appointmentReport.summary.avgSessionsPerPatient / 5) * 100 || 0),
        },
        {
            id: 'ticket',
            label: 'Ticket médio',
            description: 'Valor médio recebido por sessão paga.',
            value: avgTicketDisplay.value,
            accent: 'text-purple-600',
            progress:
                Math.min(
                    100,
                    appointmentReport.summary.avgTicket
                        ? (Number(appointmentReport.summary.avgTicket) / 500) * 100
                        : 0
                ) || 0,
        },
    ]);

    const applyAppointmentReport = (payload = {}) => {
        const payments = payload?.payments ?? {};
        appointmentReport.payments.paid.appointments = payments?.paid?.appointments ?? 0;
        appointmentReport.payments.paid.value = payments?.paid?.value ?? '0.00';
        appointmentReport.payments.pending.appointments = payments?.pending?.appointments ?? 0;
        appointmentReport.payments.pending.value = payments?.pending?.value ?? '0.00';

        const status = payload?.appointments ?? {};
        appointmentReport.appointments.done = status?.done?.count ?? 0;
        appointmentReport.appointments.canceled = status?.canceled?.count ?? 0;
        appointmentReport.appointments.missed = status?.missed?.count ?? 0;

        const lists = payload?.lists ?? {};
        appointmentReport.lists.paid = lists?.paid ?? [];
        appointmentReport.lists.pending = lists?.pending ?? [];
        appointmentReport.lists.done = lists?.done ?? [];
        appointmentReport.lists.canceled = lists?.canceled ?? [];
        appointmentReport.lists.missed = lists?.missed ?? [];

        const summary = payload?.summary ?? {};
        appointmentReport.summary.attendanceRate =
            typeof summary?.attendance_rate === 'number' ? summary.attendance_rate : null;
        appointmentReport.summary.avgSessionsPerPatient = Number(summary?.avg_sessions_per_patient ?? 0);
        appointmentReport.summary.avgTicket = summary?.avg_ticket ?? '0.00';
        appointmentReport.summary.totalSessions = Number(summary?.total_sessions ?? 0);
        appointmentReport.summary.uniquePatients = Number(summary?.unique_patients ?? 0);
    };

    const fetchAppointmentReport = async () => {
        reportLoading.value = true;
        reportError.value = '';
        const params = {};
        if (reportFilters.from) params.from = reportFilters.from;
        if (reportFilters.to) params.to = reportFilters.to;

        try {
            const { data } = await axios.get('/api/reports/appointments', { params });
            applyAppointmentReport(data ?? {});
            appliedReportFilters.from = data?.filters?.from ?? params.from ?? '';
            appliedReportFilters.to = data?.filters?.to ?? params.to ?? '';
        } catch (error) {
            reportError.value =
                error?.response?.data?.message ?? 'Não foi possível carregar os relatórios de agendamentos.';
        } finally {
            reportLoading.value = false;
        }
    };

    const clearReportFilters = () => {
        reportFilters.from = '';
        reportFilters.to = '';
        fetchAppointmentReport();
    };

    const statusLabel = (status) => statusLabels[status] ?? status;
    const statusBadgeClass = (status) => statusBadgeClasses[status] ?? 'badge-neutral';

    return {
        appointmentReport,
        reportFilters,
        appliedReportFilters,
        reportError,
        reportLoading,
        hasReportFiltersFilled,
        reportFiltersInfo,
        fetchAppointmentReport,
        clearReportFilters,
        healthCards,
        statusLabel,
        statusBadgeClass,
    };
}
