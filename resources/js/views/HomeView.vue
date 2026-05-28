<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import { useAppointmentReports } from '../composables/useAppointmentReports';
import { formatMoney } from '../utils/formatters';
import HomeShellHeader from '../components/home/HomeShellHeader.vue';
import HomeSectionTabs from '../components/home/HomeSectionTabs.vue';
import HomeOverviewPanel from '../components/home/HomeOverviewPanel.vue';
import HomeProfilePanel from '../components/home/HomeProfilePanel.vue';
import HomeSettingsPanel from '../components/home/HomeSettingsPanel.vue';

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

const sessionDurationOptions = [30, 45, 50, 60, 75, 90].map(d => ({ label: `${d} min`, value: d }));

const googleError = ref('');
const googleProcessing = ref(false);
const googleConnected = computed(() => Boolean(authPsychologist.value?.google_calendar_connected));
const googleStatusMessage = ref('');
const googleStatusType = ref('success');
let googleStatusTimeoutId: any;

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
    reportError,
    reportLoading,
    fetchAppointmentReport,
} = useAppointmentReports();

const quickLinks = computed(() => [
    {
        id: 'patients',
        label: 'Pacientes',
        title: 'Base de pacientes',
        description: 'Cadastro, histórico e prontuários',
        icon: 'M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM16 16H8a4 4 0 00-4 4v4h16v-4a4 4 0 00-4-4z',
        to: { name: 'patients' },
        color: 'primary' as const,
    },
    {
        id: 'schedule',
        label: 'Agenda',
        title: 'Sessões e horários',
        description: 'Planejamento da rotina clínica',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        to: { name: 'schedule' },
        color: 'success' as const,
    },
    {
        id: 'reports',
        label: 'Relatórios',
        title: 'Indicadores',
        description: 'Compare presença, receita e faltas',
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        to: { name: 'reports' },
        color: 'warning' as const,
    },
    {
        id: 'exports',
        label: 'Exportação',
        title: 'Arquivos clínicos',
        description: 'Exporte dados para conferência',
        icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        to: { name: 'exports' },
        color: 'error' as const,
    },
]);

const todayLabel = computed(() =>
    new Intl.DateTimeFormat('pt-BR', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
    }).format(new Date())
);

const attendancePercent = computed(() => {
    const rate = appointmentReport.summary.attendanceRate;
    return typeof rate === 'number' && !Number.isNaN(rate) ? Math.round(rate * 1000) / 10 : null;
});

const dashboardMetrics = computed(() => [
    {
        id: 'sessions',
        label: 'Sessões no período',
        value: appointmentReport.summary.totalSessions,
        detail: `${appointmentReport.summary.uniquePatients} pacientes únicos`,
        tone: 'border-slate-200 bg-white text-slate-900',
    },
    {
        id: 'attendance',
        label: 'Comparecimento',
        value: attendancePercent.value === null ? 'Sem dados' : `${attendancePercent.value.toFixed(1)}%`,
        detail: `${appointmentReport.appointments.done} concluídas e ${appointmentReport.appointments.missed} faltas`,
        tone: 'border-emerald-200 bg-emerald-50 text-emerald-950',
    },
    {
        id: 'ticket',
        label: 'Ticket médio',
        value: formatMoney(appointmentReport.summary.avgTicket),
        detail: 'Média dos atendimentos pagos',
        tone: 'border-cyan-200 bg-cyan-50 text-cyan-950',
    },
    {
        id: 'pending',
        label: 'A receber',
        value: formatMoney(appointmentReport.payments.pending.value),
        detail: `${appointmentReport.payments.pending.appointments} sessões pendentes`,
        tone: 'border-amber-200 bg-amber-50 text-amber-950',
    },
]);

const integrationItems = computed(() => [
    {
        label: 'Google Calendar',
        status: googleConnected.value ? 'Conectado' : 'Desconectado',
        class: googleConnected.value ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600',
    },
    {
        label: 'WhatsApp',
        status: reminderSettings.whatsappEnabled ? 'Ativo' : 'Inativo',
        class: reminderSettings.whatsappEnabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600',
    },
    {
        label: 'E-mail',
        status: reminderSettings.emailEnabled ? 'Ativo' : 'Inativo',
        class: reminderSettings.emailEnabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600',
    },
]);

