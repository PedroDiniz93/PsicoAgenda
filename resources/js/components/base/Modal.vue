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
        class="fixed inset-0 z-modal-backdrop bg-[#2f3130]/40 backdrop-blur-sm"
        @click="$emit('update:modelValue', false)"
      />
    </transition>
    <transition name="modal">
      <div v-show="modelValue" class="fixed inset-0 z-modal flex items-start justify-center px-4 py-10">
        <div class="w-full max-w-2xl rounded-2xl border border-[#e2e2e2] bg-white shadow-[0_24px_60px_rgba(65,95,118,0.12)]" @click.stop>
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
