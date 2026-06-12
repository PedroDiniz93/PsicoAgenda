<script setup lang="ts">
import { ref, computed } from 'vue';
import AppIcon from './AppIcon.vue';

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
    <label v-if="label" class="text-sm font-semibold text-[#42474c]">
      {{ label }}
      <span v-if="required" class="text-[#ba1a1a]">*</span>
    </label>
    <div class="relative">
      <button
        type="button"
        :class="[
          'w-full rounded-lg border px-4 py-2 text-left text-sm transition-colors',
          'flex items-center justify-between gap-2',
          'focus:outline-none focus:ring-2 focus:ring-[#cae6ff]',
          'disabled:bg-[#f3f4f3] disabled:text-[#73787d] disabled:cursor-not-allowed',
          hasError
            ? 'border-[#ffb4ab] bg-[#ffdad6] focus:border-[#ba1a1a]'
            : 'border-[#e2e2e2] bg-white text-[#1a1c1c] focus:border-[#415f76]',
        ]"
        :disabled="disabled"
        @click="open = !open"
      >
        <span>{{ selectedLabel }}</span>
        <AppIcon name="ChevronDown" class="size-4 transition-transform" :class="{ 'rotate-180': open }" />
      </button>
      <div
        v-if="open"
        class="absolute left-0 right-0 top-full z-dropdown mt-1 overflow-hidden rounded-lg border border-[#e2e2e2] bg-white shadow-[0_24px_60px_rgba(65,95,118,0.08)]"
        @click.self="open = false"
      >
        <button
          v-for="option in options"
          :key="option.value"
          type="button"
          class="w-full px-4 py-2 text-left text-sm transition-colors hover:bg-[#f3f4f3]"
          :class="option.value === modelValue ? 'bg-[#cae6ff] font-semibold text-[#415f76]' : 'text-[#42474c]'"
          @click="
            emit('update:modelValue', option.value);
            open = false;
          "
        >
          {{ option.label }}
        </button>
      </div>
    </div>
    <p v-if="hasError" class="text-xs text-[#ba1a1a]">{{ error }}</p>
  </div>
</template>
