<script setup lang="ts">
import { computed } from 'vue';

interface Column {
  key: string;
  label: string;
  class?: string;
  sortable?: boolean;
}

interface Props {
  columns: Column[];
  rows: Array<Record<string, any>>;
  loading?: boolean;
  striped?: boolean;
  hoverable?: boolean;
}

withDefaults(defineProps<Props>(), {
  loading: false,
  striped: true,
  hoverable: true,
});
</script>

<template>
  <div class="overflow-x-auto rounded-lg border border-neutral-100">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-neutral-100 bg-neutral-50">
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            :class="[
              'px-6 py-3 font-semibold text-neutral-700 uppercase tracking-wide',
              column.class || '',
            ]"
          >
            {{ column.label }}
          </th>
        </tr>
      </thead>
      <tbody v-if="!loading && rows.length > 0">
        <tr
          v-for="(row, idx) in rows"
          :key="idx"
          :class="[
            'border-b border-neutral-100 transition-colors',
            striped && idx % 2 === 0 ? 'bg-neutral-50' : 'bg-white',
            hoverable ? 'hover:bg-neutral-100' : '',
          ]"
        >
          <td v-for="column in columns" :key="`${idx}-${column.key}`" :class="['px-6 py-4 text-neutral-700', column.class || '']">
            <slot :name="`cell-${column.key}`" :row="row" :column="column">
              {{ row[column.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
      <tbody v-else-if="loading">
        <tr>
          <td :colspan="columns.length" class="px-6 py-8 text-center text-neutral-500">
            <div class="inline-flex items-center gap-2">
              <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
              </svg>
              Carregando...
            </div>
          </td>
        </tr>
      </tbody>
      <tbody v-else>
        <tr>
          <td :colspan="columns.length" class="px-6 py-8 text-center text-neutral-500">
            Nenhum registro encontrado
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
