<script setup lang="ts">
import { ref, computed } from 'vue';

interface Props {
  modelValue?: string;
  options: Array<{ label: string; value: string | number }>;
  label?: string;
  placeholder?: string;
  error?: string;
  disabled?: boolean;
  required?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Selecione...',
});

const emit = defineEmits<{
  'update:modelValue': [value: string | number];
}>();

const open = ref(false);

const selectedLabel = computed(() => {
  const selected = props.options.find((opt) => opt.value === props.modelValue);
  return selected?.label || props.placeholder;
});

const hasError = computed(() => Boolean(props.error));
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" class="text-sm font-medium text-neutral-700">
      {{ label }}
      <span v-if="required" class="text-error-600">*</span>
    </label>
    <div class="relative">
      <button
        type="button"
        :class="[
          'w-full rounded-lg border px-4 py-2 text-sm text-left transition-colors',
          'flex items-center justify-between gap-2',
          'focus:outline-none focus:ring-2 focus:ring-primary-200',
          'disabled:bg-neutral-100 disabled:text-neutral-500 disabled:cursor-not-allowed',
          hasError
            ? 'border-error-300 bg-error-50 focus:border-error-500'
            : 'border-neutral-200 bg-white focus:border-primary-500',
        ]"
        :disabled="disabled"
        @click="open = !open"
      >
        <span>{{ selectedLabel }}</span>
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" :style="{ transform: open ? 'rotate(180deg)' : 'rotate(0deg)' }" />
        </svg>
      </button>
      <div
        v-if="open"
        class="absolute top-full left-0 right-0 z-dropdown mt-1 rounded-lg border border-neutral-200 bg-white shadow-lg"
        @click.self="open = false"
      >
        <button
          v-for="option in options"
          :key="option.value"
          type="button"
          class="w-full px-4 py-2 text-left text-sm hover:bg-primary-50 transition-colors first:rounded-t-lg last:rounded-b-lg"
          :class="option.value === modelValue ? 'bg-primary-100 text-primary-800 font-medium' : 'text-neutral-700'"
          @click="
            emit('update:modelValue', option.value);
            open = false;
          "
        >
          {{ option.label }}
        </button>
      </div>
    </div>
    <p v-if="hasError" class="text-xs text-error-600">{{ error }}</p>
  </div>
</template>
