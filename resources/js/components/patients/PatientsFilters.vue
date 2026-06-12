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
    <section class="rounded-t-xl border border-[#c2c7cd]/10 bg-white shadow-[0_20px_40px_-10px_rgba(93,123,147,0.06)]">
        <form class="flex flex-col gap-4 border-b border-[#c2c7cd]/20 p-6 lg:flex-row lg:items-center lg:justify-between" @submit.prevent="$emit('search')">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="option in filterOptions"
                    :key="option.value"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition"
                    :class="filters.status === option.value
                        ? 'bg-[#cbe6d4]/80 text-[#506859]'
                        : 'text-[#73787d] hover:bg-[#eeeeed]'"
                    type="button"
                    @click="filters.status = option.value; $emit('search')"
                >
                    {{ option.label }}
                </button>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <label class="relative block min-w-0 sm:w-72" for="patients-search">
                    <span class="sr-only">Busca</span>
                    <AppIcon name="Search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-[#73787d]" />
                    <input
                        id="patients-search"
                        v-model="filters.q"
                        class="h-11 w-full rounded-full border border-[#c2c7cd]/30 bg-[#f9f9f8] pl-10 pr-4 text-sm text-[#1a1c1c] placeholder:text-[#73787d] focus:border-[#415f76] focus:ring-[#415f76]"
                        placeholder="Nome, e-mail ou telefone"
                        type="search"
                    />
                </label>

                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-[#415f76] px-4 text-sm font-semibold text-white transition hover:bg-[#2b4a60]" type="submit">
                    <AppIcon name="Search" class="size-4" />
                    Filtrar
                </button>
                <button
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-[#c2c7cd]/30 bg-[#eeeeed] px-4 text-sm font-semibold text-[#415f76] transition hover:bg-[#e8e8e7] disabled:cursor-not-allowed disabled:opacity-50"
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
