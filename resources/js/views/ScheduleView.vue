<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const auth = useAuthStore();

const formatDate = (value) => {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const formatDateLabel = (value) => {
    try {
        return new Intl.DateTimeFormat('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date(`${value}T00:00:00`));
    } catch {
        return value;
    }
};

const formatTimeLabel = (value) => {
    try {
        return new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' }).format(new Date(value));
    } catch {
        return value;
    }
};

const toLocalInputValue = (value) => {
    if (!value) return '';
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const fromLocalInputToIso = (value) => {
    if (!value) return null;
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return null;
    return date.toISOString();
};

const addDays = (base, offset) => {
    const date = new Date(`${base}T00:00:00`);
    date.setDate(date.getDate() + offset);
    return formatDate(date);
};

const addMinutesToLocalInput = (value, minutes) => {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    date.setMinutes(date.getMinutes() + minutes);
    return toLocalInputValue(date);
};

const formatCurrency = (value) => {
    if (value === undefined || value === null || value === '') return null;
    const number = Number(value);
    if (Number.isNaN(number)) return null;
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(number);
};

const authUser = computed(() => auth.user ?? {});
const authPsychologist = computed(() => authUser.value.psychologist ?? {});

const sessionDuration = ref(authPsychologist.value.session_duration ?? 50);

const scheduleDate = ref(formatDate(new Date()));
const scheduleLoading = ref(false);
const scheduleError = ref('');
const appointments = ref([]);
const autoFillEndEnabled = ref(true);

const appointmentModalOpen = ref(false);
const appointmentSubmitting = ref(false);
const editingAppointment = ref(null);

const appointmentForm = reactive({
    patientId: '',
    startAt: '',
    endAt: '',
    status: 'scheduled',
    type: 'online',
    price: '',
    paidAt: '',
});

const appointmentErrors = reactive({
    patientId: '',
    startAt: '',
    endAt: '',
    status: '',
    type: '',
    price: '',
    paidAt: '',
});

const appointmentMessage = ref('');

const appointmentStatusOptions = [
    { value: 'scheduled', label: 'Agendado' },
    { value: 'done', label: 'Concluído' },
    { value: 'missed', label: 'Faltou' },
    { value: 'canceled', label: 'Cancelado' },
];

const appointmentStatusStyles = {
    scheduled: 'bg-blue-50 text-blue-700',
    done: 'bg-emerald-50 text-emerald-700',
    missed: 'bg-amber-50 text-amber-700',
    canceled: 'bg-slate-100 text-slate-500',
};

const appointmentTypeOptions = [
    { value: 'online', label: 'Online' },
    { value: 'in_person', label: 'Presencial' },
];

const appointmentPatientSearch = ref('');
const patientOptions = ref([]);
const patientOptionsLoading = ref(false);
let patientSearchTimeout = null;

const appointmentActionLoading = reactive({ id: null, action: '' });

const defaultSessionMinutes = computed(() => Number(sessionDuration.value) || 50);
const scheduleDayLabel = computed(() => formatDateLabel(scheduleDate.value));
const appointmentsEmpty = computed(() => appointments.value.length === 0);
const appointmentModalTitle = computed(() => (editingAppointment.value ? 'Editar agendamento' : 'Novo agendamento'));
const appointmentSubmitLabel = computed(() => (editingAppointment.value ? 'Salvar alterações' : 'Agendar'));
const selectedPatient = computed(() =>
    patientOptions.value.find((patient) => String(patient.id) === String(appointmentForm.patientId))
);
const sessionFeeTypeLabels = {
    session: 'Por sessão',
    biweekly: 'Quinzenal',
    monthly: 'Mensal',
};
const selectedPatientFeeDescription = computed(() => {
    if (!selectedPatient.value) return 'Selecione um paciente para calcular o valor.';
    if (!selectedPatient.value.session_fee_value) {
        return 'Defina o valor deste paciente no cadastro para aplicar automaticamente.';
    }

    return `${sessionFeeTypeLabels[selectedPatient.value.session_fee_type] ?? 'Plano'} · Valor contratado ${formatCurrency(
        selectedPatient.value.session_fee_value
    )}`;
});
const selectedPatientHasFee = computed(() => Boolean(selectedPatient.value?.session_fee_value));

const handleLogout = async () => {
    await auth.logout();
    router.push({ name: 'login' });
};

const clearAppointmentErrors = () => {
    Object.keys(appointmentErrors).forEach((key) => {
        appointmentErrors[key] = '';
    });
};

const resetAppointmentForm = () => {
    autoFillEndEnabled.value = true;
    appointmentForm.patientId = '';
    appointmentForm.startAt = `${scheduleDate.value}T09:00`;
    appointmentForm.endAt = '';
    appointmentForm.status = 'scheduled';
    appointmentForm.type = 'online';
    appointmentForm.price = '';
    appointmentForm.paidAt = '';
    appointmentMessage.value = '';
    clearAppointmentErrors();
};

const ensurePatientOption = (patient) => {
    if (!patient?.id) return;
    if (!patientOptions.value.some((option) => option.id === patient.id)) {
        patientOptions.value = [...patientOptions.value, patient];
    }
};

const openCreateAppointment = () => {
    editingAppointment.value = null;
    resetAppointmentForm();
    appointmentModalOpen.value = true;
    fetchPatientOptions();
};

const openEditAppointment = (appointment) => {
    autoFillEndEnabled.value = false;
    editingAppointment.value = appointment;
    appointmentForm.patientId = String(appointment.patient_id ?? appointment.patient?.id ?? '');
    appointmentForm.startAt = toLocalInputValue(appointment.start_at);
    appointmentForm.endAt = toLocalInputValue(appointment.end_at);
    appointmentForm.status = appointment.status ?? 'scheduled';
    appointmentForm.type = appointment.type ?? 'online';
    appointmentForm.price = appointment.price ?? '';
    appointmentForm.paidAt = toLocalInputValue(appointment.paid_at);
    appointmentMessage.value = '';
    clearAppointmentErrors();
    appointmentModalOpen.value = true;
    if (appointment.patient) {
        ensurePatientOption(appointment.patient);
    }
    autoFillEndEnabled.value = true;
    fetchPatientOptions(appointment.patient?.name ?? '');
};

const calculatePatientFeeValue = (patient) => {
    const baseValue = Number(patient?.session_fee_value);
    if (Number.isNaN(baseValue) || baseValue <= 0) {
        return null;
    }

    let result = baseValue;
    if (patient.session_fee_type === 'biweekly') {
        result = baseValue / 2;
    } else if (patient.session_fee_type === 'monthly') {
        result = baseValue / 4;
    }

    return Number(result.toFixed(2));
};

const syncPriceWithPatient = () => {
    const patient = selectedPatient.value;
    const fee = calculatePatientFeeValue(patient);
    if (fee !== null && !Number.isNaN(fee)) {
        appointmentForm.price = fee;
    } else if (!editingAppointment.value) {
        appointmentForm.price = '';
    }
};

watch(
    () => appointmentForm.patientId,
    () => {
        syncPriceWithPatient();
    }
);

watch(
    () => patientOptions.value,
    () => {
        syncPriceWithPatient();
    }
);

const closeAppointmentModal = () => {
    appointmentModalOpen.value = false;
    editingAppointment.value = null;
    autoFillEndEnabled.value = true;
};

const fetchAppointments = async () => {
    scheduleLoading.value = true;
    scheduleError.value = '';

    try {
        const params = { from: scheduleDate.value, to: scheduleDate.value };
        const { data } = await axios.get('/api/appointments', { params });
        appointments.value = Array.isArray(data) ? data : [];
    } catch (error) {
        scheduleError.value = error?.response?.data?.message ?? 'Não foi possível carregar a agenda.';
        appointments.value = [];
    } finally {
        scheduleLoading.value = false;
    }
};

const handleScheduleDateChange = () => {
    fetchAppointments();
};

const changeScheduleDay = (offset) => {
    scheduleDate.value = addDays(scheduleDate.value, offset);
    fetchAppointments();
};

const sanitizeAppointmentPayload = () => ({
    patient_id: appointmentForm.patientId ? Number(appointmentForm.patientId) : null,
    start_at: fromLocalInputToIso(appointmentForm.startAt),
    end_at: fromLocalInputToIso(appointmentForm.endAt),
    status: appointmentForm.status,
    type: appointmentForm.type || null,
    price: appointmentForm.price ? Number(appointmentForm.price) : null,
    paid_at: fromLocalInputToIso(appointmentForm.paidAt),
});

const submitAppointment = async () => {
    clearAppointmentErrors();
    appointmentMessage.value = '';
    appointmentSubmitting.value = true;

    const payload = sanitizeAppointmentPayload();

    try {
        if (!payload.patient_id) {
            appointmentErrors.patientId = 'Selecione um paciente.';
            throw new Error('Paciente obrigatório');
        }

        if (editingAppointment.value) {
            await axios.put(`/api/appointments/${editingAppointment.value.id}`, payload);
        } else {
            await axios.post('/api/appointments', payload);
        }

        await fetchAppointments();
        closeAppointmentModal();
    } catch (error) {
        if (error?.response?.status === 422) {
            const errors = error.response.data.errors ?? {};
            let hasFieldErrors = false;
            Object.entries(errors).forEach(([field, messages]) => {
                if (field === 'patient_id') {
                    appointmentErrors.patientId = messages[0];
                    hasFieldErrors = true;
                } else if (field === 'start_at') {
                    appointmentErrors.startAt = messages[0];
                    hasFieldErrors = true;
                } else if (field === 'end_at') {
                    appointmentErrors.endAt = messages[0];
                    hasFieldErrors = true;
                } else if (field in appointmentErrors) {
                    appointmentErrors[field] = messages[0];
                    hasFieldErrors = true;
                }
            });
            if (hasFieldErrors) {
                appointmentMessage.value = 'Corrija os campos destacados e tente novamente.';
            } else {
                appointmentMessage.value = error?.response?.data?.message ?? 'Não foi possível salvar o agendamento.';
            }
        } else if (error instanceof Error && error.message === 'Paciente obrigatório') {
            appointmentMessage.value = 'Selecione um paciente para continuar.';
        } else {
            appointmentMessage.value = error?.response?.data?.message ?? 'Erro ao salvar o agendamento.';
        }
    } finally {
        appointmentSubmitting.value = false;
    }
};

const fetchPatientOptions = async (search = '') => {
    patientOptionsLoading.value = true;

    try {
        const params = { status: 'active' };
        if (search?.trim()) {
            params.q = search.trim();
        }

        const { data } = await axios.get('/api/patients', { params });
        const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
        patientOptions.value = list;
    } catch {
        // keep previous list
    } finally {
        patientOptionsLoading.value = false;
    }
};

const handlePatientSearchInput = () => {
    if (patientSearchTimeout) {
        clearTimeout(patientSearchTimeout);
    }

    patientSearchTimeout = setTimeout(() => {
        fetchPatientOptions(appointmentPatientSearch.value);
    }, 400);
};

const performAppointmentAction = async (appointment, action) => {
    if (!appointment?.id) return;
    if (action === 'cancel') {
        const confirmed = window.confirm('Deseja cancelar este agendamento?');
        if (!confirmed) return;
    } else if (action === 'missed') {
        const confirmed = window.confirm('Confirmar ausência do paciente?');
        if (!confirmed) return;
    }

    appointmentActionLoading.id = appointment.id;
    appointmentActionLoading.action = action;

    try {
        let endpoint = '';
        if (action === 'done') {
            endpoint = `/api/appointments/${appointment.id}/mark-done`;
        } else if (action === 'missed') {
            endpoint = `/api/appointments/${appointment.id}/mark-missed`;
        } else {
            endpoint = `/api/appointments/${appointment.id}/cancel`;
        }

        await axios.post(endpoint);
        await fetchAppointments();
    } catch (error) {
        window.alert(error?.response?.data?.message ?? 'Não foi possível atualizar o agendamento.');
    } finally {
        appointmentActionLoading.id = null;
        appointmentActionLoading.action = '';
    }
};

const fetchProfile = async () => {
    try {
        const { data } = await axios.get('/api/psychologist/profile');
        const psychologist = data?.psychologist ?? data ?? {};
        sessionDuration.value = psychologist.session_duration ?? sessionDuration.value;
        if (auth.user) {
            auth.user = { ...auth.user, psychologist };
        }
    } catch {
        // ignore, fallback to auth data
    }
};

watch(
    () => appointmentForm.startAt,
    (start) => {
        if (!start || !defaultSessionMinutes.value || !autoFillEndEnabled.value) return;
        appointmentForm.endAt = addMinutesToLocalInput(start, defaultSessionMinutes.value);
    }
);

onMounted(() => {
    fetchProfile();
    fetchAppointments();
});
</script>

<template>
    <div class="mx-auto min-h-screen max-w-5xl px-4 py-10">
        <header class="mb-8 flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-white px-6 py-4 shadow">
            <div>
                <p class="text-sm font-medium text-slate-500">Agenda</p>
                <h1 class="text-2xl font-semibold text-slate-900">Organize seus atendimentos</h1>
                <p class="text-sm text-slate-500">Controle horários, marque faltas e mantenha os dias em ordem.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <RouterLink
                    :to="{ name: 'home' }"
                    class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                >
                    <svg class="-ms-1 me-2 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
                    </svg>
                    Dashboard
                </RouterLink>
            </div>
        </header>

        <section class="rounded-2xl bg-white p-6 shadow">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-500">Agenda</p>
                    <h1 class="text-2xl font-semibold text-slate-900 capitalize">{{ scheduleDayLabel }}</h1>
                    <p class="text-sm text-slate-500">Visualize e organize os atendimentos do dia.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                        type="button"
                        @click="changeScheduleDay(-1)"
                    >
                        Dia anterior
                    </button>
                    <input
                        v-model="scheduleDate"
                        class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        type="date"
                        @change="handleScheduleDateChange"
                    />
                    <button
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                        type="button"
                        @click="changeScheduleDay(1)"
                    >
                        Próximo dia
                    </button>
                </div>
                <div class="ms-auto">
                    <button
                        class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300"
                        type="button"
                        @click="openCreateAppointment"
                    >
                        <svg class="-ms-1 me-2 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                        </svg>
                        Novo agendamento
                    </button>
                </div>
            </div>

            <div v-if="scheduleError" class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p>{{ scheduleError }}</p>
                    <button
                        class="rounded-xl border border-red-200 px-4 py-2 text-xs font-semibold text-red-600 transition hover:border-red-300 hover:text-red-800"
                        type="button"
                        @click="fetchAppointments"
                    >
                        Tentar novamente
                    </button>
                </div>
            </div>

            <div v-else class="mt-6">
                <div v-if="scheduleLoading" class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-12 text-center text-sm text-slate-500">
                    Carregando agenda...
                </div>

                <div v-else-if="appointmentsEmpty" class="rounded-2xl border border-dashed border-slate-200 px-6 py-16 text-center text-sm text-slate-500">
                    Nenhum agendamento para este dia.
                    <div class="mt-4">
                        <button
                            class="inline-flex items-center rounded-xl border border-blue-100 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:border-blue-200 hover:bg-blue-100"
                            type="button"
                            @click="openCreateAppointment"
                        >
                            Agendar atendimento
                        </button>
                    </div>
                </div>

                <ul v-else class="space-y-4">
                    <li
                        v-for="appointment in appointments"
                        :key="appointment.id"
                        class="rounded-2xl border border-slate-100 p-4 shadow-sm hover:border-blue-100"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-500">
                                    {{ formatTimeLabel(appointment.start_at) }} - {{ formatTimeLabel(appointment.end_at) }}
                                </p>
                                <p class="text-lg font-semibold text-slate-900">
                                    {{ appointment.patient?.name ?? 'Paciente removido' }}
                                </p>
                                <p class="text-sm text-slate-500">
                                    {{
                                        appointmentTypeOptions.find((option) => option.value === appointment.type)?.label ??
                                        'Tipo não informado'
                                    }}
                                </p>
                                <p v-if="appointment.meeting_url" class="mt-1">
                                    <a
                                        :href="appointment.meeting_url"
                                        class="inline-flex items-center gap-1 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 transition hover:border-blue-200 hover:bg-blue-100"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10v11h11" />
                                        </svg>
                                        Abrir link da sessão
                                    </a>
                                </p>
                                <p v-if="formatCurrency(appointment.price)" class="text-sm text-slate-500">
                                    {{ formatCurrency(appointment.price) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="appointmentStatusStyles[appointment.status] ?? 'bg-slate-100 text-slate-600'"
                                >
                                    {{
                                        appointmentStatusOptions.find((option) => option.value === appointment.status)?.label ??
                                        'Status'
                                    }}
                                </span>
                                <div class="mt-3 flex flex-wrap justify-end gap-2 text-xs font-semibold">
                                    <button
                                        class="rounded-xl border border-slate-200 px-3 py-1 text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                                        type="button"
                                        @click="openEditAppointment(appointment)"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        v-if="appointment.status !== 'done' && appointment.status !== 'canceled'"
                                        class="rounded-xl border border-emerald-200 px-3 py-1 text-emerald-700 transition hover:border-emerald-300 hover:text-emerald-900 disabled:cursor-not-allowed disabled:opacity-60"
                                        type="button"
                                        :disabled="appointmentActionLoading.id === appointment.id && appointmentActionLoading.action === 'done'"
                                        @click="performAppointmentAction(appointment, 'done')"
                                    >
                                        Concluir
                                    </button>
                                    <button
                                        v-if="appointment.status === 'scheduled'"
                                        class="rounded-xl border border-amber-200 px-3 py-1 text-amber-700 transition hover:border-amber-300 hover:text-amber-900 disabled:cursor-not-allowed disabled:opacity-60"
                                        type="button"
                                        :disabled="appointmentActionLoading.id === appointment.id && appointmentActionLoading.action === 'missed'"
                                        @click="performAppointmentAction(appointment, 'missed')"
                                    >
                                        Faltou
                                    </button>
                                    <button
                                        v-if="appointment.status !== 'canceled'"
                                        class="rounded-xl border border-red-200 px-3 py-1 text-red-600 transition hover:border-red-300 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-60"
                                        type="button"
                                        :disabled="appointmentActionLoading.id === appointment.id && appointmentActionLoading.action === 'cancel'"
                                        @click="performAppointmentAction(appointment, 'cancel')"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <div
            v-if="appointmentModalOpen"
            class="fixed inset-0 z-20 flex items-start justify-center bg-slate-900/40 px-4 py-10 backdrop-blur-sm"
            @click.self="closeAppointmentModal"
        >
            <div class="w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">{{ appointmentModalTitle }}</h2>
                        <p class="text-sm text-slate-500">Preencha os campos para organizar a sua agenda.</p>
                    </div>
                    <button
                        class="rounded-full border border-slate-200 p-2 text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                        type="button"
                        @click="closeAppointmentModal"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M6 18 18 6" />
                        </svg>
                    </button>
                </div>

                <form class="space-y-5" @submit.prevent="submitAppointment">
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="appointment-patient">Paciente</label>
                        <div class="mt-1 flex flex-col gap-3 md:flex-row">
                            <div class="flex-1">
                                <input
                                    id="appointment-patient"
                                    v-model="appointmentPatientSearch"
                                    class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                    placeholder="Buscar paciente pelo nome..."
                                    type="search"
                                    @input="handlePatientSearchInput"
                                />
                                <p class="mt-1 text-xs text-slate-500">Digite para filtrar e depois selecione abaixo.</p>
                            </div>
                            <div class="md:w-56">
                                <select
                                    v-model="appointmentForm.patientId"
                                    class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                    required
                                >
                                    <option value="" disabled>Selecione o paciente</option>
                                    <option v-for="patient in patientOptions" :key="patient.id" :value="patient.id">
                                        {{ patient.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <p v-if="appointmentErrors.patientId" class="mt-1 text-xs text-red-600">{{ appointmentErrors.patientId }}</p>
                        <p v-if="patientOptionsLoading" class="mt-1 text-xs text-slate-500">Carregando pacientes...</p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="appointment-start">Início</label>
                            <input
                                id="appointment-start"
                                v-model="appointmentForm.startAt"
                                class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                type="datetime-local"
                                required
                            />
                            <p v-if="appointmentErrors.startAt" class="mt-1 text-xs text-red-600">{{ appointmentErrors.startAt }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="appointment-end">Fim</label>
                            <input
                                id="appointment-end"
                                v-model="appointmentForm.endAt"
                                class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                type="datetime-local"
                                required
                            />
                            <p v-if="appointmentErrors.endAt" class="mt-1 text-xs text-red-600">{{ appointmentErrors.endAt }}</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="appointment-status">Status</label>
                            <select
                                id="appointment-status"
                                v-model="appointmentForm.status"
                                class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            >
                                <option v-for="option in appointmentStatusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p v-if="appointmentErrors.status" class="mt-1 text-xs text-red-600">{{ appointmentErrors.status }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="appointment-type">Tipo</label>
                            <select
                                id="appointment-type"
                                v-model="appointmentForm.type"
                                class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            >
                                <option v-for="option in appointmentTypeOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p v-if="appointmentErrors.type" class="mt-1 text-xs text-red-600">{{ appointmentErrors.type }}</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="appointment-price">Valor</label>
                            <input
                                id="appointment-price"
                                v-model="appointmentForm.price"
                                class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-900 shadow-inner"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="Valor definido pelo paciente"
                                disabled
                            />
                            <p
                                class="mt-1 text-xs"
                                :class="selectedPatientHasFee ? 'text-slate-500' : 'text-amber-600'"
                            >
                                {{ selectedPatientFeeDescription }}
                            </p>
                            <p v-if="appointmentErrors.price" class="mt-1 text-xs text-red-600">{{ appointmentErrors.price }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="appointment-paid">Pago em</label>
                            <input
                                id="appointment-paid"
                                v-model="appointmentForm.paidAt"
                                class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                type="datetime-local"
                            />
                            <p v-if="appointmentErrors.paidAt" class="mt-1 text-xs text-red-600">{{ appointmentErrors.paidAt }}</p>
                        </div>
                    </div>

                    <p v-if="appointmentMessage" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ appointmentMessage }}
                    </p>

                    <div class="flex justify-end gap-3">
                        <button
                            class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                            type="button"
                            @click="closeAppointmentModal"
                        >
                            Cancelar
                        </button>
                        <button
                            class="inline-flex items-center rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300 disabled:cursor-not-allowed disabled:bg-blue-300"
                            type="submit"
                            :disabled="appointmentSubmitting"
                        >
                            <svg v-if="appointmentSubmitting" class="-ms-1 me-2 size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            {{ appointmentSubmitLabel }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
