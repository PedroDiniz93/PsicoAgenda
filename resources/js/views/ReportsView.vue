<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { formatMoney, formatDateTime } from '../utils/formatters';
import { useAppointmentReports } from '../composables/useAppointmentReports';

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

onMounted(() => {
    fetchAppointmentReport();
});
</script>

<template>
    <div class="mx-auto min-h-screen w-full max-w-6xl px-4 py-10 lg:px-8">
        <section class="space-y-6 rounded-2xl bg-white p-6 shadow">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Relatórios</p>
                    <h1 class="text-2xl font-semibold text-slate-900">Acompanhe seus resultados</h1>
                    <p class="text-sm text-slate-500">{{ reportFiltersInfo }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <RouterLink
                        :to="{ name: 'home' }"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
                        </svg>
                        Dashboard
                    </RouterLink>
                    <button
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                        type="button"
                        @click="reportFiltersOpen = !reportFiltersOpen"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        {{ reportFiltersOpen ? 'Ocultar filtros' : 'Ajustar filtros' }}
                    </button>
                    <button
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow shadow-blue-600/30 transition hover:bg-blue-700"
                        type="button"
                        :disabled="reportLoading"
                        @click="fetchAppointmentReport"
                    >
                        <svg v-if="reportLoading" class="-ms-1 me-2 size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Atualizar
                    </button>
                </div>
            </div>

            <transition name="fade">
                <form
                    v-if="reportFiltersOpen"
                    class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                    @submit.prevent="fetchAppointmentReport"
                >
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <label class="flex flex-col text-sm font-semibold text-slate-600">
                            <span>De</span>
                            <input
                                v-model="reportFilters.from"
                                class="mt-1 rounded-2xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                type="date"
                            />
                        </label>
                        <label class="flex flex-col text-sm font-semibold text-slate-600">
                            <span>Até</span>
                            <input
                                v-model="reportFilters.to"
                                class="mt-1 rounded-2xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                type="date"
                            />
                        </label>
                        <div class="flex items-end gap-2 md:col-span-2">
                            <button
                                :disabled="reportLoading"
                                class="flex-1 rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                                type="submit"
                            >
                                Aplicar
                            </button>
                            <button
                                v-if="hasReportFiltersFilled"
                                :disabled="reportLoading"
                                class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-60"
                                type="button"
                                @click="clearReportFilters"
                            >
                                Limpar
                            </button>
                        </div>
                    </div>
                </form>
            </transition>

            <div v-if="reportError" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                {{ reportError }}
            </div>
            <div
                v-else-if="reportLoading"
                class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-500"
            >
                Carregando relatório...
            </div>

            <div v-else class="space-y-6">
                <div class="grid gap-4 md:grid-cols-3">
                    <article
                        v-for="card in healthCards"
                        :key="card.id"
                        class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 shadow-sm"
                    >
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ card.label }}</p>
                        <p class="text-2xl font-semibold text-slate-900">{{ card.value }}</p>
                        <p class="text-xs text-slate-500">{{ card.description }}</p>

                    </article>
                </div>
                <div class="rounded-2xl border border-slate-100 p-6">
                    <p class="text-sm font-semibold text-slate-600">Agendamentosnos </p>
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        <div
                            v-for="section in statusListSections"
                            :key="section.key"
                            class="rounded-2xl border border-slate-100 p-4"
                        >
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">{{ section.label }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ appointmentReport.lists[section.key].length }} atendimentos
                                    </p>
                                </div>
                                <span class="text-lg font-bold text-slate-400">
                                    {{ appointmentReport.appointments[section.key] ?? 0 }}
                                </span>
                            </div>
                            <div
                                v-if="appointmentReport.lists[section.key]?.length"
                                class="space-y-3 text-sm text-slate-600"
                            >
                                <div
                                    v-for="item in appointmentReport.lists[section.key].slice(0, 4)"
                                    :key="`status-${section.key}-${item.id}`"
                                    class="rounded-xl border border-slate-100 px-3 py-2"
                                >
                                    <p class="font-semibold text-slate-900">
                                        {{ item.patient?.name ?? 'Paciente sem nome' }}
                                    </p>
                                    <p class="text-xs text-slate-500">{{ formatDateTime(item.start_at) }}</p>
                                </div>
                                <p v-if="appointmentReport.lists[section.key].length > 4" class="text-xs text-slate-500">
                                    Mostrando os 4 registros mais recentes.
                                </p>
                            </div>
                            <p v-else class="text-sm text-slate-500">{{ section.empty }}</p>
                        </div>
                    </div>
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-inner">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Pagos</p>
                        <p class="text-3xl font-bold text-emerald-900">
                            {{ formatMoney(appointmentReport.payments.paid.value) }}
                        </p>
                        <p class="text-sm text-emerald-800">
                            {{ appointmentReport.payments.paid.appointments }} atendimentos no período.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-inner">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Pendentes</p>
                        <p class="text-3xl font-bold text-amber-900">
                            {{ formatMoney(appointmentReport.payments.pending.value) }}
                        </p>
                        <p class="text-sm text-amber-800">
                            {{ appointmentReport.payments.pending.appointments }} atendimentos aguardando recebimento.
                        </p>
                    </div>
                </div>
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-600">Clientes que pagaram</p>
                                <p class="text-xs text-slate-500">
                                    {{ appointmentReport.payments.paid.appointments }} atendimentos registrados
                                </p>
                            </div>
                            <p class="text-lg font-semibold text-emerald-600">
                                {{ formatMoney(appointmentReport.payments.paid.value) }}
                            </p>
                        </div>
                        <div v-if="appointmentReport.lists.paid.length" class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="pb-2 pr-4 font-semibold">Paciente</th>
                                        <th class="pb-2 pr-4 font-semibold">Início</th>
                                        <th class="pb-2 pr-4 font-semibold">Valor</th>
                                        <th class="pb-2 font-semibold">Pago em</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in appointmentReport.lists.paid.slice(0, 10)"
                                        :key="`paid-${item.id}`"
                                        class="border-t border-slate-100"
                                    >
                                        <td class="py-2 pr-4">
                                            <p class="font-semibold text-slate-900">
                                                {{ item.patient?.name ?? 'Paciente sem nome' }}
                                            </p>
                                            <p class="text-xs text-slate-500">#{{ item.id }}</p>
                                        </td>
                                        <td class="py-2 pr-4 text-slate-600">
                                            {{ formatDateTime(item.start_at) }}
                                        </td>
                                        <td class="py-2 pr-4 font-semibold text-slate-900">
                                            {{ formatMoney(item.price) }}
                                        </td>
                                        <td class="py-2 text-slate-600">{{ formatDateTime(item.paid_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p v-if="appointmentReport.lists.paid.length > 10" class="mt-3 text-xs text-slate-500">
                                Mostrando os 10 pagamentos mais recentes.
                            </p>
                        </div>
                        <p v-else class="mt-4 text-sm text-slate-500">Nenhum pagamento registrado nesse período.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-600">Clientes com pendência</p>
                                <p class="text-xs text-slate-500">
                                    {{ appointmentReport.payments.pending.appointments }} atendimentos aguardando pagamento
                                </p>
                            </div>
                            <p class="text-lg font-semibold text-amber-600">
                                {{ formatMoney(appointmentReport.payments.pending.value) }}
                            </p>
                        </div>
                        <div v-if="appointmentReport.lists.pending.length" class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="pb-2 pr-4 font-semibold">Paciente</th>
                                        <th class="pb-2 pr-4 font-semibold">Início</th>
                                        <th class="pb-2 pr-4 font-semibold">Valor</th>
                                        <th class="pb-2 font-semibold">Situação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in appointmentReport.lists.pending.slice(0, 10)"
                                        :key="`pending-${item.id}`"
                                        class="border-t border-slate-100"
                                    >
                                        <td class="py-2 pr-4">
                                            <p class="font-semibold text-slate-900">
                                                {{ item.patient?.name ?? 'Paciente sem nome' }}
                                            </p>
                                            <p class="text-xs text-slate-500">#{{ item.id }}</p>
                                        </td>
                                        <td class="py-2 pr-4 text-slate-600">
                                            {{ formatDateTime(item.start_at) }}
                                        </td>
                                        <td class="py-2 pr-4 font-semibold text-slate-900">
                                            {{ formatMoney(item.price) }}
                                        </td>
                                        <td class="py-2">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(item.status)">
                                                {{ statusLabel(item.status) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <p v-if="appointmentReport.lists.pending.length > 10" class="mt-3 text-xs text-slate-500">
                                Mostrando os 10 itens mais recentes.
                            </p>
                        </div>
                        <p v-else class="mt-4 text-sm text-slate-500">Sem pendências financeiras nesse período.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
