<script setup lang="ts">
interface Props {
  status: 'success' | 'warning' | 'error' | 'info';
  closeable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  closeable: false,
});

const colorClasses = {
  success: 'border-success-200 bg-success-50 text-success-800',
  warning: 'border-warning-200 bg-warning-50 text-warning-800',
  error: 'border-error-200 bg-error-50 text-error-800',
  info: 'border-neutral-200 bg-neutral-50 text-neutral-700',
};

const iconClasses = {
  success: 'text-success-600',
  warning: 'text-warning-600',
  error: 'text-error-600',
  info: 'text-neutral-600',
};

const icons = {
  success: 'M10 6a4 4 0 100-8 4 4 0 000 8zM12.168 18.861A6 6 0 008.477 9H4.5a1 1 0 00-.5.1M4 20h16',
  warning: 'M12 9v2m0 4v2m7.07-10.07a10 10 0 11-14.14 0M12 2a10 10 0 1010 10',
  error: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2',
  info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};
</script>

<template>
  <div :class="['rounded-lg border px-4 py-3 text-sm', colorClasses[status]]" role="alert">
    <div class="flex items-start gap-3">
      <svg class="size-5 flex-shrink-0 mt-0.5" :class="iconClasses[status]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icons[status]" />
      </svg>
      <div class="flex-1">
        <slot />
      </div>
      <button
        v-if="closeable"
        type="button"
        class="flex-shrink-0 text-current opacity-70 hover:opacity-100 transition-opacity"
        @click="$emit('close')"
      >
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </div>
</template>
