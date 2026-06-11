<script setup>
import AppIcon from '../base/AppIcon.vue';

defineProps({
    filters: {
        type: Object,
        required: true,
    },
    filterOptions: {
        type: Array,
        required: true,
    },
    hasFilters: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['search', 'clear']);
</script>

<template>
    <section class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-4 shadow-sm">
        <form class="grid gap-3 lg:grid-cols-[1fr_220px_auto]" @submit.prevent="$emit('search')">
            <label class="block text-sm font-semibold text-slate-700" for="patients-search">
                Busca
                <input
                    id="patients-search"
                    v-model="filters.q"
                    class="field-input mt-2 h-11"
                    placeholder="Nome, e-mail ou telefone"
                    type="search"
                />
            </label>

            <label class="block text-sm font-semibold text-slate-700" for="patients-status">
                Status
                <select
                    id="patients-status"
                    v-model="filters.status"
                    class="field-input mt-2 h-11"
                >
                    <option v-for="option in filterOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </label>

            <div class="flex items-end gap-2">
                <button class="btn-primary h-11" type="submit">
                    <AppIcon name="Search" class="size-4" />
                    Filtrar
                </button>
                <button
                    class="btn-secondary h-11 disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="!hasFilters"
                    @click="$emit('clear')"
                >
                    <AppIcon name="X" class="size-4" />
                    Limpar
                </button>
            </div>
        </form>
    </section>
</template>
