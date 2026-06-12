<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from './stores/auth';
import AppShell from './components/AppShell.vue';

const route = useRoute();
const auth = useAuthStore();

const usesProductShell = computed(() =>
    auth.isAuthenticated
    && !auth.requiresEmailVerification
    && !route.meta?.public
    && route.name !== 'email-verification'
);
</script>

<template>
    <AppShell v-if="usesProductShell">
        <router-view />
    </AppShell>

    <div v-else class="min-h-screen text-slate-900">
        <router-view />
    </div>

</template>
