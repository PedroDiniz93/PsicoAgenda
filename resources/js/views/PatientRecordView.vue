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
const statusBadgeClasses = {
    active: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
    paused: 'bg-amber-100 text-amber-800 ring-amber-200',
    closed: 'bg-slate-200 text-slate-700 ring-slate-300',
};

const hasRecords = computed(() => records.value.length > 0);
const isEditingRecord = computed(() => Boolean(editingRecord.value));
const recordFormTitle = computed(() => (isEditingRecord.value ? 'Editar anotação' : 'Nova anotação'));
const recordFormSubmitLabel = computed(() => (isEditingRecord.value ? 'Salvar alterações' : 'Adicionar ao prontuário'));
const hasActiveFilters = computed(() => Object.values(recordFiltersApplied).some((value) => Boolean(value)));
const patientInitials = computed(() => {
    const name = patient.value?.name ?? 'Paciente';
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
});
const patientStatusLabel = computed(() => statusLabels[patient.value?.status] ?? 'Indefinido');
const patientStatusClass = computed(
    () => statusBadgeClasses[patient.value?.status] ?? 'bg-slate-100 text-slate-700 ring-slate-200'
);
const recordStats = computed(() => [
    { label: 'Registros', value: recordPagination.total ?? 0 },
    { label: 'Objetivos', value: objectiveFilterOptions.value.length },
    { label: 'Técnicas', value: techniqueFilterOptions.value.length },
]);

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
    <div class="mx-auto min-h-screen w-full max-w-7xl px-4 py-6 lg:px-8">
        <header class="mb-5 flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex size-14 shrink-0 items-center justify-center rounded-lg bg-slate-950 text-lg font-semibold text-white">
                    {{ patientInitials }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-cyan-700">Prontuário do paciente</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-normal text-slate-950">
                        {{ patient ? patient.name : 'Paciente' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">Histórico clínico, plano terapêutico e materiais vinculados.</p>
                </div>
            </div>
            <RouterLink
                :to="{ name: 'patients' }"
                class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Voltar para pacientes
            </RouterLink>
        </header>

        <div v-if="patientLoading" class="rounded-lg border border-slate-200 bg-white p-8 text-sm text-slate-500 shadow-sm">
            <div class="flex items-center gap-3">
                <svg class="size-5 animate-spin text-cyan-700" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>
                Carregando dados do paciente...
            </div>
        </div>

        <div v-else-if="patientError" class="rounded-lg border border-rose-200 bg-rose-50 p-5 text-sm text-rose-700">
            <p>{{ patientError }}</p>
            <button class="mt-3 rounded-lg border border-rose-200 px-4 py-2 text-xs font-semibold transition hover:bg-rose-100" type="button" @click="goBackToPatients">
                Voltar à lista
            </button>
        </div>

        <div v-else class="grid gap-5 lg:grid-cols-[320px_1fr]">
            <aside class="space-y-4">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500">Status</p>
                            <span :class="['mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1', patientStatusClass]">
                                {{ patientStatusLabel }}
                            </span>
                        </div>
                        <button
                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                            type="button"
                            @click="openCreateRecordForm"
                        >
                            Nova anotação
                        </button>
                    </div>

                    <div class="mt-5 space-y-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500">Contato</p>
                            <p class="mt-2 text-slate-900">{{ patient.email ?? 'E-mail não informado' }}</p>
                            <p class="mt-1 text-slate-500">{{ patient.phone ?? 'Telefone não informado' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500">Observações do cadastro</p>
                            <p class="mt-2 leading-6 text-slate-600">{{ patient.notes ?? 'Sem observações adicionais.' }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-950">Resumo do prontuário</p>
                    <div class="mt-4 grid grid-cols-3 gap-2">
                        <article v-for="stat in recordStats" :key="stat.label" class="rounded-lg bg-slate-50 px-3 py-3 text-center">
                            <p class="text-lg font-semibold text-slate-950">{{ stat.value }}</p>
                            <p class="mt-1 text-[11px] font-semibold uppercase text-slate-500">{{ stat.label }}</p>
                        </article>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-950">Filtros</p>
                        <button class="text-xs font-semibold text-cyan-700 hover:text-cyan-900" type="button" @click="recordFiltersOpen = !recordFiltersOpen">
                            {{ recordFiltersOpen ? 'Ocultar' : 'Abrir' }}
                        </button>
                    </div>
                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ recordFiltersSummary }}</p>

                    <form v-if="recordFiltersOpen" class="mt-4 space-y-3" @submit.prevent="applyRecordFilters">
                        <div class="grid grid-cols-2 gap-3">
                            <label class="text-xs font-semibold text-slate-600">
                                De
                                <input v-model="recordFilters.from" class="mt-1 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" type="date" />
                            </label>
                            <label class="text-xs font-semibold text-slate-600">
                                Até
                                <input v-model="recordFilters.to" class="mt-1 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" type="date" />
                            </label>
                        </div>
                        <label class="block text-xs font-semibold text-slate-600">
                            Objetivo
                            <input v-model="recordFilters.objective" class="mt-1 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" list="record-objectives" placeholder="Ex.: Ansiedade social" />
                        </label>
                        <label class="block text-xs font-semibold text-slate-600">
                            Técnica
                            <input v-model="recordFilters.technique" class="mt-1 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" list="record-techniques" placeholder="Ex.: Exposição" />
                        </label>
                        <label class="block text-xs font-semibold text-slate-600">
                            Busca
                            <input v-model="recordFilters.search" class="mt-1 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="Título ou notas" type="search" />
                        </label>
                        <div class="flex gap-2 pt-1">
                            <button class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50" type="button" @click="clearRecordFilters">
                                Limpar
                            </button>
                            <button class="flex-1 rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800" type="submit">
                                Aplicar
                            </button>
                        </div>
                    </form>
                    <datalist id="record-objectives">
                        <option v-for="objective in objectiveFilterOptions" :key="`objective-${objective}`" :value="objective" />
                    </datalist>
                    <datalist id="record-techniques">
                        <option v-for="technique in techniqueFilterOptions" :key="`technique-${technique}`" :value="technique" />
                    </datalist>
                </section>
            </aside>

            <main class="space-y-4">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-950">Linha do tempo clínica</h2>
                            <p class="mt-1 text-sm text-slate-500">Evoluções, intervenções, tarefas e anexos do paciente.</p>
                        </div>
                        <button
                            class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"
                            type="button"
                            @click="openCreateRecordForm"
                        >
                            Nova anotação
                        </button>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div v-if="recordsLoading" class="flex items-center gap-3 rounded-lg border border-slate-100 px-4 py-6 text-sm text-slate-500">
                        <svg class="size-5 animate-spin text-cyan-700" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Carregando anotações...
                    </div>
                    <div v-else-if="recordsError" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ recordsError }}
                    </div>
                    <div v-else-if="!hasRecords" class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                        <p class="text-sm font-semibold text-slate-700">
                            {{ hasActiveFilters ? 'Nenhuma anotação encontrada.' : 'Nenhuma anotação cadastrada.' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">Comece registrando a primeira evolução deste paciente.</p>
                    </div>
                    <div v-else class="space-y-4">
                        <article v-for="record in records" :key="record.id" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase text-slate-500">{{ formatRecordDate(record.recorded_at) }}</p>
                                    <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ record.title }}</h3>
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    <button class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50" type="button" @click="openEditRecordForm(record)">
                                        Editar
                                    </button>
                                    <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60" type="button" :disabled="recordDeletingId === record.id" @click="deleteRecord(record)">
                                        {{ recordDeletingId === record.id ? 'Excluindo...' : 'Excluir' }}
                                    </button>
                                </div>
                            </div>

                            <p v-if="record.notes" class="mt-4 whitespace-pre-line rounded-lg bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700">
                                {{ record.notes }}
                            </p>

                            <div class="mt-4 grid gap-4 xl:grid-cols-2">
                                <div v-if="record.treatment_objectives?.length">
                                    <p class="text-xs font-semibold uppercase text-slate-500">Objetivos</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span v-for="objective in record.treatment_objectives" :key="`record-${record.id}-objective-${objective}`" class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700">
                                            {{ objective }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="record.techniques?.length">
                                    <p class="text-xs font-semibold uppercase text-slate-500">Técnicas</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span v-for="technique in record.techniques" :key="`record-${record.id}-technique-${technique}`" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                            {{ technique }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="record.homework_items?.length" class="mt-4">
                                <p class="text-xs font-semibold uppercase text-slate-500">Tarefas</p>
                                <div class="mt-2 space-y-2">
                                    <div v-for="task in record.homework_items" :key="task.id ?? `${record.id}-task-${task.description}`" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm">
                                        <p class="text-slate-800">{{ task.description }}</p>
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold" :class="homeworkStatusBadges[task.status] ?? homeworkStatusBadges.pending">
                                            {{ homeworkStatusLabel(task.status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="record.attachments?.length" class="mt-4">
                                <p class="text-xs font-semibold uppercase text-slate-500">Anexos</p>
                                <div class="mt-2 grid gap-2 md:grid-cols-2">
                                    <div v-for="attachment in record.attachments" :key="attachment.id ?? `${record.id}-attachment-${attachment.name}`" class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 px-3 py-2 text-xs text-slate-600">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ attachment.name ?? 'Arquivo' }}</p>
                                            <p class="text-xs text-slate-500">{{ attachment.mime_type ?? 'Arquivo' }} · {{ formatFileSize(attachment.size) }}</p>
                                        </div>
                                        <a :href="attachment.url" class="shrink-0 rounded-lg border border-cyan-200 px-3 py-1 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-50" rel="noopener noreferrer" target="_blank">
                                            Abrir
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4 text-sm text-slate-500">
                            <p>Página {{ recordPagination.currentPage }} de {{ recordPagination.lastPage }} · {{ recordPagination.total }} registros</p>
                            <div class="flex gap-2">
                                <button class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" :disabled="recordPagination.currentPage <= 1" type="button" @click="fetchRecords(recordPagination.currentPage - 1)">
                                    Anterior
                                </button>
                                <button class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" :disabled="recordPagination.currentPage >= recordPagination.lastPage" type="button" @click="fetchRecords(recordPagination.currentPage + 1)">
                                    Próxima
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <div v-if="recordFormVisible" class="fixed inset-0 z-30 flex items-start justify-center overflow-y-auto bg-slate-950/50 px-4 py-6 backdrop-blur-sm" @click.self="closeRecordForm">
            <div class="w-full max-w-6xl rounded-lg bg-white shadow-2xl">
                <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase text-cyan-700">Plano terapêutico</p>
                        <h3 class="mt-1 text-xl font-semibold text-slate-950">{{ recordFormTitle }}</h3>
                        <p class="mt-1 text-sm text-slate-500">Registre a evolução com objetivos, técnicas, tarefas e anexos em áreas separadas.</p>
                    </div>
                    <button class="self-start rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700" type="button" @click="closeRecordForm">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M6 18 18 6" />
                        </svg>
                    </button>
                </div>

                <form class="p-5" @submit.prevent="submitRecordForm">
                    <div class="grid gap-5 lg:grid-cols-[1.15fr_0.85fr]">
                        <section class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-[1fr_220px]">
                                <label class="block text-sm font-semibold text-slate-700" for="modal-record-title">
                                    Título
                                    <input id="modal-record-title" v-model="recordForm.title" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="Ex.: Sessão 12 - avanços em autoestima" required />
                                    <span v-if="recordFormErrors.title" class="mt-1 block text-xs text-rose-600">{{ recordFormErrors.title }}</span>
                                </label>
                                <label class="block text-sm font-semibold text-slate-700" for="modal-record-date">
                                    Data e horário
                                    <input id="modal-record-date" v-model="recordForm.recordedAt" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" type="datetime-local" />
                                    <span v-if="recordFormErrors.recorded_at" class="mt-1 block text-xs text-rose-600">{{ recordFormErrors.recorded_at }}</span>
                                </label>
                            </div>
                            <label class="block text-sm font-semibold text-slate-700" for="modal-record-notes">
                                Notas clínicas
                                <textarea id="modal-record-notes" v-model="recordForm.notes" class="mt-2 min-h-96 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm leading-6 text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="Descreva observações clínicas, intervenções, respostas do paciente e encaminhamentos."></textarea>
                                <span v-if="recordFormErrors.notes" class="mt-1 block text-xs text-rose-600">{{ recordFormErrors.notes }}</span>
                            </label>
                        </section>

                        <aside class="space-y-4">
                            <section class="rounded-lg border border-slate-200 p-4">
                                <p class="text-sm font-semibold text-slate-950">Objetivos terapêuticos</p>
                                <div class="mt-3 flex gap-2">
                                    <input id="modal-objective-input" v-model="newObjective" class="h-10 min-w-0 flex-1 rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="Ex.: Reduzir ansiedade" type="text" @keyup.enter.prevent="addObjective" />
                                    <button class="rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50" type="button" @click="addObjective">Adicionar</button>
                                </div>
                                <div v-if="recordForm.treatmentObjectives.length" class="mt-3 flex flex-wrap gap-2">
                                    <span v-for="(objective, index) in recordForm.treatmentObjectives" :key="`objective-modal-${objective}-${index}`" class="inline-flex items-center gap-2 rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700">
                                        {{ objective }}
                                        <button class="text-cyan-500 transition hover:text-cyan-800" type="button" @click="removeObjective(index)">&times;</button>
                                    </span>
                                </div>
                            </section>

                            <section class="rounded-lg border border-slate-200 p-4">
                                <p class="text-sm font-semibold text-slate-950">Técnicas aplicadas</p>
                                <div class="mt-3 flex gap-2">
                                    <input id="modal-technique-input" v-model="newTechnique" class="h-10 min-w-0 flex-1 rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="Ex.: Reestruturação" type="text" @keyup.enter.prevent="addTechnique" />
                                    <button class="rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50" type="button" @click="addTechnique">Adicionar</button>
                                </div>
                                <div v-if="recordForm.techniques.length" class="mt-3 flex flex-wrap gap-2">
                                    <span v-for="(technique, index) in recordForm.techniques" :key="`technique-modal-${technique}-${index}`" class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        {{ technique }}
                                        <button class="text-emerald-500 transition hover:text-emerald-800" type="button" @click="removeTechnique(index)">&times;</button>
                                    </span>
                                </div>
                            </section>

                            <section class="rounded-lg border border-slate-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-950">Tarefas de casa</p>
                                        <p class="text-xs text-slate-500">Oriente e acompanhe o status.</p>
                                    </div>
                                    <button class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50" type="button" @click="addHomeworkItem">Adicionar</button>
                                </div>
                                <div v-if="recordForm.homeworkItems.length" class="mt-3 max-h-56 space-y-2 overflow-y-auto pr-1">
                                    <div v-for="(task, index) in recordForm.homeworkItems" :key="task.id" class="rounded-lg bg-slate-50 p-3">
                                        <input v-model="task.description" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="Descrição da tarefa" type="text" />
                                        <div class="mt-2 flex gap-2">
                                            <select v-model="task.status" class="h-10 min-w-0 flex-1 rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100">
                                                <option v-for="option in homeworkStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                            </select>
                                            <button class="rounded-lg border border-rose-200 px-3 text-xs font-semibold text-rose-700 transition hover:bg-rose-50" type="button" @click="removeHomeworkItem(index)">Remover</button>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="mt-3 text-xs text-slate-500">Nenhuma tarefa cadastrada para esta sessão.</p>
                            </section>

                            <section class="rounded-lg border border-slate-200 p-4">
                                <p class="text-sm font-semibold text-slate-950">Anexos</p>
                                <label class="mt-3 flex cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 px-4 py-5 text-center text-sm text-slate-500 transition hover:border-cyan-300 hover:text-cyan-700">
                                    <span class="font-semibold">Selecionar arquivos</span>
                                    <span class="mt-1 text-xs">PDF ou áudio</span>
                                    <input class="hidden" accept=".pdf,audio/*" multiple type="file" @change="handleAttachmentSelection" />
                                </label>

                                <div v-if="recordForm.existingAttachments.length" class="mt-3 space-y-2">
                                    <p class="text-xs font-semibold uppercase text-slate-500">Salvos</p>
                                    <div v-for="attachment in recordForm.existingAttachments" :key="attachment.id" class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-900">{{ attachment.name }}</p>
                                            <p class="text-xs text-slate-500">{{ formatFileSize(attachment.size) }}</p>
                                        </div>
                                        <button class="rounded-lg border px-3 py-1 text-xs font-semibold transition" :class="attachment.keep ? 'border-rose-200 text-rose-700 hover:bg-rose-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50'" type="button" @click="toggleExistingAttachment(attachment)">
                                            {{ attachment.keep ? 'Remover' : 'Restaurar' }}
                                        </button>
                                    </div>
                                </div>
                                <div v-if="recordForm.newAttachments.length" class="mt-3 space-y-2">
                                    <p class="text-xs font-semibold uppercase text-slate-500">Novos</p>
                                    <div v-for="(file, index) in recordForm.newAttachments" :key="`${file.name}-${index}`" class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-900">{{ file.name }}</p>
                                            <p class="text-xs text-slate-500">{{ formatFileSize(file.size) }}</p>
                                        </div>
                                        <button class="text-xs font-semibold text-rose-700 transition hover:text-rose-900" type="button" @click="removeNewAttachment(index)">Remover</button>
                                    </div>
                                </div>
                            </section>
                        </aside>
                    </div>

                    <p v-if="recordFormError" class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ recordFormError }}
                    </p>

                    <div class="mt-5 flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <button class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" type="button" @click="closeRecordForm">
                            Cancelar
                        </button>
                        <button class="inline-flex items-center rounded-lg bg-slate-950 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60" :disabled="recordFormSubmitting" type="submit">
                            <svg v-if="recordFormSubmitting" class="mr-2 size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            {{ recordFormSubmitLabel }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
