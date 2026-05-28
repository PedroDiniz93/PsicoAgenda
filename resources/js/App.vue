<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import PatientAlertsBell from './components/PatientAlertsBell.vue';
import AppShell from './components/AppShell.vue';

const route = useRoute();
// Public routes (login) render without the app chrome.
const useShell = computed(() => route.name && route.meta?.public !== true);
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <AppShell v-if="useShell">
            <router-view />
        </AppShell>
        <router-view v-else />
    </div>
    <PatientAlertsBell />
</template>
