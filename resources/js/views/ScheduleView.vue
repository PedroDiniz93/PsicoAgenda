<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const auth = useAuthStore();

const getWeekStart = (date) => {
    const cloned = new Date(date);
    const day = cloned.getDay(); // 0 (Sun) - 6 (Sat)
    const diff = (day + 6) % 7; // convert to Monday-based index
    cloned.setHours(0, 0, 0, 0);
    cloned.setDate(cloned.getDate() - diff);
    return cloned;
};

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

const normalizeWeekDate = (value) => {
    if (!value) return formatDate(getWeekStart(new Date()));
    const parsed = new Date(`${value}T00:00:00`);
    if (Number.isNaN(parsed.getTime())) {
        return formatDate(getWeekStart(new Date()));
    }
    return formatDate(getWeekStart(parsed));
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

const dividerColor = 'rgba(148, 163, 184, 0.35)';

const calendarConfig = {
    startHour: 7,
    endHour: 22,
    slotMinutes: 30,
    slotHeight: 50,
    minAppointmentHeight: 100,
};

const calendarTotalMinutes = (calendarConfig.endHour - calendarConfig.startHour) * 60;
const calendarTotalSlots = calendarTotalMinutes / calendarConfig.slotMinutes;
const hourLabelHeight = (60 / calendarConfig.slotMinutes) * calendarConfig.slotHeight;

const scheduleDate = ref(formatDate(getWeekStart(new Date())));
const scheduleLoading = ref(false);
const scheduleError = ref('');
const appointments = ref([]);
const autoFillEndEnabled = ref(true);
const nowTick = ref(Date.now());
let nowInterval = null;

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
    repeatUntil: '',
});

const recurrenceForm = reactive({
    enabled: false,
    until: '',
});

const recurrenceActionLoading = ref(false);

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

const appointmentStatusBadgeClasses = {
    recurrence: 'border-purple-200 bg-purple-100 text-purple-700',
    scheduled: 'border-blue-200 bg-blue-50 text-blue-700',
    done: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    missed: 'border-amber-200 bg-amber-50 text-amber-700',
    canceled: 'border-slate-200 bg-slate-100 text-slate-500',
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

const todayString = computed(() => formatDate(new Date(nowTick.value)));

const weekDays = computed(() => {
    const start = new Date(`${scheduleDate.value}T00:00:00`);
    return Array.from({ length: 7 }).map((_, index) => {
        const day = new Date(start);
        day.setDate(day.getDate() + index);
        const iso = formatDate(day);
        return {
            date: iso,
            label: new Intl.DateTimeFormat('pt-BR', {
                weekday: 'long',
                day: '2-digit',
                month: 'short',
            }).format(day),
            shortLabel: new Intl.DateTimeFormat('pt-BR', {
                weekday: 'short',
                day: '2-digit',
                month: '2-digit',
            }).format(day),
            dayNumber: String(day.getDate()).padStart(2, '0'),
            monthShort: new Intl.DateTimeFormat('pt-BR', { month: 'short' }).format(day),
            isToday: iso === todayString.value,
        };
    });
});

const scheduleWeekLabel = computed(() => {
    const start = new Date(`${scheduleDate.value}T00:00:00`);
    const end = new Date(start);
    end.setDate(end.getDate() + 6);
    const formatter = new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: 'long' });
    return `${formatter.format(start)} – ${formatter.format(end)}`;
});

const appointmentsByDay = computed(() => {
    const grouped = Object.fromEntries(weekDays.value.map((day) => [day.date, []]));
    appointments.value.forEach((appointment) => {
        const start = new Date(appointment.start_at ?? appointment.startAt);
        if (Number.isNaN(start.getTime())) {
            return;
        }
        const dateKey = formatDate(start);
        if (grouped[dateKey]) {
            grouped[dateKey].push(appointment);
        }
    });
    return grouped;
});

const appointmentsEmpty = computed(() => appointments.value.length === 0);
const calendarColumnHeight = computed(() => calendarTotalSlots * calendarConfig.slotHeight);
const calendarTimeLabels = computed(() => {
    const labels = [];
    for (let hour = calendarConfig.startHour; hour < calendarConfig.endHour; hour += 1) {
        const label = `${String(hour).padStart(2, '0')}:00`;
        labels.push(label);
    }
    return labels;
});
const calendarHourLines = computed(() =>
    Array.from({ length: calendarConfig.endHour - calendarConfig.startHour + 1 }, (_, index) => index * calendarConfig.slotHeight * 2)
);

