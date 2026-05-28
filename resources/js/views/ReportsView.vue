<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { RouterLink } from 'vue-router';
import { formatMoney, formatDateTime } from '../utils/formatters';
import { useAppointmentReports } from '../composables/useAppointmentReports';

import ReportHeader from '../components/reports/ReportHeader.vue';
import MetricCards from '../components/reports/MetricCards.vue';
import AppointmentStatus from '../components/reports/AppointmentStatus.vue';
import PaymentSummary from '../components/reports/PaymentSummary.vue';
import PaymentTable from '../components/reports/PaymentTable.vue';
import Alert from '../components/base/Alert.vue';
import Card from '../components/base/Card.vue';

const statusListSections = [
    { key: 'done', label: 'Concluídos', empty: 'Nenhum atendimento concluído.' },
    { key: 'canceled', label: 'Cancelados', empty: 'Nenhum cancelamento registrado.' },
    { key: 'missed', label: 'Faltas', empty: 'Sem faltas no período.' },
];

const reportFiltersOpen = ref(false);

const {
    appointmentReport,
    reportFilters,
    reportError,
    reportLoading,
    hasReportFiltersFilled,
    reportFiltersInfo,
    fetchAppointmentReport,
    clearReportFilters,
    healthCards,
    statusLabel,
    statusBadgeClass,
} = useAppointmentReports();

const paymentSummaryCards = computed(() => [
    {
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        label: 'Pagos',
        value: formatMoney(appointmentReport.value?.payments?.paid?.value ?? 0),
        description: `${appointmentReport.value?.payments?.paid?.appointments ?? 0} atendimentos recebidos`,
        color: 'success',
    },
    {
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        label: 'Pendentes',
        value: formatMoney(appointmentReport.value?.payments?.pending?.value ?? 0),
        description: `${appointmentReport.value?.payments?.pending?.appointments ?? 0} atendimentos aguardando`,
        color: 'warning',
    },
]);

onMounted(() => {
    fetchAppointmentReport();
});
</script>

<template>
    <div class="mx-auto min-h-screen w-full max-w-7xl px-4 py-8 lg:px-8">
        <section class="space-y-8">
            <!-- Header com filtros -->
            <ReportHeader
                :report-filters-open="reportFiltersOpen"
                :report-loading="reportLoading"
                :has-report-filters-filled="hasReportFiltersFilled"
                :filters="reportFilters"
                @toggle-filters="reportFiltersOpen = !reportFiltersOpen"
                @refresh="fetchAppointmentReport"
                @apply-filters="fetchAppointmentReport"
                @clear-filters="clearReportFilters"
            >
                <template #info>
                    {{ reportFiltersInfo }}
                </template>
            </ReportHeader>

            <!-- Error Alert -->
            <Alert v-if="reportError" status="error">
                {{ reportError }}
            </Alert>

            <!-- Loading State -->
            <Card v-else-if="reportLoading" class="flex justify-center py-12">
                <div class="flex items-center gap-2 text-neutral-600">
                    <svg class="size-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    Carregando relatório...
                </div>
            </Card>

            <!-- Content -->
            <template v-else>
                <!-- Metric Cards -->
                <div class="space-y-6">
                    <MetricCards :cards="healthCards" />

                    <!-- Appointment Status -->
                    <AppointmentStatus
                        :sections="statusListSections"
                        :items="appointmentReport.lists"
                        :format-date-time="formatDateTime"
                    />

                    <!-- Payment Summary Cards -->
                    <PaymentSummary :cards="paymentSummaryCards" />

                    <!-- Payment Tables -->
                    <div class="grid gap-6 lg:grid-cols-2">
                        <PaymentTable
                            title="Clientes que pagaram"
                            subtitle="Pagamentos recebidos no período"
                            :total="appointmentReport.payments.paid.value"
                            :count="appointmentReport.payments.paid.appointments"
                            :items="appointmentReport.lists.paid"
                            color="success"
                            :columns="[]"
                            :format-date-time="formatDateTime"
                            :format-money="formatMoney"
                            show-paid-date
                        />
                        <PaymentTable
                            title="Clientes com pendência"
                            subtitle="Aguardando recebimento"
                            :total="appointmentReport.payments.pending.value"
                            :count="appointmentReport.payments.pending.appointments"
                            :items="appointmentReport.lists.pending"
                            color="warning"
                            :columns="[]"
                            :format-date-time="formatDateTime"
                            :format-money="formatMoney"
                            :status-label="statusLabel"
                        />
                    </div>
                </div>
            </template>
        </section>
    </div>
</template>
