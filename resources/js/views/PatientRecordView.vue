<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const patientId = computed(() => Number(route.params.id));
const patient = ref(null);
const patientLoading = ref(true);
const patientError = ref('');

const records = ref([]);
const recordPagination = reactive({
    currentPage: 1,
    lastPage: 1,
    perPage: 10,
    total: 0,
});
const recordsLoading = ref(false);
const recordsError = ref('');

const recordFiltersOpen = ref(false);
const recordFilters = reactive({
    from: '',
    to: '',
    objective: '',
    technique: '',
    search: '',
});
const recordFiltersApplied = reactive({
    from: '',
    to: '',
    objective: '',
    technique: '',
    search: '',
});

const recordFormVisible = ref(false);
const recordFormSubmitting = ref(false);
const recordFormError = ref('');
const editingRecord = ref(null);
const recordForm = reactive({
    title: '',
    recordedAt: '',
    notes: '',
    treatmentObjectives: [],
    techniques: [],
    homeworkItems: [],
    existingAttachments: [],
    newAttachments: [],
});
const recordFormErrors = reactive({
    title: '',
    recorded_at: '',
    notes: '',
});
const recordDeletingId = ref(null);
const newObjective = ref('');
const newTechnique = ref('');

const homeworkStatusOptions = [
    { value: 'pending', label: 'Pendente' },
    { value: 'in_progress', label: 'Em andamento' },
    { value: 'done', label: 'Concluída' },
];
const homeworkStatusBadges = {
    pending: 'border-amber-200 bg-amber-50 text-amber-700',
    in_progress: 'border-blue-200 bg-blue-50 text-blue-700',
    done: 'border-emerald-200 bg-emerald-50 text-emerald-700',
};
const homeworkStatusLabel = (status) =>
    homeworkStatusOptions.find((option) => option.value === status)?.label ?? 'Pendente';

const statusLabels = {
    active: 'Ativo',
    paused: 'Pausado',
    closed: 'Encerrado',
};

const hasRecords = computed(() => records.value.length > 0);
const isEditingRecord = computed(() => Boolean(editingRecord.value));
const recordFormTitle = computed(() => (isEditingRecord.value ? 'Editar anotação' : 'Nova anotação'));
const recordFormSubmitLabel = computed(() => (isEditingRecord.value ? 'Salvar alterações' : 'Adicionar ao prontuário'));
const hasActiveFilters = computed(() => Object.values(recordFiltersApplied).some((value) => Boolean(value)));

const recordFiltersSummary = computed(() => {
    if (!hasActiveFilters.value) return 'Sem filtros aplicados no momento.';
    const summary = [];
    if (recordFiltersApplied.from) summary.push(`De ${formatDateFilter(recordFiltersApplied.from)}`);
    if (recordFiltersApplied.to) summary.push(`até ${formatDateFilter(recordFiltersApplied.to)}`);
    if (recordFiltersApplied.objective) summary.push(`Objetivo: "${recordFiltersApplied.objective}"`);
    if (recordFiltersApplied.technique) summary.push(`Técnica: "${recordFiltersApplied.technique}"`);
    if (recordFiltersApplied.search) summary.push(`Busca: "${recordFiltersApplied.search}"`);
    return summary.join(' · ');
});

const objectiveFilterOptions = computed(() => uniqueRecordsValues('treatment_objectives'));
const techniqueFilterOptions = computed(() => uniqueRecordsValues('techniques'));

const uniqueRecordsValues = (field) => {
    const set = new Set();
    records.value.forEach((record) => {
        const values = Array.isArray(record[field]) ? record[field] : [];
        values.forEach((value) => {
            if (typeof value === 'string') {
                const normalized = value.trim();
                if (normalized) set.add(normalized);
            }
        });
    });
    return Array.from(set);
};

const formatDateFilter = (value) => {
    if (!value) return '';
    try {
        return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium' }).format(new Date(value));
    } catch (error) {
        return value;
    }
};

