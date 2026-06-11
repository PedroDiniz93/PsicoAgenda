<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { formatMoney, formatDateTime } from '../utils/formatters';
import { useAppointmentReports } from '../composables/useAppointmentReports';

import ReportHeader from '../components/reports/ReportHeader.vue';
import MetricCards from '../components/reports/MetricCards.vue';
import AppointmentStatus from '../components/reports/AppointmentStatus.vue';
import PaymentSummary from '../components/reports/PaymentSummary.vue';
import PaymentTable from '../components/reports/PaymentTable.vue';
import Alert from '../components/base/Alert.vue';
import Card from '../components/base/Card.vue';
import AppIcon from '../components/base/AppIcon.vue';

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
        icon: 'BadgeCheck',
        label: 'Pagos',
        value: formatMoney(appointmentReport.payments.paid.value ?? 0),
        description: `${appointmentReport.payments.paid.appointments ?? 0} atendimentos recebidos`,
        color: 'success',
    },
    {
        icon: 'ClockAlert',
        label: 'Pendentes',
        value: formatMoney(appointmentReport.payments.pending.value ?? 0),
        description: `${appointmentReport.payments.pending.appointments ?? 0} atendimentos aguardando`,
        color: 'warning',
    },
]);

const statusListSections = computed(() => [
    {
        key: 'done',
        label: 'Concluídos',
        empty: 'Nenhum atendimento concluído.',
        count: appointmentReport.appointments.done,
    },
    {
        key: 'missed',
        label: 'Faltas',
        empty: 'Sem faltas no período.',
        count: appointmentReport.appointments.missed,
    },
    {
        key: 'canceled',
        label: 'Cancelados',
        empty: 'Nenhum cancelamento registrado.',
        count: appointmentReport.appointments.canceled,
    },
]);

onMounted(() => {
    fetchAppointmentReport();
});
</script>

<template>
    <div class="page-shell">
        <section class="space-y-5">
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

            <Alert v-if="reportError" status="error">
                {{ reportError }}
            </Alert>

            <Card v-else-if="reportLoading" class="flex justify-center py-12">
                <div class="flex items-center gap-2 text-[#58635f]">
                    <AppIcon name="LoaderCircle" class="size-5 animate-spin" />
                    Carregando relatório...
                </div>
            </Card>

            <template v-else>
                <div class="space-y-5">
                    <MetricCards :cards="healthCards" />

                    <PaymentSummary :cards="paymentSummaryCards" />

                    <AppointmentStatus
                        :sections="statusListSections"
                        :items="appointmentReport.lists"
                        :format-date-time="formatDateTime"
                    />

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
