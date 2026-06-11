<script setup lang="ts">
import { RouterLink } from 'vue-router';
import AppIcon from '../base/AppIcon.vue';

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
  <div class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-6 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <p class="section-kicker">Relatórios</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-normal text-slate-950">Indicadores do consultório</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-[#58635f]">
          Analise presença, faltas, cancelamentos e recebimentos em um período específico.
        </p>
        <p class="mt-2 text-sm text-slate-500">
          <slot name="info" />
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <RouterLink
          :to="{ name: 'home' }"
          class="btn-secondary h-10"
        >
          <AppIcon name="ChevronLeft" class="size-4" />
          Dashboard
        </RouterLink>
        <button
          class="btn-secondary h-10"
          type="button"
          @click="$emit('toggle-filters')"
        >
          <AppIcon :name="reportFiltersOpen ? 'EyeOff' : 'Filter'" class="size-4" />
          {{ reportFiltersOpen ? 'Ocultar filtros' : 'Filtrar' }}
        </button>
      </div>
    </div>

    <transition name="fade">
      <form
        v-if="reportFiltersOpen"
        class="mt-5 rounded-xl border border-[#e7e1d6] bg-[#f8f5ef] p-4"
        @submit.prevent="$emit('apply-filters')"
      >
        <div class="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
          <div>
            <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">De</label>
            <input
              v-model="filters.from"
              type="date"
              class="field-input h-11"
            />
          </div>
          <div>
            <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">Até</label>
            <input
              v-model="filters.to"
              type="date"
              class="field-input h-11"
            />
          </div>
          <div class="flex gap-2">
            <button
              class="btn-primary h-11 disabled:cursor-not-allowed disabled:opacity-60"
              type="submit"
              :disabled="reportLoading"
            >
              <AppIcon v-if="reportLoading" name="LoaderCircle" class="mr-2 size-4 animate-spin" />
              {{ reportLoading ? 'Filtrando...' : 'Aplicar filtros' }}
            </button>
            <button
              v-if="hasReportFiltersFilled"
              type="button"
              class="btn-secondary h-11 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="reportLoading"
              @click="$emit('clear-filters')"
            >
              <AppIcon name="X" class="size-4" />
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
