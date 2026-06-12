<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import HomeAppSidebar from './home/HomeAppSidebar.vue';
import HomeTopBar from './home/HomeTopBar.vue';
import PatientFormModal from './patients/PatientFormModal.vue';

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
const isPatientFormOpen = ref(false);
const editingPatient = ref(null);
const patientFormSubmitting = ref(false);
const patientFormError = ref('');
const patientFormErrors = reactive({
    name: '',
    email: '',
    phone: '',
    cpf: '',
    birth_date: '',
    emergency_contacts: '',
    minor_guardian_name: '',
    minor_guardian_phone: '',
    status: '',
    notes: '',
    session_fee_type: '',
    session_fee_value: '',
});
const patientForm = reactive({
    name: '',
    email: '',
    phone: '',
    cpf: '',
    birthDate: '',
    emergencyContacts: [],
    minorGuardianName: '',
    minorGuardianPhone: '',
    status: 'active',
    notes: '',
    sessionFeeType: 'session',
    sessionFeeValue: '',
});
let patientSearchTimeoutId;
let patientSearchRequestId = 0;

const patientStatusOptions = [
    { value: 'active', label: 'Ativo' },
    { value: 'paused', label: 'Pausado' },
    { value: 'closed', label: 'Encerrado' },
];

const sessionFeeTypeOptions = [
    { value: 'session', label: 'Por sessão' },
    { value: 'biweekly', label: 'Quinzenal' },
    { value: 'monthly', label: 'Mensal' },
];

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
        id: 'profile',
        label: 'Perfil',
        icon: 'UserRoundCog',
        to: { name: 'profile' },
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

const clearPatientFormErrors = () => {
    Object.keys(patientFormErrors).forEach((key) => {
        patientFormErrors[key] = '';
    });
};

const resetPatientForm = () => {
    patientForm.name = '';
    patientForm.email = '';
    patientForm.phone = '';
    patientForm.cpf = '';
    patientForm.birthDate = '';
    patientForm.emergencyContacts = [];
    patientForm.minorGuardianName = '';
    patientForm.minorGuardianPhone = '';
    patientForm.status = 'active';
    patientForm.notes = '';
    patientForm.sessionFeeType = 'session';
    patientForm.sessionFeeValue = '';
    clearPatientFormErrors();
    patientFormError.value = '';
};

const normalizeEmergencyContacts = (contacts) => {
    if (!Array.isArray(contacts)) return [];

    return contacts.slice(0, 3).map((contact) => ({
        name: contact?.name ?? '',
        phone: contact?.phone ?? '',
        relationship: contact?.relationship ?? '',
    }));
};

const openPatientEditForm = (patient) => {
    editingPatient.value = patient;
    patientForm.name = patient.name ?? '';
    patientForm.email = patient.email ?? '';
    patientForm.phone = patient.phone ?? '';
    patientForm.cpf = patient.cpf ?? '';
    patientForm.birthDate = patient.birth_date ?? '';
    patientForm.emergencyContacts = normalizeEmergencyContacts(patient.emergency_contacts);
    patientForm.minorGuardianName = patient.minor_guardian_name ?? '';
    patientForm.minorGuardianPhone = patient.minor_guardian_phone ?? '';
    patientForm.status = patient.status ?? 'active';
    patientForm.notes = patient.notes ?? '';
    patientForm.sessionFeeType = patient.session_fee_type ?? 'session';
    patientForm.sessionFeeValue = patient.session_fee_value ?? '';
    clearPatientFormErrors();
    patientFormError.value = '';
    isPatientFormOpen.value = true;
};

const closePatientEditForm = () => {
    isPatientFormOpen.value = false;
    editingPatient.value = null;
};

const openPatientForEditById = async (patientId) => {
    if (!patientId) return;

    patientFormError.value = '';

    try {
        const { data } = await axios.get(`/api/patients/${patientId}`);
        openPatientEditForm(data);
    } catch (error) {
        patientFormError.value = error?.response?.data?.message ?? 'Não foi possível abrir o paciente para edição.';
        isPatientFormOpen.value = true;
    }
};

const editPatientFromAutocomplete = async (patient) => {
    closePatientAutocomplete();
    await openPatientForEditById(patient?.id);
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

const isMinorBirthDate = (birthDate) => {
    if (!birthDate) return false;

    const parsed = new Date(`${birthDate}T00:00:00`);
    if (Number.isNaN(parsed.getTime())) return false;

    const today = new Date();
    let age = today.getFullYear() - parsed.getFullYear();
    const monthDiff = today.getMonth() - parsed.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < parsed.getDate())) {
        age -= 1;
    }

    return age < 18;
};

const sanitizePatientPayload = () => ({
    name: patientForm.name.trim(),
    email: patientForm.email.trim() || null,
    phone: patientForm.phone.trim() || null,
    cpf: patientForm.cpf.trim() || null,
    birth_date: patientForm.birthDate || null,
    emergency_contacts: patientForm.emergencyContacts
        .map((contact) => ({
            name: contact.name?.trim() ?? '',
            phone: contact.phone?.trim() ?? '',
            relationship: contact.relationship?.trim() ?? '',
        }))
        .filter((contact) => contact.name || contact.phone || contact.relationship)
        .slice(0, 3),
    minor_guardian_name: isMinorBirthDate(patientForm.birthDate) ? patientForm.minorGuardianName.trim() || null : null,
    minor_guardian_phone: isMinorBirthDate(patientForm.birthDate) ? patientForm.minorGuardianPhone.trim() || null : null,
    status: patientForm.status,
    notes: patientForm.notes.trim() ? patientForm.notes.trim() : null,
    session_fee_type: patientForm.sessionFeeType || null,
    session_fee_value:
        patientForm.sessionFeeValue === '' || patientForm.sessionFeeValue === null
            ? null
            : Number(patientForm.sessionFeeValue),
});

const submitPatientEdit = async () => {
    if (!editingPatient.value?.id) return;

    clearPatientFormErrors();
    patientFormError.value = '';
    patientFormSubmitting.value = true;

    try {
        await axios.put(`/api/patients/${editingPatient.value.id}`, sanitizePatientPayload());
        closePatientEditForm();
    } catch (error) {
        if (error?.response?.status === 422) {
            const errors = error.response.data.errors ?? {};
            Object.entries(errors).forEach(([field, messages]) => {
                if (patientFormErrors[field] !== undefined) {
                    patientFormErrors[field] = messages[0];
                } else if (field.startsWith('emergency_contacts.')) {
                    patientFormErrors.emergency_contacts = messages[0];
                }
            });
            patientFormError.value = 'Corrija os campos destacados e tente novamente.';
        } else {
            patientFormError.value = error?.response?.data?.message ?? 'Não foi possível salvar o paciente.';
        }
    } finally {
        patientFormSubmitting.value = false;
    }
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

        <div class="min-h-screen">
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

            <PatientFormModal
                :open="isPatientFormOpen"
                modal-title="Editar paciente"
                is-editing
                submit-label="Salvar alterações"
                :form="patientForm"
                :form-errors="patientFormErrors"
                :form-error="patientFormError"
                :form-submitting="patientFormSubmitting"
                :patient-status-options="patientStatusOptions"
                :session-fee-type-options="sessionFeeTypeOptions"
                @close="closePatientEditForm"
                @submit="submitPatientEdit"
            />
        </div>
    </div>
</template>
