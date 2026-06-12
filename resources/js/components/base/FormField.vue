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
    <label v-if="label" class="text-sm font-semibold text-[#42474c]">
      {{ label }}
      <span v-if="required" class="text-[#ba1a1a]">*</span>
    </label>
    <input
      :value="modelValue"
      :type="type || 'text'"
      :placeholder="placeholder"
      :disabled="disabled"
      :class="[
        'w-full rounded-lg border px-4 py-2 text-sm text-[#1a1c1c] transition-colors',
        'focus:outline-none focus:ring-2 focus:ring-[#cae6ff]',
        'disabled:bg-[#f3f4f3] disabled:text-[#73787d] disabled:cursor-not-allowed',
        hasError
          ? 'border-[#ffb4ab] bg-[#ffdad6] focus:border-[#ba1a1a]'
          : 'border-[#e2e2e2] bg-white focus:border-[#415f76]',
      ]"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="hasError" class="text-xs text-[#ba1a1a]">{{ error }}</p>
    <p v-else-if="hasHelp" class="text-xs text-[#73787d]">{{ help }}</p>
  </div>
</template>
