<script setup>
import { useTheme } from '../composables/useTheme';
import AppIcon from './AppIcon.vue';

const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <button
        type="button"
        class="btn btn-secondary !p-2.5"
        :aria-label="isDark ? 'Ativar tema claro' : 'Ativar tema escuro'"
        :title="isDark ? 'Tema claro' : 'Tema escuro'"
        @click="toggleTheme"
    >
        <AppIcon :name="isDark ? 'sun' : 'moon'" :size="18" />
    </button>
</template>
