<script setup lang="ts">
defineProps<{
    tabs: Array<{ id: string; label: string }>;
    activeTab: string;
}>();

defineEmits<{
    change: [tabId: string];
}>();
</script>

<template>
    <nav class="mb-6 flex w-full gap-1 rounded-lg border border-slate-200 bg-white p-1 shadow-sm" aria-label="Seções da home">
        <button
            v-for="tab in tabs"
            :key="tab.id"
            class="h-10 flex-1 rounded-md px-3 text-sm font-semibold transition"
            :class="
                activeTab === tab.id
                    ? 'bg-slate-950 text-white shadow-sm'
                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
            "
            type="button"
            @click="$emit('change', tab.id)"
        >
            {{ tab.label }}
        </button>
    </nav>
</template>
