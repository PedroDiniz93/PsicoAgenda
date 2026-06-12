<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AppIcon from '../base/AppIcon.vue';
import PatientAlertsBell from '../PatientAlertsBell.vue';

interface PatientResult {
    id: number;
    name?: string;
    email?: string | null;
    phone?: string | null;
    status?: string | null;
}

const searchContainerRef = ref<HTMLElement | null>(null);

const props = defineProps<{
    todayLabel: string;
    userName: string;
    userEmail: string;
    searchTerm: string;
    sidebarCollapsed: boolean;
    patientResults: PatientResult[];
    patientAutocompleteLoading: boolean;
    patientAutocompleteError: string;
    showPatientAutocomplete: boolean;
}>();

const emit = defineEmits<{
    'update:searchTerm': [value: string];
    searchPatients: [];
    toggleSidebar: [];
    openMobileSidebar: [];
    closePatientAutocomplete: [];
    editPatient: [patient: PatientResult];
    openPatientRecord: [patient: PatientResult];
}>();

const handleDocumentClick = (event: MouseEvent) => {
    if (!props.showPatientAutocomplete) return;
    if (!searchContainerRef.value) return;
    if (searchContainerRef.value.contains(event.target as Node)) return;
    emit('closePatientAutocomplete');
};

onMounted(() => {
    document.addEventListener('click', handleDocumentClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
    <header class="sticky top-0 z-30 border-b border-[#e2e2e2] bg-[#f9f9f8]/95 backdrop-blur">
        <div class="mx-auto flex h-auto max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:h-16 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div class="flex min-w-0 items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <button
                        class="rounded-full p-2 text-[#42474c] transition hover:bg-[#eeeeed] hover:text-[#415f76] lg:hidden"
                        type="button"
                        aria-label="Abrir menu"
                        @click="$emit('openMobileSidebar')"
                    >
                        <AppIcon name="Menu" class="size-5" />
                    </button>
                    <div>
                        <p class="section-kicker capitalize">{{ todayLabel }}</p>
                        <p class="mt-1 truncate text-sm text-[#73787d]">{{ userEmail }}</p>
                    </div>
                </div>
                <div class="hidden size-9 shrink-0 items-center justify-center rounded-full border border-[#c2c7cd] bg-white text-sm font-bold text-[#415f76] sm:flex lg:hidden">
                    {{ userName.slice(0, 1).toUpperCase() }}
                </div>
            </div>

            <form ref="searchContainerRef" class="relative w-full max-w-xl lg:flex-1" @submit.prevent="$emit('searchPatients')">
                <label class="sr-only" for="global-patient-search">Buscar pacientes</label>
                <div class="relative">
                    <AppIcon name="Search" class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-[#73787d]" />
                    <input
                        id="global-patient-search"
                        class="h-11 w-full rounded-full border border-transparent bg-[#eeeeed] pl-10 pr-4 text-sm outline-none transition focus:border-[#415f76] focus:bg-white focus:ring-2 focus:ring-[rgba(65,95,118,0.16)]"
                        :value="searchTerm"
                        placeholder="Buscar pacientes..."
                        type="search"
                        autocomplete="off"
                        @input="$emit('update:searchTerm', ($event.target as HTMLInputElement).value)"
                    />
                </div>

                <div
                    v-if="showPatientAutocomplete && searchTerm.trim().length >= 3"
                    class="absolute left-0 right-0 top-12 z-50 overflow-hidden rounded-2xl border border-[#e2e2e2] bg-white shadow-2xl"
                >
                    <div v-if="patientAutocompleteLoading" class="px-4 py-3 text-sm text-[#73787d]">
                        Buscando pacientes...
                    </div>

                    <div v-else-if="patientAutocompleteError" class="px-4 py-3 text-sm text-[#ba1a1a]">
                        {{ patientAutocompleteError }}
                    </div>

                    <div v-else-if="!patientResults.length" class="px-4 py-3 text-sm text-[#73787d]">
                        Nenhum paciente encontrado.
                    </div>

                    <ul v-else class="max-h-96 divide-y divide-[#eeeeed] overflow-auto">
                        <li v-for="patient in patientResults" :key="patient.id" class="px-4 py-3">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-[#1a1c1c]">{{ patient.name ?? `Paciente #${patient.id}` }}</p>
                                    <p class="mt-1 truncate text-xs text-[#73787d]">
                                        {{ patient.email || patient.phone || 'Sem contato cadastrado' }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    <button
                                        class="inline-flex h-8 items-center gap-1 rounded-lg border border-[#e2e2e2] px-2.5 text-xs font-semibold text-[#42474c] transition hover:bg-[#f3f4f3] hover:text-[#415f76]"
                                        type="button"
                                        @click.stop="$emit('editPatient', patient)"
                                    >
                                        <AppIcon name="Pencil" class="size-3.5" />
                                        Editar
                                    </button>
                                    <button
                                        class="inline-flex h-8 items-center gap-1 rounded-lg bg-[#415f76] px-2.5 text-xs font-semibold text-white transition hover:bg-[#2b4a60]"
                                        type="button"
                                        @click.stop="$emit('openPatientRecord', patient)"
                                    >
                                        <AppIcon name="FileText" class="size-3.5" />
                                        Prontuário
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </form>

            <div class="flex items-center justify-end gap-2">
                <PatientAlertsBell placement="header" />
                <button class="rounded-full p-2 text-[#42474c] transition hover:bg-[#eeeeed] hover:text-[#415f76]" type="button" aria-label="Ajuda">
                    <AppIcon name="CircleHelp" class="size-5" />
                </button>
            </div>
        </div>
    </header>
</template>
