<script setup lang="ts">
import { computed } from 'vue';
import Button from '../base/Button.vue';

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
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-widest text-neutral-500">Relatórios</p>
        <h1 class="text-3xl font-bold text-neutral-900">Acompanhe seus resultados</h1>
        <p class="mt-1 text-sm text-neutral-600">
          <slot name="info" />
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <Button variant="secondary" size="md" @click="$emit('toggle-filters')">
          <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          {{ reportFiltersOpen ? 'Ocultar' : 'Filtros' }}
        </Button>
        <Button :loading="reportLoading" @click="$emit('refresh')">
          <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Atualizar
        </Button>
      </div>
    </div>

    <transition name="fade">
      <form
        v-if="reportFiltersOpen"
        class="rounded-xl border border-neutral-200 bg-neutral-50 p-5"
        @submit.prevent="$emit('apply-filters')"
      >
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 lg:items-end">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-600 mb-2">De</label>
            <input
              :value="filters.from"
              type="date"
              :class="[
                'w-full rounded-lg border border-neutral-200 px-4 py-2 text-sm',
                'focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500',
              ]"
              @input="$emit('update:filters', { ...filters, from: $event.target.value })"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-600 mb-2">Até</label>
            <input
              :value="filters.to"
              type="date"
              :class="[
                'w-full rounded-lg border border-neutral-200 px-4 py-2 text-sm',
                'focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500',
              ]"
              @input="$emit('update:filters', { ...filters, to: $event.target.value })"
            />
          </div>
          <div class="flex gap-2 md:col-span-2">
            <Button type="submit" :loading="reportLoading" class="flex-1">
              Aplicar filtros
            </Button>
            <Button
              v-if="hasReportFiltersFilled"
              type="button"
              variant="secondary"
              :disabled="reportLoading"
              @click="$emit('clear-filters')"
            >
              Limpar
            </Button>
          </div>
        </div>
      </form>
    </transition>

    <slot />
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
