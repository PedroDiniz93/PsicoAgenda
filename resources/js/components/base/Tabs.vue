<script setup lang="ts">
import { ref, computed } from 'vue';

interface Tab {
  id: string;
  label: string;
}

interface Props {
  tabs: Tab[];
  activeTab?: string;
}

const props = withDefaults(defineProps<Props>(), {
  activeTab: '',
});

const emit = defineEmits<{
  'update:activeTab': [value: string];
}>();

const currentTab = ref(props.activeTab || (props.tabs[0]?.id || ''));

const active = computed(() => currentTab.value);

const selectTab = (tabId: string) => {
  currentTab.value = tabId;
  emit('update:activeTab', tabId);
};
</script>

<template>
  <div>
    <div class="flex gap-4 border-b border-neutral-100">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        :class="[
          'px-4 py-3 text-sm font-medium transition-colors border-b-2 -mb-px',
          active === tab.id
            ? 'border-primary-600 text-primary-600'
            : 'border-transparent text-neutral-600 hover:text-neutral-900',
        ]"
        @click="selectTab(tab.id)"
      >
        {{ tab.label }}
      </button>
    </div>
    <div>
      <slot :active-tab="active" />
    </div>
  </div>
</template>
