<script setup lang="ts">
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

interface DashboardQuickLink {
    id: string;
    label: string;
    title: string;
    description: string;
    icon: string;
    to: { name: string };
    accent: Accent;
}

interface DashboardInsight {
    id: string;
    label: string;
    title: string;
    description: string;
    icon: string;
    accent: Accent;
}

defineProps<{
    dashboardHero: DashboardHero;
    reportLoading: boolean;
    reportError: string;
    dashboardMetrics: DashboardMetric[];
    primaryActions: DashboardAction[];
    quickLinks: DashboardQuickLink[];
    insightCards: DashboardInsight[];
}>();

defineEmits<{
    refresh: [];
}>();

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
                    <button class="btn-secondary h-10 disabled:opacity-60" type="button" :disabled="reportLoading" @click="$emit('refresh')">
                        <AppIcon name="RefreshCw" :class="['size-4', reportLoading ? 'animate-spin' : '']" />
                        Atualizar
                    </button>
                </div>
            </div>

            <p v-if="reportError" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ reportError }}
            </p>

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
                <div>
                    <p class="section-kicker">Contexto</p>
                    <h3 class="mt-1 text-xl font-semibold text-slate-950">Leitura do período</h3>
                </div>

                <div class="mt-5 space-y-3">
                    <article
                        v-for="card in insightCards"
                        :key="card.id"
                        :class="['rounded-xl border border-[#e2e2e2] p-4', accentClasses[card.accent].soft]"
                    >
                        <div class="flex items-start gap-3">
                            <span :class="['rounded-xl p-2', accentClasses[card.accent].icon]">
                                <AppIcon :name="card.icon" class="size-5" />
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-[#73787d]">{{ card.label }}</p>
                                <h4 class="mt-1 text-base font-semibold text-slate-950">{{ card.title }}</h4>
                                <p class="mt-1 text-sm leading-5 text-[#58635f]">{{ card.description }}</p>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-6 shadow-sm">
                <div>
                    <p class="section-kicker">Protocolo</p>
                    <h3 class="mt-1 text-xl font-semibold text-slate-950">Ações operacionais</h3>
                </div>

                <div class="mt-5 grid gap-3">
                    <RouterLink
                        v-for="link in quickLinks"
                        :key="link.id"
                        :to="link.to"
                        class="group flex items-center gap-3 rounded-xl border border-[#e2e2e2] bg-white p-4 text-left transition hover:-translate-y-0.5 hover:border-[#c2c7cd] hover:bg-[#f9f9f8] hover:shadow-sm"
                    >
                        <span :class="['rounded-xl p-2 transition group-hover:scale-105', accentClasses[link.accent].icon]">
                            <AppIcon :name="link.icon" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-xs font-semibold text-[#73787d]">{{ link.label }}</span>
                            <span class="mt-0.5 block text-sm font-semibold text-slate-950">{{ link.title }}</span>
                            <span class="mt-0.5 block text-sm leading-5 text-[#58635f]">{{ link.description }}</span>
                        </span>
                        <AppIcon name="ChevronRight" class="size-4 shrink-0 text-[#73787d]" />
                    </RouterLink>
                </div>
            </section>
        </div>
    </section>
</template>
