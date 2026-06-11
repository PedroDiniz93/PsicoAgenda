<script setup lang="ts">
import { computed } from 'vue';
import * as LucideIcons from '@lucide/vue';

interface Props {
    name: string;
    size?: number;
    strokeWidth?: number;
    ariaHidden?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    size: 18,
    strokeWidth: 2,
    ariaHidden: true,
});

const fallbackIconName = 'Circle';

const iconComponent = computed(() => {
    const iconMap = LucideIcons as Record<string, unknown>;
    return (iconMap[props.name] as object) ?? (iconMap[fallbackIconName] as object);
});
</script>

<template>
    <component
        :is="iconComponent"
        :size="size"
        :stroke-width="strokeWidth"
        :aria-hidden="ariaHidden"
    />
</template>
