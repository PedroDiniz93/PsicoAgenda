<script setup lang="ts">
import { RouterLink } from 'vue-router';

interface Props {
  reportFiltersOpen: boolean;
  reportLoading: boolean;
  hasReportFiltersFilled: boolean;
  filters: {
    from: string;
    to: string;
  };
}

defineProps<Props>();

defineEmits<{
  'toggle-filters': [];
  'apply-filters': [];
  'clear-filters': [];
  'refresh': [];
}>();
</script>

<template>
  <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <p class="text-sm font-semibold text-cyan-700">Relatórios</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-normal text-slate-950">Indicadores do consultório</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
          Analise presença, faltas, cancelamentos e recebimentos em um período específico.
        </p>
        <p class="mt-2 text-sm text-slate-500">
          <slot name="info" />
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <RouterLink
          :to="{ name: 'home' }"
          class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
        <button
          class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
          type="button"
          @click="$emit('toggle-filters')"
        >
          {{ reportFiltersOpen ? 'Ocultar filtros' : 'Filtrar' }}
        </button>
      </div>
    </div>

    <transition name="fade">
      <form
        v-if="reportFiltersOpen"
        class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4"
        @submit.prevent="$emit('apply-filters')"
      >
        <div class="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
          <div>
            <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">De</label>
            <input
              v-model="filters.from"
              type="date"
              class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
            />
          </div>
          <div>
            <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">Até</label>
            <input
              v-model="filters.to"
              type="date"
              class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
            />
          </div>
          <div class="flex gap-2">
            <button
              class="inline-flex h-11 items-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
              type="submit"
              :disabled="reportLoading"
            >
              {{ reportLoading ? 'Filtrando...' : 'Aplicar filtros' }}
            </button>
            <button
              v-if="hasReportFiltersFilled"
              type="button"
              class="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="reportLoading"
              @click="$emit('clear-filters')"
            >
              Limpar
            </button>
          </div>
        </div>
      </form>
    </transition>

  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 200ms ease, max-height 200ms ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