const formatRecordDate = (value) => {
    if (!value) return 'Sem data';
    try {
        return new Intl.DateTimeFormat('pt-BR', {
            dateStyle: 'full',
            timeStyle: 'short',
        }).format(new Date(value));
    } catch (_) {
        return value;
    }
};

const toInputDateValue = (value) => {
    if (!value) return '';
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const tzOffset = date.getTimezoneOffset();
    const localDate = new Date(date.getTime() - tzOffset * 60000);
    return localDate.toISOString().slice(0, 16);
};

const fromInputDateValue = (value) => {
    if (!value) return null;
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date.toISOString();
};

const formatFileSize = (bytes) => {
    if (!bytes) return '—';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex += 1;
    }
    return `${size.toFixed(unitIndex === 0 ? 0 : 2)} ${units[unitIndex]}`;
};

const generateLocalId = () => `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;

const resetRecordForm = () => {
    recordForm.title = '';
    recordForm.recordedAt = '';
    recordForm.notes = '';
    recordForm.treatmentObjectives = [];
    recordForm.techniques = [];
    recordForm.homeworkItems = [];
    recordForm.existingAttachments = [];
    recordForm.newAttachments = [];
    newObjective.value = '';
    newTechnique.value = '';
    recordFormError.value = '';
    Object.keys(recordFormErrors).forEach((key) => {
        recordFormErrors[key] = '';
    });
};

const addObjective = () => {
    const value = newObjective.value.trim();
    if (!value) return;
    if (!recordForm.treatmentObjectives.includes(value)) {
        recordForm.treatmentObjectives = [...recordForm.treatmentObjectives, value];
    }
    newObjective.value = '';
};

const removeObjective = (index) => {
    recordForm.treatmentObjectives = recordForm.treatmentObjectives.filter((_, idx) => idx !== index);
};

const addTechnique = () => {
    const value = newTechnique.value.trim();
    if (!value) return;
    if (!recordForm.techniques.includes(value)) {
        recordForm.techniques = [...recordForm.techniques, value];
    }
    newTechnique.value = '';
};

const removeTechnique = (index) => {
    recordForm.techniques = recordForm.techniques.filter((_, idx) => idx !== index);
};

const addHomeworkItem = () => {
    recordForm.homeworkItems = [
        ...recordForm.homeworkItems,
        { id: generateLocalId(), description: '', status: 'pending' },
    ];
};

const removeHomeworkItem = (index) => {
    recordForm.homeworkItems = recordForm.homeworkItems.filter((_, idx) => idx !== index);
};

const handleAttachmentSelection = (event) => {
    const files = Array.from(event.target?.files ?? []);
    if (!files.length) return;
    recordForm.newAttachments = [...recordForm.newAttachments, ...files];
    event.target.value = '';
};

const removeNewAttachment = (index) => {
    recordForm.newAttachments = recordForm.newAttachments.filter((_, idx) => idx !== index);
};

const toggleExistingAttachment = (attachment) => {
    attachment.keep = !attachment.keep;
};

const openCreateRecordForm = () => {
    editingRecord.value = null;
    resetRecordForm();
    recordForm.recordedAt = toInputDateValue(new Date());
    recordFormVisible.value = true;
};

const openEditRecordForm = (record) => {
    editingRecord.value = record;
    recordForm.title = record.title ?? '';
    recordForm.recordedAt = toInputDateValue(record.recorded_at);
    recordForm.notes = record.notes ?? '';
    recordForm.treatmentObjectives = Array.isArray(record.treatment_objectives) ? [...record.treatment_objectives] : [];
    recordForm.techniques = Array.isArray(record.techniques) ? [...record.techniques] : [];
    recordForm.homeworkItems = Array.isArray(record.homework_items)
        ? record.homework_items.map((item) => ({
              id: item.id ?? generateLocalId(),
              description: item.description ?? '',
              status: item.status ?? 'pending',
          }))
        : [];
    recordForm.existingAttachments = Array.isArray(record.attachments)
        ? record.attachments.map((attachment) => ({
              ...attachment,
              keep: true,
          }))
        : [];
    recordForm.newAttachments = [];
    recordFormError.value = '';
    Object.keys(recordFormErrors).forEach((key) => {
        recordFormErrors[key] = '';
    });
    recordFormVisible.value = true;
};

const closeRecordForm = () => {
    recordFormVisible.value = false;
    editingRecord.value = null;
    resetRecordForm();
};

const buildRecordFormData = () => {
    const formData = new FormData();
    formData.append('title', recordForm.title.trim());
    formData.append('notes', recordForm.notes?.trim() ?? '');
    formData.append('recorded_at', fromInputDateValue(recordForm.recordedAt) ?? '');
    recordForm.treatmentObjectives.forEach((value, index) => {
        formData.append(`treatment_objectives[${index}]`, value);
    });
    recordForm.techniques.forEach((value, index) => {
        formData.append(`techniques[${index}]`, value);
    });
    recordForm.homeworkItems.forEach((item, index) => {
        if (item.id) {
            formData.append(`homework_items[${index}][id]`, item.id);
        }
        formData.append(`homework_items[${index}][description]`, item.description ?? '');
        formData.append(`homework_items[${index}][status]`, item.status ?? 'pending');
    });
    recordForm.existingAttachments
        .filter((attachment) => attachment.keep && attachment.id)
        .forEach((attachment) => {
            formData.append('existing_attachments[]', attachment.id);
        });
    recordForm.newAttachments.forEach((file) => {
        formData.append('attachments[]', file);
    });
    return formData;
};

const fetchPatient = async () => {
    patientLoading.value = true;
    patientError.value = '';
    patient.value = null;

    const id = patientId.value;
    if (!id) {
        patientError.value = 'Paciente não encontrado.';
        patientLoading.value = false;
        return;
    }

    try {
        const { data } = await axios.get(`/api/patients/${id}`);
        patient.value = data;
    } catch (error) {
        const message = error?.response?.data?.message ?? 'Não foi possível carregar o paciente.';
        patientError.value = message;
    } finally {
        patientLoading.value = false;
    }
};

const buildRecordFiltersParams = (page) => {
    const params = { page };
    Object.entries(recordFiltersApplied).forEach(([key, value]) => {
        if (value) params[key] = value;
    });
    return params;
};

const fetchRecords = async (page = 1) => {
    recordsLoading.value = true;
    recordsError.value = '';

    try {
        const params = buildRecordFiltersParams(page);
        const { data } = await axios.get(`/api/patients/${patientId.value}/records`, {
            params,
        });
        const list = Array.isArray(data?.data) ? data.data : [];
        records.value = list;
        const meta = data?.meta ?? data ?? {};
        recordPagination.currentPage = meta.current_page ?? page;
        recordPagination.lastPage = meta.last_page ?? 1;
        recordPagination.perPage = meta.per_page ?? list.length ?? 10;
        recordPagination.total = meta.total ?? list.length;
    } catch (error) {
        records.value = [];
        recordsError.value = error?.response?.data?.message ?? 'Não foi possível carregar o prontuário.';
    } finally {
        recordsLoading.value = false;
    }
};

const applyRecordFilters = () => {
    Object.keys(recordFilters).forEach((key) => {
        recordFiltersApplied[key] = recordFilters[key];
    });
    fetchRecords(1);
};

const clearRecordFilters = () => {
    Object.keys(recordFilters).forEach((key) => {
        recordFilters[key] = '';
        recordFiltersApplied[key] = '';
    });
    fetchRecords(1);
};

const submitRecordForm = async () => {
    if (!recordFormVisible.value) return;
    recordFormError.value = '';
    Object.keys(recordFormErrors).forEach((key) => {
        recordFormErrors[key] = '';
    });

    const payload = buildRecordFormData();
    recordFormSubmitting.value = true;

    try {
        const config = { headers: { 'Content-Type': 'multipart/form-data' } };
        if (isEditingRecord.value && editingRecord.value?.id) {
            payload.append('_method', 'PUT');
            await axios.post(`/api/patients/${patientId.value}/records/${editingRecord.value.id}`, payload, config);
        } else {
            await axios.post(`/api/patients/${patientId.value}/records`, payload, config);
        }
        closeRecordForm();
        await fetchRecords(1);
    } catch (error) {
        if (error?.response?.status === 422) {
            const errors = error.response.data.errors ?? {};
            let genericMessage = '';
            Object.entries(errors).forEach(([field, messages]) => {
                if (recordFormErrors[field] !== undefined) {
                    recordFormErrors[field] = messages[0];
                } else if (!genericMessage && Array.isArray(messages)) {
                    genericMessage = messages[0];
                }
            });
            recordFormError.value = genericMessage || 'Corrija os campos destacados e tente novamente.';
        } else {
            recordFormError.value = error?.response?.data?.message ?? 'Não foi possível salvar a anotação.';
        }
    } finally {
        recordFormSubmitting.value = false;
    }
};

const deleteRecord = async (record) => {
    if (!record?.id) return;
    const confirmed = window.confirm('Deseja realmente excluir esta anotação?');
    if (!confirmed) return;
    recordDeletingId.value = record.id;
    try {
        await axios.delete(`/api/patients/${patientId.value}/records/${record.id}`);
        await fetchRecords(recordPagination.currentPage);
    } catch (error) {
        const message = error?.response?.data?.message ?? 'Não foi possível excluir a anotação.';
        window.alert(message);
    } finally {
        recordDeletingId.value = null;
    }
};

const goBackToPatients = () => {
    router.push({ name: 'patients' });
};

const resetRecordFiltersState = () => {
    Object.keys(recordFilters).forEach((key) => {
        recordFilters[key] = '';
        recordFiltersApplied[key] = '';
    });
};

watch(
    () => route.params.id,
    () => {
        closeRecordForm();
        resetRecordFiltersState();
        fetchPatient();
        fetchRecords();
    }
);

onMounted(() => {
    fetchPatient();
    fetchRecords();
});
</script>

<template>
    <div class="mx-auto max-w-5xl px-4 py-10">
        <header class="mb-8 flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-white px-6 py-4 shadow">
            <div>
                <p class="text-sm font-medium text-slate-500">Prontuário</p>
                <h1 class="text-2xl font-semibold text-slate-900">
                    {{ patient ? patient.name : 'Paciente' }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">Anotações clínicas e histórico do paciente.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <RouterLink
                    :to="{ name: 'patients' }"
                    class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                >
                    <svg class="-ms-1 me-2 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
                    </svg>
                    Voltar
                </RouterLink>
            </div>
        </header>

        <section class="mb-6 rounded-2xl bg-white p-6 shadow">
            <div v-if="patientLoading" class="flex items-center gap-3 text-sm text-slate-500">
                <svg class="size-5 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>
                Carregando dados do paciente...
            </div>
            <template v-else>
                <div v-if="patientError" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ patientError }}
                    <button class="mt-2 text-xs font-semibold underline" type="button" @click="goBackToPatients">Voltar à lista</button>
                </div>
                <div v-else-if="patient" class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contato</p>
                        <p class="mt-2 text-sm text-slate-900">{{ patient.email ?? 'E-mail não informado' }}</p>
                        <p class="text-sm text-slate-500">{{ patient.phone ?? 'Telefone não informado' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">
                            {{ statusLabels[patient.status] ?? 'Indefinido' }}
                        </p>
                        <p class="text-sm text-slate-500">{{ patient.notes ?? 'Sem observações adicionais.' }}</p>
                    </div>
                </div>
            </template>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Anotações do prontuário</h2>
                    <p class="text-sm text-slate-500">Registre evoluções, hipóteses e condutas de cada atendimento.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                        type="button"
                        @click="recordFiltersOpen = !recordFiltersOpen"
                    >
                        <svg class="me-2 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                        {{ recordFiltersOpen ? 'Ocultar filtros' : 'Filtros avançados' }}
                    </button>
                    <button
                        class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                        type="button"
                        @click="openCreateRecordForm"
                    >
                        <svg class="-ms-1 me-2 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
                        </svg>
                        Nova anotação
                    </button>
                </div>
            </div>

            <transition name="fade">
                <div v-if="recordFiltersOpen" class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <form class="space-y-4" @submit.prevent="applyRecordFilters">
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <label class="text-sm font-semibold text-slate-600">
                                <span>De</span>
                                <input
                                    v-model="recordFilters.from"
                                    class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                    type="date"
                                />
                            </label>
                            <label class="text-sm font-semibold text-slate-600">
                                <span>Até</span>
                                <input
                                    v-model="recordFilters.to"
                                    class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                    type="date"
                                />
                            </label>
                            <label class="text-sm font-semibold text-slate-600">
                                <span>Objetivo</span>
                                <input
                                    v-model="recordFilters.objective"
                                    class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                    list="record-objectives"
                                    placeholder="Ex.: Ansiedade social"
                                    type="text"
                                />
                            </label>
                            <label class="text-sm font-semibold text-slate-600">
                                <span>Técnica</span>
                                <input
                                    v-model="recordFilters.technique"
                                    class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                    list="record-techniques"
                                    placeholder="Ex.: Exposição"
                                    type="text"
                                />
                            </label>
                        </div>
                        <label class="text-sm font-semibold text-slate-600">
                            <span>Buscar em títulos e notas</span>
                            <input
                                v-model="recordFilters.search"
                                class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                placeholder="Palavra-chave"
                                type="search"
                            />
                        </label>
                        <div class="flex flex-wrap justify-end gap-3">
                            <button
                                class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                                type="button"
                                @click="clearRecordFilters"
                            >
                                Limpar filtros
                            </button>
                            <button
                                class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                                type="submit"
                            >
                                Aplicar filtros
                            </button>
                        </div>
                    </form>
                    <datalist id="record-objectives">
                        <option v-for="objective in objectiveFilterOptions" :key="`objective-${objective}`" :value="objective" />
                    </datalist>
                    <datalist id="record-techniques">
                        <option v-for="technique in techniqueFilterOptions" :key="`technique-${technique}`" :value="technique" />
                    </datalist>
                </div>
            </transition>
            <p class="mb-6 text-xs text-slate-500">{{ recordFiltersSummary }}</p>

            <transition name="fade">
                <div
                    v-if="recordFormVisible"
                    class="fixed inset-0 z-30 flex items-start justify-center bg-slate-900/40 px-4 py-10 backdrop-blur-sm"
                    @click.self="closeRecordForm"
                >
                    <div class="flex w-full max-w-3xl flex-col rounded-3xl bg-white p-6 shadow-2xl max-h-[90vh]">
                        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 shrink-0">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Plano terapêutico</p>
                                <h3 class="text-xl font-semibold text-slate-900">{{ recordFormTitle }}</h3>
                                <p class="text-sm text-slate-500">Detalhe objetivos, intervenções, tarefas e anexos desta sessão.</p>
                            </div>
                            <button
                                class="rounded-full border border-slate-200 p-2 text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                type="button"
                                @click="closeRecordForm"
                            >
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M6 18 18 6" />
                                </svg>
                            </button>
                        </div>
                        <form class="flex flex-1 flex-col gap-5 overflow-hidden" @submit.prevent="submitRecordForm">
                            <div class="space-y-5 overflow-y-auto pr-2">
                                <div class="grid gap-5 md:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700" for="modal-record-title">Título</label>
                                        <input
                                            id="modal-record-title"
                                            v-model="recordForm.title"
                                            class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                            placeholder="Ex.: Sessão 12 - avanços em autoestima"
                                            required
                                        />
                                        <p v-if="recordFormErrors.title" class="mt-1 text-xs text-red-600">{{ recordFormErrors.title }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700" for="modal-record-date">Data e horário</label>
                                        <input
                                            id="modal-record-date"
                                            v-model="recordForm.recordedAt"
                                            class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                            type="datetime-local"
                                        />
                                        <p v-if="recordFormErrors.recorded_at" class="mt-1 text-xs text-red-600">{{ recordFormErrors.recorded_at }}</p>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700" for="modal-record-notes">Conteúdo textual</label>
                                    <textarea
                                        id="modal-record-notes"
                                        v-model="recordForm.notes"
                                        class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        rows="6"
                                        placeholder="Descreva as observações clínicas, intervenções e encaminhamentos."
                                    />
                                    <p v-if="recordFormErrors.notes" class="mt-1 text-xs text-red-600">{{ recordFormErrors.notes }}</p>
                                </div>
                                <div class="grid gap-5 md:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700" for="modal-objective-input">Objetivos terapêuticos</label>
                                        <div class="mt-2 flex gap-2">
                                            <input
                                                id="modal-objective-input"
                                                v-model="newObjective"
                                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                placeholder="Ex.: Reduzir crises de ansiedade"
                                                type="text"
                                                @keyup.enter.prevent="addObjective"
                                            />
                                            <button
                                                class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                                                type="button"
                                                @click="addObjective"
                                            >
                                                Adicionar
                                            </button>
                                        </div>
                                        <div v-if="recordForm.treatmentObjectives.length" class="mt-3 flex flex-wrap gap-2">
                                            <span
                                                v-for="(objective, index) in recordForm.treatmentObjectives"
                                                :key="`objective-modal-${objective}-${index}`"
                                                class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"
                                            >
                                                {{ objective }}
                                                <button class="text-blue-500 transition hover:text-blue-800" type="button" @click="removeObjective(index)">
                                                    &times;
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700" for="modal-technique-input">Técnicas aplicadas</label>
                                        <div class="mt-2 flex gap-2">
                                            <input
                                                id="modal-technique-input"
                                                v-model="newTechnique"
                                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                placeholder="Ex.: Reestruturação cognitiva"
                                                type="text"
                                                @keyup.enter.prevent="addTechnique"
                                            />
                                            <button
                                                class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                                                type="button"
                                                @click="addTechnique"
                                            >
                                                Adicionar
                                            </button>
                                        </div>
                                        <div v-if="recordForm.techniques.length" class="mt-3 flex flex-wrap gap-2">
                                            <span
                                                v-for="(technique, index) in recordForm.techniques"
                                                :key="`technique-modal-${technique}-${index}`"
                                                class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700"
                                            >
                                                {{ technique }}
                                                <button class="text-emerald-500 transition hover:text-emerald-800" type="button" @click="removeTechnique(index)">
                                                    &times;
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white/70 p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">Tarefas de casa</p>
                                            <p class="text-xs text-slate-500">Registre orientações e atualize o status.</p>
                                        </div>
                                        <button
                                            class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                                            type="button"
                                            @click="addHomeworkItem"
                                        >
                                            Adicionar tarefa
                                        </button>
                                    </div>
                                    <div v-if="recordForm.homeworkItems.length" class="mt-4 space-y-3 max-h-64 overflow-y-auto pr-1">
                                        <div
                                            v-for="(task, index) in recordForm.homeworkItems"
                                            :key="task.id"
                                            class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3"
                                        >
                                            <div class="flex flex-col gap-2 md:flex-row md:items-center">
                                                <input
                                                    v-model="task.description"
                                                    class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    placeholder="Descrição da tarefa"
                                                    type="text"
                                                />
                                                <select
                                                    v-model="task.status"
                                                    class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                >
                                                    <option v-for="option in homeworkStatusOptions" :key="option.value" :value="option.value">
                                                        {{ option.label }}
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="mt-2 text-right">
                                                <button
                                                    class="text-xs font-semibold text-red-500 transition hover:text-red-700"
                                                    type="button"
                                                    @click="removeHomeworkItem(index)"
                                                >
                                                    Remover tarefa
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-else class="mt-3 text-xs text-slate-500">Nenhuma tarefa cadastrada para esta sessão.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white/70 p-4">
                                    <p class="text-sm font-semibold text-slate-800">Anexos (PDF/áudio)</p>
                                    <p class="text-xs text-slate-500">Envie materiais de apoio ou gravações relevantes.</p>
                                    <label class="mt-3 flex w-full cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500 hover:border-blue-300 hover:text-blue-700">
                                        <svg class="mb-2 size-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-8-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        <span class="font-semibold">Clique para selecionar arquivos</span>
                                        <input class="hidden" accept=".pdf,audio/*" multiple type="file" @change="handleAttachmentSelection" />
                                    </label>
                                    <div v-if="recordForm.existingAttachments.length" class="mt-4 space-y-2">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Anexos salvos</p>
                                        <div
                                            v-for="attachment in recordForm.existingAttachments"
                                            :key="attachment.id"
                                            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-sm text-slate-700"
                                        >
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ attachment.name }}</p>
                                                <p class="text-xs text-slate-500">{{ attachment.mime_type }} · {{ formatFileSize(attachment.size) }}</p>
                                            </div>
                                            <button
                                                class="rounded-xl border px-3 py-1 text-xs font-semibold transition"
                                                :class="attachment.keep ? 'border-red-200 text-red-600 hover:border-red-300 hover:text-red-700' : 'border-emerald-200 text-emerald-700 hover:border-emerald-300 hover:text-emerald-800'"
                                                type="button"
                                                @click="toggleExistingAttachment(attachment)"
                                            >
                                                {{ attachment.keep ? 'Remover' : 'Restaurar' }}
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="recordForm.newAttachments.length" class="mt-4 space-y-2">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Anexos recentes</p>
                                        <div
                                            v-for="(file, index) in recordForm.newAttachments"
                                            :key="`${file.name}-${index}`"
                                            class="flex items-center justify-between rounded-xl border border-slate-100 bg-white px-3 py-2 text-sm text-slate-700"
                                        >
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ file.name }}</p>
                                                <p class="text-xs text-slate-500">{{ file.type || 'Arquivo' }} · {{ formatFileSize(file.size) }}</p>
                                            </div>
                                            <button
                                                class="text-xs font-semibold text-red-500 transition hover:text-red-700"
                                                type="button"
                                                @click="removeNewAttachment(index)"
                                            >
                                                Remover
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-if="recordFormError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
                                {{ recordFormError }}
                            </p>
                            <div class="flex justify-end gap-3 shrink-0">
                                <button
                                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                                    type="button"
                                    @click="closeRecordForm"
                                >
                                    Cancelar
                                </button>
                                <button
                                    class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300 disabled:cursor-not-allowed disabled:bg-blue-300"
                                    :disabled="recordFormSubmitting"
                                    type="submit"
                                >
                                    <svg v-if="recordFormSubmitting" class="-ms-1 me-2 size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                    </svg>
                                    {{ recordFormSubmitLabel }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </transition>
            <div v-if="recordsLoading" class="flex items-center gap-3 rounded-2xl border border-slate-100 px-4 py-3 text-sm text-slate-500">
                <svg class="size-5 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>
                Carregando anotações...
            </div>
            <div v-else-if="recordsError" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ recordsError }}
            </div>
            <div v-else>
                <div v-if="!hasRecords" class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    {{
                        hasActiveFilters
                            ? 'Nenhuma anotação encontrada para os filtros aplicados.'
                            : 'Nenhuma anotação cadastrada. Comece registrando a primeira evolução.'
                    }}
                </div>
                <div v-else class="space-y-4">
                    <article
                        v-for="record in records"
                        :key="record.id"
                        class="rounded-2xl border border-slate-100 bg-white px-5 py-4 shadow-sm"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ formatRecordDate(record.recorded_at) }}
                                </p>
                                <h3 class="text-lg font-semibold text-slate-900">{{ record.title }}</h3>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                                    type="button"
                                    @click="openEditRecordForm(record)"
                                >
                                    Editar
                                </button>
                                <button
                                    class="rounded-xl border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:border-red-300 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-60"
                                    type="button"
                                    :disabled="recordDeletingId === record.id"
                                    @click="deleteRecord(record)"
                                >
                                    <svg v-if="recordDeletingId === record.id" class="-ms-1 me-2 inline size-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                    </svg>
                                    Excluir
                                </button>
                            </div>
                        </div>
                        <div class="mt-4 space-y-4 text-sm text-slate-700">
                            <div v-if="record.treatment_objectives?.length">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Objetivos focados</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span
                                        v-for="objective in record.treatment_objectives"
                                        :key="`record-${record.id}-objective-${objective}`"
                                        class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"
                                    >
                                        {{ objective }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="record.techniques?.length">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Técnicas aplicadas</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span
                                        v-for="technique in record.techniques"
                                        :key="`record-${record.id}-technique-${technique}`"
                                        class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700"
                                    >
                                        {{ technique }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="record.homework_items?.length" class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tarefas de casa</p>
                                <div
                                    v-for="task in record.homework_items"
                                    :key="task.id ?? `${record.id}-task-${task.description}`"
                                    class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-sm"
                                >
                                    <p class="text-slate-800">{{ task.description }}</p>
                                    <span
                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                                        :class="homeworkStatusBadges[task.status] ?? homeworkStatusBadges.pending"
                                    >
                                        {{ homeworkStatusLabel(task.status) }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="record.attachments?.length" class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Anexos</p>
                                <div
                                    v-for="attachment in record.attachments"
                                    :key="attachment.id ?? `${record.id}-attachment-${attachment.name}`"
                                    class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white px-3 py-2 text-xs text-slate-600"
                                >
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ attachment.name ?? 'Arquivo' }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ attachment.mime_type ?? 'Arquivo' }} · {{ formatFileSize(attachment.size) }}
                                        </p>
                                    </div>
                                    <a
                                        :href="attachment.url"
                                        class="rounded-xl border border-blue-200 px-3 py-1 text-xs font-semibold text-blue-600 transition hover:border-blue-300 hover:text-blue-800"
                                        rel="noopener noreferrer"
                                        target="_blank"
                                    >
                                        Abrir
                                    </a>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Notas clínicas</p>
                                <p v-if="record.notes" class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                    {{ record.notes }}
                                </p>
                                <p v-else class="mt-1 text-xs text-slate-400">Sem notas textuais nesta sessão.</p>
                            </div>
                        </div>
                    </article>

                    <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
                        <p>
                            Mostrando página {{ recordPagination.currentPage }} de {{ recordPagination.lastPage }}
                            ({{ recordPagination.total }} registros)
                        </p>
                        <div class="flex gap-2">
                            <button
                                class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="recordPagination.currentPage <= 1"
                                type="button"
                                @click="fetchRecords(recordPagination.currentPage - 1)"
                            >
                                Anterior
                            </button>
                            <button
                                class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="recordPagination.currentPage >= recordPagination.lastPage"
                                type="button"
                                @click="fetchRecords(recordPagination.currentPage + 1)"
                            >
                                Próxima
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
