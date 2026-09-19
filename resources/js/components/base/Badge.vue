<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  status?: 'success' | 'warning' | 'error' | 'info' | 'primary';
}

const props = withDefaults(defineProps<Props>(), {
  status: 'info',
});

const statusClasses = {
  success: 'bg-[var(--spa-secondary-soft)] text-[var(--spa-secondary)]',
  warning: 'bg-[var(--spa-warning-soft)] text-[var(--spa-warning)]',
  error: 'bg-[var(--spa-error-soft)] text-[var(--spa-error)]',
  info: 'bg-[var(--spa-surface-muted)] text-[var(--spa-ink-soft)]',
  primary: 'bg-[var(--spa-accent-soft)] text-[var(--spa-accent)]',
};

const computedClass = computed(() => ['px-3 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-2', statusClasses[props.status]].join(' '));
</script>

<template>
  <span :class="computedClass">
    <slot />
  </span>
</template>
