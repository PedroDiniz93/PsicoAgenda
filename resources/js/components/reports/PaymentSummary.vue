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
  success: 'border-emerald-200 bg-emerald-50',
  warning: 'border-amber-200 bg-amber-50',
};

const textColorClasses = {
  success: 'text-emerald-950',
  warning: 'text-amber-950',
};

const badgeColorClasses = {
  success: 'text-emerald-700',
  warning: 'text-amber-700',
};
</script>

<template>
  <div class="grid gap-4 lg:grid-cols-2">
    <div
      v-for="(card, idx) in cards"
      :key="`payment-summary-${idx}`"
      :class="['rounded-lg border p-5 shadow-sm transition', colorClasses[card.color]]"
    >
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
          <p class="text-xs font-semibold uppercase text-slate-500">{{ card.label }}</p>
          <p :class="['mt-2 text-3xl font-semibold tracking-normal', textColorClasses[card.color]]">{{ card.value }}</p>
          <p class="mt-1 text-sm text-slate-600">{{ card.description }}</p>
        </div>
        <svg :class="['size-10 flex-shrink-0', badgeColorClasses[card.color]]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="card.icon" />
        </svg>
      </div>
    </div>
  </div>
</template>
