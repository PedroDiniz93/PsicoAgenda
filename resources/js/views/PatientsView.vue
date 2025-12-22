<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import axios from 'axios';

const patients = ref([]);
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    perPage: 10,
    total: 0,
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
    active: { label: 'Ativo', classes: 'bg-green-100 text-green-800' },
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

const loading = ref(false);
const errorMessage = ref('');

const isFormOpen = ref(false);
const editingPatient = ref(null);
const formSubmitting = ref(false);
const formError = ref('');
const deletingId = ref(null);
const formErrors = reactive({
    name: '',
    email: '',
    phone: '',
    status: '',
    notes: '',
    session_fee_type: '',
    session_fee_value: '',
});

const form = reactive({
    name: '',
    email: '',
    phone: '',
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
    form.status = patient.status ?? 'active';
    form.notes = patient.notes ?? '';
    form.sessionFeeType = patient.session_fee_type ?? 'session';
    form.sessionFeeValue = patient.session_fee_value ?? '';
    clearFormErrors();
    formError.value = '';
    isFormOpen.value = true;
};

const closeForm = () => {
    isFormOpen.value = false;
    editingPatient.value = null;
};

const resetForm = () => {
    form.name = '';
    form.email = '';
    form.phone = '';
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

const sanitizePayload = () => ({
    name: form.name.trim(),
    email: form.email.trim() || null,
    phone: form.phone.trim() || null,
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

const deletePatient = async (patient) => {
    if (!patient?.id) return;
    const confirmed = window.confirm(`Deseja realmente excluir o paciente "${patient.name}"?`);
    if (!confirmed) return;

    deletingId.value = patient.id;
    const currentPage = pagination.currentPage;

    try {
        await axios.delete(`/api/patients/${patient.id}`);
        await fetchPatients(currentPage);
        if (pagination.currentPage > pagination.lastPage && pagination.lastPage >= 1) {
            await fetchPatients(pagination.lastPage);
        }
    } catch (error) {
        const message = error?.response?.data?.message ?? 'Não foi possível excluir o paciente.';
        window.alert(message);
    } finally {
        deletingId.value = null;
    }
};

onMounted(() => {
    fetchPatients();
});
</script>

<template>
    <div class="mx-auto max-w-6xl px-4 py-10">
        <header class="mb-8 flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-white px-6 py-4 shadow">
            <div>
                <p class="text-sm font-medium text-slate-500">Cadastro</p>
                <h1 class="text-2xl font-semibold text-slate-900">Pacientes</h1>
                <p class="mt-1 text-sm text-slate-500">Gerencie seus pacientes e mantenha os dados atualizados.</p>
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
                <form class="flex flex-1 flex-wrap gap-4" @submit.prevent="handleSearch">
                    <label class="flex-1" for="patients-search">
                        <span class="block text-sm font-medium text-slate-600">Busca</span>
                        <input
                            id="patients-search"
                            v-model="filters.q"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="Buscar por nome, e-mail ou telefone"
                            type="search"
                        />
                    </label>

                    <label class="w-full max-w-xs" for="patients-status">
                        <span class="block text-sm font-medium text-slate-600">Status</span>
                        <select
                            id="patients-status"
                            v-model="filters.status"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        >
                            <option v-for="option in filterOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </label>

                    <div class="flex items-end gap-3">
                        <button
                            class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                            type="submit"
                        >
                            Aplicar filtros
                        </button>
                        <button
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-60"
                            type="button"
                            :disabled="!hasFilters"
                            @click="clearFilters"
                        >
                            Limpar
                        </button>
                    </div>
                </form>

                <button
                    class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 md:self-end"
                    type="button"
                    @click="openCreateForm"
                >
                    <svg class="-ms-1 me-2 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                    </svg>
                    Novo paciente
                </button>
            </div>

            <div v-if="errorMessage" class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p>{{ errorMessage }}</p>
                    <button
                        class="rounded-xl border border-red-200 px-4 py-2 text-xs font-semibold text-red-600 transition hover:border-red-300 hover:text-red-800"
                        type="button"
                        @click="fetchPatients(pagination.currentPage)"
                    >
                        Tentar novamente
                    </button>
                </div>
            </div>

            <div
                v-else
                class="mt-6 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm shadow-slate-100/60"
            >
                <div v-if="loading" class="flex items-center justify-center gap-3 py-16 text-sm text-slate-500">
                    <svg class="size-5 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    Carregando pacientes...
                </div>

                <template v-else>
                    <table v-if="patients.length" class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-6 py-3">Paciente</th>
                                <th class="px-6 py-3">Contato</th>
                                <th class="px-6 py-3">Plano</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Observações</th>
                                <th class="px-6 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            <tr v-for="patient in patients" :key="patient.id" class="hover:bg-slate-50/60">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-900">{{ patient.name }}</p>
                                    <p v-if="patient.id" class="text-xs text-slate-500">#{{ patient.id }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p v-if="patient.email" class="text-sm text-slate-700">{{ patient.email }}</p>
                                    <p v-if="patient.phone" class="text-sm text-slate-500">{{ patient.phone }}</p>
                                    <p v-if="!patient.email && !patient.phone" class="text-sm text-slate-400">—</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ sessionFeeLabel(patient.session_fee_type) }}
                                    </p>
                                    <p v-if="patient.session_fee_value" class="text-xs text-slate-500">
                                        {{ formatCurrency(patient.session_fee_value) }}
                                    </p>
                                    <p v-else class="text-xs text-slate-400">Valor não definido</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="statusBadges[patient.status]?.classes ?? 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ statusBadges[patient.status]?.label ?? 'Desconhecido' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="max-w-xs text-sm text-slate-500">
                                        {{ patient.notes ?? 'Sem observações cadastradas.' }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <RouterLink
                                            class="rounded-xl border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-50"
                                            :to="{ name: 'patient-records', params: { id: patient.id } }"
                                        >
                                            Prontuário
                                        </RouterLink>
                                        <button
                                            class="rounded-xl border border-blue-100 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:border-blue-200 hover:bg-blue-50"
                                            type="button"
                                            @click="openEditForm(patient)"
                                        >
                                            Editar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-else class="px-8 py-16 text-center text-sm text-slate-500">
                        Nenhum paciente encontrado para os filtros selecionados.
                    </div>
                </template>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-4 text-sm text-slate-500" v-if="!errorMessage">
                <p>{{ paginationSummary }}</p>
                <div class="flex items-center gap-3">
                    <button
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                        type="button"
                        :disabled="!canGoPrev"
                        @click="goToPrevious"
                    >
                        Anterior
                    </button>
                    <button
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                        type="button"
                        :disabled="!canGoNext"
                        @click="goToNext"
                    >
                        Próxima
                    </button>
                </div>
            </div>
        </section>

        <div
            v-if="isFormOpen"
            class="fixed inset-0 z-20 flex items-start justify-center bg-slate-900/40 px-4 py-10 backdrop-blur-sm"
            @click.self="closeForm"
        >
            <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">{{ modalTitle }}</h2>
                        <p class="text-sm text-slate-500">
                            {{ isEditing ? 'Atualize os dados do paciente.' : 'Preencha os dados para cadastrar um novo paciente.' }}
                        </p>
                    </div>
                    <button
                        class="rounded-full border border-slate-200 p-2 text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                        type="button"
                        @click="closeForm"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M6 18 18 6" />
                        </svg>
                    </button>
                </div>

                <form class="space-y-5" @submit.prevent="submitForm">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="patient-name">Nome completo</label>
                            <input
                                id="patient-name"
                                v-model="form.name"
                                class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                type="text"
                                placeholder="Nome do paciente"
                                required
                            />
                            <p v-if="formErrors.name" class="mt-1 text-xs text-red-600">{{ formErrors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="patient-status">Status</label>
                            <select
                                id="patient-status"
                                v-model="form.status"
                                class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                required
                            >
                                <option v-for="option in patientStatusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p v-if="formErrors.status" class="mt-1 text-xs text-red-600">{{ formErrors.status }}</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="patient-email">E-mail</label>
                            <input
                                id="patient-email"
                                v-model="form.email"
                                class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                type="email"
                                placeholder="email@paciente.com"
                            />
                            <p v-if="formErrors.email" class="mt-1 text-xs text-red-600">{{ formErrors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="patient-phone">Telefone</label>
                            <input
                                id="patient-phone"
                                v-model="form.phone"
                                class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                type="tel"
                                placeholder="(00) 00000-0000"
                            />
                            <p v-if="formErrors.phone" class="mt-1 text-xs text-red-600">{{ formErrors.phone }}</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="patient-fee-type">Tipo de cobrança</label>
                            <select
                                id="patient-fee-type"
                                v-model="form.sessionFeeType"
                                class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            >
                                <option v-for="option in sessionFeeTypeOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p v-if="formErrors.session_fee_type" class="mt-1 text-xs text-red-600">
                                {{ formErrors.session_fee_type }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="patient-fee-value">Valor combinado</label>
                            <input
                                id="patient-fee-value"
                                v-model="form.sessionFeeValue"
                                class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0,00"
                            />
                            <p v-if="formErrors.session_fee_value" class="mt-1 text-xs text-red-600">
                                {{ formErrors.session_fee_value }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="patient-notes">Observações</label>
                        <textarea
                            id="patient-notes"
                            v-model="form.notes"
                            class="mt-1 min-h-32 w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="Informações adicionais, horários preferidos, histórico, etc."
                        ></textarea>
                        <p v-if="formErrors.notes" class="mt-1 text-xs text-red-600">{{ formErrors.notes }}</p>
                    </div>

                    <p
                        v-if="formError"
                        class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        {{ formError }}
                    </p>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button
                            class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                            type="button"
                            @click="closeForm"
                        >
                            Cancelar
                        </button>
                        <button
                            class="inline-flex items-center rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300 disabled:cursor-not-allowed disabled:bg-blue-300"
                            type="submit"
                            :disabled="formSubmitting"
                        >
                            <svg
                                v-if="formSubmitting"
                                class="-ms-1 me-2 size-4 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            {{ submitLabel }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
