<script setup>
import { computed } from 'vue';
import { formatCpf, formatPhone } from '../../utils/formatters';

const props = defineProps({
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

const isMinorPatient = computed(() => {
    if (!props.form.birthDate) return false;

    const birthDate = new Date(`${props.form.birthDate}T00:00:00`);
    if (Number.isNaN(birthDate.getTime())) return false;

    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age -= 1;
    }

    return age < 18;
});

const emergencyContacts = computed(() => props.form.emergencyContacts ?? []);
const canAddEmergencyContact = computed(() => emergencyContacts.value.length < 3);

const addEmergencyContact = () => {
    if (!canAddEmergencyContact.value) return;

    props.form.emergencyContacts.push({
        name: '',
        phone: '',
        relationship: '',
    });
};

const removeEmergencyContact = (index) => {
    props.form.emergencyContacts.splice(index, 1);
};

const maskCpf = () => {
    props.form.cpf = formatCpf(props.form.cpf);
};

const maskPhone = (field) => {
    props.form[field] = formatPhone(props.form[field]);
};

const maskEmergencyContactPhone = (contact) => {
    contact.phone = formatPhone(contact.phone);
};
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
                            maxlength="15"
                            @input="maskPhone('phone')"
                        />
                        <span v-if="formErrors.phone" class="mt-1 block text-xs text-rose-600">{{ formErrors.phone }}</span>
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700" for="patient-cpf">
                        CPF
                        <input
                            id="patient-cpf"
                            v-model="form.cpf"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            type="text"
                            placeholder="000.000.000-00"
                            maxlength="14"
                            inputmode="numeric"
                            @input="maskCpf"
                        />
                        <span v-if="formErrors.cpf" class="mt-1 block text-xs text-rose-600">{{ formErrors.cpf }}</span>
                    </label>

                    <label class="block text-sm font-semibold text-slate-700" for="patient-birth-date">
                        Data de nascimento
                        <input
                            id="patient-birth-date"
                            v-model="form.birthDate"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            type="date"
                        />
                        <span v-if="formErrors.birth_date" class="mt-1 block text-xs text-rose-600">{{ formErrors.birth_date }}</span>
                    </label>
                </div>

                <div v-if="isMinorPatient" class="grid gap-4 rounded-lg border border-amber-200 bg-amber-50 p-4 md:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700" for="patient-minor-guardian-name">
                        Responsável pelo menor
                        <input
                            id="patient-minor-guardian-name"
                            v-model="form.minorGuardianName"
                            class="mt-2 h-11 w-full rounded-lg border border-amber-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            type="text"
                            placeholder="Nome do responsável"
                            required
                        />
                        <span v-if="formErrors.minor_guardian_name" class="mt-1 block text-xs text-rose-600">
                            {{ formErrors.minor_guardian_name }}
                        </span>
                    </label>

                    <label class="block text-sm font-semibold text-slate-700" for="patient-minor-guardian-phone">
                        Telefone do responsável
                        <input
                            id="patient-minor-guardian-phone"
                            v-model="form.minorGuardianPhone"
                            class="mt-2 h-11 w-full rounded-lg border border-amber-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            type="tel"
                            placeholder="(00) 00000-0000"
                            maxlength="15"
                            required
                            @input="maskPhone('minorGuardianPhone')"
                        />
                        <span v-if="formErrors.minor_guardian_phone" class="mt-1 block text-xs text-rose-600">
                            {{ formErrors.minor_guardian_phone }}
                        </span>
                    </label>
                </div>

                <section class="rounded-lg border border-slate-200 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Contatos de emergência</p>
                            <p class="mt-1 text-xs text-slate-500">Até 3 contatos por paciente.</p>
                        </div>
                        <button
                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                            type="button"
                            :disabled="!canAddEmergencyContact"
                            @click="addEmergencyContact"
                        >
                            Adicionar contato
                        </button>
                    </div>

                    <div v-if="emergencyContacts.length" class="mt-4 space-y-4">
                        <div
                            v-for="(contact, index) in emergencyContacts"
                            :key="index"
                            class="grid gap-3 rounded-lg bg-slate-50 p-3 md:grid-cols-[1fr_1fr_1fr_auto]"
                        >
                            <label class="block text-xs font-semibold text-slate-600" :for="`emergency-contact-name-${index}`">
                                Nome
                                <input
                                    :id="`emergency-contact-name-${index}`"
                                    v-model="contact.name"
                                    class="mt-2 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                                    type="text"
                                    placeholder="Nome"
                                />
                            </label>

                            <label class="block text-xs font-semibold text-slate-600" :for="`emergency-contact-phone-${index}`">
                                Telefone
                                <input
                                    :id="`emergency-contact-phone-${index}`"
                                    v-model="contact.phone"
                                    class="mt-2 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                                    type="tel"
                                    placeholder="(00) 00000-0000"
                                    maxlength="15"
                                    @input="maskEmergencyContactPhone(contact)"
                                />
                            </label>

                            <label class="block text-xs font-semibold text-slate-600" :for="`emergency-contact-relationship-${index}`">
                                Relação
                                <input
                                    :id="`emergency-contact-relationship-${index}`"
                                    v-model="contact.relationship"
                                    class="mt-2 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                                    type="text"
                                    placeholder="Família, amigo..."
                                />
                            </label>

                            <button
                                class="self-end rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                                type="button"
                                @click="removeEmergencyContact(index)"
                            >
                                Remover
                            </button>
                        </div>
                    </div>

                    <p v-else class="mt-4 rounded-lg bg-slate-50 px-3 py-3 text-sm text-slate-500">
                        Nenhum contato de emergência adicionado.
                    </p>
                    <span v-if="formErrors.emergency_contacts" class="mt-2 block text-xs text-rose-600">
                        {{ formErrors.emergency_contacts }}
                    </span>
                </section>

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
