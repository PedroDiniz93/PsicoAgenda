<script setup>
import PatientsExportPanel from '../components/PatientsExportPanel.vue';
import { RouterLink } from 'vue-router';
import AppIcon from '../components/base/AppIcon.vue';
</script>

<template>
    <div class="page-shell">
        <header class="mb-6 rounded-2xl border border-[#e2ddd3] bg-white/95 p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                    <p class="section-kicker">Ferramentas</p>
                    <h1 class="mt-1 text-2xl font-semibold text-slate-950">Exportação de pacientes</h1>
                    <p class="mt-1 max-w-2xl text-sm text-[#58635f]">
                        Gere arquivos organizados para pacientes, agenda e prontuários sem sair do fluxo do consultório.
                </p>
            </div>
            <RouterLink
                :to="{ name: 'home' }"
                    class="btn-secondary w-fit"
            >
                    <AppIcon name="ChevronLeft" class="size-4" />
                Dashboard
            </RouterLink>
            </div>
        </header>

        <PatientsExportPanel />
    </div>
</template>
