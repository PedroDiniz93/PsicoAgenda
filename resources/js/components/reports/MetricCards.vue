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
  primary: 'bg-primary-50 border-primary-200',
  success: 'bg-success-50 border-success-200',
  warning: 'bg-warning-50 border-warning-200',
  error: 'bg-error-50 border-error-200',
};

const textColorClasses = {
  primary: 'text-primary-800',
  success: 'text-success-800',
  warning: 'text-warning-800',
  error: 'text-error-800',
};

const iconColor = {
  primary: 'text-primary-600',
  success: 'text-success-600',
  warning: 'text-warning-600',
  error: 'text-error-600',
};
</script>

<template>
  <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
    <article
      v-for="card in cards"
      :key="card.id"
      :class="[
        'rounded-xl border p-5 transition-all hover:shadow-md',
        colorClasses[card.color || 'primary'],
      ]"
    >
      <div class="flex items-start justify-between gap-3">
        <div class="flex-1">
          <p class="text-xs font-bold uppercase tracking-widest text-neutral-600">{{ card.label }}</p>
          <p class="mt-2 text-3xl font-bold text-neutral-900">{{ card.value }}</p>
          <p class="mt-1 text-xs text-neutral-600">{{ card.description }}</p>
        </div>
        <svg v-if="card.icon" :class="['size-8 flex-shrink-0', iconColor[card.color || 'primary']]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="card.icon" />
        </svg>
      </div>
    </article>
  </div>
</template>
