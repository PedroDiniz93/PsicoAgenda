<script setup>
import { reactive, ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import AppIcon from '../components/AppIcon.vue';
import ThemeToggle from '../components/ThemeToggle.vue';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const form = reactive({
    email: '',
    password: '',
});

const showPassword = ref(false);
const formError = ref('');
const isSubmitting = computed(() => auth.loading);
const sessionMessage = computed(() => {
    const reason = route.query.session ?? route.query.reason;
    if (reason === 'expired') {
        return 'Sua sessão expirou. Entre novamente para continuar.';
    }
    return '';
});

const highlights = [
    { icon: 'patients', title: 'Pacientes organizados', text: 'Cadastros, status e prontuários em um só lugar.' },
    { icon: 'calendar', title: 'Agenda inteligente', text: 'Sessões, faltas e lembretes automáticos.' },
    { icon: 'chart', title: 'Relatórios claros', text: 'Acompanhe atendimentos e pagamentos com facilidade.' },
];

const onSubmit = async () => {
    formError.value = '';
    try {
        await auth.login({ email: form.email, password: form.password });
        const redirect = route.query.redirect;
        if (redirect && typeof redirect === 'string') {
            router.push(redirect);
        } else {
            router.push({ name: 'home' });
        }
    } catch (error) {
        formError.value = error?.message ?? 'Falha ao autenticar';
    }
};
</script>

<template>
    <div class="grid min-h-screen lg:grid-cols-2">
        <!-- Branding panel -->
        <div class="relative hidden flex-col justify-between overflow-hidden bg-primary p-12 text-primary-foreground lg:flex">
            <div class="flex items-center gap-3">
                <span class="flex size-11 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">
                    <AppIcon name="sparkle" :size="24" />
                </span>
                <div class="leading-tight">
                    <p class="text-lg font-semibold">PsicoAgenda</p>
                    <p class="text-sm text-white/70">Gestão clínica para psicólogos</p>
                </div>
            </div>

            <div class="max-w-md">
                <h2 class="text-balance text-3xl font-semibold leading-tight">
                    Cuide dos seus pacientes. Nós cuidamos da gestão.
                </h2>
                <p class="mt-3 text-pretty text-white/75">
                    Centralize agenda, prontuários e relatórios em uma plataforma feita para o dia a dia clínico.
                </p>

                <ul class="mt-8 space-y-4">
                    <li v-for="item in highlights" :key="item.title" class="flex items-start gap-3">
                        <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/15">
                            <AppIcon :name="item.icon" :size="18" />
                        </span>
                        <div>
                            <p class="font-semibold">{{ item.title }}</p>
                            <p class="text-sm text-white/70">{{ item.text }}</p>
                        </div>
                    </li>
                </ul>
            </div>

            <p class="text-sm text-white/60">© {{ new Date().getFullYear() }} PsicoAgenda. Todos os direitos reservados.</p>
        </div>

        <!-- Form panel -->
        <div class="relative flex items-center justify-center px-4 py-12 sm:px-8">
            <div class="absolute right-4 top-4">
                <ThemeToggle />
            </div>

            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                        <AppIcon name="sparkle" :size="20" />
                    </span>
                    <p class="text-lg font-semibold text-foreground">PsicoAgenda</p>
                </div>

                <div class="mb-8">
                    <p class="text-sm font-semibold text-primary">Área restrita</p>
                    <h1 class="mt-1 text-2xl font-semibold text-foreground">Bem-vindo de volta</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Use seu e-mail e senha cadastrados para entrar.</p>
                </div>

                <p
                    v-if="sessionMessage"
                    class="mb-5 rounded-xl border border-warning/30 bg-warning-soft px-4 py-2.5 text-sm text-warning"
                >
                    {{ sessionMessage }}
                </p>

                <form class="space-y-5" @submit.prevent="onSubmit">
                    <div>
                        <label class="field-label" for="email">E-mail</label>
                        <input
                            id="email"
                            v-model="form.email"
                            class="field-input"
                            type="email"
                            placeholder="seu@email.com"
                            required
                            autocomplete="email"
                        />
                    </div>

                    <div>
                        <label class="field-label" for="password">Senha</label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                class="field-input pr-12"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-xs font-semibold text-muted-foreground hover:text-primary"
                                @click="showPassword = !showPassword"
                            >
                                {{ showPassword ? 'Ocultar' : 'Mostrar' }}
                            </button>
                        </div>
                    </div>

                    <p v-if="formError" class="rounded-xl border border-danger/30 bg-danger-soft px-4 py-2.5 text-sm text-danger">
                        {{ formError }}
                    </p>

                    <button class="btn btn-primary w-full" :disabled="isSubmitting" type="submit">
                        <svg
                            v-if="isSubmitting"
                            class="-ms-1 size-4 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                            role="presentation"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        {{ isSubmitting ? 'Entrando...' : 'Entrar' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
