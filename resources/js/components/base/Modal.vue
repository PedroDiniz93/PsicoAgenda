<script setup lang="ts">
import { ref } from 'vue';

interface Props {
  modelValue?: boolean;
}

defineProps<Props>();
defineEmits<{
  'update:modelValue': [value: boolean];
  close: [];
}>();

const closeButton = ref<HTMLElement | null>(null);
</script>

<template>
  <teleport to="body">
    <transition name="fade">
      <div
        v-show="modelValue"
        class="fixed inset-0 z-modal-backdrop bg-[#0f1418]/45 backdrop-blur-sm"
        @click="$emit('update:modelValue', false)"
      />
    </transition>
    <transition name="modal">
      <div v-show="modelValue" class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto px-4 py-4 sm:items-center sm:py-8" role="dialog" aria-modal="true" tabindex="-1" @keydown.esc="$emit('update:modelValue', false)">
        <div class="my-auto max-h-[calc(100vh-2rem)] w-full max-w-2xl overflow-y-auto rounded-2xl border border-[var(--spa-border-soft)] bg-[var(--spa-surface)] shadow-[var(--spa-shadow)] sm:max-h-[calc(100vh-4rem)]" @click.stop>
          <slot />
        </div>
      </div>
    </transition>
  </teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 200ms ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.modal-enter-active,
.modal-leave-active {
  transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
