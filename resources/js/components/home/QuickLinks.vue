<script setup lang="ts">
import { RouterLink } from 'vue-router';

interface QuickLink {
  id: string;
  label: string;
  title: string;
  description: string;
  icon: string;
  to: { name: string };
  color: 'primary' | 'success' | 'warning' | 'error';
  bgColor: string;
  textColor: string;
}

interface Props {
  links: QuickLink[];
}

defineProps<Props>();

const colorClasses = {
  primary: { bg: 'bg-primary-50', border: 'border-primary-200', text: 'text-primary-600', hover: 'hover:bg-primary-100' },
  success: { bg: 'bg-success-50', border: 'border-success-200', text: 'text-success-600', hover: 'hover:bg-success-100' },
  warning: { bg: 'bg-warning-50', border: 'border-warning-200', text: 'text-warning-600', hover: 'hover:bg-warning-100' },
  error: { bg: 'bg-error-50', border: 'border-error-200', text: 'text-error-600', hover: 'hover:bg-error-100' },
};
</script>

<template>
  <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    <RouterLink
      v-for="link in links"
      :key="link.id"
      :to="link.to"
      :class="[
        'group flex flex-col justify-between p-6 rounded-xl border transition-all duration-200 hover:shadow-lg',
        colorClasses[link.color].bg,
        colorClasses[link.color].border,
      ]"
    >
      <!-- Content -->
      <div class="space-y-1">
        <p class="text-xs font-bold uppercase tracking-widest text-neutral-600">{{ link.label }}</p>
        <h3 class="text-lg font-semibold text-neutral-900">{{ link.title }}</h3>
        <p class="text-sm text-neutral-600">{{ link.description }}</p>
      </div>

      <!-- Icon -->
      <div :class="['rounded-lg p-3 w-fit mt-4 transition-transform group-hover:scale-110', colorClasses[link.color].hover, colorClasses[link.color].text]">
        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" />
        </svg>
      </div>
    </RouterLink>
  </div>
</template>
