<script setup>
import { computed, ref } from 'vue';
import { RouterLink, useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import AppIcon from './AppIcon.vue';
import ThemeToggle from './ThemeToggle.vue';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const mobileOpen = ref(false);

const navItems = [
    { name: 'home', label: 'Dashboard', icon: 'dashboard' },
    { name: 'patients', label: 'Pacientes', icon: 'patients' },
    { name: 'schedule', label: 'Agenda', icon: 'calendar' },
    { name: 'reports', label: 'Relatórios', icon: 'chart' },
    { name: 'exports', label: 'Exportação', icon: 'download' },
];

const authUser = computed(() => auth.user ?? {});
const psychologist = computed(() => authUser.value.psychologist ?? {});
const userName = computed(() => psychologist.value.name ?? authUser.value.name ?? 'Psicólogo(a)');
const userEmail = computed(() => authUser.value.email ?? psychologist.value.email ?? '');
const initials = computed(() => {
    const parts = String(userName.value).trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return 'PS';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
});

const currentTitle = computed(() => route.meta?.title ?? 'PsicoAgenda');

const isActive = (name) => route.name === name || (name === 'patients' && route.name === 'patient-records');

const handleLogout = async () => {
    await auth.logout();
    router.push({ name: 'login' });
};

const closeMobile = () => {
    mobileOpen.value = false;
};
</script>

<template>
    <div class="flex min-h-screen bg-background">
        <!-- Mobile overlay -->
        <div
            v-if="mobileOpen"
            class="fixed inset-0 z-30 bg-foreground/40 backdrop-blur-sm lg:hidden"
            @click="closeMobile"
        />

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-border bg-surface transition-transform duration-300 lg:static lg:translate-x-0"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 items-center gap-3 border-b border-border px-6">
                <span class="flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                    <AppIcon name="sparkle" :size="20" />
                </span>
                <div class="leading-tight">
                    <p class="text-base font-semibold text-foreground">PsicoAgenda</p>
                    <p class="text-xs text-subtle-foreground">Gestão clínica</p>
                </div>
                <button
                    type="button"
                    class="ml-auto rounded-lg p-1.5 text-muted-foreground hover:bg-surface-muted lg:hidden"
                    aria-label="Fechar menu"
                    @click="closeMobile"
                >
                    <AppIcon name="close" :size="20" />
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5" aria-label="Navegação principal">
                <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-subtle-foreground">Menu</p>
                <RouterLink
                    v-for="item in navItems"
                    :key="item.name"
                    :to="{ name: item.name }"
                    class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
                    :class="
                        isActive(item.name)
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-muted-foreground hover:bg-surface-muted hover:text-foreground'
                    "
                    @click="closeMobile"
                >
                    <AppIcon :name="item.icon" :size="20" />
                    <span>{{ item.label }}</span>
                    <AppIcon
                        v-if="isActive(item.name)"
                        name="chevronRight"
                        :size="16"
                        class="ml-auto opacity-80"
                    />
                </RouterLink>
            </nav>

            <div class="border-t border-border p-3">
                <div class="flex items-center gap-3 rounded-xl bg-surface-muted px-3 py-2.5">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-soft text-sm font-bold text-primary-soft-foreground">
                        {{ initials }}
                    </span>
                    <div class="min-w-0 flex-1 leading-tight">
                        <p class="truncate text-sm font-semibold text-foreground">{{ userName }}</p>
                        <p class="truncate text-xs text-subtle-foreground">{{ userEmail }}</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-muted-foreground transition hover:bg-danger-soft hover:text-danger"
                        aria-label="Sair"
                        title="Sair"
                        @click="handleLogout"
                    >
                        <AppIcon name="logout" :size="18" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main column -->
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-border bg-surface/80 px-4 backdrop-blur-md sm:px-6">
                <button
                    type="button"
                    class="rounded-lg p-2 text-muted-foreground hover:bg-surface-muted lg:hidden"
                    aria-label="Abrir menu"
                    @click="mobileOpen = true"
                >
                    <AppIcon name="menu" :size="22" />
                </button>

                <div class="min-w-0">
                    <p class="text-xs font-medium text-subtle-foreground">PsicoAgenda</p>
                    <h1 class="truncate text-lg font-semibold text-foreground">{{ currentTitle }}</h1>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <ThemeToggle />
                </div>
            </header>

            <main class="flex-1">
                <div class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:py-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
