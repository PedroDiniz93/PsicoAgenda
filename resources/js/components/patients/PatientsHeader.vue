<script setup>
import { RouterLink } from 'vue-router';
import AppIcon from '../base/AppIcon.vue';
import AppPageHeader from '../layout/AppPageHeader.vue';

defineEmits(['create']);
</script>

<template>
    <AppPageHeader
        kicker="Consultório"
        title="Pacientes"
        description="Gerencie cadastros e acesse prontuários com as informações essenciais em primeiro plano."
    >
        <template #actions>
                <RouterLink
                    :to="{ name: 'home' }"
                    class="btn-secondary h-10"
                >
                    <AppIcon name="ChevronLeft" class="size-4" />
                    Dashboard
                </RouterLink>
                <button
                    class="btn-primary h-10"
                    type="button"
                    @click="$emit('create')"
                >
                    <AppIcon name="UserRoundPlus" class="size-4" />
                    Cadastrar paciente
                </button>
        </template>
    </AppPageHeader>
</template>
