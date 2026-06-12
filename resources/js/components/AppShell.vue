<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import HomeAppSidebar from './home/HomeAppSidebar.vue';
import HomeTopBar from './home/HomeTopBar.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);
const patientSearch = ref('');
const patientAutocompleteResults = ref([]);
const patientAutocompleteLoading = ref(false);
const patientAutocompleteError = ref('');
const patientAutocompleteOpen = ref(false);
let patientSearchTimeoutId;
let patientSearchRequestId = 0;

const authUser = computed(() => auth.user ?? {});
const authPsychologist = computed(() => authUser.value.psychologist ?? {});
const isAdmin = computed(() => authUser.value.role === 'admin');
const userName = computed(() => authPsychologist.value.name ?? authUser.value.name ?? 'Psicólogo(a)');
const userEmail = computed(() => authUser.value.email ?? authPsychologist.value.email ?? '');

const todayLabel = computed(() =>
    new Intl.DateTimeFormat('pt-BR', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
    }).format(new Date())
);

const navigationItems = computed(() => [
    {
        id: 'dashboard',
        label: 'Dashboard',
        icon: 'LayoutDashboard',
        to: { name: 'home' },
    },
    {
        id: 'patients',
        label: 'Pacientes',
        icon: 'UsersRound',
        to: { name: 'patients' },
    },
    {
        id: 'schedule',
        label: 'Agenda',
        icon: 'CalendarDays',
        to: { name: 'schedule' },
    },
    {
        id: 'reports',
        label: 'Relatórios',
        icon: 'ChartColumn',
        to: { name: 'reports' },
    },
    {
        id: 'finance',
        label: 'Financeiro',
        icon: 'WalletCards',
        to: { name: 'finance' },
    },
    {
        id: 'exports',
        label: 'Exportação',
        icon: 'FolderArchive',
        to: { name: 'exports' },
    },
    {
        id: 'settings',
        label: 'Configurações',
        icon: 'Settings',
        to: { name: 'settings' },
    },
    ...(isAdmin.value
        ? [
            {
                id: 'admin',
                label: 'Admin',
                icon: 'ShieldCheck',
                to: { name: 'home', query: { tab: 'admin' } },
            },
        ]
        : []),
]);


const resetPatientAutocomplete = () => {
    patientAutocompleteResults.value = [];
    patientAutocompleteLoading.value = false;
    patientAutocompleteError.value = '';
    patientAutocompleteOpen.value = false;
};

const fetchPatientAutocomplete = async (term) => {
    const requestId = ++patientSearchRequestId;
    patientAutocompleteLoading.value = true;
    patientAutocompleteError.value = '';
    patientAutocompleteOpen.value = true;

    try {
        const { data } = await axios.get('/api/patients', {
            params: {
                q: term,
                per_page: 5,
            },
        });

        if (requestId !== patientSearchRequestId) return;
        patientAutocompleteResults.value = Array.isArray(data?.data) ? data.data.slice(0, 5) : [];
    } catch (error) {
        if (requestId !== patientSearchRequestId) return;
        patientAutocompleteResults.value = [];
        patientAutocompleteError.value = error?.response?.data?.message ?? 'Não foi possível buscar pacientes.';
    } finally {
        if (requestId === patientSearchRequestId) {
            patientAutocompleteLoading.value = false;
        }
    }
};

watch(patientSearch, (value) => {
    const term = value.trim();

    if (patientSearchTimeoutId) {
        clearTimeout(patientSearchTimeoutId);
    }

    if (term.length < 3) {
        patientSearchRequestId += 1;
        resetPatientAutocomplete();
        return;
    }

    patientAutocompleteOpen.value = true;
    patientSearchTimeoutId = window.setTimeout(() => fetchPatientAutocomplete(term), 250);
});

const closePatientAutocomplete = () => {
    patientAutocompleteOpen.value = false;
};

const editPatientFromAutocomplete = (patient) => {
    patientSearch.value = patient?.name ?? patientSearch.value;
    closePatientAutocomplete();
    router.push({
        name: 'patients',
        query: patient?.name ? { q: patient.name } : {},
    });
};

const openPatientRecordFromAutocomplete = (patient) => {
    if (!patient?.id) return;

    closePatientAutocomplete();
    router.push({
        name: 'patient-records',
        params: { id: patient.id },
    });
};

const handleLogout = async () => {
    await auth.logout();
    router.push({ name: 'login' });
};

const searchPatients = () => {
    const q = patientSearch.value.trim();
    closePatientAutocomplete();
    router.push({
        name: 'patients',
        query: q ? { q } : {},
    });
    mobileSidebarOpen.value = false;
};
</script>

<template>
    <div class="min-h-screen bg-[#f9f9f8]">
        <HomeAppSidebar
            :items="navigationItems"
            :collapsed="sidebarCollapsed"
            :mobile-open="mobileSidebarOpen"
            :user-name="userName"
            @close-mobile="mobileSidebarOpen = false"
            @logout="handleLogout"
            @toggle-collapse="sidebarCollapsed = !sidebarCollapsed"
        />

        <div
            class="min-h-screen transition-[padding] duration-200"
            :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'"
        >
            <HomeTopBar
                v-model:search-term="patientSearch"
                :today-label="todayLabel"
                :user-name="userName"
                :user-email="userEmail"
                :sidebar-collapsed="sidebarCollapsed"
                :patient-results="patientAutocompleteResults"
                :patient-autocomplete-loading="patientAutocompleteLoading"
                :patient-autocomplete-error="patientAutocompleteError"
                :show-patient-autocomplete="patientAutocompleteOpen"
                @search-patients="searchPatients"
                @close-patient-autocomplete="closePatientAutocomplete"
                @edit-patient="editPatientFromAutocomplete"
                @open-patient-record="openPatientRecordFromAutocomplete"
                @toggle-sidebar="sidebarCollapsed = !sidebarCollapsed"
                @open-mobile-sidebar="mobileSidebarOpen = true"
            />

            <slot />
        </div>
    </div>
</template>
