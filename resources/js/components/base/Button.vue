<script setup lang="ts">
import { computed } from 'vue';

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
  primary: 'bg-primary-600 text-white hover:bg-primary-700 disabled:bg-primary-300',
  secondary: 'border border-neutral-200 text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50 disabled:opacity-60',
  danger: 'bg-error-600 text-white hover:bg-error-700 disabled:bg-error-300',
  ghost: 'text-neutral-700 hover:bg-neutral-100 disabled:opacity-60',
  success: 'bg-success-600 text-white hover:bg-success-700 disabled:bg-success-300',
  warning: 'bg-warning-600 text-white hover:bg-warning-700 disabled:bg-warning-300',
};

const sizeClasses = {
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-4 py-2 text-sm',
  lg: 'px-6 py-3 text-base',
};

const baseClasses = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-200 disabled:cursor-not-allowed';

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
    <svg v-if="loading" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
    </svg>
    <slot />
  </button>
</template>
