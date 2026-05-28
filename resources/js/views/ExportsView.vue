<script setup>
import PatientsExportPanel from '../components/PatientsExportPanel.vue';
import { RouterLink } from 'vue-router';
</script>

<template>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                    <p class="text-sm font-semibold text-cyan-700">Ferramentas</p>
                    <h1 class="mt-1 text-2xl font-semibold text-slate-950">Exportação de pacientes</h1>
                    <p class="mt-1 max-w-2xl text-sm text-slate-500">
                        Gere arquivos organizados para pacientes, agenda e prontuários sem sair do fluxo do consultório.
                </p>
            </div>
            <RouterLink
                :to="{ name: 'home' }"
                    class="inline-flex w-fit items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-800"
            >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
                </svg>
                Dashboard
            </RouterLink>
            </div>
        </header>

        <PatientsExportPanel />
    </div>
</template>
