<script setup>
import PatientsExportPanel from '../components/PatientsExportPanel.vue';
import { RouterLink } from 'vue-router';
</script>

<template>
    <div class="mx-auto max-w-6xl px-4 py-10">
        <header class="mb-8 flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-white px-6 py-4 shadow">
            <div>
                <p class="text-sm font-medium text-slate-500">Ferramentas</p>
                <h1 class="text-2xl font-semibold text-slate-900">Exportação de pacientes</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Gere arquivos em CSV com os dados completos dos pacientes selecionados.
                </p>
            </div>
            <RouterLink
                :to="{ name: 'home' }"
                class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
            >
                <svg class="-ms-1 me-2 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
                </svg>
                Dashboard
            </RouterLink>
        </header>

        <PatientsExportPanel />
    </div>
</template>
