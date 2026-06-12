<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  status?: 'success' | 'warning' | 'error' | 'info' | 'primary';
}

const props = withDefaults(defineProps<Props>(), {
  status: 'info',
});

const statusClasses = {
  success: 'bg-[#cbe6d4] text-[#4c6455]',
  warning: 'bg-[#e8e1d9] text-[#605b55]',
  error: 'bg-[#ffdad6] text-[#ba1a1a]',
  info: 'bg-[#f3f4f3] text-[#42474c]',
  primary: 'bg-[#cae6ff] text-[#415f76]',
};

const computedClass = computed(() => ['px-3 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-2', statusClasses[props.status]].join(' '));
</script>

<template>
  <span :class="computedClass">
    <slot />
  </span>
</template>
