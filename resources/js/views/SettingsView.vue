<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import HomeSettingsPanel from '../components/home/HomeSettingsPanel.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const authUser = computed(() => auth.user ?? {});
const authPsychologist = computed(() => authUser.value.psychologist ?? {});
const googleConnected = computed(() => Boolean(authPsychologist.value?.google_calendar_connected));

const googleError = ref('');
const googleProcessing = ref(false);
const googleStatusMessage = ref('');
const googleStatusType = ref('success');
let googleStatusTimeoutId: number | null = null;

const reminderSettings = reactive({
    daysBefore: 1,
    whatsappEnabled: false,
    whatsappSenderPhoneId: '',
    whatsappSenderDisplayNumber: '',
    emailEnabled: false,
});
const reminderMessage = ref('');
const reminderMessageType = ref('success');
const reminderSaving = ref(false);

const integrationItems = computed(() => [
    {
        label: 'Google Calendar',
        status: googleConnected.value ? 'Conectado' : 'Desconectado',
        class: googleConnected.value ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600',
    },
    {
        label: 'WhatsApp',
        status: reminderSettings.whatsappEnabled
            ? (reminderSettings.whatsappSenderPhoneId ? 'Ativo' : 'Configurar número')
            : 'Inativo',
        class: reminderSettings.whatsappEnabled
            ? (reminderSettings.whatsappSenderPhoneId ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700')
            : 'bg-slate-100 text-slate-600',
    },
    {
        label: 'E-mail',
        status: reminderSettings.emailEnabled ? 'Ativo' : 'Inativo',
        class: reminderSettings.emailEnabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600',
    },
]);

const setReminderSettings = (psychologist: any = {}) => {
    const days = Number(psychologist?.whatsapp_confirm_days_before ?? 1);
    reminderSettings.daysBefore = Number.isNaN(days) ? 1 : days;
    reminderSettings.whatsappEnabled = Boolean(psychologist?.whatsapp_confirm_enabled);
    reminderSettings.whatsappSenderPhoneId = psychologist?.whatsapp_sender_phone_id ?? '';
    reminderSettings.whatsappSenderDisplayNumber = psychologist?.whatsapp_sender_display_number ?? '';
    reminderSettings.emailEnabled = Boolean(psychologist?.email_confirm_enabled);
};

const fetchProfile = async () => {
    try {
        const { data } = await axios.get('/api/psychologist/profile');
        const psychologist = data?.psychologist ?? data ?? {};
        setReminderSettings(psychologist);
        if (auth.user) {
            auth.user = { ...auth.user, psychologist };
        }
    } catch (error: any) {
        reminderMessageType.value = 'error';
        reminderMessage.value = error?.response?.data?.message ?? 'Não foi possível carregar as configurações.';
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
            whatsapp_sender_phone_id: reminderSettings.whatsappSenderPhoneId.trim() || null,
            whatsapp_sender_display_number: reminderSettings.whatsappSenderDisplayNumber.trim() || null,
            email_confirm_enabled: Boolean(reminderSettings.emailEnabled),
        });
        const psychologist = data?.psychologist ?? data ?? {};
        setReminderSettings(psychologist);
        if (auth.user) {
            auth.user = { ...auth.user, psychologist };
        }
        reminderMessageType.value = 'success';
        reminderMessage.value = 'Preferências de lembrete atualizadas com sucesso.';
    } catch (error: any) {
        reminderMessageType.value = 'error';
        reminderMessage.value = error?.response?.data?.message ?? 'Não foi possível salvar as configurações de lembrete.';
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
});

onBeforeUnmount(() => {
    if (googleStatusTimeoutId) {
        clearTimeout(googleStatusTimeoutId);
    }
});
</script>

<template>
    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <HomeSettingsPanel
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
</template>
