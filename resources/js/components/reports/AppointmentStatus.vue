<script setup lang="ts">
import Badge from '../base/Badge.vue';
import Card from '../base/Card.vue';

interface Item {
  id: string | number;
  patient?: { name: string };
  start_at: string;
  status?: string;
}

interface Section {
  key: string;
  label: string;
  empty: string;
  count?: number;
}

interface Props {
  sections: Section[];
  items: Record<string, Item[]>;
  statusLabel?: (status: string) => string;
  statusBadgeClass?: (status: string) => string;
  formatDateTime: (date: string) => string;
}

withDefaults(defineProps<Props>(), {
  statusLabel: (s) => s,
  statusBadgeClass: () => 'bg-neutral-100 text-neutral-800',
});
</script>

<template>
  <Card>
    <div class="flex items-center justify-between gap-3 mb-6">
      <div>
        <p class="text-sm font-semibold text-neutral-900">Agendamentos</p>
        <p class="text-xs text-neutral-600">Resumo dos atendimentos</p>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
      <div
        v-for="section in sections"
        :key="section.key"
        class="rounded-lg border border-neutral-100 bg-neutral-50 p-4"
      >
        <div class="mb-4 flex items-center justify-between">
          <div>
            <p class="text-sm font-semibold text-neutral-900">{{ section.label }}</p>
            <p class="text-xs text-neutral-600">{{ section.count || 0 }} atendimentos</p>
          </div>
        </div>

        <div
          v-if="items[section.key]?.length"
          class="space-y-2"
        >
          <div
            v-for="item in items[section.key].slice(0, 4)"
            :key="`${section.key}-${item.id}`"
            class="rounded-lg border border-neutral-100 bg-white p-3 text-sm transition-colors hover:bg-neutral-50"
          >
            <p class="font-medium text-neutral-900">
              {{ item.patient?.name ?? 'Paciente sem nome' }}
            </p>
            <p class="text-xs text-neutral-500">{{ formatDateTime(item.start_at) }}</p>
          </div>

          <p v-if="items[section.key].length > 4" class="text-xs text-neutral-500 pt-2">
            + {{ items[section.key].length - 4 }} mais registros
          </p>
        </div>
        <p v-else class="text-sm text-neutral-600">{{ section.empty }}</p>
      </div>
    </div>
  </Card>
</template>
