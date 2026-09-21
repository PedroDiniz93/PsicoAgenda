<script setup>
import { reactive, ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import AppIcon from '../components/base/AppIcon.vue';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const form = reactive({
    email: '',
    password: '',
    remember: false,
});

const formError = ref('');
const showPassword = ref(false);
const isSubmitting = computed(() => auth.loading);
const passwordInputType = computed(() => (showPassword.value ? 'text' : 'password'));
const sessionMessage = computed(() => {
    const reason = route.query.session ?? route.query.reason;
    if (reason === 'expired') {
        return 'Sua sessão expirou. Entre novamente para continuar.';
    }
    return '';
});

const onSubmit = async () => {
    formError.value = '';
    try {
        await auth.login({
            email: form.email,
            password: form.password,
            remember: form.remember,
        });

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
    <div class="auth-screen">
        <main class="auth-card grid max-w-6xl md:min-h-[640px] md:grid-cols-[0.9fr_1.1fr]">
            <section class="relative hidden overflow-hidden bg-[var(--spa-accent)] p-12 md:flex md:items-end lg:p-16">
                <img
                    class="absolute inset-0 h-full w-full object-cover opacity-35 mix-blend-luminosity"
                    :src="'/images/stitch/login-therapy-room.jpg'"
                    alt="Sala de terapia minimalista com poltrona verde, planta e luz natural suave"
                />
                <div class="relative z-10 max-w-sm space-y-5 text-left text-white">
                    <h1 class="font-display text-4xl font-semibold leading-tight">PsicoAgenda</h1>
                    <p class="text-base leading-7 text-white/85">
                        Agenda, prontuários e gestão do consultório em um só lugar.
                    </p>
                    <div class="pt-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/12 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20">
                            <AppIcon name="ShieldCheck" class="size-[18px]" />
                            Acesso restrito ao profissional
                        </span>
                    </div>
                </div>
            </section>

            <section class="flex flex-col justify-center bg-[var(--spa-surface)] px-6 py-10 sm:px-10 md:p-12 lg:p-16">
                <div class="mx-auto w-full max-w-md space-y-9">
                    <header class="space-y-2">
                        <div class="mb-6 flex items-center gap-2 md:hidden">
                            <AppIcon name="HeartPulse" class="size-8 text-[#415f76]" />
                            <span class="font-display text-2xl font-bold text-[#415f76]">PsicoAgenda</span>
                        </div>
                        <h2 class="font-display text-[26px] font-semibold leading-[1.3] text-[#1a1c1c] md:text-[32px]">
                            Bem-vindo de volta
                        </h2>
                        <p class="text-base leading-6 text-[#42474c]">
                            Acesse sua conta para gerenciar seus pacientes e sessões.
                        </p>
                    </header>

                    <p class="text-xs leading-5 text-[var(--spa-ink-muted)]">
                        Ao continuar, você concorda com os
                        <a class="font-semibold text-[var(--spa-accent)] underline underline-offset-2" href="/termos-de-servico">Termos de Serviço</a>
                        e a
                        <a class="font-semibold text-[var(--spa-accent)] underline underline-offset-2" href="/politica-de-privacidade">Política de Privacidade</a>.
                    </p>

                    <p
                        v-if="sessionMessage"
                        class="rounded-lg border border-[#ccc5be] bg-[#e8e1d9] px-4 py-3 text-sm font-medium text-[#605b55]"
                    >
                        {{ sessionMessage }}
                    </p>

                    <form class="space-y-6" @submit.prevent="onSubmit">
                        <div class="space-y-1">
                            <label class="text-sm font-semibold leading-5 text-[#42474c]" for="email">E-mail Profissional</label>
                            <div class="relative">
                                <AppIcon name="Mail" class="absolute left-4 top-1/2 size-5 -translate-y-1/2 text-[#73787d]" />
                                <input
                                    id="email"
                                    v-model="form.email"
                                    class="field-input h-12 pl-12"
                                    type="email"
                                    placeholder="exemplo@terapia.com"
                                    required
                                    autocomplete="email"
                                />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center justify-between gap-4">
                                <label class="text-sm font-semibold leading-5 text-[#42474c]" for="password">Senha</label>
                                <RouterLink
                                    class="text-sm font-semibold leading-5 text-[#415f76] transition hover:underline"
                                    :to="{ name: 'forgot-password' }"
                                >
                                    Esqueci minha senha
                                </RouterLink>
                            </div>
                            <div class="relative">
                                <AppIcon name="Lock" class="absolute left-4 top-1/2 size-5 -translate-y-1/2 text-[#73787d]" />
                                <input
                                    id="password"
                                    v-model="form.password"
                                    class="field-input h-12 pl-12 pr-12"
                                    :type="passwordInputType"
                                    placeholder="••••••••"
                                    required
                                    autocomplete="current-password"
                                />
                                <button
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-[#73787d] transition hover:text-[#415f76]"
                                    type="button"
                                    :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                                    @click="showPassword = !showPassword"
                                >
                                    <AppIcon :name="showPassword ? 'EyeOff' : 'Eye'" class="size-5" />
                                </button>
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2 text-base text-[#42474c] transition hover:text-[#1a1c1c]">
                            <input
                                v-model="form.remember"
                                class="h-5 w-5 rounded border-[#c2c7cd] text-[#415f76] focus:ring-[#415f76]/20"
                                type="checkbox"
                            />
                            Lembrar de mim por 30 dias
                        </label>

                        <p v-if="formError" class="rounded-lg border border-[#ffb4ab] bg-[#ffdad6] px-4 py-3 text-sm font-medium text-[#ba1a1a]">
                            {{ formError }}
                        </p>

                        <button
                            class="btn-primary h-12 w-full text-base"
                            :disabled="isSubmitting"
                            type="submit"
                        >
                            <AppIcon v-if="isSubmitting" name="LoaderCircle" class="size-5 animate-spin" />
                            <template v-else>
                                Entrar
                                <AppIcon name="ArrowRight" class="size-5" />
                            </template>
                        </button>
                    </form>

                </div>
            </section>
        </main>
    </div>
</template>
