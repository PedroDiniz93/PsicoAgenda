<script setup lang="ts">
import Card from '../base/Card.vue';
import AppIcon from '../base/AppIcon.vue';

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
          <AppIcon name="CircleDollarSign" class="size-8" />
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
          <AppIcon name="Clock3" class="size-8" />
        </div>
      </div>
    </Card>
  </div>
</template>
