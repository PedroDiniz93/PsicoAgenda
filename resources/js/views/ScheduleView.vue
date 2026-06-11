<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import AppIcon from '../components/base/AppIcon.vue';

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
    slotHeight: 52.5,
    minAppointmentHeight: 105,
};

const calendarTotalMinutes = (calendarConfig.endHour - calendarConfig.startHour) * 60;
const calendarTotalSlots = calendarTotalMinutes / calendarConfig.slotMinutes;
const hourLabelHeight = (60 / calendarConfig.slotMinutes) * calendarConfig.slotHeight;

const scheduleDate = ref(formatDate(getWeekStart(new Date())));
const scheduleCategory = ref('agenda');
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

const scheduleCategories = [
    { id: 'agenda', label: 'Agenda' },
    { id: 'availability', label: 'Disponibilidade' },
    { id: 'blocks', label: 'Bloqueios e férias' },
];

const appointmentPatientSearch = ref('');
const patientOptions = ref([]);
const patientOptionsLoading = ref(false);
let patientSearchTimeout = null;

const appointmentActionLoading = reactive({ id: null, action: '' });

const weekdayOptions = [
    { value: 1, label: 'Segunda' },
    { value: 2, label: 'Terça' },
    { value: 3, label: 'Quarta' },
    { value: 4, label: 'Quinta' },
    { value: 5, label: 'Sexta' },
    { value: 6, label: 'Sábado' },
    { value: 0, label: 'Domingo' },
];

const defaultAvailabilityRules = () => weekdayOptions.map((day) => ({
    weekday: day.value,
    enabled: false,
    startTime: '08:00',
    endTime: '18:00',
}));

const availabilityLoading = ref(false);
const availabilitySaving = ref(false);
const blockSaving = ref(false);
const availabilityMessage = ref('');
const availabilityMessageType = ref('success');
const availabilityRules = ref(defaultAvailabilityRules());
const scheduleBlocks = ref([]);
const dailyAppointmentLimit = ref('');
const blockForm = reactive({
    type: 'block',
    startsAt: '',
    endsAt: '',
    reason: '',
});

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
const enabledAvailabilityRulesCount = computed(() =>
    availabilityRules.value.filter((rule) => rule.enabled).length
);
const appointmentStatusLabel = (status) =>
    appointmentStatusOptions.find((option) => option.value === status)?.label ?? 'Agendado';
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
            const isPaid = Boolean(appointment.paid_at ?? appointment.paidAt);
            const badgeLabel = isRecurring ? 'Recorrente' : statusLabel;
            const badgeClass =
                appointmentStatusBadgeClasses[isRecurring ? 'recurrence' : statusKey] ??
                'border-slate-200 bg-slate-100 text-slate-600';
            const typeLabel =
                appointmentTypeOptions.find((option) => option.value === appointment.type)?.label ?? 'Sessão';

            const meetingUrl = appointment.meeting_url ?? appointment.meetingUrl ?? '';

            return {
                appointment,
                top,
                height,
                offset: 0,
                statusKey,
                badgeLabel,
                badgeClass,
                isPaid,
                typeLabel,
                meetingUrl,
            };
        });

        columns[day.date] = items.filter(Boolean);
    });

    return columns;
});

const timeStringToMinutes = (value) => {
    const [hours = '0', minutes = '0'] = String(value ?? '').split(':');
    return Number(hours) * 60 + Number(minutes);
};

const minutesToCalendarBox = (startMinutes, endMinutes) => {
    const dayStart = calendarConfig.startHour * 60;
    const dayEnd = calendarConfig.endHour * 60;
    const clippedStart = Math.max(dayStart, startMinutes);
    const clippedEnd = Math.min(dayEnd, endMinutes);

    if (clippedEnd <= clippedStart) {
        return null;
    }

    return {
        top: ((clippedStart - dayStart) / calendarConfig.slotMinutes) * calendarConfig.slotHeight,
        height: ((clippedEnd - clippedStart) / calendarConfig.slotMinutes) * calendarConfig.slotHeight,
    };
};

