<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import AppIcon from '../base/AppIcon.vue';

interface NavigationItem {
    id: string;
    label: string;
    icon: string;
    to: { name: string; query?: Record<string, string> };
    group?: string;
}

const route = useRoute();
const activeItemElement = ref<HTMLElement | null>(null);

const setActiveItemElement = (element: HTMLElement | { $el?: HTMLElement } | null, item: NavigationItem) => {
    if (!isItemActive(item)) return;

    const domElement = element && '$el' in element ? element.$el : element;
    activeItemElement.value = domElement instanceof HTMLElement ? domElement : null;
};

const props = defineProps<{
    items: NavigationItem[];
    collapsed: boolean;
    mobileOpen: boolean;
    userName: string;
}>();

const groupedItems = computed(() => {
    const groups: Array<{ label: string; items: NavigationItem[] }> = [];

    props.items.forEach((item) => {
        const label = item.group ?? 'Navegação';
        const existing = groups.find((group) => group.label === label);
        if (existing) existing.items.push(item);
        else groups.push({ label, items: [item] });
    });

    return groups;
});

const isItemActive = (item: NavigationItem) => {
    if (item.to.name !== route.name) return false;

    if (item.to.name === 'home') {
        const targetTab = item.to.query?.tab ?? 'overview';
        const currentTab = Array.isArray(route.query.tab) ? route.query.tab[0] : route.query.tab;
        return targetTab === (currentTab ?? 'overview');
    }

    return true;
};

watch(
    () => route.fullPath,
    async () => {
        await nextTick();
        activeItemElement.value?.scrollIntoView({ block: 'nearest' });
    },
    { immediate: true }
);

defineEmits<{
    closeMobile: [];
    logout: [];
    toggleCollapse: [];
}>();
</script>

<template>
    <div
        v-if="mobileOpen"
        class="fixed inset-0 z-40 bg-[#0f1418]/45 backdrop-blur-[2px] lg:hidden"
        aria-hidden="true"
        @click="$emit('closeMobile')"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-white/10 bg-[var(--spa-sidebar)] text-[var(--spa-sidebar-ink)] shadow-2xl shadow-slate-950/10 transition-all duration-200"
        :class="[
            collapsed ? 'lg:w-20' : 'lg:w-64',
            mobileOpen ? 'w-72 translate-x-0' : 'w-72 -translate-x-full lg:translate-x-0',
        ]"
    >
        <div class="flex h-full min-h-0 flex-col px-3 py-5">
            <div class="flex items-center gap-3 px-2 pb-5" :class="collapsed ? 'lg:justify-center' : ''">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white/12 text-white ring-1 ring-white/15">
                    <AppIcon name="HeartPulse" class="size-5" />
                </span>
                <div class="min-w-0" :class="collapsed ? 'lg:hidden' : ''">
                    <p class="font-display text-xl font-semibold leading-tight text-white">PsicoAgenda</p>
                    <p class="mt-0.5 text-[11px] text-[var(--spa-sidebar-muted)]">Gestão do consultório</p>
                </div>
            </div>

            <button
                class="mb-3 hidden items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-[var(--spa-sidebar-muted)] transition hover:bg-white/10 hover:text-white lg:flex"
                :class="collapsed ? 'justify-center' : ''"
                type="button"
                :aria-label="collapsed ? 'Expandir menu' : 'Recolher menu'"
                @click="$emit('toggleCollapse')"
            >
                <AppIcon :name="collapsed ? 'PanelLeftOpen' : 'PanelLeftClose'" class="size-5" />
                <span :class="collapsed ? 'lg:hidden' : ''">{{ collapsed ? 'Expandir' : 'Recolher' }}</span>
            </button>

            <nav class="min-h-0 flex-1 space-y-3 overflow-y-auto pb-4" aria-label="Navegação principal">
                <div v-for="group in groupedItems" :key="group.label">
                    <p
                        class="mb-1.5 px-3 text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--spa-sidebar-muted)]"
                        :class="collapsed ? 'lg:hidden' : ''"
                    >
                        {{ group.label }}
                    </p>
                    <div class="grid gap-1">
                        <RouterLink
                            v-for="item in group.items"
                            :key="item.id"
                            :to="item.to"
                            class="sidebar-nav-item flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40"
                            :class="[
                                collapsed ? 'lg:justify-center' : '',
                                isItemActive(item) ? 'bg-white text-[var(--spa-sidebar)] shadow-sm' : 'text-[var(--spa-sidebar-muted)] hover:bg-white/10 hover:text-white',
                            ]"
                            :title="collapsed ? item.label : undefined"
                            :ref="(element) => setActiveItemElement(element, item)"
                            @click="$emit('closeMobile')"
                        >
                            <AppIcon :name="item.icon" class="size-5 shrink-0" />
                            <span class="truncate" :class="collapsed ? 'lg:hidden' : ''">{{ item.label }}</span>
                        </RouterLink>
                    </div>
                </div>
            </nav>

            <div class="grid gap-2 border-t border-white/10 pt-4">
                <div class="flex items-center gap-3 px-3 py-2" :class="collapsed ? 'lg:justify-center' : ''">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white/12 text-xs font-bold text-white">
                        {{ userName.slice(0, 1).toUpperCase() }}
                    </span>
                    <p class="truncate text-xs font-semibold text-white" :class="collapsed ? 'lg:hidden' : ''">{{ userName }}</p>
                </div>
                <button
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-[var(--spa-sidebar-muted)] transition hover:bg-white/10 hover:text-white"
                    :class="collapsed ? 'lg:justify-center' : ''"
                    type="button"
                    @click="$emit('logout')"
                >
                    <AppIcon name="LogOut" class="size-5 shrink-0" />
                    <span :class="collapsed ? 'lg:hidden' : ''">Sair</span>
                </button>
            </div>
        </div>
    </aside>
</template>
