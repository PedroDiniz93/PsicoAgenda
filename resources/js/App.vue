<script setup>
import PatientAlertsBell from './components/PatientAlertsBell.vue';
</script>

<template>
    <div class="min-h-screen bg-slate-100 text-slate-900">
        <router-view />
    </div>
    <PatientAlertsBell />
</template>
