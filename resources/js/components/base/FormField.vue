<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  label?: string;
  placeholder?: string;
  type?: string;
  error?: string;
  disabled?: boolean;
  help?: string;
  required?: boolean;
  modelValue?: string | number;
}

defineProps<Props>();
defineEmits<{
  'update:modelValue': [value: string | number];
}>();

const hasError = computed(() => Boolean(props.error));
const hasHelp = computed(() => Boolean(props.help) && !hasError.value);
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" class="text-sm font-medium text-neutral-700">
      {{ label }}
      <span v-if="required" class="text-error-600">*</span>
    </label>
    <input
      :value="modelValue"
      :type="type || 'text'"
      :placeholder="placeholder"
      :disabled="disabled"
      :class="[
        'w-full rounded-lg border px-4 py-2 text-sm transition-colors',
        'focus:outline-none focus:ring-2 focus:ring-primary-200',
        'disabled:bg-neutral-100 disabled:text-neutral-500 disabled:cursor-not-allowed',
        hasError
          ? 'border-error-300 bg-error-50 focus:border-error-500'
          : 'border-neutral-200 bg-white focus:border-primary-500',
      ]"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="hasError" class="text-xs text-error-600">{{ error }}</p>
    <p v-else-if="hasHelp" class="text-xs text-neutral-500">{{ help }}</p>
  </div>
</template>