const calendarDayAvailability = computed(() => {
    const grouped = Object.fromEntries(weekDays.value.map((day) => [day.date, []]));

    weekDays.value.forEach((day) => {
        const weekday = new Date(`${day.date}T00:00:00`).getDay();
        availabilityRules.value
            .filter((rule) => rule.enabled && Number(rule.weekday) === weekday)
            .forEach((rule) => {
                const box = minutesToCalendarBox(timeStringToMinutes(rule.startTime), timeStringToMinutes(rule.endTime));
                if (box) {
                    grouped[day.date].push({ ...box, label: `${rule.startTime} - ${rule.endTime}` });
                }
            });
    });

    return grouped;
});

const calendarDayBlocks = computed(() => {
    const grouped = Object.fromEntries(weekDays.value.map((day) => [day.date, []]));

    weekDays.value.forEach((day) => {
        const dayStart = new Date(`${day.date}T00:00:00`);
        const dayEnd = new Date(`${day.date}T23:59:59`);

        scheduleBlocks.value.forEach((block) => {
            const startsAt = new Date(block.starts_at);
            const endsAt = new Date(block.ends_at);
            if (Number.isNaN(startsAt.getTime()) || Number.isNaN(endsAt.getTime())) return;
            if (startsAt > dayEnd || endsAt < dayStart) return;

            const visibleStart = startsAt < dayStart ? dayStart : startsAt;
            const visibleEnd = endsAt > dayEnd ? dayEnd : endsAt;
            const startMinutes = visibleStart.getHours() * 60 + visibleStart.getMinutes();
            const endMinutes = visibleEnd.getHours() * 60 + visibleEnd.getMinutes();
            const box = minutesToCalendarBox(startMinutes, endMinutes);
            if (box) {
                grouped[day.date].push({
                    ...box,
                    label: block.type === 'vacation' ? 'Férias' : 'Bloqueio',
                    reason: block.reason,
                    type: block.type,
                });
            }
        });
    });

    return grouped;
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

const applyAvailability = (payload = {}) => {
    const nextRules = defaultAvailabilityRules();
    (payload.rules ?? []).forEach((rule) => {
        const index = nextRules.findIndex((item) => Number(item.weekday) === Number(rule.weekday));
        if (index >= 0) {
            nextRules[index] = {
                weekday: Number(rule.weekday),
                enabled: Boolean(rule.is_active),
                startTime: rule.start_time ?? '08:00',
                endTime: rule.end_time ?? '18:00',
            };
        }
    });

    availabilityRules.value = nextRules;
    scheduleBlocks.value = payload.blocks ?? [];
    dailyAppointmentLimit.value = payload.daily_appointment_limit ?? '';
};

const fetchAvailability = async () => {
    availabilityLoading.value = true;

    try {
        const params = { from: scheduleDate.value, to: addDays(scheduleDate.value, 6) };
        const { data } = await axios.get('/api/availability', { params });
        applyAvailability(data ?? {});
    } catch (error) {
        availabilityMessageType.value = 'error';
        availabilityMessage.value = error?.response?.data?.message ?? 'Não foi possível carregar a disponibilidade.';
    } finally {
        availabilityLoading.value = false;
    }
};

const saveAvailabilitySettings = async () => {
    availabilitySaving.value = true;
    availabilityMessage.value = '';

    try {
        const rules = availabilityRules.value
            .filter((rule) => rule.enabled)
            .map((rule) => ({
                weekday: rule.weekday,
                start_time: rule.startTime,
                end_time: rule.endTime,
                is_active: true,
            }));

        const { data } = await axios.put('/api/availability/settings', {
            daily_appointment_limit: dailyAppointmentLimit.value === '' ? null : Number(dailyAppointmentLimit.value),
            rules,
        });
        applyAvailability(data ?? {});
        availabilityMessageType.value = 'success';
        availabilityMessage.value = 'Disponibilidade salva.';
    } catch (error) {
        availabilityMessageType.value = 'error';
        availabilityMessage.value = error?.response?.data?.message ?? 'Não foi possível salvar a disponibilidade.';
    } finally {
        availabilitySaving.value = false;
    }
};

const resetBlockForm = () => {
    blockForm.type = 'block';
    blockForm.startsAt = '';
    blockForm.endsAt = '';
    blockForm.reason = '';
};

const createScheduleBlock = async () => {
    blockSaving.value = true;
    availabilityMessage.value = '';

    try {
        await axios.post('/api/availability/blocks', {
            type: blockForm.type,
            starts_at: fromLocalInputToIso(blockForm.startsAt),
            ends_at: fromLocalInputToIso(blockForm.endsAt),
            reason: blockForm.reason.trim() || null,
        });
        resetBlockForm();
        await fetchAvailability();
        availabilityMessageType.value = 'success';
        availabilityMessage.value = 'Bloqueio salvo.';
    } catch (error) {
        availabilityMessageType.value = 'error';
        availabilityMessage.value = error?.response?.data?.message ?? 'Não foi possível salvar o bloqueio.';
    } finally {
        blockSaving.value = false;
    }
};

const deleteScheduleBlock = async (block) => {
    if (!block?.id) return;
    const confirmed = window.confirm('Remover este bloqueio da agenda?');
    if (!confirmed) return;

    try {
        await axios.delete(`/api/availability/blocks/${block.id}`);
        await fetchAvailability();
    } catch (error) {
        availabilityMessageType.value = 'error';
        availabilityMessage.value = error?.response?.data?.message ?? 'Não foi possível remover o bloqueio.';
    }
};

const refreshSchedule = () => {
    fetchAppointments();
    fetchAvailability();
};

const blockTypeLabel = (type) => (type === 'vacation' ? 'Férias' : 'Bloqueio');
const formatDateTimeLabel = (value) => {
    if (!value) return '';
    try {
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(value));
    } catch {
        return value;
    }
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
    refreshSchedule();
};

const changeWeek = (offset) => {
    scheduleDate.value = addDays(scheduleDate.value, offset * 7);
    refreshSchedule();
};

const goToToday = () => {
    scheduleDate.value = formatDate(getWeekStart(new Date()));
    refreshSchedule();
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
    fetchAvailability();
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
    <div class="page-shell space-y-6">
        <header class="surface-panel p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="section-kicker">Agenda</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">Planejamento clínico semanal</h1>
                    <p class="mt-2 max-w-3xl text-sm text-[#58635f]">
                        Visualize sessões, organize disponibilidade e controle bloqueios em áreas separadas para manter o fluxo limpo.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <RouterLink :to="{ name: 'home' }" class="btn-secondary">
                        <AppIcon name="ChevronLeft" class="size-4" />
                        Dashboard
                    </RouterLink>
                    <button class="btn-primary" type="button" @click="openCreateAppointment">
                        <AppIcon name="CalendarPlus2" class="size-4" />
                        Novo agendamento
                    </button>
                </div>
            </div>
        </header>

        <section class="surface-panel space-y-6 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-[#58635f]">Semana selecionada</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900 capitalize">{{ scheduleWeekLabel }}</h2>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button class="btn-secondary" type="button" @click="changeWeek(-1)">
                        <AppIcon name="ChevronLeft" class="size-4" />
                        Semana anterior
                    </button>
                    <button class="btn-secondary" type="button" @click="goToToday">
                        <AppIcon name="CalendarCheck2" class="size-4" />
                        Esta semana
                    </button>
                    <input
                        v-model="scheduleDate"
                        class="field-input min-w-[11rem]"
                        type="date"
                        @change="handleScheduleDateChange"
                    />
                    <button class="btn-secondary" type="button" @click="changeWeek(1)">
                        Próxima semana
                        <AppIcon name="ChevronRight" class="size-4" />
                    </button>
                </div>
            </div>

            <nav class="flex flex-wrap gap-2 border-t border-[#ece6db] pt-4" aria-label="Categorias da agenda">
                <button
                    v-for="section in scheduleCategories"
                    :key="section.id"
                    class="tab-button"
                    :class="scheduleCategory === section.id ? 'tab-button--active' : ''"
                    type="button"
                    @click="scheduleCategory = section.id"
                >
                    <AppIcon
                        :name="section.id === 'agenda' ? 'CalendarDays' : section.id === 'availability' ? 'Clock3' : 'Ban'"
                        class="size-4"
                    />
                    {{ section.label }}
                </button>
            </nav>

            <section v-if="scheduleCategory === 'agenda'" class="space-y-4">
                <div class="grid gap-3 md:grid-cols-3">
                    <article class="surface-subtle px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#58635f]">Sessões na semana</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ appointments.length }}</p>
                    </article>
                    <article class="surface-subtle px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#58635f]">Dias com disponibilidade</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ enabledAvailabilityRulesCount }}</p>
                    </article>
                    <article class="surface-subtle px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#58635f]">Bloqueios na semana</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ scheduleBlocks.length }}</p>
                    </article>
                </div>

                <div v-if="scheduleError" class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p>{{ scheduleError }}</p>
                        <button class="btn-secondary text-red-700 hover:text-red-800" type="button" @click="fetchAppointments">
                            <AppIcon name="RefreshCcw" class="size-4" />
                            Tentar novamente
                        </button>
                    </div>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-if="scheduleLoading"
                        class="rounded-2xl border border-[#ece6db] bg-[#f8f5ef] px-4 py-12 text-center text-sm text-[#58635f]"
                    >
                        <span class="inline-flex items-center gap-2">
                            <AppIcon name="LoaderCircle" class="size-4 animate-spin" />
                            Carregando agenda...
                        </span>
                    </div>

                    <template v-else>
                        <div class="hidden overflow-x-auto lg:block">
                            <div class="min-w-[1080px] rounded-2xl border border-[#e7e1d6]">
                                <div class="grid grid-cols-[80px_repeat(7,minmax(0,1fr))] border-b border-[#ece6db] bg-[#f8f5ef] text-xs font-semibold uppercase tracking-wide text-[#58635f]">
                                    <div class="px-2 py-3 text-center">Horário</div>
                                    <div
                                        v-for="day in weekDays"
                                        :key="day.date"
                                        class="px-4 py-3 text-center transition"
                                        :class="day.isToday ? 'rounded-t-2xl bg-[#e7eee8] text-[#3f4f46]' : ''"
                                    >
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[#58635f]">
                                            {{ day.shortLabel }}
                                        </p>
                                        <p class="text-lg font-semibold text-slate-900">{{ day.dayNumber }}</p>
                                        <p class="text-xs text-slate-400">{{ day.monthShort }}</p>
                                        <span
                                            v-if="day.isToday"
                                            class="mt-1 inline-flex items-center justify-center rounded-full bg-[#dce6de] px-2 py-0.5 text-[10px] font-semibold text-[#3f4f46]"
                                        >
                                            Hoje
                                        </span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-[80px_repeat(7,minmax(0,1fr))]">
                                    <div class="border-r border-[#ece6db] bg-white">
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
                                        class="relative border-l border-[#ece6db] transition"
                                        :class="day.isToday ? 'bg-[#eef3ee]' : 'bg-white hover:bg-[#fcfaf6]'"
                                        :style="{ height: `${calendarColumnHeight}px` }"
                                    >
                                        <div class="pointer-events-none absolute inset-0">
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
                                                v-for="slot in calendarDayAvailability[day.date] ?? []"
                                                :key="`availability-${day.date}-${slot.label}`"
                                                class="pointer-events-none absolute inset-x-2 z-0 rounded-xl border border-[#c9d8cd] bg-[#eef4ef]/80"
                                                :style="{ top: `${slot.top}px`, height: `${slot.height}px` }"
                                            >
                                                <span class="absolute right-2 top-1 text-[10px] font-semibold text-[#4e6655]">Livre {{ slot.label }}</span>
                                            </div>
                                            <div
                                                v-for="block in calendarDayBlocks[day.date] ?? []"
                                                :key="`block-${day.date}-${block.label}-${block.top}`"
                                                class="pointer-events-none absolute inset-x-2 z-[15] rounded-xl border px-2 py-1 text-[10px] font-semibold"
                                                :class="block.type === 'vacation' ? 'border-[#e6c8cc] bg-[#faeff1]/90 text-[#7d4950]' : 'border-[#dfd5c3] bg-[#f9f4ea]/90 text-[#6e5939]'"
                                                :style="{ top: `${block.top}px`, height: `${block.height}px` }"
                                            >
                                                {{ block.label }}{{ block.reason ? ` · ${block.reason}` : '' }}
                                            </div>
                                            <div
                                                v-if="currentTimeIndicator && currentTimeIndicator.date === day.date"
                                                class="pointer-events-none absolute inset-x-2 z-10 flex items-center gap-2 text-[10px] font-semibold text-[#9a4f57]"
                                                :style="{ top: `${currentTimeIndicator.offset}px` }"
                                            >
                                                <div class="h-px flex-1 bg-[#9a4f57]/80"></div>
                                                <span class="rounded-full bg-[#9a4f57]/10 px-2 py-0.5">Agora</span>
                                            </div>
                                            <div
                                                v-for="item in calendarDayAppointments[day.date] ?? []"
                                                :key="item.appointment.id"
                                                class="group absolute z-20 w-[94%] cursor-pointer overflow-hidden rounded-2xl border border-[#d7d2c7] bg-[#f8f4ec] px-3 py-2 text-left text-xs text-[#39423e] shadow transition hover:border-[#bcb39f] hover:bg-[#f3ede2] focus:outline-none"
                                                :class="{ 'border-[#c7c0dc] bg-[#f1eef9] text-[#473f61]': item.appointment.recurrence_id }"
                                                :style="{ top: `${item.top}px`, height: `${item.height}px`, left: '3%' }"
                                                role="button"
                                                tabindex="0"
                                                @click.stop="openEditAppointment(item.appointment)"
                                                @keydown.enter.prevent="openEditAppointment(item.appointment)"
                                            >
                                                <div class="flex h-full flex-col justify-between overflow-hidden">
                                                    <div class="space-y-1">
                                                        <p class="text-[11px] font-semibold text-[#6f7a75]">
                                                            {{ formatTimeLabel(item.appointment.start_at) }} - {{ formatTimeLabel(item.appointment.end_at) }}
                                                        </p>
                                                        <p class="truncate text-sm font-semibold text-[#2d3531]">
                                                            {{ item.appointment.patient?.name ?? 'Paciente removido' }}
                                                        </p>
                                                        <div class="flex items-center justify-between gap-1">
                                                            <p class="text-[10px] uppercase tracking-wide text-[#89948f]">
                                                                {{ item.typeLabel }}
                                                            </p>
                                                            <a
                                                                v-if="item.meetingUrl"
                                                                :href="item.meetingUrl"
                                                                class="inline-flex items-center gap-1 rounded-full border border-[#c9d8cd] px-2 py-0.5 text-[10px] font-semibold text-[#4e6655] transition hover:border-[#b9ccbe] hover:bg-[#edf3ee] focus:outline-none"
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                title="Abrir link da sessão"
                                                                @click.stop
                                                                @keydown.enter.stop
                                                            >
                                                                <AppIcon name="Video" class="size-3" />
                                                                Meet
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <div class="mt-1 flex flex-wrap gap-1">
                                                        <span class="inline-flex max-w-full items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="item.badgeClass">
                                                            <span class="truncate">{{ item.badgeLabel }}</span>
                                                        </span>
                                                        <span
                                                            v-if="item.isPaid"
                                                            class="inline-flex items-center rounded-full border border-[#c9d8cd] bg-[#edf3ee] px-2 py-0.5 text-[10px] font-semibold text-[#4e6655]"
                                                        >
                                                            Pago
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 lg:hidden">
                            <article
                                v-for="day in weekDays"
                                :key="`mobile-${day.date}`"
                                class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[#58635f]">{{ day.label }}</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900">
                                            {{ (appointmentsByDay[day.date] ?? []).length }} sessão(ões)
                                        </p>
                                    </div>
                                    <span
                                        v-if="day.isToday"
                                        class="rounded-full bg-[#e7eee8] px-2.5 py-1 text-xs font-semibold text-[#3f4f46]"
                                    >
                                        Hoje
                                    </span>
                                </div>

                                <div v-if="(appointmentsByDay[day.date] ?? []).length" class="mt-3 space-y-2">
                                    <button
                                        v-for="appointment in appointmentsByDay[day.date] ?? []"
                                        :key="`mobile-appointment-${appointment.id}`"
                                        class="w-full rounded-xl border border-[#e7e1d6] bg-[#fcfaf6] px-3 py-2 text-left transition hover:border-[#c9c1b3]"
                                        type="button"
                                        @click="openEditAppointment(appointment)"
                                    >
                                        <p class="text-sm font-semibold text-slate-900">
                                            {{ appointment.patient?.name ?? 'Paciente removido' }}
                                        </p>
                                        <p class="mt-1 text-xs text-[#58635f]">
                                            {{ formatTimeLabel(appointment.start_at) }} - {{ formatTimeLabel(appointment.end_at) }}
                                        </p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span class="rounded-full border border-[#e2ddd3] px-2 py-0.5 text-[11px] font-semibold text-[#58635f]">
                                                {{ appointmentTypeOptions.find((option) => option.value === appointment.type)?.label ?? 'Sessão' }}
                                            </span>
                                            <span class="rounded-full border border-[#e2ddd3] px-2 py-0.5 text-[11px] font-semibold text-[#58635f]">
                                                {{ appointmentStatusLabel(appointment.status) }}
                                            </span>
                                        </div>
                                    </button>
                                </div>
                                <p v-else class="mt-3 rounded-xl border border-dashed border-[#e2ddd3] px-3 py-3 text-center text-sm text-[#58635f]">
                                    Sem agendamentos para este dia.
                                </p>
                            </article>
                        </div>

                        <div
                            v-if="appointmentsEmpty"
                            class="rounded-2xl border border-dashed border-[#d8d2c5] px-6 py-8 text-center text-sm text-[#58635f]"
                        >
                            Nenhum agendamento encontrado para esta semana.
                            <div class="mt-4">
                                <button class="btn-secondary" type="button" @click="openCreateAppointment">
                                    <AppIcon name="CalendarPlus2" class="size-4" />
                                    Agendar atendimento
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <section v-else-if="scheduleCategory === 'availability'" class="space-y-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-base font-semibold text-slate-950">Disponibilidade automática</p>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-[#58635f]">
                            Configure horários livres e limite diário. Novos agendamentos entram apenas nas janelas ativas.
                        </p>
                    </div>
                    <button class="btn-primary" type="button" :disabled="availabilitySaving" @click="saveAvailabilitySettings">
                        <AppIcon v-if="availabilitySaving" name="LoaderCircle" class="size-4 animate-spin" />
                        {{ availabilitySaving ? 'Salvando...' : 'Salvar disponibilidade' }}
                    </button>
                </div>

                <div class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-4">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">Horários livres semanais</p>
                        <label class="flex items-center gap-2 text-sm font-medium text-[#58635f]">
                            Limite por dia
                            <input
                                v-model="dailyAppointmentLimit"
                                class="field-input w-20"
                                min="0"
                                max="40"
                                type="number"
                            />
                        </label>
                    </div>

                    <div class="grid gap-2 md:grid-cols-2">
                        <div
                            v-for="rule in availabilityRules"
                            :key="rule.weekday"
                            class="grid grid-cols-[1fr_auto_auto] items-center gap-2 rounded-xl border border-[#efeadf] px-3 py-2"
                        >
                            <label class="flex items-center gap-2 text-sm font-semibold text-[#39423e]">
                                <input v-model="rule.enabled" class="size-4 rounded border-slate-300 text-[#3f4f46] focus:ring-[#3f4f46]" type="checkbox" />
                                {{ weekdayOptions.find((day) => day.value === rule.weekday)?.label }}
                            </label>
                            <input v-model="rule.startTime" class="field-input w-24 disabled:bg-slate-100" type="time" :disabled="!rule.enabled" />
                            <input v-model="rule.endTime" class="field-input w-24 disabled:bg-slate-100" type="time" :disabled="!rule.enabled" />
                        </div>
                    </div>
                </div>
            </section>

            <section v-else class="space-y-4">
                <div>
                    <p class="text-base font-semibold text-slate-950">Bloqueios e férias</p>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-[#58635f]">
                        Registre indisponibilidades específicas para manter a agenda realista e evitar conflitos.
                    </p>
                </div>

                <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
                    <div class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-4">
                        <form class="space-y-3" @submit.prevent="createScheduleBlock">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="space-y-1">
                                    <span class="text-xs font-semibold text-[#58635f]">Tipo</span>
                                    <select v-model="blockForm.type" class="field-input">
                                        <option value="block">Bloqueio</option>
                                        <option value="vacation">Férias</option>
                                    </select>
                                </label>
                                <label class="space-y-1">
                                    <span class="text-xs font-semibold text-[#58635f]">Motivo</span>
                                    <input v-model="blockForm.reason" class="field-input" placeholder="Ex.: férias" />
                                </label>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="space-y-1">
                                    <span class="text-xs font-semibold text-[#58635f]">Início</span>
                                    <input v-model="blockForm.startsAt" class="field-input" required type="datetime-local" />
                                </label>
                                <label class="space-y-1">
                                    <span class="text-xs font-semibold text-[#58635f]">Fim</span>
                                    <input v-model="blockForm.endsAt" class="field-input" required type="datetime-local" />
                                </label>
                            </div>
                            <button class="btn-primary w-full" type="submit" :disabled="blockSaving">
                                <AppIcon v-if="blockSaving" name="LoaderCircle" class="size-4 animate-spin" />
                                {{ blockSaving ? 'Salvando...' : 'Adicionar bloqueio' }}
                            </button>
                        </form>
                    </div>

                    <div class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-4">
                        <p class="text-sm font-semibold text-slate-900">Bloqueios da semana</p>
                        <div class="mt-4 space-y-2">
                            <div v-if="availabilityLoading" class="rounded-xl bg-[#f8f5ef] px-3 py-2 text-sm text-[#58635f]">
                                Carregando disponibilidade...
                            </div>
                            <div
                                v-for="block in scheduleBlocks"
                                :key="block.id"
                                class="flex items-start justify-between gap-3 rounded-xl border border-[#efeadf] px-3 py-2 text-sm"
                            >
                                <div>
                                    <p class="font-semibold text-slate-800">
                                        {{ blockTypeLabel(block.type) }}{{ block.reason ? ` · ${block.reason}` : '' }}
                                    </p>
                                    <p class="text-xs text-[#58635f]">
                                        {{ formatDateTimeLabel(block.starts_at) }} - {{ formatDateTimeLabel(block.ends_at) }}
                                    </p>
                                </div>
                                <button class="text-xs font-semibold text-[#9a4f57] hover:text-[#87464d]" type="button" @click="deleteScheduleBlock(block)">
                                    Remover
                                </button>
                            </div>
                            <p
                                v-if="!availabilityLoading && scheduleBlocks.length === 0"
                                class="rounded-xl border border-dashed border-[#d8d2c5] px-3 py-3 text-center text-sm text-[#58635f]"
                            >
                                Nenhum bloqueio nesta semana.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <p
                v-if="availabilityMessage && scheduleCategory !== 'agenda'"
                class="rounded-xl border px-4 py-3 text-sm"
                :class="availabilityMessageType === 'success' ? 'border-[#c9d8cd] bg-[#eff4f0] text-[#365341]' : 'border-[#e6c8cc] bg-[#faeff1] text-[#7d4950]'"
            >
                {{ availabilityMessage }}
            </p>
        </section>

        <div
            v-if="appointmentModalOpen"
            class="fixed inset-0 z-20 flex items-start justify-center bg-slate-900/40 px-4 py-10 backdrop-blur-sm"
            @click.self="closeAppointmentModal"
        >
            <div class="w-full max-w-3xl rounded-3xl border border-[#e2ddd3] bg-white p-6 shadow-2xl">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">{{ appointmentModalTitle }}</h2>
                        <p class="text-sm text-[#58635f]">Preencha os campos para organizar a sua agenda.</p>
                    </div>
                    <button
                        class="rounded-full border border-[#e2ddd3] p-2 text-[#58635f] transition hover:border-[#c9c1b3] hover:text-[#1f2522]"
                        type="button"
                        @click="closeAppointmentModal"
                    >
                        <AppIcon name="X" class="size-5" />
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
                                    class="field-input"
                                    placeholder="Buscar paciente pelo nome..."
                                    type="search"
                                    @input="handlePatientSearchInput"
                                />
                                <p class="mt-1 text-xs text-[#58635f]">Digite para filtrar e depois selecione abaixo.</p>
                            </div>
                            <div class="md:w-56">
                                <select v-model="appointmentForm.patientId" class="field-input" required>
                                    <option value="" disabled>Selecione o paciente</option>
                                    <option v-for="patient in patientOptions" :key="patient.id" :value="patient.id">
                                        {{ patient.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <p v-if="appointmentErrors.patientId" class="mt-1 text-xs text-red-600">{{ appointmentErrors.patientId }}</p>
                        <p v-if="patientOptionsLoading" class="mt-1 text-xs text-[#58635f]">Carregando pacientes...</p>
                    </div>

                    <div class="rounded-2xl border border-[#ece6db] bg-[#f8f5ef]/80 p-4">
                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Repetir semanalmente</p>
                                <p class="text-xs text-[#58635f]">Cria automaticamente este horário nas próximas semanas.</p>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-[#39423e]">
                                <input
                                    v-model="recurrenceForm.enabled"
                                    :disabled="!recurrenceControlsEnabled"
                                    class="size-4 rounded border-slate-300 text-[#3f4f46] focus:ring-[#3f4f46] disabled:cursor-not-allowed"
                                    type="checkbox"
                                />
                                <span>{{ recurrenceControlsEnabled ? 'Ativar' : 'Disponível ao criar' }}</span>
                            </label>
                        </div>

                        <p
                            v-if="!recurrenceControlsEnabled && !editingRecurringAppointment"
                            class="mt-2 text-xs text-[#58635f]"
                        >
                            Para configurar uma recorrência, crie um novo agendamento com o horário desejado.
                        </p>

                        <div v-if="recurrenceForm.enabled && recurrenceControlsEnabled" class="mt-4 grid gap-4 md:max-w-md md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700" for="recurrence-until">Repetir até</label>
                                <input id="recurrence-until" v-model="recurrenceForm.until" class="field-input mt-1" type="date" :min="appointmentStartDateOnly" />
                                <p v-if="appointmentErrors.repeatUntil" class="mt-1 text-xs text-red-600">{{ appointmentErrors.repeatUntil }}</p>
                                <p class="mt-1 text-xs text-[#58635f]">Deixe em branco para manter sem data final.</p>
                            </div>
                        </div>

                        <div
                            v-if="editingRecurringAppointment"
                            class="mt-4 rounded-2xl border border-[#d2c9e7] bg-[#f5f2fb] p-4 text-sm text-[#554a74]"
                        >
                            <p>Este agendamento faz parte de uma recorrência semanal.</p>
                            <button
                                class="mt-3 inline-flex items-center justify-center rounded-xl border border-[#d2c9e7] px-4 py-2 text-xs font-semibold text-[#554a74] transition hover:bg-[#efe9fa] disabled:cursor-not-allowed disabled:opacity-60"
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
                            <input id="appointment-start" v-model="appointmentForm.startAt" class="field-input mt-1" type="datetime-local" required />
                            <p v-if="appointmentErrors.startAt" class="mt-1 text-xs text-red-600">{{ appointmentErrors.startAt }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="appointment-end">Fim</label>
                            <input id="appointment-end" v-model="appointmentForm.endAt" class="field-input mt-1" type="datetime-local" required />
                            <p v-if="appointmentErrors.endAt" class="mt-1 text-xs text-red-600">{{ appointmentErrors.endAt }}</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="appointment-status">Status</label>
                            <select id="appointment-status" v-model="appointmentForm.status" class="field-input mt-1">
                                <option v-for="option in appointmentStatusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p v-if="appointmentErrors.status" class="mt-1 text-xs text-red-600">{{ appointmentErrors.status }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="appointment-type">Tipo</label>
                            <select id="appointment-type" v-model="appointmentForm.type" class="field-input mt-1">
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
                                class="field-input mt-1 bg-[#f6f2ea]"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="Valor definido pelo paciente"
                                disabled
                            />
                            <p class="mt-1 text-xs" :class="selectedPatientHasFee ? 'text-[#58635f]' : 'text-[#8b6b3f]'">
                                {{ selectedPatientFeeDescription }}
                            </p>
                            <p v-if="appointmentErrors.price" class="mt-1 text-xs text-red-600">{{ appointmentErrors.price }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="appointment-paid">Pago em</label>
                            <input id="appointment-paid" v-model="appointmentForm.paidAt" class="field-input mt-1" type="datetime-local" />
                            <p v-if="appointmentErrors.paidAt" class="mt-1 text-xs text-red-600">{{ appointmentErrors.paidAt }}</p>
                        </div>
                    </div>

                    <p v-if="appointmentMessage" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ appointmentMessage }}
                    </p>

                    <div class="flex justify-end gap-3">
                        <button class="btn-secondary" type="button" @click="closeAppointmentModal">Cancelar</button>
                        <button class="btn-primary px-5 py-2.5" type="submit" :disabled="appointmentSubmitting">
                            <AppIcon v-if="appointmentSubmitting" name="LoaderCircle" class="-ms-1 me-2 size-4 animate-spin" />
                            {{ appointmentSubmitLabel }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
