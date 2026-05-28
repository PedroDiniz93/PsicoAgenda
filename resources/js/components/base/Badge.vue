<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  status?: 'success' | 'warning' | 'error' | 'info' | 'primary';
}

const props = withDefaults(defineProps<Props>(), {
  status: 'info',
});

const statusClasses = {
  success: 'bg-success-100 text-success-800',
  warning: 'bg-warning-100 text-warning-800',
  error: 'bg-error-100 text-error-800',
  info: 'bg-neutral-100 text-neutral-800',
  primary: 'bg-primary-100 text-primary-800',
};

const computedClass = computed(() => ['px-3 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-2', statusClasses[props.status]].join(' '));
</script>

<template>
  <span :class="computedClass">
    <slot />
  </span>
</template>
