<script setup>
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
    <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <form class="grid gap-3 lg:grid-cols-[1fr_220px_auto]" @submit.prevent="$emit('search')">
            <label class="block text-sm font-semibold text-slate-700" for="patients-search">
                Busca
                <input
                    id="patients-search"
                    v-model="filters.q"
                    class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                    placeholder="Nome, e-mail ou telefone"
                    type="search"
                />
            </label>

            <label class="block text-sm font-semibold text-slate-700" for="patients-status">
                Status
                <select
                    id="patients-status"
                    v-model="filters.status"
                    class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                >
                    <option v-for="option in filterOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </label>

            <div class="flex items-end gap-2">
                <button
                    class="inline-flex h-11 items-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"
                    type="submit"
                >
                    Filtrar
                </button>
                <button
                    class="inline-flex h-11 items-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="!hasFilters"
                    @click="$emit('clear')"
                >
                    Limpar
                </button>
            </div>
        </form>
    </section>
</template>
