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
const patientNameFilter = ref('');
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
const normalizedPatientNameFilter = computed(() => patientNameFilter.value.trim().toLowerCase());
const filteredExportPatients = computed(() => {
    if (!normalizedPatientNameFilter.value) return exportPatients.value;

    return exportPatients.value.filter((patient) =>
        String(patient.name ?? '').toLowerCase().includes(normalizedPatientNameFilter.value)
    );
});
const filteredPatientIds = computed(() => filteredExportPatients.value.map((patient) => patient.id));
const filteredSelectedCount = computed(() =>
    filteredPatientIds.value.filter((id) => exportSelection.selected.includes(id)).length
);
const visiblePatientsSelected = computed(
    () => filteredPatientIds.value.length > 0 && filteredSelectedCount.value === filteredPatientIds.value.length
);

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
    if (!filteredPatientIds.value.length) return;

    if (!visiblePatientsSelected.value) {
        exportSelection.selected = Array.from(
            new Set([...exportSelection.selected, ...filteredPatientIds.value])
        );
    } else {
        exportSelection.selected = exportSelection.selected.filter(
            (id) => !filteredPatientIds.value.includes(id)
        );
    }
};

watch(
    [() => exportSelection.selected, filteredPatientIds],
    () => {
        exportSelection.selectAll = visiblePatientsSelected.value;
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
    <div class="space-y-5">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold text-cyan-700">Arquivos CSV</p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-950">Dados para exportação</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Escolha as informações que devem entrar no arquivo e selecione os pacientes na grid.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-800 disabled:cursor-not-allowed disabled:opacity-50"
                        type="button"
                        :disabled="exportPatientsLoading"
                        @click="fetchExportPatients"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M20 8A7.5 7.5 0 0 0 6.2 4.8L4 7m16 10-2.2 2.2A7.5 7.5 0 0 1 4 16" />
                        </svg>
                        {{ exportPatientsLoading ? 'Carregando...' : 'Recarregar' }}
                    </button>
                    <button
                        class="inline-flex items-center gap-2 rounded-lg bg-cyan-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-800 disabled:cursor-not-allowed disabled:bg-cyan-300"
                        type="button"
                        :disabled="!exportReady || exportSubmitting || exportPatientsLoading"
                        @click="submitExport"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                        </svg>
                        {{ exportSubmitting ? 'Gerando...' : 'Exportar CSV' }}
                    </button>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-950">Tipos de informação</h3>
                    <p class="text-sm text-slate-500">
                        O download será entregue em .zip com um CSV por tipo selecionado.
                    </p>
                </div>
                <p class="text-sm font-semibold text-slate-600">
                    {{ exportTypeSelection.length }} de {{ exportTypeOptions.length }} selecionados
                </p>
            </div>
            <div class="grid gap-3 lg:grid-cols-3">
                <label
                    v-for="option in exportTypeOptions"
                    :key="option.value"
                    class="flex min-h-24 items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-sm transition"
                    :class="exportTypeSelection.includes(option.value)
                        ? 'border-cyan-200 bg-cyan-50 text-slate-700'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                >
                    <input
                        class="mt-1 size-4 rounded border-slate-300 text-cyan-700 focus:ring-cyan-600"
                        v-model="exportTypeSelection"
                        type="checkbox"
                        :value="option.value"
                    />
                    <span>
                        <span class="block font-semibold text-slate-950">{{ option.label }}</span>
                        <span class="text-xs leading-5 text-slate-500">{{ option.description }}</span>
                    </span>
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-950">Pacientes</h3>
                        <p class="text-sm text-slate-500">
                            {{ exportSelection.selected.length }} selecionado(s) de
                            {{ exportPatients.length }} pacientes carregados.
                        </p>
                    </div>
                    <label class="w-full max-w-md">
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Filtrar por nome
                        </span>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" />
                            </svg>
                            <input
                                v-model="patientNameFilter"
                                class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100"
                                type="search"
                                placeholder="Buscar paciente pelo nome"
                            />
                        </div>
                    </label>
                </div>
            </div>

            <div v-if="exportPatientsLoading" class="px-6 py-16 text-center text-sm text-slate-500">
                Carregando pacientes para exportação...
            </div>
            <div v-else-if="exportPatientsError" class="px-6 py-4 text-sm text-rose-600">
                {{ exportPatientsError }}
            </div>
            <div v-else>
                <div v-if="!exportPatients.length" class="px-6 py-16 text-center text-sm text-slate-500">
                    Nenhum paciente disponível para exportação no momento.
                </div>
                <div v-else-if="!filteredExportPatients.length" class="px-6 py-16 text-center text-sm text-slate-500">
                    Nenhum paciente encontrado para "{{ patientNameFilter }}".
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">
                                    <input
                                        class="size-4 rounded border-slate-300 text-cyan-700 focus:ring-cyan-600"
                                        type="checkbox"
                                        :checked="exportSelection.selectAll"
                                        aria-label="Selecionar todos os pacientes filtrados para exportar"
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
                            <tr
                                v-for="patient in filteredExportPatients"
                                :key="`export-${patient.id}`"
                                class="transition hover:bg-slate-50"
                            >
                                <td class="px-4 py-3 align-top">
                                    <input
                                        class="size-4 rounded border-slate-300 text-cyan-700 focus:ring-cyan-600"
                                        v-model="exportSelection.selected"
                                        :value="patient.id"
                                        type="checkbox"
                                        :aria-label="`Selecionar paciente ${patient.name ?? patient.id}`"
                                    />
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <p class="font-semibold text-slate-950">
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
                                <td class="max-w-sm px-4 py-3 align-top text-sm text-slate-600">
                                    <span class="line-clamp-2">
                                        {{ patient.notes ?? 'Sem observações registradas.' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="flex flex-col gap-1 border-t border-slate-100 px-4 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                        <span>
                            Exibindo {{ filteredExportPatients.length }} de {{ exportPatients.length }} pacientes.
                        </span>
                        <span>
                            {{ filteredSelectedCount }} selecionado(s) nesta visualização.
                        </span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
