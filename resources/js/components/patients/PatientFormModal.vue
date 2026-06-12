<script setup>
import { computed, ref, watch } from 'vue';
import { formatCpf, formatPhone } from '../../utils/formatters';
import AppIcon from '../base/AppIcon.vue';

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

const formatBirthDateDisplay = (value) => {
    if (!value) return '';

    const parts = String(value).split('-');
    if (parts.length !== 3) return '';

    const [year, month, day] = parts;
    if (!year || !month || !day) return '';

    return `${day}/${month}/${year}`;
};

const formatBirthDateMask = (value) => {
    const digits = String(value ?? '').replace(/\D/g, '').slice(0, 8);

    if (digits.length <= 2) return digits;
    if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;
    return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
};

const birthDateDisplayToIso = (value) => {
    const digits = String(value ?? '').replace(/\D/g, '').slice(0, 8);
    if (digits.length !== 8) return '';

    const day = digits.slice(0, 2);
    const month = digits.slice(2, 4);
    const year = digits.slice(4, 8);

    const parsed = new Date(`${year}-${month}-${day}T00:00:00`);
    if (
        Number.isNaN(parsed.getTime()) ||
        parsed.getFullYear() !== Number(year) ||
        parsed.getMonth() + 1 !== Number(month) ||
        parsed.getDate() !== Number(day)
    ) {
        return '';
    }

    return `${year}-${month}-${day}`;
};

const birthDateDisplay = ref(formatBirthDateDisplay(props.form.birthDate));
const birthDateInputRef = ref(null);

watch(
    () => props.form.birthDate,
    (value) => {
        birthDateDisplay.value = formatBirthDateDisplay(value);
    }
);

const handleBirthDateInput = (event) => {
    const value = event.target.value || '';
    birthDateDisplay.value = formatBirthDateMask(value);
    props.form.birthDate = birthDateDisplayToIso(value);
};

const syncBirthDateFromPicker = (event) => {
    const value = event.target.value || '';
    props.form.birthDate = value;
    birthDateDisplay.value = formatBirthDateDisplay(value);
};

