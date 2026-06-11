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
    <nav class="mb-6 flex w-full gap-1 rounded-2xl border border-[#e2ddd3] bg-white/95 p-1 shadow-sm" aria-label="Seções da home">
        <button
            v-for="tab in tabs"
            :key="tab.id"
            class="h-10 flex-1 rounded-xl px-3 text-sm font-semibold transition"
            :class="
                activeTab === tab.id
                    ? 'bg-[#3f4f46] text-white shadow-sm'
                    : 'text-[#58635f] hover:bg-[#f6f2ea] hover:text-[#1f2522]'
            "
            type="button"
            @click="$emit('change', tab.id)"
        >
            {{ tab.label }}
        </button>
    </nav>
</template>
