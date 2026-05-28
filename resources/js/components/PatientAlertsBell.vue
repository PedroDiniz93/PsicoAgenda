<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAlertsStore } from '../stores/alerts';
import { useAuthStore } from '../stores/auth';

const alertsStore = useAlertsStore();
const authStore = useAuthStore();
const isPanelOpen = ref(false);
const containerRef = ref(null);
const route = useRoute();
const router = useRouter();

const inactivePatients = computed(() => alertsStore.inactivePatients ?? []);
const inactiveCount = computed(() => alertsStore.inactiveCount);
const loading = computed(() => alertsStore.loadingInactivePatients);
const error = computed(() => alertsStore.inactivePatientsError);
const thresholdDays = computed(() => alertsStore.thresholdDays);
const shouldShowBadge = computed(
    () => inactiveCount.value > 0 && alertsStore.hasUnreadInactivePatients
);
const acknowledgingAlerts = ref(false);

const badgeLabel = computed(() => {
    const count = inactiveCount.value;
    if (count > 99) return '99+';
    if (count > 0) return String(count);
    return '';
});

const dateFormatter = new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
});

const fetchAlerts = () => {
    if (!authStore.isAuthenticated) return;
    alertsStore.fetchInactivePatients();
};

const togglePanel = () => {
    if (!authStore.isAuthenticated) return;
    isPanelOpen.value = !isPanelOpen.value;
};

const closePanel = () => {
    isPanelOpen.value = false;
};

const refreshAlerts = () => {
    fetchAlerts();
};

const acknowledgeAlerts = async () => {
    if (acknowledgingAlerts.value) return;
    if (!alertsStore.hasUnreadInactivePatients) return;
    if (!inactiveCount.value) return;

    acknowledgingAlerts.value = true;

    try {
        await alertsStore.acknowledgeInactivePatients();
    } catch (error) {
        console.error(error);
    } finally {
        acknowledgingAlerts.value = false;
    }
};

const handleOutsideClick = (event) => {
    if (!isPanelOpen.value) return;
    if (!containerRef.value) return;
    if (containerRef.value.contains(event.target)) return;
    closePanel();
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
    if (authStore.isAuthenticated) {
        fetchAlerts();
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideClick);
});

watch(
    () => authStore.isAuthenticated,
    (isAuthenticated) => {
        if (isAuthenticated) {
            fetchAlerts();
        } else {
            alertsStore.reset();
            closePanel();
        }
    }
);

watch(
    () => route.fullPath,
    () => {
        closePanel();
    }
);

watch(
    () => isPanelOpen.value,
    (isOpen) => {
        if (isOpen) {
            acknowledgeAlerts();
        }
    }
);

watch(
    () => alertsStore.hasUnreadInactivePatients,
    (hasUnread) => {
        if (hasUnread && isPanelOpen.value) {
            acknowledgeAlerts();
        }
    }
);

const navigateToPatients = () => {
    closePanel();
    router.push({ name: 'patients' });
};

const formatLastActivityLabel = (patient) => {
    const days = patient?.days_since_last_appointment;
    const lastAppointment = patient?.last_appointment_at;
    const referenceDate = patient?.reference_date;

    const daysLabel = typeof days === 'number' ? `${days} dia(s) sem agendar` : 'Sem registros de agenda';

    if (lastAppointment) {
        return `${daysLabel} • Último: ${dateFormatter.format(new Date(lastAppointment))}`;
    }

    if (referenceDate) {
        return `${daysLabel} • Desde: ${dateFormatter.format(new Date(referenceDate))}`;
    }

    return daysLabel;
};
</script>

<template>
    <div v-if="authStore.isAuthenticated" ref="containerRef" class="fixed bottom-6 right-6 z-50">
        <button
            class="relative inline-flex items-center justify-center rounded-full border border-border bg-surface p-3 text-muted-foreground shadow-lg transition hover:text-primary"
            type="button"
            aria-label="Alertas de pacientes sem agendamento"
            :aria-expanded="isPanelOpen"
            @click.stop="togglePanel"
        >
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M14.25 17h5.25l-1.5-1.5a2.5 2.5 0 0 1-.73-1.77V11a6.5 6.5 0 0 0-5.32-6.39V4a1.75 1.75 0 0 0-3.5 0v.61A6.5 6.5 0 0 0 3.43 11v2.73c0 .66-.26 1.29-.73 1.77L1.2 17h5.3M9 21h6"
                />
            </svg>
            <span
                v-if="shouldShowBadge"
                class="absolute -right-1 -top-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-danger px-1 text-xs font-bold text-white"
            >
                {{ badgeLabel }}
            </span>
        </button>

        <transition name="fade">
            <div
                v-if="isPanelOpen"
                class="absolute bottom-16 right-0 w-80 rounded-2xl border border-border bg-surface p-4 text-sm text-foreground shadow-2xl"
            >
                <div class="mb-4 flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold text-foreground">Pacientes sem agendar</p>
                        <p class="text-xs text-subtle-foreground">Há mais de {{ thresholdDays }} dia(s)</p>
                    </div>
                    <button
                        class="rounded-full border border-border p-1 px-2 text-xs font-semibold text-muted-foreground transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="loading"
                        @click.stop="refreshAlerts"
                    >
                        {{ loading ? '...' : 'Atualizar' }}
                    </button>
                </div>

                <div v-if="error" class="rounded-xl bg-danger-soft p-3 text-xs text-danger">
                    <p>{{ error }}</p>
                    <button
                        class="mt-2 w-full rounded-xl border border-danger/30 px-3 py-2 text-xs font-semibold text-danger hover:bg-danger-soft"
                        type="button"
                        :disabled="loading"
                        @click.stop="refreshAlerts"
                    >
                        Tentar novamente
                    </button>
                </div>
                <div
                    v-else-if="loading"
                    class="rounded-xl border border-dashed border-border p-4 text-center text-xs text-subtle-foreground"
                >
                    Carregando alertas...
                </div>
                <div v-else-if="!inactiveCount" class="rounded-xl border border-border bg-surface-muted p-4 text-xs text-muted-foreground">
                    <p>Todos os pacientes têm agendamentos recentes.</p>
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="patient in inactivePatients"
                        :key="patient.id"
                        class="rounded-xl border border-border p-3"
                    >
                        <p class="text-sm font-semibold text-foreground">{{ patient.name ?? `Paciente #${patient.id}` }}</p>
                        <p class="text-xs text-subtle-foreground">{{ formatLastActivityLabel(patient) }}</p>
                    </div>
                    <button
                        class="w-full rounded-xl border border-border px-3 py-2 text-xs font-semibold text-muted-foreground transition hover:border-primary hover:text-primary"
                        type="button"
                        @click.stop="navigateToPatients"
                    >
                        Ver pacientes
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
