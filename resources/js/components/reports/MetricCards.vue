<script setup lang="ts">
import Badge from '../base/Badge.vue';

interface Card {
  id: string;
  label: string;
  value: string | number;
  description: string;
  icon?: string;
  color?: 'primary' | 'success' | 'warning' | 'error';
}

interface Props {
  cards: Card[];
}

defineProps<Props>();

const colorClasses = {
  primary: 'bg-white border-slate-200',
  success: 'bg-emerald-50 border-emerald-200',
  warning: 'bg-amber-50 border-amber-200',
  error: 'bg-rose-50 border-rose-200',
};

const iconColor = {
  primary: 'text-cyan-700',
  success: 'text-emerald-700',
  warning: 'text-amber-700',
  error: 'text-rose-700',
};
</script>

<template>
  <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
    <article
      v-for="card in cards"
      :key="card.id"
      :class="[
        'rounded-lg border p-5 shadow-sm transition hover:shadow-md',
        colorClasses[card.color || 'primary'],
      ]"
    >
      <div class="flex items-start justify-between gap-3">
        <div class="flex-1">
          <p class="text-xs font-semibold uppercase text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-3xl font-semibold tracking-normal text-slate-950">{{ card.value }}</p>
          <p class="mt-1 text-sm text-slate-600">{{ card.description }}</p>
        </div>
        <svg v-if="card.icon" :class="['size-8 flex-shrink-0', iconColor[card.color || 'primary']]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="card.icon" />
        </svg>
      </div>
    </article>
  </div>
</template>
