<script setup lang="ts">
import Card from '../base/Card.vue';
import Badge from '../base/Badge.vue';
import DataTable from '../base/DataTable.vue';

interface Item {
  id: string | number;
  patient?: { name: string };
  start_at: string;
  price: number;
  paid_at?: string;
  status?: string;
}

interface Props {
  title: string;
  subtitle: string;
  total: number;
  count: number;
  items: Item[];
  color: 'success' | 'warning';
  columns: Array<{ key: string; label: string; class?: string }>;
  formatDateTime: (date: string) => string;
  formatMoney: (value: number) => string;
  statusLabel?: (status: string) => string;
  showPaidDate?: boolean;
}

withDefaults(defineProps<Props>(), {
  statusLabel: (s) => s,
  showPaidDate: false,
});
</script>

<template>
  <Card>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-sm font-semibold text-neutral-900">{{ title }}</p>
        <p class="text-xs text-neutral-600">{{ subtitle }}</p>
      </div>
      <div class="text-right">
        <p :class="['text-2xl font-bold', color === 'success' ? 'text-success-600' : 'text-warning-600']">
          {{ formatMoney(total) }}
        </p>
        <p class="text-xs text-neutral-600">{{ count }} atendimentos</p>
      </div>
    </div>

    <div v-if="items.length > 0" class="space-y-4">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="border-b border-neutral-200 bg-neutral-50">
            <tr>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-neutral-600">Paciente</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-neutral-600">Data</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-neutral-600">Valor</th>
              <th v-if="showPaidDate" class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-neutral-600">Pago em</th>
              <th v-else class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-neutral-600">Situação</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-neutral-100">
            <tr v-for="(item, idx) in items.slice(0, 10)" :key="`payment-${item.id}-${idx}`" class="hover:bg-neutral-50 transition-colors">
              <td class="px-4 py-3">
                <p class="font-medium text-neutral-900">{{ item.patient?.name ?? 'Paciente sem nome' }}</p>
                <p class="text-xs text-neutral-500">#{{ item.id }}</p>
              </td>
              <td class="px-4 py-3 text-neutral-700">{{ formatDateTime(item.start_at) }}</td>
              <td class="px-4 py-3 font-medium text-neutral-900">{{ formatMoney(item.price) }}</td>
              <td v-if="showPaidDate" class="px-4 py-3 text-neutral-700">{{ formatDateTime(item.paid_at || '') }}</td>
              <td v-else class="px-4 py-3">
                <Badge :status="item.status ? (item.status === 'pending' ? 'warning' : 'info') : 'info'">
                  {{ statusLabel?.(item.status || '') || item.status }}
                </Badge>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-if="items.length > 10" class="text-xs text-neutral-600">
        Mostrando os 10 mais recentes de {{ items.length }} total.
      </p>
    </div>
    <div v-else class="py-8 text-center">
      <p class="text-sm text-neutral-600">
        {{
          color === 'success'
            ? 'Nenhum pagamento registrado nesse período.'
            : 'Sem pendências financeiras nesse período.'
        }}
      </p>
    </div>
  </Card>
</template>
