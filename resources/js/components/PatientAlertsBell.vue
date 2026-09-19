<script setup>
import { computed, inject, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAlertsStore } from '../stores/alerts';
import { useAuthStore } from '../stores/auth';
import AppIcon from './base/AppIcon.vue';
import { formatDateOnly } from '../utils/formatters';

const props = defineProps({
    placement: {
        type: String,
        default: 'fixed',
        validator: (value) => ['fixed', 'header'].includes(value),
    },
});

const alertsStore = useAlertsStore();
const authStore = useAuthStore();
const isPanelOpen = ref(false);
const containerRef = ref(null);
const route = useRoute();
const router = useRouter();
const privacyMode = inject('privacyMode', ref(false));

const inactivePatients = computed(() => alertsStore.inactivePatients ?? []);
const inactiveCount = computed(() => alertsStore.inactiveCount);
const loading = computed(() => alertsStore.loadingInactivePatients);
const error = computed(() => alertsStore.inactivePatientsError);
const thresholdDays = computed(() => alertsStore.thresholdDays);
const shouldShowBadge = computed(
    () => !privacyMode.value && inactiveCount.value > 0 && alertsStore.hasUnreadInactivePatients
);
const acknowledgingAlerts = ref(false);

const badgeLabel = computed(() => {
    const count = inactiveCount.value;
    if (count > 99) return '99+';
    if (count > 0) return String(count);
    return '';
});

const dateFormatter = (value) => formatDateOnly(value, 'America/Sao_Paulo');

const fetchAlerts = () => {
    if (privacyMode.value) return;
    if (!authStore.isAuthenticated) return;
    if (authStore.requiresEmailVerification) return;
    alertsStore.fetchInactivePatients();
};

const togglePanel = () => {
    if (!authStore.isAuthenticated) return;
    if (authStore.requiresEmailVerification) return;
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
    } catch {
        // O feedback da store é suficiente; respostas clínicas não devem ir para o console.
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
        if (isAuthenticated && !authStore.requiresEmailVerification) {
            fetchAlerts();
        } else {
            alertsStore.reset();
            closePanel();
        }
    }
);

watch(privacyMode, (enabled) => {
    if (enabled) {
        alertsStore.reset();
        closePanel();
        return;
    }

    fetchAlerts();
});

watch(
    () => authStore.requiresEmailVerification,
    (requiresEmailVerification) => {
        if (requiresEmailVerification) {
            alertsStore.reset();
            closePanel();
        } else if (authStore.isAuthenticated) {
            fetchAlerts();
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
        return `${daysLabel} • Último: ${dateFormatter(lastAppointment)}`;
    }

    if (referenceDate) {
        return `${daysLabel} • Desde: ${dateFormatter(referenceDate)}`;
    }

    return daysLabel;
};

const containerClass = computed(() =>
    props.placement === 'header'
        ? 'relative'
        : 'fixed bottom-6 right-6 z-50'
);

const buttonClass = computed(() =>
    props.placement === 'header'
        ? 'app-focus relative rounded-lg p-2 text-[var(--spa-ink-soft)] transition hover:bg-[var(--spa-surface-muted)] hover:text-[var(--spa-accent)]'
        : 'app-focus relative inline-flex items-center justify-center rounded-full border border-[var(--spa-border-soft)] bg-[var(--spa-surface)] p-3 text-[var(--spa-ink-soft)] shadow-lg shadow-slate-900/5 transition hover:text-[var(--spa-accent)]'
);

const panelClass = computed(() =>
    props.placement === 'header'
        ? 'absolute right-0 top-12 w-[min(20rem,calc(100vw-2rem))] rounded-2xl border border-[var(--spa-border-soft)] bg-[var(--spa-surface)] p-4 text-sm text-[var(--spa-ink-soft)] shadow-[var(--spa-shadow)]'
        : 'absolute bottom-16 right-0 w-80 rounded-2xl border border-[var(--spa-border-soft)] bg-[var(--spa-surface)] p-4 text-sm text-[var(--spa-ink-soft)] shadow-[var(--spa-shadow)]'
);
</script>

<template>
    <div v-if="authStore.isAuthenticated && !authStore.requiresEmailVerification" ref="containerRef" :class="containerClass">
        <button
            :class="buttonClass"
            type="button"
            aria-label="Alertas de pacientes sem agendamento"
            :aria-expanded="isPanelOpen"
            @click.stop="togglePanel"
        >
            <AppIcon name="BellRing" :class="placement === 'header' ? 'size-5' : 'size-6'" />
            <span
                v-if="shouldShowBadge"
                class="absolute -right-1 -top-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-xs font-bold text-white"
            >
                {{ badgeLabel }}
            </span>
        </button>

        <transition name="fade">
            <div
                v-if="isPanelOpen"
                :class="panelClass"
            >
                <div class="mb-4 flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold text-slate-900">Pacientes sem agendar</p>
                        <p class="text-xs text-slate-500">Há mais de {{ thresholdDays }} dia(s)</p>
                    </div>
                </div>

                <div v-if="privacyMode" class="surface-subtle p-4 text-xs text-[var(--spa-ink-muted)]">
                    Alertas ocultos no modo privacidade.
                </div>
                <div v-else-if="error" class="rounded-xl bg-red-50 p-3 text-xs text-red-700">
                    <p>{{ error }}</p>
                    <button
                        class="mt-2 w-full rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100"
                        type="button"
                        :disabled="loading"
                        @click.stop="refreshAlerts"
                    >
                        Tentar novamente
                    </button>
                </div>
                <div
                    v-else-if="loading"
                    class="rounded-xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-500"
                >
                    Carregando alertas...
                </div>
                <div v-else-if="!inactiveCount" class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-xs text-slate-500">
                    <p>Todos os pacientes têm agendamentos recentes.</p>
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="patient in inactivePatients"
                        :key="patient.id"
                        class="rounded-xl border border-slate-100 p-3"
                    >
                        <p class="text-sm font-semibold text-slate-900">{{ patient.name ?? `Paciente #${patient.id}` }}</p>
                        <p class="text-xs text-slate-500">{{ formatLastActivityLabel(patient) }}</p>
                    </div>
                    <button
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-600"
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
