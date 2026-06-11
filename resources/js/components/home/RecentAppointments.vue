<script setup lang="ts">
import Card from '../base/Card.vue';
import AppIcon from '../base/AppIcon.vue';

interface RecentAppointment {
  id: string;
  patient_name: string;
  date: string;
  time: string;
  type: string;
  status: 'completed' | 'pending' | 'cancelled';
}

interface Props {
  appointments: RecentAppointment[];
  loading?: boolean;
}

withDefaults(defineProps<Props>(), {
  loading: false,
});

const statusColors = {
  completed: 'bg-success-100 text-success-800',
  pending: 'bg-warning-100 text-warning-800',
  cancelled: 'bg-error-100 text-error-800',
};

const statusLabels = {
  completed: 'Concluído',
  pending: 'Pendente',
  cancelled: 'Cancelado',
};
</script>

<template>
  <Card>
    <div class="mb-4">
      <h3 class="text-lg font-semibold text-neutral-900">Agendamentos recentes</h3>
      <p class="text-sm text-neutral-600">Últimas sessões realizadas e próximas</p>
    </div>

    <div v-if="loading" class="py-8 text-center">
      <AppIcon name="LoaderCircle" class="inline-block size-6 animate-spin text-primary-600" />
      <p class="text-sm text-neutral-600 mt-2">Carregando...</p>
    </div>

    <div v-else-if="appointments.length" class="space-y-3">
      <div
        v-for="appointment in appointments"
        :key="appointment.id"
        class="flex items-center justify-between p-3 rounded-lg border border-neutral-100 hover:bg-neutral-50 transition"
      >
        <div class="flex-1">
          <p class="font-medium text-neutral-900">{{ appointment.patient_name }}</p>
          <div class="mt-1 flex flex-wrap gap-3 text-xs text-neutral-600">
            <span class="inline-flex items-center gap-1.5">
              <AppIcon name="CalendarDays" class="size-3.5" />
              {{ appointment.date }}
            </span>
            <span class="inline-flex items-center gap-1.5">
              <AppIcon name="Clock3" class="size-3.5" />
              {{ appointment.time }}
            </span>
            <span class="inline-flex items-center gap-1.5">
              <AppIcon name="NotebookPen" class="size-3.5" />
              {{ appointment.type }}
            </span>
          </div>
        </div>
        <span :class="['px-3 py-1 rounded-full text-xs font-semibold', statusColors[appointment.status]]">
          {{ statusLabels[appointment.status] }}
        </span>
      </div>
    </div>

    <div v-else class="py-8 text-center text-neutral-600">
      <p>Nenhum agendamento encontrado</p>
    </div>
  </Card>
</template>
