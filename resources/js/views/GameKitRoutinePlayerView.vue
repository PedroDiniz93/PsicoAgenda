<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import AppIcon from '../components/base/AppIcon.vue';

const route = useRoute();
const loading = ref(true), error = ref(''), routine = ref(null);
const categories = { study: { label: 'Estudo e trabalho', icon: 'BookOpen', color: 'bg-blue-50 text-blue-700 border-blue-200' }, rest: { label: 'Descanso e sono', icon: 'Moon', color: 'bg-violet-50 text-violet-700 border-violet-200' }, leisure: { label: 'Lazer', icon: 'Sparkles', color: 'bg-amber-50 text-amber-700 border-amber-200' }, self_care: { label: 'Autocuidado', icon: 'Heart', color: 'bg-rose-50 text-rose-700 border-rose-200' } };
onMounted(async () => { try { routine.value = (await axios.get('/api/gamekit/routine/play/' + route.params.token)).data.routine; } catch (e) { error.value = e?.response?.data?.message ?? 'Esta rotina não está mais disponível.'; } finally { loading.value = false; } });
</script>

<template>
    <main class="min-h-screen bg-[#f4f6f3] px-4 py-8 sm:px-6 sm:py-12"><section class="mx-auto w-full max-w-3xl"><div v-if="loading" class="rounded-3xl bg-white p-12 text-center text-sm text-[var(--spa-ink-muted)] shadow-sm">Carregando rotina...</div><div v-else-if="error" class="rounded-3xl bg-white p-12 text-center text-sm text-red-700 shadow-sm">{{ error }}</div><div v-else-if="routine" class="space-y-5"><header class="rounded-3xl bg-white p-6 shadow-sm sm:p-8"><p class="section-kicker">GameKit Psi · Rotina diária</p><h1 class="mt-1 text-2xl font-semibold text-[var(--spa-ink)] sm:text-3xl">{{ routine.name }}</h1><p class="mt-2 text-sm leading-6 text-[var(--spa-ink-muted)]">Uma organização possível para o seu dia. Ajuste com o seu psicólogo conforme fizer sentido.</p></header><section class="rounded-3xl bg-white p-5 shadow-sm sm:p-8"><div class="border-l-2 border-[var(--spa-border)] pl-5"><article v-for="(block, index) in routine.blocks" :key="index" class="relative mb-5 rounded-2xl border p-4 last:mb-0" :class="categories[block.category]?.color ?? 'border-[var(--spa-border)]'"><span class="absolute -left-[31px] top-5 flex size-4 rounded-full border-4 border-[#f4f6f3] bg-[var(--spa-accent)]"></span><div class="flex items-start gap-3"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white"><AppIcon :name="block.icon || categories[block.category]?.icon || 'CircleCheck'" class="size-5" /></span><div><p class="text-xs font-bold uppercase tracking-wide text-[var(--spa-ink-muted)]">{{ block.start_time }} · {{ block.duration_minutes }} min</p><h2 class="mt-1 font-semibold text-[var(--spa-ink)]">{{ block.title }}</h2><p class="mt-1 text-xs font-semibold text-[var(--spa-ink-muted)]">{{ categories[block.category]?.label }}</p><p v-if="block.description" class="mt-2 text-sm leading-5 text-[var(--spa-ink-muted)]">{{ block.description }}</p></div></div></article></div></section></div></section></main>
</template>
