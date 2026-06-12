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
  primary: 'bg-[#415f76] text-white hover:bg-[#2b4a60] disabled:bg-[#abcae5]',
  secondary: 'border border-[#e2e2e2] bg-white text-[#42474c] hover:border-[#c2c7cd] hover:bg-[#f3f4f3] disabled:opacity-60',
  danger: 'bg-[#ba1a1a] text-white hover:bg-[#93000a] disabled:bg-[#ffb4ab]',
  ghost: 'text-[#42474c] hover:bg-[#f3f4f3] disabled:opacity-60',
  success: 'bg-[#4c6455] text-white hover:bg-[#344c3e] disabled:bg-[#b2cdbb]',
  warning: 'bg-[#605b55] text-white hover:bg-[#4a4640] disabled:bg-[#ccc5be]',
};

const sizeClasses = {
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-4 py-2 text-sm',
  lg: 'px-6 py-3 text-base',
};

const baseClasses = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#cae6ff] disabled:cursor-not-allowed';

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
