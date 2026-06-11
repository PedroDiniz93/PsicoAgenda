<script setup>
import { RouterLink } from 'vue-router';
import AppIcon from '../base/AppIcon.vue';

defineEmits(['create']);
</script>

<template>
    <header class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="section-kicker">Cadastro clínico</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-normal text-slate-950">Pacientes</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-[#58635f]">
                    Organize dados de contato, status de acompanhamento, valores combinados e acesso rápido ao prontuário.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <RouterLink :to="{ name: 'home' }" class="btn-secondary h-10">
                    <AppIcon name="ChevronLeft" class="size-4" />
                    Dashboard
                </RouterLink>
                <button class="btn-primary h-10" type="button" @click="$emit('create')">
                    <AppIcon name="UserRoundPlus" class="size-4" />
                    Novo paciente
                </button>
            </div>
        </div>
    </header>
</template>
