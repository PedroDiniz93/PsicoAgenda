<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import PatientsHeader from '../components/patients/PatientsHeader.vue';
import PatientsFilters from '../components/patients/PatientsFilters.vue';
import PatientsList from '../components/patients/PatientsList.vue';
import PatientFormModal from '../components/patients/PatientFormModal.vue';
import AppIcon from '../components/base/AppIcon.vue';

const route = useRoute();

const patients = ref([]);
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    perPage: 10,
    total: 0,
});

const metrics = reactive({
    total: 0,
    createdThisMonth: 0,
    active: 0,
    paused: 0,
    closed: 0,
});

const filters = reactive({
    q: '',
    status: 'all',
});

const filterOptions = [
    { value: 'all', label: 'Todos' },
    { value: 'active', label: 'Ativos' },
    { value: 'paused', label: 'Pausados' },
    { value: 'closed', label: 'Encerrados' },
];

const patientStatusOptions = filterOptions.filter((option) => option.value !== 'all');

const statusBadges = {
    active: { label: 'Ativo', classes: 'bg-emerald-100 text-emerald-800' },
    paused: { label: 'Pausado', classes: 'bg-amber-100 text-amber-800' },
    closed: { label: 'Encerrado', classes: 'bg-slate-200 text-slate-600' },
};

const sessionFeeTypeOptions = [
    { value: 'session', label: 'Por sessão' },
    { value: 'biweekly', label: 'Quinzenal' },
    { value: 'monthly', label: 'Mensal' },
];

const sessionFeeLabel = (type) => {
    const option = sessionFeeTypeOptions.find((item) => item.value === type);
    return option ? option.label : 'Não definido';
};

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const formatCurrency = (value) => currencyFormatter.format(Number(value ?? 0));

const formatDate = (value) => {
    if (!value) return '';

    return new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(new Date(`${value}T00:00:00Z`));
};

const loading = ref(false);
const errorMessage = ref('');

