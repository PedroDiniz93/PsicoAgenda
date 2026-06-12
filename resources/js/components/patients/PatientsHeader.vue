<script setup>
import { RouterLink } from 'vue-router';
import AppIcon from '../base/AppIcon.vue';

defineEmits(['create']);
</script>

<template>
    <header>
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="font-['Source_Serif_4'] text-3xl font-semibold tracking-[-0.02em] text-[#1a1c1c] lg:text-4xl">
                    Base de Pacientes
                </h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-[#73787d]">
                    Gerencie seus pacientes, prontuários e dados de acompanhamento.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <RouterLink
                    :to="{ name: 'home' }"
                    class="inline-flex h-11 items-center gap-2 rounded-xl border border-[#c2c7cd]/30 bg-[#eeeeed] px-4 text-sm font-semibold text-[#415f76] transition hover:bg-[#e8e8e7]"
                >
                    <AppIcon name="ChevronLeft" class="size-4" />
                    Dashboard
                </RouterLink>
                <button
                    class="inline-flex h-11 items-center gap-2 rounded-xl bg-[#4c6455] px-4 text-sm font-semibold text-white shadow-[0_20px_40px_-10px_rgba(93,123,147,0.12)] transition hover:bg-[#3f5648]"
                    type="button"
                    @click="$emit('create')"
                >
                    <AppIcon name="UserRoundPlus" class="size-4" />
                    Cadastrar paciente
                </button>
            </div>
        </div>
    </header>
</template>
