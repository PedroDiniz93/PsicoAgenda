<script setup lang="ts">
import { RouterLink } from 'vue-router';

defineProps<{
    todayLabel: string;
    userName: string;
    userEmail: string;
}>();

defineEmits<{
    logout: [];
}>();
</script>

<template>
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-5 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div>
                <p class="text-sm font-medium capitalize text-slate-500">{{ todayLabel }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-normal text-slate-950">Olá, {{ userName }}</h1>
                    <span
                        class="inline-flex h-5 items-center rounded-full border border-amber-200 bg-amber-50 px-2 text-[11px] font-semibold uppercase leading-none tracking-normal text-amber-700"
                    >
                         Beta
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ userEmail }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <RouterLink
                    :to="{ name: 'schedule' }"
                    class="inline-flex h-10 items-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Nova sessão
                </RouterLink>
                <RouterLink
                    :to="{ name: 'patients' }"
                    class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                >
                    Pacientes
                </RouterLink>
                <button
                    class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                    type="button"
                    @click="$emit('logout')"
                >
                    Sair
                </button>
            </div>
        </div>
    </header>
</template>
