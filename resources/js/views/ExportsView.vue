<script setup>
import { inject, ref } from 'vue';
import PatientsExportPanel from '../components/PatientsExportPanel.vue';
import { RouterLink } from 'vue-router';
import AppIcon from '../components/base/AppIcon.vue';
import AppPageHeader from '../components/layout/AppPageHeader.vue';

const privacyMode = inject('privacyMode', ref(false));
</script>

<template>
    <div class="page-shell">
        <section v-if="privacyMode" class="empty-state">
            <AppIcon name="EyeOff" class="mx-auto mb-3 size-7 text-[var(--spa-accent)]" />
            <h1 class="text-2xl font-semibold text-[var(--spa-ink)]">Exportações protegidas</h1>
            <p class="mx-auto mt-2 max-w-lg text-sm">Desative o modo privacidade para acessar dados e gerar arquivos sensíveis.</p>
        </section>

        <template v-else>
        <AppPageHeader
            kicker="Gestão"
            title="Exportações"
            description="Gere arquivos organizados para pacientes, agenda e prontuários com o cuidado exigido por dados clínicos."
        >
            <template #actions>
            <RouterLink
                :to="{ name: 'home' }"
                class="btn-secondary w-fit"
            >
                <AppIcon name="ChevronLeft" class="size-4" />
                Dashboard
            </RouterLink>
            </template>
        </AppPageHeader>

        <PatientsExportPanel />
        </template>
    </div>
</template>
