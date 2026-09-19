<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
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
const route = useRoute();
const pageTitle = computed(() => String(route.meta?.title ?? 'PsicoAgenda'));

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
    privacyMode: boolean;
}>();

const emit = defineEmits<{
    'update:searchTerm': [value: string];
    searchPatients: [];
    toggleSidebar: [];
    openMobileSidebar: [];
    closePatientAutocomplete: [];
    editPatient: [patient: PatientResult];
    openPatientRecord: [patient: PatientResult];
    togglePrivacyMode: [];
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
    <header class="sticky top-0 z-30 border-b border-[var(--spa-border-soft)] bg-[color:var(--spa-bg)]/95 backdrop-blur-xl">
        <div class="mx-auto flex min-h-16 max-w-[1440px] flex-col gap-3 px-4 py-3 sm:px-6 lg:flex-row lg:items-center lg:px-8">
            <div class="flex min-w-0 items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <button
                        class="app-focus rounded-lg p-2 text-[var(--spa-ink-soft)] transition hover:bg-[var(--spa-surface-muted)] hover:text-[var(--spa-accent)] lg:hidden"
                        type="button"
                        aria-label="Abrir menu"
                        @click="$emit('openMobileSidebar')"
                    >
                        <AppIcon name="Menu" class="size-5" />
                    </button>
                    <div>
                        <p class="text-sm font-bold text-[var(--spa-ink)]">{{ pageTitle }}</p>
                        <p class="mt-0.5 text-xs capitalize text-[var(--spa-ink-muted)]">{{ todayLabel }}</p>
                    </div>
                </div>
                <div class="hidden size-9 shrink-0 items-center justify-center rounded-full border border-[var(--spa-border)] bg-[var(--spa-surface)] text-sm font-bold text-[var(--spa-accent)] sm:flex lg:hidden">
                    {{ userName.slice(0, 1).toUpperCase() }}
                </div>
            </div>

            <form ref="searchContainerRef" class="relative w-full lg:ml-auto lg:max-w-xl lg:flex-1" @submit.prevent="$emit('searchPatients')">
                <label class="sr-only" for="global-patient-search">Buscar pacientes</label>
                <div class="relative">
                    <AppIcon name="Search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-[var(--spa-ink-muted)]" />
                    <input
                        id="global-patient-search"
                        class="h-10 w-full rounded-lg border border-[var(--spa-border-soft)] bg-[var(--spa-surface)] pl-10 pr-4 text-sm text-[var(--spa-ink)] outline-none transition placeholder:text-[var(--spa-ink-muted)] focus:border-[var(--spa-accent)] focus:ring-2 focus:ring-[var(--spa-focus)] disabled:cursor-not-allowed disabled:opacity-65"
                        :value="searchTerm"
                        :placeholder="privacyMode ? 'Busca oculta no modo privacidade' : 'Buscar pacientes'"
                        type="search"
                        autocomplete="off"
                        :disabled="privacyMode"
                        @input="$emit('update:searchTerm', ($event.target as HTMLInputElement).value)"
                    />
                </div>

                <div
                    v-if="showPatientAutocomplete && searchTerm.trim().length >= 3"
                    class="absolute left-0 right-0 top-12 z-50 overflow-hidden rounded-2xl border border-[var(--spa-border-soft)] bg-[var(--spa-surface)] shadow-[var(--spa-shadow)]"
                >
                    <div v-if="patientAutocompleteLoading" class="px-4 py-3 text-sm text-[var(--spa-ink-muted)]">
                        Buscando pacientes...
                    </div>

                    <div v-else-if="patientAutocompleteError" class="px-4 py-3 text-sm text-[var(--spa-error)]">
                        {{ patientAutocompleteError }}
                    </div>

                    <div v-else-if="!patientResults.length" class="px-4 py-3 text-sm text-[var(--spa-ink-muted)]">
                        Nenhum paciente encontrado.
                    </div>

                    <ul v-else class="max-h-96 divide-y divide-[var(--spa-border-soft)] overflow-auto">
                        <li v-for="patient in patientResults" :key="patient.id" class="px-4 py-3">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-[var(--spa-ink)]">{{ patient.name ?? `Paciente #${patient.id}` }}</p>
                                    <p class="mt-1 truncate text-xs text-[var(--spa-ink-muted)]">Abrir cadastro ou prontuário</p>
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    <button
                                        class="inline-flex h-8 items-center gap-1 rounded-lg border border-[var(--spa-border-soft)] px-2.5 text-xs font-semibold text-[var(--spa-ink-soft)] transition hover:bg-[var(--spa-surface-muted)] hover:text-[var(--spa-accent)]"
                                        type="button"
                                        @click.stop="$emit('editPatient', patient)"
                                    >
                                        <AppIcon name="Pencil" class="size-3.5" />
                                        Editar
                                    </button>
                                    <button
                                        class="inline-flex h-8 items-center gap-1 rounded-lg bg-[var(--spa-accent)] px-2.5 text-xs font-semibold text-white transition hover:bg-[var(--spa-accent-hover)]"
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

            <div class="flex items-center justify-end gap-1">
                <button
                    class="app-focus rounded-lg p-2 text-[var(--spa-ink-soft)] transition hover:bg-[var(--spa-surface-muted)] hover:text-[var(--spa-accent)]"
                    :class="privacyMode ? 'bg-[var(--spa-accent-soft)] text-[var(--spa-accent)]' : ''"
                    type="button"
                    :aria-pressed="privacyMode"
                    :aria-label="privacyMode ? 'Desativar modo privacidade' : 'Ativar modo privacidade'"
                    :title="privacyMode ? 'Desativar modo privacidade' : 'Ativar modo privacidade'"
                    @click="$emit('togglePrivacyMode')"
                >
                    <AppIcon :name="privacyMode ? 'EyeOff' : 'Eye'" class="size-5" />
                </button>
                <PatientAlertsBell placement="header" />
                <div class="ml-1 hidden items-center gap-2 border-l border-[var(--spa-border-soft)] pl-3 sm:flex">
                    <span class="flex size-8 items-center justify-center rounded-full bg-[var(--spa-accent-soft)] text-xs font-bold text-[var(--spa-accent)]">
                        {{ userName.slice(0, 1).toUpperCase() }}
                    </span>
                    <div class="hidden max-w-36 xl:block">
                        <p class="truncate text-xs font-semibold text-[var(--spa-ink)]">{{ userName }}</p>
                        <p v-if="!privacyMode" class="truncate text-[10px] text-[var(--spa-ink-muted)]">{{ userEmail }}</p>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
