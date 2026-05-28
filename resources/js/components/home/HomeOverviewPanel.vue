<script setup lang="ts">
import { RouterLink } from 'vue-router';

defineProps<{
    reportLoading: boolean;
    reportError: string;
    dashboardMetrics: Array<any>;
    quickLinks: Array<any>;
}>();

defineEmits<{
    refresh: [];
}>();
</script>

<template>
    <section class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <RouterLink
                v-for="link in quickLinks"
                :key="link.id"
                :to="link.to"
                class="group rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-cyan-200 hover:shadow-md"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-500">{{ link.label }}</p>
                        <h3 class="mt-2 text-base font-semibold text-slate-950">{{ link.title }}</h3>
                        <p class="mt-1 text-sm leading-5 text-slate-600">{{ link.description }}</p>
                    </div>
                    <span class="rounded-lg bg-slate-100 p-2 text-slate-500 transition group-hover:bg-cyan-50 group-hover:text-cyan-700">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" />
                        </svg>
                    </span>
                </div>
            </RouterLink>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-semibold text-cyan-700">Painel do consultório</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-normal text-slate-950">Rotina clínica e financeira em um só lugar</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        Acompanhe presença, recebimentos, pendências e os próximos movimentos da sua agenda.
                    </p>
                </div>
            </div>

            <p v-if="reportError" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ reportError }}
            </p>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="metric in dashboardMetrics"
                    :key="metric.id"
                    :class="['rounded-lg border p-4', metric.tone]"
                >
                    <p class="text-xs font-semibold uppercase text-current/60">{{ metric.label }}</p>
                    <p class="mt-2 text-2xl font-semibold tracking-normal">{{ metric.value }}</p>
                    <p class="mt-2 text-sm text-current/70">{{ metric.detail }}</p>
                </article>
            </div>
        </section>

    </section>
</template>
