<script setup lang="ts">
import { RouterLink } from 'vue-router';
import AppIcon from '../base/AppIcon.vue';

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
                class="group rounded-2xl border border-[#e2ddd3] bg-white/95 p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#c9c1b3] hover:shadow-md"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="section-kicker">{{ link.label }}</p>
                        <h3 class="mt-2 text-base font-semibold text-slate-950">{{ link.title }}</h3>
                        <p class="mt-1 text-sm leading-5 text-[#58635f]">{{ link.description }}</p>
                    </div>
                    <span class="rounded-xl bg-[#f6f2ea] p-2 text-[#58635f] transition group-hover:bg-[#e9efe9] group-hover:text-[#3f4f46]">
                        <AppIcon :name="link.icon" class="size-5" />
                    </span>
                </div>
            </RouterLink>
        </div>

        <section class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="section-kicker">Painel do consultório</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-normal text-slate-950">Rotina clínica e financeira em um só lugar</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#58635f]">
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
                    :class="['rounded-xl border p-4', metric.tone]"
                >
                    <p class="text-xs font-semibold uppercase text-current/60">{{ metric.label }}</p>
                    <p class="mt-2 text-2xl font-semibold tracking-normal">{{ metric.value }}</p>
                    <p class="mt-2 text-sm text-current/70">{{ metric.detail }}</p>
                </article>
            </div>
        </section>

    </section>
</template>
