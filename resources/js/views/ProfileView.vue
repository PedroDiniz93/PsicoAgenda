<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import HomeProfilePanel from '../components/home/HomeProfilePanel.vue';

const auth = useAuthStore();
const defaultTimezone = 'America/Sao_Paulo';

const authUser = computed(() => auth.user ?? {});

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

const sessionDurationOptions = [30, 45, 50, 60, 75, 90].map((duration) => ({
    label: `${duration} min`,
    value: duration,
}));

const setProfileForm = (psychologist: any = {}) => {
    profileForm.name = psychologist.name ?? authUser.value.name ?? '';
    profileForm.email = psychologist.email ?? authUser.value.email ?? '';
    profileForm.phone = psychologist.phone ?? '';
    profileForm.timezone = psychologist.timezone ?? defaultTimezone;
    profileForm.sessionDuration = psychologist.session_duration ?? 50;
    profileForm.allowOnline = psychologist.allow_online ?? true;
    profileForm.allowInPerson = psychologist.allow_in_person ?? true;
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
        profileMessage.value = 'Perfil atualizado com sucesso.';
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

onMounted(() => {
    fetchProfile();
});
</script>

<template>
    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <section class="mb-6 rounded-2xl border border-[#e2ddd3] bg-white/95 p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#6c6b60]">Perfil</p>
            <h1 class="mt-2 text-2xl font-semibold text-[#1f2522]">Editar perfil profissional</h1>
            <p class="mt-2 max-w-3xl text-sm text-[#58635f]">
                Atualize os dados usados nos agendamentos, na comunicação com pacientes e na configuração da agenda.
            </p>
        </section>

        <HomeProfilePanel
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
    </main>
</template>