const calendarDayAppointments = computed(() => {
    const columns = {};
    const slotHeight = calendarConfig.slotHeight;

    weekDays.value.forEach((day) => {
        const items = (appointmentsByDay.value[day.date] ?? []).map((appointment, index) => {
            const start = new Date(appointment.start_at ?? appointment.startAt);
            const end = new Date(appointment.end_at ?? appointment.endAt);
            if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
                return null;
            }

            const startMinutes = start.getHours() * 60 + start.getMinutes();
            const endMinutes = end.getHours() * 60 + end.getMinutes();
            const minutesFromStart = Math.max(0, startMinutes - calendarConfig.startHour * 60);
            const durationMinutes = Math.max(15, endMinutes - startMinutes);

            const top = (minutesFromStart / calendarConfig.slotMinutes) * slotHeight;
            const rawHeight = (durationMinutes / calendarConfig.slotMinutes) * slotHeight;
            const height = Math.max(calendarConfig.minAppointmentHeight, rawHeight);

            const statusKey = appointment.status ?? 'scheduled';
            const statusLabel =
                appointmentStatusOptions.find((option) => option.value === statusKey)?.label ?? 'Status';
            const isRecurring = Boolean(appointment.recurrence_id) && statusKey === 'scheduled';
            const badgeLabel = isRecurring ? 'Recorrente' : statusLabel;
            const badgeClass =
                appointmentStatusBadgeClasses[isRecurring ? 'recurrence' : statusKey] ??
                'border-slate-200 bg-slate-100 text-slate-600';
            const typeLabel =
                appointmentTypeOptions.find((option) => option.value === appointment.type)?.label ?? 'Sessão';

            return {
                appointment,
                top,
                height,
                offset: 0,
                statusKey,
                badgeLabel,
                badgeClass,
                typeLabel,
            };
        });

        columns[day.date] = items.filter(Boolean);
    });

    return columns;
});

const currentTimeIndicator = computed(() => {
    const indicatorDate = todayString.value;
    if (!weekDays.value.some((day) => day.date === indicatorDate)) {
        return null;
    }

    const now = new Date(nowTick.value);
    const minutes = now.getHours() * 60 + now.getMinutes();
    const dayStart = calendarConfig.startHour * 60;
    const dayEnd = calendarConfig.endHour * 60;

    if (minutes < dayStart || minutes > dayEnd) {
        return null;
    }

    const offset = ((minutes - dayStart) / calendarConfig.slotMinutes) * calendarConfig.slotHeight;

    return {
        date: indicatorDate,
        offset,
    };
});
const appointmentModalTitle = computed(() => (editingAppointment.value ? 'Editar agendamento' : 'Novo agendamento'));
const appointmentSubmitLabel = computed(() => (editingAppointment.value ? 'Salvar alterações' : 'Agendar'));
const editingRecurringAppointment = computed(() => Boolean(editingAppointment.value?.recurrence_id));
const recurrenceControlsEnabled = computed(() => !editingAppointment.value);
const appointmentStartDateOnly = computed(() => {
    if (!appointmentForm.startAt) return scheduleDate.value;
    const [date] = appointmentForm.startAt.split('T');
    return date || scheduleDate.value;
});
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
    recurrenceForm.enabled = false;
    recurrenceForm.until = '';
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
    recurrenceForm.enabled = false;
    recurrenceForm.until = '';
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
    recurrenceForm.enabled = false;
    recurrenceForm.until = '';
};

