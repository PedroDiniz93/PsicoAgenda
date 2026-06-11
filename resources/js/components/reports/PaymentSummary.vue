<script setup lang="ts">
import Card from '../base/Card.vue';
import AppIcon from '../base/AppIcon.vue';

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
  success: 'border-[#c9d8cd] bg-[#eff4f0]',
  warning: 'border-[#dfd5c3] bg-[#f9f4ea]',
};

const textColorClasses = {
  success: 'text-[#26362d]',
  warning: 'text-[#4a3f30]',
};

const badgeColorClasses = {
  success: 'text-[#4e6655]',
  warning: 'text-[#8b6b3f]',
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
        <AppIcon :name="card.icon" :class="['size-10 flex-shrink-0', badgeColorClasses[card.color]]" :stroke-width="1.8" />
      </div>
    </div>
  </div>
</template>
