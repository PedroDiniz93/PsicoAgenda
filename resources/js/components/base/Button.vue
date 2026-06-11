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
  primary: 'bg-[#3f4f46] text-white hover:bg-[#36433c] disabled:bg-[#aab5ae]',
  secondary: 'border border-[#e2ddd3] text-[#58635f] hover:border-[#c9c1b3] hover:bg-[#fbf8f2] disabled:opacity-60',
  danger: 'bg-[#9a4f57] text-white hover:bg-[#87464d] disabled:bg-[#c69ba0]',
  ghost: 'text-[#58635f] hover:bg-[#f3efe7] disabled:opacity-60',
  success: 'bg-[#4e6655] text-white hover:bg-[#435a4a] disabled:bg-[#9db2a2]',
  warning: 'bg-[#8b6b3f] text-white hover:bg-[#775d38] disabled:bg-[#bea789]',
};

const sizeClasses = {
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-4 py-2 text-sm',
  lg: 'px-6 py-3 text-base',
};

const baseClasses = 'inline-flex items-center justify-center gap-2 rounded-xl font-medium transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#d4ddd5] disabled:cursor-not-allowed';

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