const fetchAppointments = async () => {
    scheduleLoading.value = true;
    scheduleError.value = '';

    try {
        const params = { from: scheduleDate.value, to: addDays(scheduleDate.value, 6) };
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
    scheduleDate.value = normalizeWeekDate(scheduleDate.value);
    fetchAppointments();
};

const changeWeek = (offset) => {
    scheduleDate.value = addDays(scheduleDate.value, offset * 7);
    fetchAppointments();
};

const goToToday = () => {
    scheduleDate.value = formatDate(getWeekStart(new Date()));
    fetchAppointments();
};

const sanitizeAppointmentPayload = () => {
    const payload = {
        patient_id: appointmentForm.patientId ? Number(appointmentForm.patientId) : null,
        start_at: fromLocalInputToIso(appointmentForm.startAt),
        end_at: fromLocalInputToIso(appointmentForm.endAt),
        status: appointmentForm.status,
        type: appointmentForm.type || null,
        price: appointmentForm.price ? Number(appointmentForm.price) : null,
        paid_at: fromLocalInputToIso(appointmentForm.paidAt),
    };

    if (recurrenceForm.enabled && !editingAppointment.value) {
        payload.repeat_weekly = true;
        payload.repeat_until = recurrenceForm.until || null;
    }

    return payload;
};

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
                } else if (field === 'repeat_until') {
                    appointmentErrors.repeatUntil = messages[0];
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

const stopRecurringSeries = async () => {
    if (!editingAppointment.value?.recurrence_id) return;
    const confirmed = window.confirm('Deseja encerrar esta recorrência? Novos agendamentos não serão gerados.');
    if (!confirmed) return;

    recurrenceActionLoading.value = true;
    appointmentMessage.value = '';

    try {
        await axios.delete(`/api/recurring-appointments/${editingAppointment.value.recurrence_id}`);
        appointmentMessage.value = 'Recorrência encerrada. Novos agendamentos não serão criados.';
        if (editingAppointment.value) {
            editingAppointment.value.recurrence_id = null;
            editingAppointment.value.recurrence = null;
        }
        await fetchAppointments();
    } catch (error) {
        appointmentMessage.value = error?.response?.data?.message ?? 'Não foi possível encerrar a recorrência.';
    } finally {
        recurrenceActionLoading.value = false;
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
    nowInterval = setInterval(() => {
        nowTick.value = Date.now();
    }, 60000);
});

onBeforeUnmount(() => {
    if (nowInterval) {
        clearInterval(nowInterval);
        nowInterval = null;
    }
});
</script>

<template>
    <div class="mx-auto min-h-screen max-w-7xl px-6 py-10">
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
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-6">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-500">Semana selecionada</p>
                    <h1 class="text-2xl font-semibold text-slate-900 capitalize">{{ scheduleWeekLabel }}</h1>
                    <p class="text-sm text-slate-500">Visualize e organize os atendimentos da semana.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 lg:flex-none">
                    <button
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                        type="button"
                        @click="changeWeek(-1)"
                    >
                        Semana anterior
                    </button>
                    <button
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                        type="button"
                        @click="goToToday"
                    >
                        Esta semana
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
                        @click="changeWeek(1)"
                    >
                        Próxima semana
                    </button>
                </div>
                <div class="lg:ms-auto">
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

                <div v-else>
                    <div class="overflow-x-auto">
                        <div class="min-w-[1200px] rounded-2xl border border-slate-100">
                            <div class="grid grid-cols-[80px_repeat(7,minmax(0,1fr))] border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <div class="px-2 py-3 text-center">Horário</div>
                                <div
                                    v-for="day in weekDays"
                                    :key="day.date"
                                    class="px-4 py-3 text-center transition"
                                    :class="day.isToday ? 'bg-blue-50/40 rounded-t-2xl text-blue-600' : ''"
                                >
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        {{ day.shortLabel }}
                                    </p>
                                    <p class="text-lg font-semibold text-slate-900">
                                        {{ day.dayNumber }}
                                    </p>
                                    <p class="text-xs text-slate-400">{{ day.monthShort }}</p>
                                    <span
                                        v-if="day.isToday"
                                        class="mt-1 inline-flex items-center justify-center rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700"
                                    >
                                        Hoje
                                    </span>
                                </div>
                            </div>
                            <div class="grid grid-cols-[80px_repeat(7,minmax(0,1fr))]">
                                <div class="border-r border-slate-100 bg-white">
                                    <div
                                        v-for="label in calendarTimeLabels"
                                        :key="label"
                                        class="flex items-start justify-end px-2 text-xs text-slate-400"
                                        :style="{ height: `${hourLabelHeight}px` }"
                                    >
                                        <span class="-mt-2">{{ label }}</span>
                                    </div>
                                </div>
                                <div
                                    v-for="day in weekDays"
                                    :key="day.date"
                                    class="relative border-l border-slate-100 transition"
                                    :class="day.isToday ? 'bg-blue-50/20' : 'bg-white hover:bg-slate-50/30'"
                                    :style="{ height: `${calendarColumnHeight}px` }"
                                >
                                    <div class="absolute inset-0 pointer-events-none">
                                        <div
                                            v-for="(line, index) in calendarHourLines"
                                            :key="index"
                                            class="absolute inset-x-0 border-t"
                                            :style="{ top: `${line}px`, borderColor: dividerColor }"
                                        ></div>
                                    </div>

                                    <div class="relative h-full">
                                        <div class="pointer-events-none absolute inset-y-0 left-1 z-10 w-px bg-slate-200/70"></div>
                                        <div class="pointer-events-none absolute inset-y-0 right-1 z-10 w-px bg-slate-200/70"></div>
                                        <div
                                            v-if="currentTimeIndicator && currentTimeIndicator.date === day.date"
                                            class="pointer-events-none absolute inset-x-2 z-10 flex items-center gap-2 text-[10px] font-semibold text-red-500"
                                            :style="{ top: `${currentTimeIndicator.offset}px` }"
                                        >
                                            <div class="h-px flex-1 bg-red-400"></div>
                                            <span class="rounded-full bg-red-500/10 px-2 py-0.5">Agora</span>
                                        </div>
                                        <div
                                            v-for="item in calendarDayAppointments[day.date] ?? []"
                                            :key="item.appointment.id"
                                            class="group absolute z-20 w-[94%] cursor-pointer rounded-2xl border border-slate-200 bg-blue-50/90 px-3 py-2 text-left text-xs text-slate-700 shadow hover:border-blue-300 hover:bg-blue-100 focus:outline-none overflow-hidden"
                                            :class="{ 'bg-purple-50 text-purple-900 border-purple-200': item.appointment.recurrence_id }"
                                            :style="{
                                                top: `${item.top}px`,
                                                height: `${item.height}px`,
                                                left: '3%',
                                            }"
                                            role="button"
                                            tabindex="0"
                                            @click.stop="openEditAppointment(item.appointment)"
                                            @keydown.enter.prevent="openEditAppointment(item.appointment)"
                                        >
                                            <div class="flex h-full flex-col justify-between overflow-hidden">
                                                <div class="space-y-1">
                                                    <p class="text-[11px] font-semibold text-slate-500 group-[.bg-purple-50\\/90]:text-purple-700">
                                                        {{ formatTimeLabel(item.appointment.start_at) }} - {{ formatTimeLabel(item.appointment.end_at) }}
                                                    </p>
                                                    <p class="text-sm font-semibold text-slate-800 group-[.bg-purple-50\\/90]:text-purple-900 truncate">
                                                        {{ item.appointment.patient?.name ?? 'Paciente removido' }}
                                                    </p>
                                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 group-[.bg-purple-50\\/90]:text-purple-600">
                                                        {{ item.typeLabel }}
                                                    </p>
                                                </div>
                                                <span
                                                    class="mt-1 inline-flex max-w-full items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                                    :class="item.badgeClass"
                                                >
                                                    <span class="truncate">{{ item.badgeLabel }}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="appointmentsEmpty"
                        class="mt-4 rounded-2xl border border-dashed border-slate-200 px-6 py-6 text-center text-sm text-slate-500"
                    >
                        Nenhum agendamento encontrado para esta semana.
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
                </div>
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

                    <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Repetir semanalmente</p>
                                <p class="text-xs text-slate-500">Cria automaticamente este horário nas próximas semanas.</p>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input
                                    v-model="recurrenceForm.enabled"
                                    :disabled="!recurrenceControlsEnabled"
                                    class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 disabled:cursor-not-allowed"
                                    type="checkbox"
                                />
                                <span>{{ recurrenceControlsEnabled ? 'Ativar' : 'Disponível ao criar' }}</span>
                            </label>
                        </div>

                        <p
                            v-if="!recurrenceControlsEnabled && !editingRecurringAppointment"
                            class="mt-2 text-xs text-slate-500"
                        >
                            Para configurar uma recorrência, crie um novo agendamento com o horário desejado.
                        </p>

                        <div
                            v-if="recurrenceForm.enabled && recurrenceControlsEnabled"
                            class="mt-4 grid gap-4 md:max-w-md md:grid-cols-2"
                        >
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700" for="recurrence-until">Repetir até</label>
                                <input
                                    id="recurrence-until"
                                    v-model="recurrenceForm.until"
                                    class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                    type="date"
                                    :min="appointmentStartDateOnly"
                                />
                                <p v-if="appointmentErrors.repeatUntil" class="mt-1 text-xs text-red-600">
                                    {{ appointmentErrors.repeatUntil }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">Deixe em branco para manter sem data final.</p>
                            </div>
                        </div>

                        <div
                            v-if="editingRecurringAppointment"
                            class="mt-4 rounded-2xl border border-purple-200 bg-purple-50/70 p-4 text-sm text-purple-900"
                        >
                            <p>Este agendamento faz parte de uma recorrência semanal.</p>
                            <button
                                class="mt-3 inline-flex items-center justify-center rounded-xl border border-purple-200 px-4 py-2 text-xs font-semibold text-purple-800 transition hover:border-purple-300 hover:bg-purple-100 disabled:cursor-not-allowed disabled:opacity-60"
                                type="button"
                                :disabled="recurrenceActionLoading"
                                @click="stopRecurringSeries"
                            >
                                {{ recurrenceActionLoading ? 'Encerrando...' : 'Encerrar recorrência' }}
                            </button>
                        </div>
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
