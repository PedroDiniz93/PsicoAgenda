<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import AppIcon from '../base/AppIcon.vue';

type Accent = 'primary' | 'success' | 'warning' | 'tertiary' | 'error';

interface DashboardHero {
    kicker: string;
    title: string;
    description: string;
}

interface DashboardMetric {
    id: string;
    label: string;
    value: string | number;
    detail: string;
    icon: string;
    accent: Accent;
}

interface DashboardAction {
    id: string;
    label: string;
    icon: string;
    to: { name: string };
    variant: 'primary' | 'secondary';
}

interface NextPatient {
    id: number;
    patient: {
        id: number;
        name: string;
    };
    start_at: string;
    end_at: string;
    time_label: string;
    modality: string;
    modality_label: string;
    status: string;
    status_label: string;
    status_accent: Accent;
}

interface WeeklyAttendanceDay {
    key: string;
    label: string;
    full_label: string;
    count: number;
    ratio: number;
}

interface WeeklyAttendances {
    label: string;
    from: string;
    to: string;
    total: number;
    average_daily: number;
    days: WeeklyAttendanceDay[];
}

const props = defineProps<{
    dashboardHero: DashboardHero;
    dashboardLoading: boolean;
    dashboardError: string;
    dashboardMetrics: DashboardMetric[];
    primaryActions: DashboardAction[];
    nextPatients: NextPatient[];
    weeklyAttendances: WeeklyAttendances;
}>();

defineEmits<{
    refresh: [];
}>();

const dashboardHero = computed(() => props.dashboardHero);
const dashboardLoading = computed(() => props.dashboardLoading);
const dashboardError = computed(() => props.dashboardError);
const dashboardMetrics = computed(() => props.dashboardMetrics);
const primaryActions = computed(() => props.primaryActions);
const nextPatients = computed(() => props.nextPatients);
const weeklyAttendances = computed(() => props.weeklyAttendances);

const weeklyMax = computed(() => Math.max(0, ...weeklyAttendances.value.days.map((day) => day.count)));
const weeklyAverage = computed(() => Number(weeklyAttendances.value.average_daily ?? 0).toFixed(1));

const accentClasses: Record<Accent, { border: string; icon: string; soft: string; value: string }> = {
    primary: {
        border: 'border-l-[#415f76]',
        icon: 'bg-[#cae6ff] text-[#415f76]',
        soft: 'bg-[#f4f8fb]',
        value: 'text-[#415f76]',
    },
    success: {
        border: 'border-l-[#4c6455]',
        icon: 'bg-[#cbe6d4] text-[#4c6455]',
        soft: 'bg-[#eff4f0]',
        value: 'text-[#4c6455]',
    },
    warning: {
        border: 'border-l-[#d8c3a3]',
        icon: 'bg-[#f7f2e9] text-[#6b593d]',
        soft: 'bg-[#fbf7ef]',
        value: 'text-[#6b593d]',
    },
    tertiary: {
        border: 'border-l-[#79746d]',
        icon: 'bg-[#e8e1d9] text-[#605b55]',
        soft: 'bg-[#f7f4f1]',
        value: 'text-[#605b55]',
    },
    error: {
        border: 'border-l-[#ba1a1a]',
        icon: 'bg-[#ffdad6] text-[#ba1a1a]',
        soft: 'bg-[#fff6f5]',
        value: 'text-[#ba1a1a]',
    },
};

const appointmentAccent = (appointment: NextPatient): Accent => (
    appointment.modality === 'in_person' ? 'success' : 'primary'
);

const patientInitials = (name: string) => name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('') || 'P';

const barHeight = (count: number) => {
    if (weeklyMax.value <= 0 || count <= 0) {
        return '0%';
    }

    return `${Math.max(8, Math.round((count / weeklyMax.value) * 100))}%`;
};
</script>