const openBirthDatePicker = () => {
    const input = birthDateInputRef.value;

    if (!input) return;

    if (typeof input.showPicker === 'function') {
        input.showPicker();
        return;
    }

    input.click();
};
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-[70] flex items-center justify-center overflow-y-auto bg-[#1a1c1c]/40 px-4 py-6 backdrop-blur-sm"
        @click.self="$emit('close')"
    >
        <div class="flex max-h-[calc(100vh-3rem)] w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-[#c2c7cd]/20 bg-[#f9f9f8] shadow-2xl">
            <header class="flex items-start justify-between gap-4 border-b border-[#c2c7cd]/10 bg-white px-6 py-6">
                <div>
                    <h2 class="font-['Source_Serif_4'] text-2xl font-semibold text-[#1a1c1c]">{{ modalTitle }}</h2>
                    <p class="mt-1 text-sm text-[#42474c]">
                        {{ isEditing ? 'Atualize os dados do paciente.' : 'Preencha os dados para cadastrar um novo paciente.' }}
                    </p>
                </div>
                <button
                    class="flex size-10 shrink-0 items-center justify-center rounded-full text-[#42474c] transition hover:bg-[#f3f4f3] hover:text-[#415f76]"
                    type="button"
                    aria-label="Fechar"
                    @click="$emit('close')"
                >
                    <AppIcon name="X" class="size-5" />
                </button>
            </header>

            <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="$emit('submit')">
                <div class="min-h-0 flex-1 space-y-8 overflow-y-auto px-6 py-6">
                    <section>
                        <div class="mb-5 flex items-center gap-3">
                            <AppIcon name="UserRound" class="size-5 text-[#415f76]" />
                            <h3 class="text-xs font-semibold uppercase text-[#415f76]">Informações pessoais</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <label class="block text-xs font-semibold text-[#42474c]" for="patient-name">
                                Nome completo
                                <input
                                    id="patient-name"
                                    v-model="form.name"
                                    class="mt-2 h-11 w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                    type="text"
                                    placeholder="Nome do paciente"
                                    required
                                />
                                <span v-if="formErrors.name" class="mt-1 block text-xs text-[#ba1a1a]">{{ formErrors.name }}</span>
                            </label>

                            <label class="block text-xs font-semibold text-[#42474c]" for="patient-status">
                                Status
                                <select
                                    id="patient-status"
                                    v-model="form.status"
                                    class="mt-2 h-11 w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                    required
                                >
                                    <option v-for="option in patientStatusOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <span v-if="formErrors.status" class="mt-1 block text-xs text-[#ba1a1a]">{{ formErrors.status }}</span>
                            </label>

                            <label class="block text-xs font-semibold text-[#42474c]" for="patient-cpf">
                                CPF
                                <input
                                    id="patient-cpf"
                                    v-model="form.cpf"
                                    class="mt-2 h-11 w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                    type="text"
                                    placeholder="000.000.000-00"
                                    maxlength="14"
                                    inputmode="numeric"
                                    @input="maskCpf"
                                />
                                <span v-if="formErrors.cpf" class="mt-1 block text-xs text-[#ba1a1a]">{{ formErrors.cpf }}</span>
                            </label>

                            <label class="block text-xs font-semibold text-[#42474c]" for="patient-birth-date">
                                Data de nascimento
                                <div class="mt-2 flex items-center gap-2">
                                    <input
                                        v-model="birthDateDisplay"
                                        class="h-11 w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                        placeholder="dd/mm/aaaa"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="10"
                                        @input="handleBirthDateInput"
                                    />
                                    <button
                                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-[#c2c7cd] bg-white text-[#415f76] transition hover:bg-[#f3f4f3]"
                                        type="button"
                                        aria-label="Abrir calendário"
                                        @click="openBirthDatePicker"
                                    >
                                        <AppIcon name="CalendarDays" class="size-5" />
                                    </button>
                                </div>
                                <input
                                    id="patient-birth-date"
                                    ref="birthDateInputRef"
                                    class="sr-only"
                                    type="date"
                                    :value="form.birthDate || ''"
                                    @change="syncBirthDateFromPicker"
                                />
                                <span v-if="formErrors.birth_date" class="mt-1 block text-xs text-[#ba1a1a]">{{ formErrors.birth_date }}</span>
                            </label>
                        </div>
                    </section>

                    <hr class="border-[#c2c7cd]/20" />

                    <section>
                        <div class="mb-5 flex items-center gap-3">
                            <AppIcon name="Contact" class="size-5 text-[#415f76]" />
                            <h3 class="text-xs font-semibold uppercase text-[#415f76]">Contato</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <label class="block text-xs font-semibold text-[#42474c]" for="patient-phone">
                                Telefone
                                <div class="relative mt-2">
                                    <AppIcon name="MessageCircle" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-[#4c6455]" />
                                    <input
                                        id="patient-phone"
                                        v-model="form.phone"
                                        class="h-11 w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] pl-10 pr-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                        type="tel"
                                        placeholder="(00) 00000-0000"
                                        maxlength="15"
                                        @input="maskPhone('phone')"
                                    />
                                </div>
                                <span v-if="formErrors.phone" class="mt-1 block text-xs text-[#ba1a1a]">{{ formErrors.phone }}</span>
                            </label>

                            <label class="block text-xs font-semibold text-[#42474c]" for="patient-email">
                                E-mail
                                <input
                                    id="patient-email"
                                    v-model="form.email"
                                    class="mt-2 h-11 w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                    type="email"
                                    placeholder="email@paciente.com"
                                />
                                <span v-if="formErrors.email" class="mt-1 block text-xs text-[#ba1a1a]">{{ formErrors.email }}</span>
                            </label>
                        </div>
                    </section>

                    <section v-if="isMinorPatient" class="rounded-xl border border-[#c08e3a]/20 bg-[#e8e1d9]/35 p-5">
                        <div class="mb-5 flex items-center gap-3">
                            <AppIcon name="ShieldAlert" class="size-5 text-[#605b55]" />
                            <h3 class="text-xs font-semibold uppercase text-[#605b55]">Responsável pelo menor</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <label class="block text-xs font-semibold text-[#42474c]" for="patient-minor-guardian-name">
                                Nome do responsável
                                <input
                                    id="patient-minor-guardian-name"
                                    v-model="form.minorGuardianName"
                                    class="mt-2 h-11 w-full rounded-lg border border-[#c2c7cd] bg-white px-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                    type="text"
                                    placeholder="Nome do responsável"
                                    required
                                />
                                <span v-if="formErrors.minor_guardian_name" class="mt-1 block text-xs text-[#ba1a1a]">
                                    {{ formErrors.minor_guardian_name }}
                                </span>
                            </label>

                            <label class="block text-xs font-semibold text-[#42474c]" for="patient-minor-guardian-phone">
                                Telefone do responsável
                                <input
                                    id="patient-minor-guardian-phone"
                                    v-model="form.minorGuardianPhone"
                                    class="mt-2 h-11 w-full rounded-lg border border-[#c2c7cd] bg-white px-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                    type="tel"
                                    placeholder="(00) 00000-0000"
                                    maxlength="15"
                                    required
                                    @input="maskPhone('minorGuardianPhone')"
                                />
                                <span v-if="formErrors.minor_guardian_phone" class="mt-1 block text-xs text-[#ba1a1a]">
                                    {{ formErrors.minor_guardian_phone }}
                                </span>
                            </label>
                        </div>
                    </section>

                    <hr class="border-[#c2c7cd]/20" />

                    <section class="rounded-xl border border-[#4c6455]/10 bg-[#cbe6d4]/20 p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <AppIcon name="Siren" class="size-5 text-[#ba1a1a]" />
                                <div>
                                    <h3 class="text-sm font-semibold text-[#1a1c1c]">Contatos de emergência</h3>
                                    <p class="mt-1 text-xs text-[#42474c]">Até 3 contatos por paciente.</p>
                                </div>
                            </div>
                            <button
                                class="inline-flex items-center gap-2 rounded-full border border-[#c2c7cd] bg-white px-4 py-2 text-xs font-semibold text-[#42474c] transition hover:bg-[#f3f4f3] disabled:cursor-not-allowed disabled:opacity-50"
                                type="button"
                                :disabled="!canAddEmergencyContact"
                                @click="addEmergencyContact"
                            >
                                <AppIcon name="Plus" class="size-4" />
                                Adicionar contato
                            </button>
                        </div>

                        <div v-if="emergencyContacts.length" class="mt-5 space-y-4">
                            <div
                                v-for="(contact, index) in emergencyContacts"
                                :key="index"
                                class="grid gap-4 rounded-xl border border-[#c2c7cd]/30 bg-white p-4 md:grid-cols-[1fr_1fr_1fr_auto]"
                            >
                                <label class="block text-xs font-semibold text-[#42474c]" :for="`emergency-contact-name-${index}`">
                                    Nome
                                    <input
                                        :id="`emergency-contact-name-${index}`"
                                        v-model="contact.name"
                                        class="mt-2 h-10 w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                        type="text"
                                        placeholder="Nome"
                                    />
                                </label>

                                <label class="block text-xs font-semibold text-[#42474c]" :for="`emergency-contact-phone-${index}`">
                                    Telefone
                                    <input
                                        :id="`emergency-contact-phone-${index}`"
                                        v-model="contact.phone"
                                        class="mt-2 h-10 w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                        type="tel"
                                        placeholder="(00) 00000-0000"
                                        maxlength="15"
                                        @input="maskEmergencyContactPhone(contact)"
                                    />
                                </label>

                                <label class="block text-xs font-semibold text-[#42474c]" :for="`emergency-contact-relationship-${index}`">
                                    Relação
                                    <input
                                        :id="`emergency-contact-relationship-${index}`"
                                        v-model="contact.relationship"
                                        class="mt-2 h-10 w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                        type="text"
                                        placeholder="Família, amigo..."
                                    />
                                </label>

                                <button
                                    class="self-end rounded-lg border border-[#ffdad6] px-3 py-2 text-xs font-semibold text-[#ba1a1a] transition hover:bg-[#ffdad6]/45"
                                    type="button"
                                    @click="removeEmergencyContact(index)"
                                >
                                    Remover
                                </button>
                            </div>
                        </div>

                        <p v-else class="mt-5 rounded-lg bg-white px-4 py-3 text-sm text-[#42474c]">
                            Nenhum contato de emergência adicionado.
                        </p>
                        <span v-if="formErrors.emergency_contacts" class="mt-2 block text-xs text-[#ba1a1a]">
                            {{ formErrors.emergency_contacts }}
                        </span>
                    </section>

                    <hr class="border-[#c2c7cd]/20" />

                    <section>
                        <div class="mb-5 flex items-center gap-3">
                            <AppIcon name="ClipboardPenLine" class="size-5 text-[#415f76]" />
                            <h3 class="text-xs font-semibold uppercase text-[#415f76]">Cobrança e observações</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <label class="block text-xs font-semibold text-[#42474c]" for="patient-fee-type">
                                Tipo de cobrança
                                <select
                                    id="patient-fee-type"
                                    v-model="form.sessionFeeType"
                                    class="mt-2 h-11 w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                >
                                    <option v-for="option in sessionFeeTypeOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <span v-if="formErrors.session_fee_type" class="mt-1 block text-xs text-[#ba1a1a]">
                                    {{ formErrors.session_fee_type }}
                                </span>
                            </label>

                            <label class="block text-xs font-semibold text-[#42474c]" for="patient-fee-value">
                                Valor combinado
                                <input
                                    id="patient-fee-value"
                                    v-model="form.sessionFeeValue"
                                    class="mt-2 h-11 w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0,00"
                                />
                                <span v-if="formErrors.session_fee_value" class="mt-1 block text-xs text-[#ba1a1a]">
                                    {{ formErrors.session_fee_value }}
                                </span>
                            </label>
                        </div>

                        <label class="mt-5 block text-xs font-semibold text-[#42474c]" for="patient-notes">
                            Observações
                            <textarea
                                id="patient-notes"
                                v-model="form.notes"
                                class="mt-2 min-h-28 w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-3 py-3 text-sm text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#abcae5]/40"
                                placeholder="Informações adicionais, horários preferidos, histórico, etc."
                            ></textarea>
                            <span v-if="formErrors.notes" class="mt-1 block text-xs text-[#ba1a1a]">{{ formErrors.notes }}</span>
                        </label>
                    </section>

                    <p v-if="formError" class="rounded-lg border border-[#ffdad6] bg-[#ffdad6]/45 px-4 py-3 text-sm text-[#93000a]">
                        {{ formError }}
                    </p>
                </div>

                <footer class="flex flex-wrap items-center justify-end gap-4 border-t border-[#c2c7cd]/10 bg-white px-6 py-5">
                    <button
                        class="rounded-full border border-[#73787d] px-6 py-2 text-sm font-semibold text-[#42474c] transition hover:bg-[#f3f4f3]"
                        type="button"
                        @click="$emit('close')"
                    >
                        Cancelar
                    </button>
                    <button
                        class="inline-flex items-center gap-2 rounded-full bg-[#415f76] px-6 py-2 text-sm font-semibold text-white shadow-lg transition hover:bg-[#2b4a60] disabled:cursor-not-allowed disabled:opacity-60"
                        type="submit"
                        :disabled="formSubmitting"
                    >
                        <AppIcon v-if="formSubmitting" name="LoaderCircle" class="size-4 animate-spin" />
                        <AppIcon v-else name="Save" class="size-4" />
                        {{ submitLabel }}
                    </button>
                </footer>
            </form>
        </div>
    </div>
</template>
