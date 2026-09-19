<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';

interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'success' | 'warning';
  size?: 'sm' | 'md' | 'lg';
  fullWidth?: boolean;
  disabled?: boolean;
  loading?: boolean;
  type?: 'button' | 'submit' | 'reset';
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  fullWidth: false,
  disabled: false,
  loading: false,
  type: 'button',
});

const variantClasses = {
  primary: 'bg-[var(--spa-accent)] text-white hover:bg-[var(--spa-accent-hover)]',
  secondary: 'border border-[var(--spa-border-soft)] bg-[var(--spa-surface)] text-[var(--spa-ink-soft)] hover:border-[var(--spa-border)] hover:bg-[var(--spa-surface-muted)]',
  danger: 'bg-[var(--spa-error)] text-white hover:brightness-90',
  ghost: 'text-[var(--spa-ink-soft)] hover:bg-[var(--spa-surface-muted)]',
  success: 'bg-[var(--spa-secondary)] text-white hover:brightness-90',
  warning: 'bg-[var(--spa-warning)] text-white hover:brightness-90',
};

const sizeClasses = {
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-4 py-2 text-sm',
  lg: 'px-6 py-3 text-base',
};

const baseClasses = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--spa-focus)] disabled:cursor-not-allowed disabled:opacity-60';

const computedClass = computed(() => {
  const classes = [
    baseClasses,
    variantClasses[props.variant],
    sizeClasses[props.size],
    props.fullWidth ? 'w-full' : '',
  ];
  return classes.filter(Boolean).join(' ');
});
</script>

<template>
  <button :class="computedClass" :type="type" :disabled="disabled || loading">
    <AppIcon v-if="loading" name="LoaderCircle" class="size-4 animate-spin" />
    <slot />
  </button>
</template>
