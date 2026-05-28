<script setup>
import { RouterLink } from 'vue-router';

defineProps({
    patients: {
        type: Array,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    errorMessage: {
        type: String,
        default: '',
    },
    paginationSummary: {
        type: String,
        default: '',
    },
    canGoPrev: {
        type: Boolean,
        default: false,
    },
    canGoNext: {
        type: Boolean,
        default: false,
    },
    statusBadges: {
        type: Object,
        required: true,
    },
    sessionFeeLabel: {
        type: Function,
        required: true,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
});

defineEmits(['retry', 'edit', 'previous', 'next']);
</script>

<template>
    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div v-if="errorMessage" class="p-5">
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p>{{ errorMessage }}</p>
                    <button
                        class="rounded-lg border border-rose-200 px-4 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"
                        type="button"
                        @click="$emit('retry')"
                    >
                        Tentar novamente
                    </button>
                </div>
            </div>
        </div>

        <div v-else-if="loading" class="flex items-center justify-center gap-3 py-16 text-sm text-slate-500">
            <svg class="size-5 animate-spin text-cyan-700" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            Carregando pacientes...
        </div>

        <template v-else>
            <div v-if="patients.length" class="hidden overflow-x-auto lg:block">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Paciente</th>
                            <th class="px-5 py-3">Contato</th>
                            <th class="px-5 py-3">Cobrança</th>
                            <th class="px-5 py-3">Observações</th>
                            <th class="px-5 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        <tr v-for="patient in patients" :key="patient.id" class="transition hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="statusBadges[patient.status]?.classes ?? 'bg-slate-100 text-slate-600'"
                                >
                                    {{ statusBadges[patient.status]?.label ?? 'Desconhecido' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-950">{{ patient.name }}</p>
                                <p v-if="patient.id" class="mt-1 text-xs text-slate-500">#{{ patient.id }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p v-if="patient.email" class="text-sm text-slate-700">{{ patient.email }}</p>
                                <p v-if="patient.phone" class="mt-1 text-sm text-slate-500">{{ patient.phone }}</p>
                                <p v-if="!patient.email && !patient.phone" class="text-sm text-slate-400">Sem contato</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800">{{ sessionFeeLabel(patient.session_fee_type) }}</p>
                                <p v-if="patient.session_fee_value" class="mt-1 text-xs text-slate-500">
                                    {{ formatCurrency(patient.session_fee_value) }}
                                </p>
                                <p v-else class="mt-1 text-xs text-slate-400">Valor não definido</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="max-w-xs truncate text-sm text-slate-500">
                                    {{ patient.notes ?? 'Sem observações cadastradas.' }}
                                </p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <RouterLink
                                        class="rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50"
                                        :to="{ name: 'patient-records', params: { id: patient.id } }"
                                    >
                                        Prontuário
                                    </RouterLink>
                                    <button
                                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                        type="button"
                                        @click="$emit('edit', patient)"
                                    >
                                        Editar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="patients.length" class="divide-y divide-slate-100 lg:hidden">
                <article v-for="patient in patients" :key="patient.id" class="p-4">
                    <div class="mb-3">
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="statusBadges[patient.status]?.classes ?? 'bg-slate-100 text-slate-600'"
                        >
                            {{ statusBadges[patient.status]?.label ?? 'Desconhecido' }}
                        </span>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-950">{{ patient.name }}</p>
                            <p class="mt-1 text-xs text-slate-500">#{{ patient.id }}</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 text-sm text-slate-600">
                        <p>{{ patient.email || 'Sem e-mail' }}</p>
                        <p>{{ patient.phone || 'Sem telefone' }}</p>
                        <p>
                            <span class="font-semibold text-slate-800">{{ sessionFeeLabel(patient.session_fee_type) }}</span>
                            <span v-if="patient.session_fee_value"> · {{ formatCurrency(patient.session_fee_value) }}</span>
                        </p>
                    </div>

                    <p class="mt-4 text-sm text-slate-500">{{ patient.notes ?? 'Sem observações cadastradas.' }}</p>

                    <div class="mt-4 flex gap-2">
                        <RouterLink
                            class="flex-1 rounded-lg border border-emerald-200 px-3 py-2 text-center text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50"
                            :to="{ name: 'patient-records', params: { id: patient.id } }"
                        >
                            Prontuário
                        </RouterLink>
                        <button
                            class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                            type="button"
                            @click="$emit('edit', patient)"
                        >
                            Editar
                        </button>
                    </div>
                </article>
            </div>

            <div v-else class="px-8 py-16 text-center">
                <p class="text-sm font-semibold text-slate-700">Nenhum paciente encontrado</p>
                <p class="mt-1 text-sm text-slate-500">Ajuste os filtros ou cadastre um novo paciente.</p>
            </div>
        </template>

        <div v-if="!errorMessage" class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 px-5 py-4 text-sm text-slate-500">
            <p>{{ paginationSummary }}</p>
            <div class="flex items-center gap-2">
                <button
                    class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="!canGoPrev"
                    @click="$emit('previous')"
                >
                    Anterior
                </button>
                <button
                    class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="!canGoNext"
                    @click="$emit('next')"
                >
                    Próxima
                </button>
            </div>
        </div>
    </section>
</template>
