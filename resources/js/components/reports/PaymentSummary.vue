<script setup lang="ts">
import Card from '../base/Card.vue';

interface SummaryCard {
  icon: string;
  label: string;
  value: string | number;
  description: string;
  color: 'success' | 'warning';
}

interface Props {
  cards: SummaryCard[];
}

defineProps<Props>();

const colorClasses = {
  success: 'border-success-200 bg-success-50',
  warning: 'border-warning-200 bg-warning-50',
};

const textColorClasses = {
  success: 'text-success-800',
  warning: 'text-warning-800',
};

const badgeColorClasses = {
  success: 'text-success-600',
  warning: 'text-warning-600',
};
</script>

<template>
  <div class="grid gap-4 lg:grid-cols-2">
    <div
      v-for="(card, idx) in cards"
      :key="`payment-summary-${idx}`"
      :class="['rounded-xl border p-6 transition-all', colorClasses[card.color]]"
    >
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
          <p class="text-xs font-bold uppercase tracking-widest text-neutral-600">{{ card.label }}</p>
          <p :class="['mt-2 text-4xl font-bold', textColorClasses[card.color]]">{{ card.value }}</p>
          <p class="mt-1 text-sm text-neutral-700">{{ card.description }}</p>
        </div>
        <svg :class="['size-10 flex-shrink-0', badgeColorClasses[card.color]]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="card.icon" />
        </svg>
      </div>
    </div>
  </div>
</template>
