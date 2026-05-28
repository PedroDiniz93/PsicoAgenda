<script setup>
defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    modalTitle: {
        type: String,
        required: true,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    submitLabel: {
        type: String,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    formErrors: {
        type: Object,
        required: true,
    },
    formError: {
        type: String,
        default: '',
    },
    formSubmitting: {
        type: Boolean,
        default: false,
    },
    patientStatusOptions: {
        type: Array,
        required: true,
    },
    sessionFeeTypeOptions: {
        type: Array,
        required: true,
    },
});

defineEmits(['close', 'submit']);
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-20 flex items-start justify-center overflow-y-auto bg-slate-950/50 px-4 py-8 backdrop-blur-sm"
        @click.self="$emit('close')"
    >
        <div class="w-full max-w-2xl rounded-lg bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 p-5">
                <div>
                    <h2 class="text-xl font-semibold text-slate-950">{{ modalTitle }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ isEditing ? 'Atualize os dados do paciente.' : 'Preencha os dados para cadastrar um novo paciente.' }}
                    </p>
                </div>
                <button
                    class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                    type="button"
                    @click="$emit('close')"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M6 18 18 6" />
                    </svg>
                </button>
            </div>

            <form class="space-y-5 p-5" @submit.prevent="$emit('submit')">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700" for="patient-name">
                        Nome completo
                        <input
                            id="patient-name"
                            v-model="form.name"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            type="text"
                            placeholder="Nome do paciente"
                            required
                        />
                        <span v-if="formErrors.name" class="mt-1 block text-xs text-rose-600">{{ formErrors.name }}</span>
                    </label>

                    <label class="block text-sm font-semibold text-slate-700" for="patient-status">
                        Status
                        <select
                            id="patient-status"
                            v-model="form.status"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            required
                        >
                            <option v-for="option in patientStatusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <span v-if="formErrors.status" class="mt-1 block text-xs text-rose-600">{{ formErrors.status }}</span>
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700" for="patient-email">
                        E-mail
                        <input
                            id="patient-email"
                            v-model="form.email"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            type="email"
                            placeholder="email@paciente.com"
                        />
                        <span v-if="formErrors.email" class="mt-1 block text-xs text-rose-600">{{ formErrors.email }}</span>
                    </label>

                    <label class="block text-sm font-semibold text-slate-700" for="patient-phone">
                        Telefone
                        <input
                            id="patient-phone"
                            v-model="form.phone"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            type="tel"
                            placeholder="(00) 00000-0000"
                        />
                        <span v-if="formErrors.phone" class="mt-1 block text-xs text-rose-600">{{ formErrors.phone }}</span>
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700" for="patient-fee-type">
                        Tipo de cobrança
                        <select
                            id="patient-fee-type"
                            v-model="form.sessionFeeType"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                        >
                            <option v-for="option in sessionFeeTypeOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <span v-if="formErrors.session_fee_type" class="mt-1 block text-xs text-rose-600">
                            {{ formErrors.session_fee_type }}
                        </span>
                    </label>

                    <label class="block text-sm font-semibold text-slate-700" for="patient-fee-value">
                        Valor combinado
                        <input
                            id="patient-fee-value"
                            v-model="form.sessionFeeValue"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="0,00"
                        />
                        <span v-if="formErrors.session_fee_value" class="mt-1 block text-xs text-rose-600">
                            {{ formErrors.session_fee_value }}
                        </span>
                    </label>
                </div>

                <label class="block text-sm font-semibold text-slate-700" for="patient-notes">
                    Observações
                    <textarea
                        id="patient-notes"
                        v-model="form.notes"
                        class="mt-2 min-h-32 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                        placeholder="Informações adicionais, horários preferidos, histórico, etc."
                    ></textarea>
                    <span v-if="formErrors.notes" class="mt-1 block text-xs text-rose-600">{{ formErrors.notes }}</span>
                </label>

                <p v-if="formError" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ formError }}
                </p>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
                    <button
                        class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        type="button"
                        @click="$emit('close')"
                    >
                        Cancelar
                    </button>
                    <button
                        class="inline-flex items-center rounded-lg bg-slate-950 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        type="submit"
                        :disabled="formSubmitting"
                    >
                        <svg v-if="formSubmitting" class="mr-2 size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        {{ submitLabel }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
