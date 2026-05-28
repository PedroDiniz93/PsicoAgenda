<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import { useAppointmentReports } from '../composables/useAppointmentReports';
import AppIcon from '../components/AppIcon.vue';
import { formatMoney } from '../utils/formatters';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const defaultTimezone = 'America/Sao_Paulo';

const authUser = computed(() => auth.user ?? {});
const authPsychologist = computed(() => authUser.value.psychologist ?? {});
const userName = computed(() => authPsychologist.value.name ?? authUser.value.name ?? 'Psicólogo(a)');
const userEmail = computed(() => authUser.value.email ?? authPsychologist.value.email ?? '');

const tabs = [
    { id: 'overview', label: 'Visão geral' },
    { id: 'profile', label: 'Perfil' },
    { id: 'settings', label: 'Configurações' },
];

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Bom dia';
    if (hour < 18) return 'Boa tarde';
    return 'Boa noite';
});

const firstName = computed(() => String(userName.value).trim().split(/\s+/)[0] ?? userName.value);

const quickActions = [
    {
        name: 'patients',
        icon: 'patients',
        title: 'Pacientes',
        subtitle: 'Gerenciar cadastros',
        text: 'Editar dados, filtrar status e cadastrar novos perfis.',
    },
    {
        name: 'schedule',
        icon: 'calendar',
        title: 'Agenda',
        subtitle: 'Controlar atendimentos',
        text: 'Criar sessões, marcar faltas e acompanhar horários.',
    },
    {
        name: 'reports',
        icon: 'chart',
        title: 'Relatórios',
        subtitle: 'Analisar resultados',
        text: 'Acompanhe comparecimento, pagamentos e indicadores.',
    },
    {
        name: 'exports',
        icon: 'download',
        title: 'Exportação',
        subtitle: 'Baixar relatórios',
        text: 'Selecione pacientes e gere um CSV detalhado.',
    },
];

const statusListSections = [
    { key: 'done', label: 'Concluídos', empty: 'Nenhum atendimento concluído.' },
    { key: 'canceled', label: 'Cancelados', empty: 'Nenhum cancelamento registrado.' },
    { key: 'missed', label: 'Faltas', empty: 'Sem faltas no período.' },
];

const activeTab = ref('overview');
const profileForm = reactive({
    name: '',
    email: '',
    phone: '',
    timezone: defaultTimezone,
    sessionDuration: 50,
    allowOnline: true,
    allowInPerson: true,
});

const profileErrors = reactive({
    name: '',
    email: '',
    phone: '',
    timezone: '',
    sessionDuration: '',
    allowOnline: '',
    allowInPerson: '',
});

const profileMessage = ref('');
const profileMessageType = ref('success');
const profileLoading = ref(false);
const profileSaving = ref(false);

const timezoneOptions = [
    { label: 'São Paulo (GMT-3)', value: 'America/Sao_Paulo' },
    { label: 'Bahia (GMT-3)', value: 'America/Bahia' },
    { label: 'Fortaleza (GMT-3)', value: 'America/Fortaleza' },
    { label: 'Manaus (GMT-4)', value: 'America/Manaus' },
    { label: 'UTC', value: 'UTC' },
];

const sessionDurationOptions = [30, 45, 50, 60, 75, 90];

const googleError = ref('');
const googleProcessing = ref(false);
const googleConnected = computed(() => Boolean(authPsychologist.value?.google_calendar_connected));
const googleStatusMessage = ref('');
const googleStatusType = ref('success');
let googleStatusTimeoutId;

const reminderSettings = reactive({
    daysBefore: 1,
    whatsappEnabled: false,
    emailEnabled: false,
});
const reminderMessage = ref('');
const reminderMessageType = ref('success');
const reminderSaving = ref(false);

