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

const recordFormVisible = ref(false);
const recordFormSubmitting = ref(false);
const recordFormError = ref('');
const editingRecord = ref(null);
const recordForm = reactive({
    title: '',
    recordedAt: '',
    notes: '',
});
const recordFormErrors = reactive({
    title: '',
    recorded_at: '',
    notes: '',
});
const recordDeletingId = ref(null);

const hasRecords = computed(() => records.value.length > 0);
const isEditingRecord = computed(() => Boolean(editingRecord.value));
const recordFormTitle = computed(() => (isEditingRecord.value ? 'Editar anotação' : 'Nova anotação'));
const recordFormSubmitLabel = computed(() => (isEditingRecord.value ? 'Salvar alterações' : 'Adicionar ao prontuário'));

const statusLabels = {
    active: 'Ativo',
    paused: 'Pausado',
    closed: 'Encerrado',
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

const resetRecordForm = () => {
    recordForm.title = '';
    recordForm.recordedAt = '';
    recordForm.notes = '';
    recordFormError.value = '';
    Object.keys(recordFormErrors).forEach((key) => {
        recordFormErrors[key] = '';
    });
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

const fetchRecords = async (page = 1) => {
    recordsLoading.value = true;
    recordsError.value = '';

    try {
        const { data } = await axios.get(`/api/patients/${patientId.value}/records`, {
            params: { page },
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

const sanitizeRecordPayload = () => ({
    title: recordForm.title.trim(),
    notes: recordForm.notes.trim(),
    recorded_at: fromInputDateValue(recordForm.recordedAt),
});

const submitRecordForm = async () => {
    if (!recordFormVisible.value) return;
    recordFormError.value = '';
    Object.keys(recordFormErrors).forEach((key) => {
        recordFormErrors[key] = '';
    });

    const payload = sanitizeRecordPayload();

    recordFormSubmitting.value = true;
    try {
        if (isEditingRecord.value && editingRecord.value?.id) {
            await axios.put(`/api/patients/${patientId.value}/records/${editingRecord.value.id}`, payload);
        } else {
            await axios.post(`/api/patients/${patientId.value}/records`, payload);
        }
        closeRecordForm();
        await fetchRecords(1);
    } catch (error) {
        if (error?.response?.status === 422) {
            const errors = error.response.data.errors ?? {};
            Object.entries(errors).forEach(([field, messages]) => {
                if (recordFormErrors[field] !== undefined) {
                    recordFormErrors[field] = messages[0];
                }
            });
            recordFormError.value = 'Corrija os campos destacados e tente novamente.';
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

watch(
    () => route.params.id,
    () => {
        closeRecordForm();
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

            <div v-if="recordFormVisible" class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">{{ recordFormTitle }}</h3>
                    <button class="text-sm font-semibold text-slate-500 hover:text-slate-900" type="button" @click="closeRecordForm">
                        Cancelar
                    </button>
                </div>
                <form class="grid gap-4" @submit.prevent="submitRecordForm">
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="record-title">Título</label>
                        <input
                            id="record-title"
                            v-model="recordForm.title"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="Ex.: Sessão 12 - avanços em autoestima"
                            required
                        />
                        <p v-if="recordFormErrors.title" class="mt-1 text-xs text-red-600">{{ recordFormErrors.title }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="record-date">Data e horário</label>
                        <input
                            id="record-date"
                            v-model="recordForm.recordedAt"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            type="datetime-local"
                        />
                        <p v-if="recordFormErrors.recorded_at" class="mt-1 text-xs text-red-600">{{ recordFormErrors.recorded_at }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="record-notes">Conteúdo</label>
                        <textarea
                            id="record-notes"
                            v-model="recordForm.notes"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            rows="6"
                            placeholder="Descreva as observações clínicas, intervenções e encaminhamentos."
                            required
                        />
                        <p v-if="recordFormErrors.notes" class="mt-1 text-xs text-red-600">{{ recordFormErrors.notes }}</p>
                    </div>
                    <p v-if="recordFormError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
                        {{ recordFormError }}
                    </p>
                    <div class="flex justify-end gap-3">
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
                    Nenhuma anotação cadastrada. Comece registrando a primeira evolução.
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
                        <p class="mt-3 whitespace-pre-line text-sm text-slate-700">
                            {{ record.notes }}
                        </p>
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
