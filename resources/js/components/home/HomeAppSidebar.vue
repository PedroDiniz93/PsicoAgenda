<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router';
import AppIcon from '../base/AppIcon.vue';

interface NavigationItem {
    id: string;
    label: string;
    icon: string;
    to: { name: string; query?: Record<string, string> };
}

defineProps<{
    items: NavigationItem[];
    collapsed: boolean;
    mobileOpen: boolean;
    userName: string;
}>();

const route = useRoute();

const isItemActive = (item: NavigationItem) => {
    if (item.to.name !== route.name) return false;

    if (item.to.name === 'home') {
        const targetTab = item.to.query?.tab ?? 'overview';
        const currentTab = Array.isArray(route.query.tab) ? route.query.tab[0] : route.query.tab;
        return targetTab === (currentTab ?? 'overview');
    }

    return true;
};

defineEmits<{
    closeMobile: [];
    logout: [];
    toggleCollapse: [];
}>();
</script>

<template>
    <div
        v-if="mobileOpen"
        class="fixed inset-0 z-40 bg-[#1a1c1c]/35 lg:hidden"
        aria-hidden="true"
        @click="$emit('closeMobile')"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-[#e2e2e2] bg-white transition-all duration-200"
        :class="[
            collapsed ? 'lg:w-20' : 'lg:w-64',
            mobileOpen ? 'w-72 translate-x-0' : 'w-72 -translate-x-full lg:translate-x-0',
        ]"
    >
        <div class="flex h-full flex-col gap-5 px-4 py-5">
            <div class="flex items-center gap-3 px-2" :class="collapsed ? 'lg:justify-center' : ''">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#415f76] text-white">
                    <AppIcon name="HeartPulse" class="size-5" />
                </span>
                <div class="min-w-0" :class="collapsed ? 'lg:hidden' : ''">
                    <p class="font-display text-xl font-semibold leading-tight text-[#415f76]">PsicoControl</p>
                </div>
            </div>

            <button
                class="hidden items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-semibold text-[#42474c] transition hover:bg-[#f3f4f3] hover:text-[#415f76] lg:flex"
                :class="collapsed ? 'justify-center' : ''"
                type="button"
                @click="$emit('toggleCollapse')"
            >
                <AppIcon :name="collapsed ? 'PanelLeftOpen' : 'PanelLeftClose'" class="size-5" />
                <span :class="collapsed ? 'lg:hidden' : ''">{{ collapsed ? 'Expandir' : 'Recolher' }}</span>
            </button>

            <nav class="grid gap-1 lg:flex-1" aria-label="Navegação principal">
                <RouterLink
                    v-for="item in items"
                    :key="item.id"
                    :to="item.to"
                    class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition hover:bg-[#f3f4f3] hover:text-[#415f76]"
                    :class="[
                        collapsed ? 'lg:justify-center' : '',
                        isItemActive(item) ? 'border-r-4 border-[#415f76] bg-[#e8e8e7] text-[#415f76]' : 'text-[#42474c]',
                    ]"
                    @click="$emit('closeMobile')"
                >
                    <AppIcon :name="item.icon" class="size-5 shrink-0" />
                    <span class="truncate" :class="collapsed ? 'lg:hidden' : ''">{{ item.label }}</span>
                </RouterLink>
            </nav>

            <div class="grid gap-2 border-t border-[#e2e2e2] pt-4">
                <div class="rounded-xl bg-[#f3f4f3] px-3 py-3" :class="collapsed ? 'lg:hidden' : ''">
                    <p class="text-xs font-semibold text-[#73787d]">Conta ativa</p>
                    <p class="mt-1 truncate text-sm font-semibold text-[#1a1c1c]">{{ userName }}</p>
                </div>
                <button
                    class="flex items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-semibold text-[#42474c] transition hover:bg-[#ffdad6] hover:text-[#ba1a1a]"
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
