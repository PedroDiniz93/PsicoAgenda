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

const props = defineProps<Props>();
defineEmits<{
  'update:modelValue': [value: string | number];
}>();

const hasError = computed(() => Boolean(props.error));
const hasHelp = computed(() => Boolean(props.help) && !hasError.value);
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" class="text-sm font-semibold text-[var(--spa-ink-soft)]">
      {{ label }}
      <span v-if="required" class="text-[var(--spa-error)]">*</span>
    </label>
    <input
      :value="modelValue"
      :type="type || 'text'"
      :placeholder="placeholder"
      :disabled="disabled"
      :class="[
        'w-full rounded-lg border px-4 py-2 text-sm text-[var(--spa-ink)] transition-colors',
        'focus:outline-none focus:ring-2 focus:ring-[var(--spa-focus)]',
        'disabled:bg-[var(--spa-surface-muted)] disabled:text-[var(--spa-ink-muted)] disabled:cursor-not-allowed',
        hasError
          ? 'border-[var(--spa-error)] bg-[var(--spa-error-soft)] focus:border-[var(--spa-error)]'
          : 'border-[var(--spa-border-soft)] bg-[var(--spa-surface)] focus:border-[var(--spa-accent)]',
      ]"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="hasError" class="text-xs text-[var(--spa-error)]">{{ error }}</p>
    <p v-else-if="hasHelp" class="text-xs text-[var(--spa-ink-muted)]">{{ help }}</p>
  </div>
</template>
