<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { formatMoney } from '../utils/formatters';

const exportPatientsLoading = ref(false);
const exportPatientsError = ref('');
const exportPatients = ref([]);
const exportSelection = reactive({
    selected: [],
    selectAll: false,
});
const exportSubmitting = ref(false);
const exportTypeOptions = [
    {
        value: 'patients',
        label: 'Pacientes',
        description: 'Dados cadastrais como nome, contato e plano.',
    },
    {
        value: 'appointments',
        label: 'Agendamentos',
        description: 'Histórico dos atendimentos realizados ou planejados.',
    },
    {
        value: 'records',
        label: 'Prontuários',
        description: 'Anotações clínicas registradas por paciente.',
    },
];
const exportTypeSelection = ref(exportTypeOptions.map((option) => option.value));

const sessionFeeTypeLabels = {
    session: 'Por sessão',
    biweekly: 'Quinzenal',
    monthly: 'Mensal',
};

const patientStatusLabels = {
    active: 'Ativo',
    paused: 'Pausado',
    closed: 'Encerrado',
};

const patientStatusBadgeClasses = {
    active: 'bg-emerald-100 text-emerald-800',
    paused: 'bg-amber-100 text-amber-800',
    closed: 'bg-slate-200 text-slate-600',
};

const exportHasSelection = computed(() => exportSelection.selected.length > 0);
const exportReady = computed(
    () => exportHasSelection.value && exportTypeSelection.value.length > 0
);
const sessionFeeLabel = (type) => sessionFeeTypeLabels[type] ?? 'Não definido';

const fetchExportPatients = async () => {
    exportPatientsLoading.value = true;
    exportPatientsError.value = '';

    try {
        const { data } = await axios.get('/api/patients', { params: { per_page: 1000 } });
        const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
        exportPatients.value = list;
        exportSelection.selectAll = false;
        exportSelection.selected = [];
    } catch (error) {
        exportPatientsError.value =
            error?.response?.data?.message ?? 'Não foi possível carregar os pacientes para exportação.';
    } finally {
        exportPatientsLoading.value = false;
    }
};

const toggleExportSelectAll = () => {
    exportSelection.selectAll = !exportSelection.selectAll;
    if (exportSelection.selectAll) {
        exportSelection.selected = exportPatients.value.map((patient) => patient.id);
    } else {
        exportSelection.selected = [];
    }
};

watch(
    () => exportSelection.selected,
    () => {
        if (
            exportSelection.selected.length === exportPatients.value.length &&
            exportPatients.value.length > 0
        ) {
            exportSelection.selectAll = true;
        } else {
            exportSelection.selectAll = false;
        }
    },
    { deep: true }
);