const isFormOpen = ref(false);
const editingPatient = ref(null);
const formSubmitting = ref(false);
const formError = ref('');
const editPatientId = ref(null);
const formErrors = reactive({
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

const form = reactive({
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

const hasFilters = computed(() => Boolean(filters.q.trim()) || filters.status !== 'all');
const canGoPrev = computed(() => pagination.currentPage > 1);
const canGoNext = computed(() => pagination.currentPage < pagination.lastPage);
const isEditing = computed(() => Boolean(editingPatient.value));
const modalTitle = computed(() => (isEditing.value ? 'Editar paciente' : 'Novo paciente'));
const submitLabel = computed(() => (isEditing.value ? 'Salvar alterações' : 'Cadastrar paciente'));

const patientStats = computed(() => {
    return [
        {
            id: 'total',
            label: 'Total de pacientes',
            value: metrics.total,
            helper: `+${metrics.createdThisMonth} este mês`,
            icon: 'UsersRound',
            tone: 'primary',
        },
        {
            id: 'active',
            label: 'Pacientes ativos',
            value: metrics.active,
            helper: '',
            icon: 'UserCheck',
            tone: 'secondary',
        },
        {
            id: 'paused',
            label: 'Pausados',
            value: metrics.paused,
            helper: '',
            icon: 'PauseCircle',
            tone: 'tertiary',
        },
        {
            id: 'closed',
            label: 'Encerrados',
            value: metrics.closed,
            helper: '',
            icon: 'Archive',
            tone: 'primary',
        },
    ];
});

const paginationSummary = computed(() => {
    const total = pagination.total ?? 0;
    if (!total) {
        return loading.value ? 'Carregando pacientes...' : 'Nenhum paciente encontrado';
    }

    const start = (pagination.currentPage - 1) * pagination.perPage + 1;
    const end = Math.min(start + patients.value.length - 1, total);
    return `Mostrando ${start}-${end} de ${total} pacientes`;
});

const fetchPatients = async (page = 1) => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const params = { page };

        if (filters.status !== 'all') {
            params.status = filters.status;
        }

        if (filters.q.trim()) {
            params.q = filters.q.trim();
        }

        const { data } = await axios.get('/api/patients', { params });
        const list = Array.isArray(data?.data) ? data.data : [];

        patients.value = list;

        const meta = data?.meta ?? data ?? {};
        pagination.currentPage = meta.current_page ?? page;
        pagination.lastPage = meta.last_page ?? 1;
        const fallbackPerPage = list.length || pagination.perPage;
        pagination.perPage = meta.per_page ?? fallbackPerPage;
        pagination.total = meta.total ?? list.length;

        const responseMetrics = data?.metrics ?? {};
        metrics.total = Number(responseMetrics.total ?? pagination.total ?? 0);
        metrics.createdThisMonth = Number(responseMetrics.created_this_month ?? 0);
        metrics.active = Number(responseMetrics.active ?? 0);
        metrics.paused = Number(responseMetrics.paused ?? 0);
        metrics.closed = Number(responseMetrics.closed ?? 0);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message ?? 'Não foi possível carregar os pacientes.';
        patients.value = [];
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => {
    fetchPatients(1);
};

const clearFilters = () => {
    filters.q = '';
    filters.status = 'all';
    fetchPatients(1);
};

const goToPrevious = () => {
    if (!canGoPrev.value) return;
    fetchPatients(pagination.currentPage - 1);
};

const goToNext = () => {
    if (!canGoNext.value) return;
    fetchPatients(pagination.currentPage + 1);
};

const openCreateForm = () => {
    editingPatient.value = null;
    resetForm();
    isFormOpen.value = true;
};

const openEditForm = (patient) => {
    editingPatient.value = patient;
    form.name = patient.name ?? '';
    form.email = patient.email ?? '';
    form.phone = patient.phone ?? '';
    form.cpf = patient.cpf ?? '';
    form.birthDate = patient.birth_date ?? '';
    form.emergencyContacts = normalizeEmergencyContacts(patient.emergency_contacts);
    form.minorGuardianName = patient.minor_guardian_name ?? '';
    form.minorGuardianPhone = patient.minor_guardian_phone ?? '';
    form.status = patient.status ?? 'active';
    form.notes = patient.notes ?? '';
    form.sessionFeeType = patient.session_fee_type ?? 'session';
    form.sessionFeeValue = patient.session_fee_value ?? '';
    clearFormErrors();
    formError.value = '';
    isFormOpen.value = true;
};

const openEditFormFromAutocomplete = async (patientId) => {
    if (!patientId) return;

    try {
        const { data } = await axios.get(`/api/patients/${patientId}`);
        openEditForm(data);
        editPatientId.value = null;
    } catch {
        editPatientId.value = null;
    }
};

const closeForm = () => {
    isFormOpen.value = false;
    editingPatient.value = null;
};

const resetForm = () => {
    form.name = '';
    form.email = '';
    form.phone = '';
    form.cpf = '';
    form.birthDate = '';
    form.emergencyContacts = [];
    form.minorGuardianName = '';
    form.minorGuardianPhone = '';
    form.status = 'active';
    form.notes = '';
    form.sessionFeeType = 'session';
    form.sessionFeeValue = '';
    clearFormErrors();
    formError.value = '';
};

const clearFormErrors = () => {
    Object.keys(formErrors).forEach((key) => {
        formErrors[key] = '';
    });
};

const normalizeEmergencyContacts = (contacts) => {
    if (!Array.isArray(contacts)) return [];

    return contacts
        .slice(0, 3)
        .map((contact) => ({
            name: contact?.name ?? '',
            phone: contact?.phone ?? '',
            relationship: contact?.relationship ?? '',
        }));
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

const sanitizePayload = () => ({
    name: form.name.trim(),
    email: form.email.trim() || null,
    phone: form.phone.trim() || null,
    cpf: form.cpf.trim() || null,
    birth_date: form.birthDate || null,
    emergency_contacts: form.emergencyContacts
        .map((contact) => ({
            name: contact.name?.trim() ?? '',
            phone: contact.phone?.trim() ?? '',
            relationship: contact.relationship?.trim() ?? '',
        }))
        .filter((contact) => contact.name || contact.phone || contact.relationship)
        .slice(0, 3),
    minor_guardian_name: isMinorBirthDate(form.birthDate) ? form.minorGuardianName.trim() || null : null,
    minor_guardian_phone: isMinorBirthDate(form.birthDate) ? form.minorGuardianPhone.trim() || null : null,
    status: form.status,
    notes: form.notes.trim() ? form.notes.trim() : null,
    session_fee_type: form.sessionFeeType || null,
    session_fee_value:
        form.sessionFeeValue === '' || form.sessionFeeValue === null
            ? null
            : Number(form.sessionFeeValue),
});

const submitForm = async () => {
    formError.value = '';
    clearFormErrors();

    const payload = sanitizePayload();
    const editing = isEditing.value;
    const targetPage = editing ? pagination.currentPage : 1;

    formSubmitting.value = true;

    try {
        if (editing && editingPatient.value?.id) {
            await axios.put(`/api/patients/${editingPatient.value.id}`, payload);
        } else {
            await axios.post('/api/patients', payload);
        }

        closeForm();
        await fetchPatients(targetPage);
    } catch (error) {
        if (error?.response?.status === 422) {
            const errors = error.response.data.errors ?? {};
            Object.entries(errors).forEach(([field, messages]) => {
                if (formErrors[field] !== undefined) {
                    formErrors[field] = messages[0];
                } else if (field.startsWith('emergency_contacts.')) {
                    formErrors.emergency_contacts = messages[0];
                }
            });
            formError.value = 'Corrija os campos destacados e tente novamente.';
        } else {
            formError.value = error?.response?.data?.message ?? 'Não foi possível salvar o paciente.';
        }
    } finally {
        formSubmitting.value = false;
    }
};

onMounted(() => {
    const querySearch = Array.isArray(route.query.q) ? route.query.q[0] : route.query.q;
    filters.q = typeof querySearch === 'string' ? querySearch : '';
    const queryEdit = Array.isArray(route.query.edit) ? route.query.edit[0] : route.query.edit;
    editPatientId.value = Number(queryEdit);
    fetchPatients();
    if (Number.isInteger(editPatientId.value) && editPatientId.value > 0) {
        openEditFormFromAutocomplete(editPatientId.value);
    }
});
</script>

<template>
    <div class="page-shell">
        <div class="space-y-6">
            <PatientsHeader @create="openCreateForm" />

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="stat in patientStats"
                    :key="stat.id"
                    class="group rounded-xl border border-[#c2c7cd]/20 bg-white p-6 shadow-[0_20px_40px_-10px_rgba(93,123,147,0.06)] transition hover:border-[#415f76]/30"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg"
                            :class="{
                                'bg-[#abcae5]/20 text-[#415f76]': stat.tone === 'primary',
                                'bg-[#cbe6d4]/30 text-[#4c6455]': stat.tone === 'secondary',
                                'bg-[#e8e1d9]/60 text-[#605b55]': stat.tone === 'tertiary',
                            }"
                        >
                            <AppIcon :name="stat.icon" class="size-5" />
                        </div>
                        <span v-if="stat.helper" class="rounded bg-[#cbe6d4]/30 px-2 py-1 text-xs font-bold text-[#4c6455]">
                            {{ stat.helper }}
                        </span>
                    </div>
                    <div class="mt-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-[#73787d]">
                            {{ stat.label }}
                        </p>
                        <p class="mt-1 font-['Source_Serif_4'] text-3xl font-bold text-[#1a1c1c]">
                            {{ stat.value }}
                        </p>
                    </div>
                </article>
            </section>

            <PatientsFilters
                :filters="filters"
                :filter-options="filterOptions"
                :has-filters="hasFilters"
                @search="handleSearch"
                @clear="clearFilters"
            />

            <PatientsList
                :patients="patients"
                :loading="loading"
                :error-message="errorMessage"
                :pagination-summary="paginationSummary"
                :can-go-prev="canGoPrev"
                :can-go-next="canGoNext"
                :current-page="pagination.currentPage"
                :status-badges="statusBadges"
                :session-fee-label="sessionFeeLabel"
                :format-currency="formatCurrency"
                :format-date="formatDate"
                @retry="fetchPatients(pagination.currentPage)"
                @edit="openEditForm"
                @previous="goToPrevious"
                @next="goToNext"
            />
        </div>

        <PatientFormModal
            :open="isFormOpen"
            :modal-title="modalTitle"
            :is-editing="isEditing"
            :submit-label="submitLabel"
            :form="form"
            :form-errors="formErrors"
            :form-error="formError"
            :form-submitting="formSubmitting"
            :patient-status-options="patientStatusOptions"
            :session-fee-type-options="sessionFeeTypeOptions"
            @close="closeForm"
            @submit="submitForm"
        />
    </div>
</template>
