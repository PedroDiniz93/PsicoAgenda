<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  userName: string;
  userEmail: string;
  psychologistInfo?: {
    totalPatients?: number;
    appointmentsToday?: number;
    reviewsPending?: number;
  };
}

const props = withDefaults(defineProps<Props>(), {
  userName: 'Psicólogo(a)',
  userEmail: '',
  psychologistInfo: () => ({
    totalPatients: 0,
    appointmentsToday: 0,
    reviewsPending: 0,
  }),
});

const emit = defineEmits<{
  logout: [];
}>();

const greeting = computed(() => {
  const hour = new Date().getHours();
  if (hour < 12) return 'Bom dia';
  if (hour < 18) return 'Boa tarde';
  return 'Boa noite';
});
</script>

<template>
  <div class="border-b border-neutral-200 bg-white sticky top-0 z-sticky">
    <div class="mx-auto max-w-7xl px-4 py-4 lg:px-8">
      <div class="flex items-center justify-between gap-4">
        <!-- User Info -->
        <div class="flex-1">
          <p class="text-xs font-semibold uppercase tracking-widest text-neutral-500">{{ greeting }}</p>
          <h1 class="text-2xl font-bold text-neutral-900">{{ userName }}</h1>
          <p class="text-sm text-neutral-600">{{ userEmail }}</p>
        </div>

        <!-- Stats Overview (Optional) -->
        <div class="hidden sm:flex gap-6">
          <div class="text-center">
            <p class="text-2xl font-bold text-primary-600">{{ psychologistInfo?.totalPatients ?? 0 }}</p>
            <p class="text-xs text-neutral-600">Pacientes</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-success-600">{{ psychologistInfo?.appointmentsToday ?? 0 }}</p>
            <p class="text-xs text-neutral-600">Hoje</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-warning-600">{{ psychologistInfo?.reviewsPending ?? 0 }}</p>
            <p class="text-xs text-neutral-600">Pendências</p>
          </div>
        </div>

        <!-- Logout Button -->
        <button
          class="px-4 py-2 rounded-lg border border-neutral-200 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 transition-colors"
          @click="$emit('logout')"
        >
          Sair
        </button>
      </div>
    </div>
  </div>
</template>