const extractFilename = (disposition, fallback) => {
    if (!disposition) return fallback;
    const utfMatch = disposition.match(/filename\*=(?:UTF-8'')?([^;]+)/i);
    if (utfMatch?.[1]) {
        try {
            return decodeURIComponent(utfMatch[1].replace(/"/g, '').trim());
        } catch (error) {
            return utfMatch[1].replace(/"/g, '').trim();
        }
    }
    const match = disposition.match(/filename="?([^\";]+)"?/i);
    if (match?.[1]) {
        return match[1];
    }
    return fallback;
};

const downloadBlobResponse = (response, fallbackName) => {
    const blob = response?.data;
    if (!blob) {
        throw new Error('Arquivo de exportação inválido.');
    }
    const disposition =
        response?.headers?.['content-disposition'] ??
        response?.headers?.['Content-Disposition'] ??
        '';
    const filename = extractFilename(disposition, fallbackName);
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
};

const submitExport = async () => {
    if (!exportHasSelection.value || exportSubmitting.value) return;
    if (!exportTypeSelection.value.length) {
        window.alert('Selecione ao menos um tipo de informação para exportar.');
        return;
    }
    exportSubmitting.value = true;

    try {
        const ids = [...exportSelection.selected];
        const response = await axios.post(
            '/api/patients/export',
            { patient_ids: ids, types: [...exportTypeSelection.value] },
            { responseType: 'blob' }
        );

        downloadBlobResponse(
            response,
            `exportacao-pacientes-${new Date().toISOString().replace(/[:.]/g, '-')}.zip`
        );
    } catch (error) {
        window.alert(
            error?.response?.data?.message ?? 'Não foi possível exportar os dados selecionados.'
        );
    } finally {
        exportSubmitting.value = false;
    }
};

onMounted(fetchExportPatients);
</script>

<template>
    <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">Exportação de pacientes</h2>
                <p class="text-sm text-slate-500">
                    Gere arquivos separados com dados de pacientes, agendamentos e prontuários.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="exportPatientsLoading"
                    @click="fetchExportPatients"
                >
                    {{ exportPatientsLoading ? 'Carregando...' : 'Recarregar lista' }}
                </button>
                <button
                    class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                    type="button"
                    :disabled="!exportReady || exportSubmitting || exportPatientsLoading"
                    @click="submitExport"
                >
                    {{ exportSubmitting ? 'Gerando arquivo...' : 'Exportar CSV' }}
                </button>
            </div>
        </div>

        <div class="mb-6 rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
            <p class="mb-3 text-sm font-semibold text-slate-800">Tipos de informação</p>
            <div class="flex flex-col gap-2 lg:flex-row">
                <label
                    v-for="option in exportTypeOptions"
                    :key="option.value"
                    class="flex flex-1 items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-sm transition hover:border-slate-300"
                >
                    <input
                        class="mt-1 size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        v-model="exportTypeSelection"
                        type="checkbox"
                        :value="option.value"
                    />
                    <span>
                        <span class="block font-semibold text-slate-900">{{ option.label }}</span>
                        <span class="text-xs text-slate-500">{{ option.description }}</span>
                    </span>
                </label>
            </div>
            <p class="mt-3 text-xs text-slate-500">
                Os dados selecionados serão entregues em um arquivo .zip com um CSV por tipo.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-100">
            <div v-if="exportPatientsLoading" class="px-6 py-16 text-center text-sm text-slate-500">
                Carregando pacientes para exportação...
            </div>
            <div v-else-if="exportPatientsError" class="px-6 py-4 text-sm text-red-600">
                {{ exportPatientsError }}
            </div>
            <div v-else>
                <div v-if="!exportPatients.length" class="px-6 py-16 text-center text-sm text-slate-500">
                    Nenhum paciente disponível para exportação no momento.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3">
                                    <input
                                        class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        type="checkbox"
                                        :checked="exportSelection.selectAll"
                                        aria-label="Selecionar todos os pacientes para exportar"
                                        @change="toggleExportSelectAll"
                                    />
                                </th>
                                <th class="px-4 py-3 font-semibold">Paciente</th>
                                <th class="px-4 py-3 font-semibold">Contato</th>
                                <th class="px-4 py-3 font-semibold">Plano financeiro</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 font-semibold">Observações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="patient in exportPatients" :key="`export-${patient.id}`">
                                <td class="px-4 py-3 align-top">
                                    <input
                                        class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        v-model="exportSelection.selected"
                                        :value="patient.id"
                                        type="checkbox"
                                        :aria-label="`Selecionar paciente ${patient.name ?? patient.id}`"
                                    />
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <p class="font-semibold text-slate-900">
                                        {{ patient.name ?? 'Paciente sem nome' }}
                                    </p>
                                    <p class="text-xs text-slate-500">#{{ patient.id }}</p>
                                </td>
                                <td class="px-4 py-3 align-top text-sm">
                                    <p v-if="patient.email">{{ patient.email }}</p>
                                    <p v-if="patient.phone">{{ patient.phone }}</p>
                                    <p v-if="!patient.email && !patient.phone" class="text-slate-400">—</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ sessionFeeLabel(patient.session_fee_type) }}
                                    </p>
                                    <p v-if="patient.session_fee_value" class="text-xs text-slate-500">
                                        {{ formatMoney(patient.session_fee_value) }}
                                    </p>
                                    <p v-else class="text-xs text-slate-400">Valor não definido</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="patientStatusBadgeClasses[patient.status] ?? 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ patientStatusLabels[patient.status] ?? 'Sem status' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top text-sm text-slate-600">
                                    {{ patient.notes ?? 'Sem observações registradas.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="border-t border-slate-100 px-4 py-3 text-xs text-slate-500">
                        {{ exportSelection.selected.length }} paciente(s) selecionado(s) de
                        {{ exportPatients.length }} disponíveis.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
