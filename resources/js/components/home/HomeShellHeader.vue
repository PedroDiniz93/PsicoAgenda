<script setup lang="ts">
import { RouterLink } from 'vue-router';
import AppIcon from '../base/AppIcon.vue';

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
    <header class="border-b border-[#e2ddd3] bg-white/90 backdrop-blur">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-5 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div>
                <p class="section-kicker capitalize">{{ todayLabel }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-normal text-slate-950">Olá, {{ userName }}</h1>
                    <span
                        class="inline-flex h-5 items-center rounded-full border border-[#d8cfbf] bg-[#f8f2e8] px-2 text-[11px] font-semibold uppercase leading-none tracking-normal text-[#6e5939]"
                    >
                         Beta v2
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ userEmail }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <RouterLink
                    :to="{ name: 'schedule' }"
                    class="btn-primary h-10"
                >
                    <AppIcon name="CalendarPlus2" class="size-4" />
                    Nova sessão
                </RouterLink>
                <RouterLink
                    :to="{ name: 'patients' }"
                    class="btn-secondary h-10"
                >
                    <AppIcon name="UsersRound" class="size-4" />
                    Pacientes
                </RouterLink>
                <button
                    class="btn-secondary h-10 hover:border-[#dfc2c6] hover:bg-[#f9eff1] hover:text-[#7d4950]"
                    type="button"
                    @click="$emit('logout')"
                >
                    <AppIcon name="LogOut" class="size-4" />
                    Sair
                </button>
            </div>
        </div>
    </header>
</template>
