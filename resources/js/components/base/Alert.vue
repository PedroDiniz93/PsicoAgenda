<script setup lang="ts">
import AppIcon from './AppIcon.vue';

interface Props {
  status: 'success' | 'warning' | 'error' | 'info';
  closeable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  closeable: false,
});

const colorClasses = {
  success: 'border-[#bdd0c0] bg-[#edf3ee] text-[#365341]',
  warning: 'border-[#dbc8a9] bg-[#f8f2e8] text-[#6e5939]',
  error: 'border-[#e6c8cc] bg-[#faeff1] text-[#7d4950]',
  info: 'border-[#e2ddd3] bg-[#f7f4ee] text-[#58635f]',
};

const iconClasses = {
  success: 'text-[#4e6655]',
  warning: 'text-[#8b6b3f]',
  error: 'text-[#9a4f57]',
  info: 'text-[#58635f]',
};

const icons = {
  success: 'CircleCheck',
  warning: 'TriangleAlert',
  error: 'CircleX',
  info: 'Info',
};
</script>

<template>
  <div :class="['rounded-lg border px-4 py-3 text-sm', colorClasses[status]]" role="alert">
    <div class="flex items-start gap-3">
      <AppIcon :name="icons[status]" class="mt-0.5 size-5 shrink-0" :class="iconClasses[status]" />
      <div class="flex-1">
        <slot />
      </div>
      <button
        v-if="closeable"
        type="button"
        class="flex-shrink-0 text-current opacity-70 hover:opacity-100 transition-opacity"
        @click="$emit('close')"
      >
        <AppIcon name="X" class="size-4" />
      </button>
    </div>
  </div>
</template>
