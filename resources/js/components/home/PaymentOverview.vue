<script setup lang="ts">
import Card from '../base/Card.vue';

interface Props {
  paidValue: string | number;
  pendingValue: string | number;
  paidAppointments: number;
  pendingAppointments: number;
}

defineProps<Props>();
</script>

<template>
  <div class="grid gap-4 md:grid-cols-2">
    <!-- Pagos -->
    <Card class="border-success-200 bg-success-50">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-xs font-bold uppercase tracking-widest text-neutral-600">Pagos hoje</p>
          <p class="text-4xl font-bold text-success-900 mt-2">{{ paidValue }}</p>
          <p class="text-sm text-neutral-700 mt-2">{{ paidAppointments }} atendimentos recebidos</p>
        </div>
        <div class="p-3 rounded-lg bg-success-100 text-success-600">
          <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
    </Card>

    <!-- Pendentes -->
    <Card class="border-warning-200 bg-warning-50">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-xs font-bold uppercase tracking-widest text-neutral-600">Pendentes</p>
          <p class="text-4xl font-bold text-warning-900 mt-2">{{ pendingValue }}</p>
          <p class="text-sm text-neutral-700 mt-2">{{ pendingAppointments }} aguardando recebimento</p>
        </div>
        <div class="p-3 rounded-lg bg-warning-100 text-warning-600">
          <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
    </Card>
  </div>
</template>