const {
    appointmentReport,
    reportFilters,
    appliedReportFilters,
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

const overviewStats = computed(() => [
    {
        id: 'done',
        label: 'Sessões concluídas',
        value: appointmentReport.appointments.done,
        icon: 'check',
        tone: 'success',
    },
    {
        id: 'paid',
        label: 'Recebido',
        value: formatMoney(appointmentReport.payments.paid.value ?? '0.00'),
        icon: 'money',
        tone: 'primary',
    },
    {
        id: 'pending',
        label: 'A receber',
        value: formatMoney(appointmentReport.payments.pending.value ?? '0.00'),
        icon: 'clock',
        tone: 'warning',
    },
    {
        id: 'missed',
        label: 'Faltas',
        value: appointmentReport.appointments.missed,
        icon: 'x',
        tone: 'danger',
    },
]);

const toneClasses = {
    success: 'bg-success-soft text-success',
    primary: 'bg-primary-soft text-primary-soft-foreground',
    warning: 'bg-warning-soft text-warning',
    danger: 'bg-danger-soft text-danger',
};


const handleLogout = async () => {
    await auth.logout();
    router.push({ name: 'login' });
};

const hydrateFromAuth = () => {
    if (!authPsychologist.value) return;
    setProfileForm(authPsychologist.value);
    setReminderSettings(authPsychologist.value);
};

const setProfileForm = (psychologist = {}) => {
    profileForm.name = psychologist.name ?? authUser.value.name ?? '';
    profileForm.email = psychologist.email ?? authUser.value.email ?? '';
    profileForm.phone = psychologist.phone ?? '';
    profileForm.timezone = psychologist.timezone ?? defaultTimezone;
    profileForm.sessionDuration = psychologist.session_duration ?? 50;
    profileForm.allowOnline = psychologist.allow_online ?? true;
    profileForm.allowInPerson = psychologist.allow_in_person ?? true;
};

const setReminderSettings = (psychologist = {}) => {
    const days = Number(psychologist?.whatsapp_confirm_days_before ?? 1);
    reminderSettings.daysBefore = Number.isNaN(days) ? 1 : days;
    reminderSettings.whatsappEnabled = Boolean(psychologist?.whatsapp_confirm_enabled);
    reminderSettings.emailEnabled = Boolean(psychologist?.email_confirm_enabled);
};

const clearProfileErrors = () => {
    Object.keys(profileErrors).forEach((key) => {
        profileErrors[key] = '';
    });
};

const fetchProfile = async () => {
    profileLoading.value = true;
    profileMessage.value = '';

    try {
        const { data } = await axios.get('/api/psychologist/profile');
        const psychologist = data?.psychologist ?? data ?? {};
        setProfileForm(psychologist);
        setReminderSettings(psychologist);
        if (auth.user) {
            auth.user = { ...auth.user, psychologist };
        }
    } catch (error) {
        profileMessageType.value = 'error';
        profileMessage.value = error?.response?.data?.message ?? 'Não foi possível carregar o perfil.';
    } finally {
        profileLoading.value = false;
    }
};

const sanitizeProfilePayload = () => ({
    name: profileForm.name.trim(),
    email: profileForm.email.trim() || null,
    phone: profileForm.phone.trim() || null,
    timezone: profileForm.timezone,
    session_duration: Number(profileForm.sessionDuration),
    allow_online: Boolean(profileForm.allowOnline),
    allow_in_person: Boolean(profileForm.allowInPerson),
});

const submitProfile = async () => {
    clearProfileErrors();
    profileMessage.value = '';
    profileSaving.value = true;

    try {
        const { data } = await axios.put('/api/psychologist/profile', sanitizeProfilePayload());
        const psychologist = data?.psychologist ?? data ?? {};
        setProfileForm(psychologist);
        if (auth.user) {
            auth.user = { ...auth.user, psychologist };
        }
        profileMessageType.value = 'success';
        profileMessage.value = 'Perfil atualizado com sucesso.';
    } catch (error) {
        if (error?.response?.status === 422) {
            const errors = error.response.data.errors ?? {};
            Object.entries(errors).forEach(([field, messages]) => {
                if (field === 'session_duration') {
                    profileErrors.sessionDuration = messages[0];
                } else if (field in profileErrors) {
                    profileErrors[field] = messages[0];
                }
            });
            profileMessageType.value = 'error';
            profileMessage.value = 'Corrija os campos destacados e tente novamente.';
        } else {
            profileMessageType.value = 'error';
            profileMessage.value = error?.response?.data?.message ?? 'Erro ao salvar o perfil.';
        }
    } finally {
        profileSaving.value = false;
    }
};

const connectGoogle = async () => {
    googleError.value = '';
    googleProcessing.value = true;

    try {
        const { data } = await axios.get('/api/google/oauth/url');
        if (data?.url) {
            window.location.href = data.url;
        } else {
            throw new Error('URL de autorização não recebida.');
        }
    } catch (error) {
        googleError.value = error?.response?.data?.message ?? error?.message ?? 'Não foi possível iniciar a conexão com o Google.';
        googleProcessing.value = false;
    }
};

const disconnectGoogle = async () => {
    googleError.value = '';
    googleProcessing.value = true;

    try {
        await axios.post('/api/google/oauth/disconnect');
        if (auth.user?.psychologist) {
            auth.user.psychologist = {
                ...auth.user.psychologist,
                google_calendar_connected: false,
            };
        }
    } catch (error) {
        googleError.value = error?.response?.data?.message ?? 'Não foi possível desconectar.';
    } finally {
        googleProcessing.value = false;
    }
};

const submitReminderSettings = async () => {
    reminderMessage.value = '';
    reminderSaving.value = true;

    const sanitizedDays = Math.min(30, Math.max(0, Number(reminderSettings.daysBefore ?? 0)));
    reminderSettings.daysBefore = sanitizedDays;

    try {
        const { data } = await axios.put('/api/psychologist/settings', {
            whatsapp_confirm_enabled: Boolean(reminderSettings.whatsappEnabled),
            whatsapp_confirm_days_before: sanitizedDays,
            email_confirm_enabled: Boolean(reminderSettings.emailEnabled),
        });
        const psychologist = data?.psychologist ?? data ?? {};
        setReminderSettings(psychologist);
        if (auth.user) {
            auth.user = { ...auth.user, psychologist };
        }
        reminderMessageType.value = 'success';
        reminderMessage.value = 'Preferências de lembrete atualizadas.';
    } catch (error) {
        reminderMessageType.value = 'error';
        reminderMessage.value =
            error?.response?.data?.message ?? 'Não foi possível salvar as configurações de lembrete.';
    } finally {
        reminderSaving.value = false;
    }
};

const clearGoogleQueryParam = () => {
    if (!('google' in route.query)) return;
    const query = { ...route.query };
    delete query.google;
    router.replace({
        path: route.path,
        query,
        hash: route.hash,
    });
};

const showGoogleStatusMessage = (type, message) => {
    googleStatusType.value = type;
    googleStatusMessage.value = message;
    if (googleStatusTimeoutId) {
        clearTimeout(googleStatusTimeoutId);
    }
    googleStatusTimeoutId = window.setTimeout(() => {
        googleStatusMessage.value = '';
    }, 6000);
};

const handleGoogleCallbackStatus = () => {
    const statusParam = route.query.google;
    if (!statusParam) return;

    activeTab.value = 'settings';
    const status = Array.isArray(statusParam) ? statusParam[0] : statusParam;
    if (status === 'connected') {
        showGoogleStatusMessage('success', 'Conta do Google conectada com sucesso.');
    } else if (status === 'denied') {
        googleError.value = 'Você cancelou a conexão com o Google.';
    } else if (status === 'error') {
        googleError.value = 'Não foi possível concluir a conexão com o Google.';
    }

    clearGoogleQueryParam();
};

const applyAppointmentReport = (payload = {}) => {
    const payments = payload?.payments ?? {};
    appointmentReport.payments.paid.appointments = payments?.paid?.appointments ?? 0;
    appointmentReport.payments.paid.value = payments?.paid?.value ?? '0.00';
    appointmentReport.payments.pending.appointments = payments?.pending?.appointments ?? 0;
    appointmentReport.payments.pending.value = payments?.pending?.value ?? '0.00';

    const status = payload?.appointments ?? {};
    appointmentReport.appointments.done = status?.done?.count ?? 0;
    appointmentReport.appointments.canceled = status?.canceled?.count ?? 0;
    appointmentReport.appointments.missed = status?.missed?.count ?? 0;

    const lists = payload?.lists ?? {};
    appointmentReport.lists.paid = lists?.paid ?? [];
    appointmentReport.lists.pending = lists?.pending ?? [];
    appointmentReport.lists.done = lists?.done ?? [];
    appointmentReport.lists.canceled = lists?.canceled ?? [];
    appointmentReport.lists.missed = lists?.missed ?? [];
};

hydrateFromAuth();
onMounted(() => {
    fetchProfile();
    handleGoogleCallbackStatus();
    fetchAppointmentReport();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Greeting banner -->
        <section class="overflow-hidden rounded-2xl bg-primary px-6 py-7 text-primary-foreground sm:px-8">
            <p class="text-sm font-medium text-primary-foreground/75">{{ greeting }},</p>
            <h2 class="mt-1 text-2xl font-semibold sm:text-3xl">{{ firstName }}</h2>
            <p class="mt-1 max-w-xl text-sm text-primary-foreground/80">
                Aqui está um resumo da sua clínica. Acompanhe seus atendimentos e mantenha tudo em dia.
            </p>
        </section>

        <!-- Tabs -->
        <nav class="card flex flex-wrap gap-1 p-1.5" aria-label="Navegação do dashboard">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                class="rounded-xl px-4 py-2 text-sm font-semibold transition"
                :class="
                    activeTab === tab.id
                        ? 'bg-primary text-primary-foreground shadow-sm'
                        : 'text-muted-foreground hover:bg-surface-muted hover:text-foreground'
                "
                type="button"
                @click="activeTab = tab.id"
            >
                {{ tab.label }}
            </button>
        </nav>

        <section v-if="activeTab === 'overview'" class="space-y-6">
            <!-- KPI cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div v-for="stat in overviewStats" :key="stat.id" class="card p-5">
                    <div class="flex items-center justify-between">
                        <span
                            class="flex size-10 items-center justify-center rounded-xl"
                            :class="toneClasses[stat.tone]"
                        >
                            <AppIcon :name="stat.icon" :size="20" />
                        </span>
                    </div>
                    <p class="mt-4 text-2xl font-semibold text-foreground">{{ stat.value }}</p>
                    <p class="text-sm text-muted-foreground">{{ stat.label }}</p>
                </div>
            </div>

            <!-- Quick actions -->
            <div>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-subtle-foreground">Acesso rápido</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <RouterLink
                        v-for="action in quickActions"
                        :key="action.name"
                        :to="{ name: action.name }"
                        class="card card-hover flex flex-col gap-4 p-5"
                    >
                        <span class="flex size-11 items-center justify-center rounded-xl bg-primary-soft text-primary-soft-foreground">
                            <AppIcon :name="action.icon" :size="22" />
                        </span>
                        <div>
                            <p class="text-base font-semibold text-foreground">{{ action.title }}</p>
                            <p class="text-sm font-medium text-muted-foreground">{{ action.subtitle }}</p>
                            <p class="mt-1 text-xs text-subtle-foreground">{{ action.text }}</p>
                        </div>
                        <span class="mt-auto inline-flex items-center gap-1 text-sm font-semibold text-primary">
                            Abrir
                            <AppIcon name="chevronRight" :size="16" />
                        </span>
                    </RouterLink>
                </div>
            </div>
        </section>

        <section v-else-if="activeTab === 'profile'" class="card p-6">
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-foreground">Perfil profissional</h2>
                <p class="mt-1 text-sm text-muted-foreground">Atualize as informações que serão usadas em comunicações e agendamentos.</p>
            </div>

            <div v-if="profileLoading" class="rounded-xl border border-border bg-surface-muted px-4 py-6 text-sm text-muted-foreground">
                Carregando informações do perfil...
            </div>

            <form v-else class="space-y-5" @submit.prevent="submitProfile">
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="field-label" for="psychologist-name">Nome completo</label>
                        <input
                            id="psychologist-name"
                            v-model="profileForm.name"
                            class="field-input mt-1"
                            type="text"
                            placeholder="Nome do psicólogo"
                            required
                        />
                        <p v-if="profileErrors.name" class="mt-1 text-xs text-danger">{{ profileErrors.name }}</p>
                    </div>

                    <div>
                        <label class="field-label" for="psychologist-email">E-mail profissional</label>
                        <input
                            id="psychologist-email"
                            v-model="profileForm.email"
                            class="field-input mt-1"
                            type="email"
                            placeholder="email@clinica.com"
                        />
                        <p v-if="profileErrors.email" class="mt-1 text-xs text-danger">{{ profileErrors.email }}</p>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="field-label" for="psychologist-phone">Telefone</label>
                        <input
                            id="psychologist-phone"
                            v-model="profileForm.phone"
                            class="field-input mt-1"
                            type="tel"
                            placeholder="(00) 00000-0000"
                        />
                        <p v-if="profileErrors.phone" class="mt-1 text-xs text-danger">{{ profileErrors.phone }}</p>
                    </div>

                    <div>
                        <label class="field-label" for="psychologist-timezone">Fuso horário</label>
                        <select
                            id="psychologist-timezone"
                            v-model="profileForm.timezone"
                            class="field-input mt-1"
                            required
                        >
                            <option v-for="option in timezoneOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <p v-if="profileErrors.timezone" class="mt-1 text-xs text-danger">{{ profileErrors.timezone }}</p>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="field-label" for="session-duration">Duração padrão da sessão</label>
                        <select
                            id="session-duration"
                            v-model.number="profileForm.sessionDuration"
                            class="field-input mt-1"
                            required
                        >
                            <option v-for="option in sessionDurationOptions" :key="option" :value="option">
                                {{ option }} minutos
                            </option>
                        </select>
                        <p v-if="profileErrors.sessionDuration" class="mt-1 text-xs text-danger">
                            {{ profileErrors.sessionDuration }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 rounded-xl border border-border p-4">
                        <label class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                            <input v-model="profileForm.allowOnline" class="size-4 rounded border-border-strong accent-[var(--color-primary)]" type="checkbox" />
                            Atende online
                        </label>
                        <label class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                            <input v-model="profileForm.allowInPerson" class="size-4 rounded border-border-strong accent-[var(--color-primary)]" type="checkbox" />
                            Atende presencial
                        </label>
                    </div>
                </div>

                <p
                    v-if="profileMessage"
                    class="rounded-xl border px-4 py-3 text-sm"
                    :class="profileMessageType === 'success' ? 'border-success/30 bg-success-soft text-success' : 'border-danger/30 bg-danger-soft text-danger'"
                >
                    {{ profileMessage }}
                </p>

                <div class="flex justify-end">
                    <button class="btn btn-primary" type="submit" :disabled="profileSaving">
                        <svg v-if="profileSaving" class="-ms-1 size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Salvar perfil
                    </button>
                </div>
            </form>
        </section>

        <section v-else class="card p-6">
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-foreground">Configurações</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Ajuste integrações como Google Calendar e confirme sessões automaticamente pelo WhatsApp.
                </p>
            </div>

            <div class="rounded-xl border border-border p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-primary-soft text-primary-soft-foreground">
                            <AppIcon name="calendar" :size="20" />
                        </span>
                        <div>
                            <p class="text-sm font-medium text-subtle-foreground">Google Calendar</p>
                            <p class="text-lg font-semibold text-foreground">
                                {{ googleConnected ? 'Sincronização ativa' : 'Nenhuma conta conectada' }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ googleConnected ? 'Os novos agendamentos podem ser enviados para o seu calendário.' : 'Conecte-se para exportar automaticamente seus compromissos.' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            v-if="googleConnected"
                            class="btn !border-danger/30 !text-danger hover:!bg-danger-soft btn-secondary"
                            type="button"
                            :disabled="googleProcessing"
                            @click="disconnectGoogle"
                        >
                            <svg v-if="googleProcessing" class="-ms-1 size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            Desconectar
                        </button>
                        <button
                            v-else
                            class="btn btn-primary"
                            type="button"
                            :disabled="googleProcessing"
                            @click="connectGoogle"
                        >
                            <svg v-if="googleProcessing" class="-ms-1 size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            Conectar com Google
                        </button>
                    </div>
                </div>
                <p v-if="googleError" class="mt-4 rounded-xl border border-danger/30 bg-danger-soft px-4 py-2 text-sm text-danger">
                    {{ googleError }}
                </p>
                <p
                    v-if="googleStatusMessage"
                    class="mt-4 rounded-xl px-4 py-2 text-sm"
                    :class="
                        googleStatusType === 'success'
                            ? 'border border-success/30 bg-success-soft text-success'
                            : 'border border-primary/30 bg-primary-soft text-primary-soft-foreground'
                    "
                >
                    {{ googleStatusMessage }}
                </p>
            </div>

            <div class="mt-6 rounded-xl border border-border p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-primary-soft text-primary-soft-foreground">
                            <AppIcon name="bell" :size="20" />
                        </span>
                        <div>
                            <p class="text-sm font-medium text-subtle-foreground">Lembretes automáticos</p>
                            <p class="text-lg font-semibold text-foreground">Configure os canais de aviso</p>
                            <p class="text-sm text-muted-foreground">
                                Os lembretes são enviados para sessões com status "Agendado" usando os canais habilitados abaixo.
                            </p>
                        </div>
                    </div>
                </div>

                <form class="mt-4 space-y-5" @submit.prevent="submitReminderSettings">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="field-label" for="reminder-days-before">
                            Dias antes para enviar o lembrete
                            <input
                                id="reminder-days-before"
                                v-model.number="reminderSettings.daysBefore"
                                class="field-input mt-2"
                                type="number"
                                min="0"
                                max="30"
                            />
                        </label>
                        <div class="rounded-2xl border border-slate-100 p-4 text-sm text-slate-600">
                            <p class="font-semibold text-slate-800">Como funciona</p>
                            <p class="mt-1">
                                O sistema busca sessões na janela configurada e, se o contato estiver preenchido, envia o lembrete
                                pelos canais ativados abaixo.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 p-4 text-sm font-semibold text-slate-600">
                            WhatsApp
                            <input v-model="reminderSettings.whatsappEnabled" class="size-5 rounded border-slate-300" type="checkbox" />
                        </label>
                        <label class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 p-4 text-sm font-semibold text-slate-600">
                            E-mail
                            <input v-model="reminderSettings.emailEnabled" class="size-5 rounded border-slate-300" type="checkbox" />
                        </label>
                    </div>

                    <p
                        v-if="reminderMessage"
                        class="rounded-2xl border px-4 py-3 text-sm"
                        :class="
                            reminderMessageType === 'success'
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                : 'border-red-200 bg-red-50 text-red-700'
                        "
                    >
                        {{ reminderMessage }}
                    </p>

                    <div class="flex justify-end">
                        <button
                            class="inline-flex items-center rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300 disabled:cursor-not-allowed disabled:bg-blue-300"
                            type="submit"
                            :disabled="reminderSaving"
                        >
                            <svg v-if="reminderSaving" class="-ms-1 me-2 size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            Salvar preferências
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</template>