<template>
    <section class="space-y-6">
        <section class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-6 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="section-kicker">{{ dashboardHero.kicker }}</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-normal text-slate-950">{{ dashboardHero.title }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#58635f]">{{ dashboardHero.description }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <RouterLink
                        v-for="action in primaryActions"
                        :key="action.id"
                        :to="action.to"
                        :class="[action.variant === 'primary' ? 'btn-primary' : 'btn-secondary', 'h-10']"
                    >
                        <AppIcon :name="action.icon" class="size-4" />
                        {{ action.label }}
                    </RouterLink>
                </div>
            </div>

            <p v-if="dashboardError" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ dashboardError }}
                <button class="ml-2 font-semibold underline underline-offset-2" type="button" @click="$emit('refresh')">
                    Tentar novamente
                </button>
            </p>

            <p v-if="dashboardLoading" class="mt-4 text-sm text-[#58635f]">Carregando resumo da dashboard...</p>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="metric in dashboardMetrics"
                    :key="metric.id"
                    :class="[
                        'rounded-xl border border-[#e2e2e2] border-l-4 bg-white p-4 shadow-sm',
                        accentClasses[metric.accent].border,
                    ]"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold text-[#73787d]">{{ metric.label }}</p>
                            <p :class="['mt-2 text-3xl font-semibold tracking-normal', accentClasses[metric.accent].value]">
                                {{ metric.value }}
                            </p>
                        </div>
                        <span :class="['rounded-xl p-2', accentClasses[metric.accent].icon]">
                            <AppIcon :name="metric.icon" class="size-5" />
                        </span>
                    </div>
                    <p class="mt-3 text-sm leading-5 text-[#58635f]">{{ metric.detail }}</p>
                </article>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="mt-1 text-xl font-semibold text-slate-950">Próximos Pacientes</h3>
                    </div>

                    <RouterLink class="btn-secondary h-10" :to="{ name: 'schedule' }">
                        Ver agenda completa
                    </RouterLink>
                </div>

                <div v-if="nextPatients.length === 0" class="mt-5 rounded-xl border border-dashed border-[#d7ddd9] bg-[#f9f9f8] p-5 text-sm text-[#58635f]">
                    Sem próximos pacientes agendados.
                </div>

                <div v-else class="mt-5 space-y-3">
                    <RouterLink
                        v-for="appointment in nextPatients"
                        :key="appointment.id"
                        :to="{ name: 'patient-records', params: { id: appointment.patient.id } }"
                        class="group flex items-center justify-between gap-4 rounded-xl border border-[#e2e2e2] bg-white p-4 text-left transition hover:-translate-y-0.5 hover:border-[#c2c7cd] hover:bg-[#f9f9f8] hover:shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <div :class="['flex size-12 items-center justify-center rounded-full text-sm font-semibold', accentClasses[appointmentAccent(appointment)].icon]">
                                {{ patientInitials(appointment.patient.name) }}
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-950">{{ appointment.patient.name }}</h4>
                                <p class="mt-1 text-sm text-[#58635f]">{{ appointment.time_label }} · {{ appointment.modality_label }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span :class="['rounded-full px-3 py-1 text-xs font-semibold', accentClasses[appointmentAccent(appointment)].soft, accentClasses[appointmentAccent(appointment)].value]">
                                {{ appointment.status_label }}
                            </span>
                            <AppIcon name="ChevronRight" class="size-4 shrink-0 text-[#73787d] transition group-hover:translate-x-0.5" />
                        </div>
                    </RouterLink>
                </div>
            </section>

            <section class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="mt-1 text-xl font-semibold text-slate-950">Atendimentos Semanais</h3>
                    </div>

                    <div class="flex items-center gap-2 text-xs font-semibold text-[#58635f]">
                        <span class="size-3 rounded-full bg-[#415f76]"></span>
                        Realizados
                    </div>
                </div>

                <div class="mt-5 flex h-52 items-end justify-between gap-2 px-1">
                    <div v-for="day in weeklyAttendances.days" :key="day.key" class="flex flex-1 flex-col items-center gap-3">
                        <div class="flex h-44 w-full items-end rounded-t-lg bg-[#eeeeed] px-2 pb-0.5">
                            <div class="chart-bar w-full rounded-t-lg bg-[#5a7890]" :style="{ height: barHeight(day.count) }"></div>
                        </div>
                        <span class="text-xs font-semibold text-[#73787d]">{{ day.label }}</span>
                    </div>
                </div>

                <div class="mt-5 border-t border-[#e2e2e2] pt-4">
                    <div class="flex items-center justify-between gap-4 text-sm">
                        <span class="text-[#58635f]">Média Diária</span>
                        <span class="font-semibold text-[#415f76]">{{ weeklyAverage }} sessões</span>
                    </div>
                </div>
            </section>
        </div>
    </section>
</template>
