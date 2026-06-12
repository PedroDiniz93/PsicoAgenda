<script setup>
import { RouterLink } from 'vue-router';
import AppIcon from '../base/AppIcon.vue';

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
    currentPage: {
        type: Number,
        default: 1,
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
    formatDate: {
        type: Function,
        required: true,
    },
});

defineEmits(['retry', 'edit', 'previous', 'next']);

const getInitials = (name) => {
    const parts = String(name ?? '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2);

    if (!parts.length) return 'PC';

    return parts.map((part) => part[0]?.toUpperCase()).join('');
};

const statusPillClasses = (status) => {
    const classes = {
        active: 'bg-[#cbe6d4]/40 text-[#4c6455]',
        paused: 'bg-[#e8e1d9]/70 text-[#605b55]',
        closed: 'bg-[#e2e2e2] text-[#73787d]',
    };

    return classes[status] ?? 'bg-[#e2e2e2] text-[#73787d]';
};

const avatarClasses = (status) => {
    const classes = {
        active: 'bg-[#abcae5]/35 text-[#415f76]',
        paused: 'bg-[#e8e1d9] text-[#605b55]',
        closed: 'bg-[#cbe6d4]/35 text-[#4c6455]',
    };

    return classes[status] ?? 'bg-[#eeeeed] text-[#415f76]';
};
</script>

<template>
    <section class="overflow-hidden rounded-b-xl border-x border-b border-[#c2c7cd]/10 bg-white shadow-[0_20px_40px_-10px_rgba(93,123,147,0.06)]">
        <div v-if="errorMessage" class="p-5">
            <div class="rounded-lg border border-[#ffdad6] bg-[#ffdad6]/40 p-4 text-sm text-[#93000a]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p>{{ errorMessage }}</p>
                    <button
                        class="rounded-lg border border-[#ba1a1a]/20 px-4 py-2 text-xs font-semibold text-[#93000a] transition hover:bg-[#ffdad6]"
                        type="button"
                        @click="$emit('retry')"
                    >
                        Tentar novamente
                    </button>
                </div>
            </div>
        </div>

        <div v-else-if="loading" class="flex items-center justify-center gap-3 py-16 text-sm text-[#73787d]">
            <AppIcon name="LoaderCircle" class="size-5 animate-spin text-[#415f76]" />
            Carregando pacientes...
        </div>

        <template v-else>
            <div v-if="patients.length" class="hidden overflow-x-auto lg:block">
                <table class="w-full border-collapse">
                    <thead class="bg-[#f3f4f3] text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-[#73787d]">
                        <tr>
                            <th class="px-6 py-4">Paciente</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Cobrança</th>
                            <th class="px-6 py-4">Contato</th>
                            <th class="px-6 py-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#c2c7cd]/10 text-sm text-[#42474c]">
                        <tr v-for="(patient, index) in patients" :key="patient.id" class="group transition hover:bg-[#f3f4f3]" :class="{ 'bg-[#f3f4f3]/20': index % 2 === 1 }">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                                        :class="avatarClasses(patient.status)"
                                    >
                                        {{ getInitials(patient.name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-[#1a1c1c]">{{ patient.name }}</p>
                                        <div class="mt-1 flex flex-wrap gap-x-2 gap-y-1 text-xs text-[#73787d]">
                                            <span v-if="patient.id">#{{ patient.id }}</span>
                                            <span v-if="patient.cpf">CPF {{ patient.cpf }}</span>
                                            <span v-if="patient.birth_date">Nasc. {{ formatDate(patient.birth_date) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-bold"
                                    :class="statusPillClasses(patient.status)"
                                >
                                    {{ statusBadges[patient.status]?.label ?? 'Desconhecido' }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <p class="font-semibold text-[#42474c]">{{ sessionFeeLabel(patient.session_fee_type) }}</p>
                                <p v-if="patient.session_fee_value" class="mt-1 text-xs text-[#73787d]">
                                    {{ formatCurrency(patient.session_fee_value) }}
                                </p>
                                <p v-else class="mt-1 text-xs text-[#73787d]">Valor não definido</p>
                            </td>
                            <td class="px-6 py-5">
                                <p v-if="patient.email" class="text-sm text-[#42474c]">{{ patient.email }}</p>
                                <p v-if="patient.phone" class="mt-1 text-sm text-[#73787d]">{{ patient.phone }}</p>
                                <p v-if="patient.emergency_contacts?.length" class="mt-1 text-xs text-[#73787d]">
                                    Emergência: {{ patient.emergency_contacts[0].name }} · {{ patient.emergency_contacts[0].phone }}
                                </p>
                                <p v-if="!patient.email && !patient.phone" class="text-sm text-[#73787d]">Sem contato</p>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex justify-end gap-2">
                                    <RouterLink
                                        class="inline-flex items-center gap-1 rounded-lg bg-[#415f76]/10 px-3 py-2 text-xs font-semibold text-[#415f76] transition hover:bg-[#415f76]/20"
                                        :to="{ name: 'patient-records', params: { id: patient.id } }"
                                    >
                                        <AppIcon name="ClipboardList" class="size-4" />
                                        Prontuário
                                    </RouterLink>
                                    <button
                                        class="inline-flex size-9 items-center justify-center rounded-lg text-[#73787d] transition hover:bg-[#eeeeed] hover:text-[#4c6455]"
                                        type="button"
                                        title="Editar dados"
                                        @click="$emit('edit', patient)"
                                    >
                                        <AppIcon name="Pencil" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="patients.length" class="divide-y divide-[#c2c7cd]/10 lg:hidden">
                <article v-for="patient in patients" :key="patient.id" class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                                :class="avatarClasses(patient.status)"
                            >
                                {{ getInitials(patient.name) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-[#1a1c1c]">{{ patient.name }}</p>
                                <p class="mt-1 text-xs text-[#73787d]">#{{ patient.id }}</p>
                            </div>
                        </div>
                        <span
                            class="inline-flex shrink-0 rounded-full px-3 py-1 text-xs font-bold"
                            :class="statusPillClasses(patient.status)"
                        >
                            {{ statusBadges[patient.status]?.label ?? 'Desconhecido' }}
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 text-sm text-[#42474c]">
                        <p v-if="patient.cpf">CPF {{ patient.cpf }}</p>
                        <p v-if="patient.birth_date">Nasc. {{ formatDate(patient.birth_date) }}</p>
                        <p>{{ patient.email || 'Sem e-mail' }}</p>
                        <p>{{ patient.phone || 'Sem telefone' }}</p>
                        <p v-if="patient.emergency_contacts?.length">
                            Emergência: {{ patient.emergency_contacts[0].name }} · {{ patient.emergency_contacts[0].phone }}
                        </p>
                        <p>
                            <span class="font-semibold text-[#1a1c1c]">{{ sessionFeeLabel(patient.session_fee_type) }}</span>
                            <span v-if="patient.session_fee_value"> · {{ formatCurrency(patient.session_fee_value) }}</span>
                        </p>
                    </div>

                    <p class="mt-4 text-sm text-[#73787d]">{{ patient.notes ?? 'Sem observações cadastradas.' }}</p>

                    <div class="mt-4 flex gap-2">
                        <RouterLink
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-[#415f76]/10 px-3 py-2 text-center text-xs font-semibold text-[#415f76] transition hover:bg-[#415f76]/20"
                            :to="{ name: 'patient-records', params: { id: patient.id } }"
                        >
                            <AppIcon name="ClipboardList" class="size-4" />
                            Prontuário
                        </RouterLink>
                        <button
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-[#c2c7cd]/30 px-3 py-2 text-xs font-semibold text-[#42474c] transition hover:bg-[#eeeeed]"
                            type="button"
                            @click="$emit('edit', patient)"
                        >
                            <AppIcon name="Pencil" class="size-4" />
                            Editar
                        </button>
                    </div>
                </article>
            </div>

            <div v-else class="px-8 py-16 text-center">
                <p class="text-sm font-semibold text-[#42474c]">Nenhum paciente encontrado</p>
                <p class="mt-1 text-sm text-[#73787d]">Ajuste os filtros ou cadastre um novo paciente.</p>
            </div>
        </template>

        <div v-if="!errorMessage" class="flex flex-wrap items-center justify-between gap-4 border-t border-[#c2c7cd]/20 bg-[#f3f4f3]/20 px-6 py-4 text-sm text-[#73787d]">
            <p>{{ paginationSummary }}</p>
            <div class="flex items-center gap-2">
                <button
                    class="inline-flex size-9 items-center justify-center rounded-lg border border-[#c2c7cd]/30 text-[#73787d] transition hover:bg-[#eeeeed] disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="!canGoPrev"
                    @click="$emit('previous')"
                >
                    <AppIcon name="ChevronLeft" class="size-4" />
                    <span class="sr-only">Anterior</span>
                </button>
                <span class="flex size-9 items-center justify-center rounded-lg bg-[#415f76] text-sm font-semibold text-white">
                    {{ currentPage }}
                </span>
                <button
                    class="inline-flex size-9 items-center justify-center rounded-lg border border-[#c2c7cd]/30 text-[#73787d] transition hover:bg-[#eeeeed] disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="!canGoNext"
                    @click="$emit('next')"
                >
                    <AppIcon name="ChevronRight" class="size-4" />
                    <span class="sr-only">Próxima</span>
                </button>
            </div>
        </div>
    </section>
</template>