const handleLogout = async () => {
    await auth.logout();
    router.push({ name: 'login' });
};

const hydrateFromAuth = () => {
    if (!authPsychologist.value) return;
    setProfileForm(authPsychologist.value);
    setReminderSettings(authPsychologist.value);
};

const setProfileForm = (psychologist: any = {}) => {
    profileForm.name = psychologist.name ?? authUser.value.name ?? '';
    profileForm.email = psychologist.email ?? authUser.value.email ?? '';
    profileForm.phone = psychologist.phone ?? '';
    profileForm.timezone = psychologist.timezone ?? defaultTimezone;
    profileForm.sessionDuration = psychologist.session_duration ?? 50;
    profileForm.allowOnline = psychologist.allow_online ?? true;
    profileForm.allowInPerson = psychologist.allow_in_person ?? true;
};

const setReminderSettings = (psychologist: any = {}) => {
    const days = Number(psychologist?.whatsapp_confirm_days_before ?? 1);
    reminderSettings.daysBefore = Number.isNaN(days) ? 1 : days;
    reminderSettings.whatsappEnabled = Boolean(psychologist?.whatsapp_confirm_enabled);
    reminderSettings.emailEnabled = Boolean(psychologist?.email_confirm_enabled);
};

const clearProfileErrors = () => {
    Object.keys(profileErrors).forEach((key) => {
        profileErrors[key as keyof typeof profileErrors] = '';
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
    } catch (error: any) {
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
        profileMessage.value = 'Perfil atualizado com sucesso! ✨';
    } catch (error: any) {
        if (error?.response?.status === 422) {
            const errors = error.response.data.errors ?? {};
            Object.entries(errors).forEach(([field, messages]: [string, any]) => {
                if (field === 'session_duration') {
                    profileErrors.sessionDuration = messages[0];
                } else if (field in profileErrors) {
                    profileErrors[field as keyof typeof profileErrors] = messages[0];
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
    } catch (error: any) {
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
    } catch (error: any) {
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
        reminderMessage.value = 'Preferências de lembrete atualizadas com sucesso! ✨';
    } catch (error: any) {
        reminderMessageType.value = 'error';
        reminderMessage.value =
            error?.response?.data?.message ?? 'Não foi possível salvar as configurações de lembrete.';
    } finally {
        reminderSaving.value = false;
    }
};

hydrateFromAuth();

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

const showGoogleStatusMessage = (type: string, message: string) => {
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

onMounted(() => {
    fetchProfile();
    handleGoogleCallbackStatus();
    fetchAppointmentReport();
});
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <HomeShellHeader
            :today-label="todayLabel"
            :user-name="userName"
            :user-email="userEmail"
            @logout="handleLogout"
        />

        <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
            <HomeSectionTabs :tabs="tabs" :active-tab="activeTab" @change="activeTab = $event" />

            <HomeOverviewPanel
                v-if="activeTab === 'overview'"
                :report-loading="reportLoading"
                :report-error="reportError"
                :dashboard-metrics="dashboardMetrics"
                :quick-links="quickLinks"
                @refresh="fetchAppointmentReport"
            />

            <HomeProfilePanel
                v-else-if="activeTab === 'profile'"
                :profile-form="profileForm"
                :profile-errors="profileErrors"
                :profile-loading="profileLoading"
                :profile-saving="profileSaving"
                :profile-message="profileMessage"
                :profile-message-type="profileMessageType"
                :timezone-options="timezoneOptions"
                :session-duration-options="sessionDurationOptions"
                @submit="submitProfile"
            />

            <HomeSettingsPanel
                v-else
                :integration-items="integrationItems"
                :google-connected="googleConnected"
                :google-processing="googleProcessing"
                :google-error="googleError"
                :google-status-message="googleStatusMessage"
                :google-status-type="googleStatusType"
                :reminder-settings="reminderSettings"
                :reminder-message="reminderMessage"
                :reminder-message-type="reminderMessageType"
                :reminder-saving="reminderSaving"
                @connect-google="connectGoogle"
                @disconnect-google="disconnectGoogle"
                @submit-reminders="submitReminderSettings"
            />
        </main>
    </div>
</template>
