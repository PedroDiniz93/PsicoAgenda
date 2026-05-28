<script setup lang="ts">
import Card from '../base/Card.vue';

interface Metric {
  id: string;
  label: string;
  value: string | number;
  change?: string;
  icon?: string;
  color: 'primary' | 'success' | 'warning' | 'error';
}

interface Props {
  metrics: Metric[];
}

defineProps<Props>();

const colorClasses = {
  primary: 'text-primary-600 bg-primary-50',
  success: 'text-success-600 bg-success-50',
  warning: 'text-warning-600 bg-warning-50',
  error: 'text-error-600 bg-error-50',
};
</script>

<template>
  <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
    <Card v-for="metric in metrics" :key="metric.id" class="flex flex-col gap-3">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-neutral-600">{{ metric.label }}</p>
          <p class="text-3xl font-bold text-neutral-900 mt-1">{{ metric.value }}</p>
        </div>
        <div v-if="metric.icon" :class="['p-3 rounded-lg', colorClasses[metric.color]]">
          <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="metric.icon" />
          </svg>
        </div>
      </div>
      <p v-if="metric.change" :class="['text-xs', metric.change.includes('↑') ? 'text-success-600' : 'text-error-600']">
        {{ metric.change }}
      </p>
    </Card>
  </div>
</template>
