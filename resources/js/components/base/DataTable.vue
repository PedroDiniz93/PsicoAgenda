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
  <div class="overflow-x-auto rounded-xl border border-[#e2ddd3] bg-white">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-[#ece6db] bg-[#f8f5ef]">
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            :class="[
              'px-6 py-3 font-semibold uppercase tracking-wide text-[#58635f]',
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
            'border-b border-[#f0eadf] transition-colors',
            striped && idx % 2 === 0 ? 'bg-[#fcfaf6]' : 'bg-white',
            hoverable ? 'hover:bg-[#f6f2ea]' : '',
          ]"
        >
          <td v-for="column in columns" :key="`${idx}-${column.key}`" :class="['px-6 py-4 text-[#3f4742]', column.class || '']">
            <slot :name="`cell-${column.key}`" :row="row" :column="column">
              {{ row[column.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
      <tbody v-else-if="loading">
        <tr>
          <td :colspan="columns.length" class="px-6 py-8 text-center text-[#58635f]">
            <div class="inline-flex items-center gap-2">
              <AppIcon name="LoaderCircle" class="size-4 animate-spin" />
              Carregando...
            </div>
          </td>
        </tr>
      </tbody>
      <tbody v-else>
        <tr>
          <td :colspan="columns.length" class="px-6 py-8 text-center text-[#58635f]">
            Nenhum registro encontrado
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
