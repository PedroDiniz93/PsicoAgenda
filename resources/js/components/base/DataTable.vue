<script setup lang="ts">
import AppIcon from './AppIcon.vue';

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
  <div class="overflow-x-auto rounded-2xl border border-[#e2e2e2] bg-white">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-[#e2e2e2] bg-[#f3f4f3]">
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            :class="[
              'px-6 py-3 font-semibold text-[#73787d]',
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
            'border-b border-[#eeeeed] transition-colors',
            striped && idx % 2 === 0 ? 'bg-[#f9f9f8]' : 'bg-white',
            hoverable ? 'hover:bg-[#f3f4f3]' : '',
          ]"
        >
          <td v-for="column in columns" :key="`${idx}-${column.key}`" :class="['px-6 py-4 text-[#42474c]', column.class || '']">
            <slot :name="`cell-${column.key}`" :row="row" :column="column">
              {{ row[column.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
      <tbody v-else-if="loading">
        <tr>
          <td :colspan="columns.length" class="px-6 py-8 text-center text-[#73787d]">
            <div class="inline-flex items-center gap-2">
              <AppIcon name="LoaderCircle" class="size-4 animate-spin" />
              Carregando...
            </div>
          </td>
        </tr>
      </tbody>
      <tbody v-else>
        <tr>
          <td :colspan="columns.length" class="px-6 py-8 text-center text-[#73787d]">
            Nenhum registro encontrado
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
