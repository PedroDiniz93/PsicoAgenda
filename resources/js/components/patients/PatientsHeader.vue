<script setup>
import { RouterLink } from 'vue-router';

defineEmits(['create']);
</script>

<template>
    <header class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-cyan-700">Cadastro clínico</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-normal text-slate-950">Pacientes</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Organize dados de contato, status de acompanhamento, valores combinados e acesso rápido ao prontuário.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <RouterLink
                    :to="{ name: 'home' }"
                    class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Dashboard
                </RouterLink>
                <button
                    class="inline-flex h-10 items-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"
                    type="button"
                    @click="$emit('create')"
                >
                    Novo paciente
                </button>
            </div>
        </div>
    </header>
</template>
