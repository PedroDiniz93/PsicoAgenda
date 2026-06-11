<script setup lang="ts">
import AppIcon from '../base/AppIcon.vue';

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
  primary: 'text-[#3f4f46]',
  success: 'text-[#4e6655]',
  warning: 'text-[#8b6b3f]',
  error: 'text-[#9a4f57]',
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
        <AppIcon
          v-if="card.icon"
          :name="card.icon"
          :class="['size-8 flex-shrink-0', iconColor[card.color || 'primary']]"
          :stroke-width="1.8"
        />
      </div>
    </article>
  </div>
</template>
