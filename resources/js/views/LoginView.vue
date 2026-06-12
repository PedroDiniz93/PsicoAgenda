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
    <div class="login-screen flex min-h-screen items-center justify-center px-4 py-6 text-[#1a1c1c] sm:px-6 lg:px-8">
        <main class="grid w-full max-w-[1280px] overflow-hidden rounded-xl border border-[#e2e2e2] bg-white shadow-[0_20px_40px_-10px_rgba(93,123,147,0.08)] md:min-h-[600px] md:grid-cols-2">
            <section class="relative hidden overflow-hidden bg-[#cbe6d4]/30 p-12 md:flex md:items-center md:justify-center lg:p-20">
                <img
                    class="absolute inset-0 h-full w-full object-cover opacity-40 mix-blend-multiply grayscale-[20%]"
                    :src="'/images/stitch/login-therapy-room.jpg'"
                    alt="Sala de terapia minimalista com poltrona verde, planta e luz natural suave"
                />
                <div class="relative z-10 mx-auto max-w-sm space-y-6 text-center">
                    <h1 class="font-display text-[32px] font-semibold leading-[1.3] text-[#415f76]">PsicoControl</h1>
                    <p class="text-[18px] leading-[1.6] text-[#42474c]">
                        Transformando a gestão terapêutica com clareza empática e tecnologia humana.
                    </p>
                    <div class="pt-6">
                        <span class="inline-flex items-center gap-2 rounded-full bg-[#cbe6d4]/60 px-6 py-2 text-sm font-semibold text-[#4c6455]">
                            <AppIcon name="ShieldCheck" class="size-[18px]" />
                            Ambiente Seguro e Criptografado
                        </span>
                    </div>
                </div>
            </section>

            <section class="flex flex-col justify-center bg-[#f9f9f8] px-6 py-10 sm:px-10 md:p-12 lg:p-20">
                <div class="mx-auto w-full max-w-md space-y-12">
                    <header class="space-y-2">
                        <div class="mb-6 flex items-center gap-2 md:hidden">
                            <AppIcon name="Sprout" class="size-8 text-[#415f76]" />
                            <span class="font-display text-2xl font-bold text-[#415f76]">PsicoControl</span>
                        </div>
                        <h2 class="font-display text-[26px] font-semibold leading-[1.3] text-[#1a1c1c] md:text-[32px]">
                            Bem-vindo de volta
                        </h2>
                        <p class="text-base leading-6 text-[#42474c]">
                            Acesse sua conta para gerenciar seus pacientes e sessões.
                        </p>
                    </header>

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
                                <AppIcon name="Mail" class="absolute left-6 top-1/2 size-5 -translate-y-1/2 text-[#73787d]" />
                                <input
                                    id="email"
                                    v-model="form.email"
                                    class="w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] py-6 pl-20 pr-6 text-base text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#415f76]/20"
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
                                <AppIcon name="Lock" class="absolute left-6 top-1/2 size-5 -translate-y-1/2 text-[#73787d]" />
                                <input
                                    id="password"
                                    v-model="form.password"
                                    class="w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] py-6 pl-20 pr-[52px] text-base text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#415f76]/20"
                                    :type="passwordInputType"
                                    placeholder="••••••••"
                                    required
                                    autocomplete="current-password"
                                />
                                <button
                                    class="absolute right-6 top-1/2 -translate-y-1/2 text-[#73787d] transition hover:text-[#415f76]"
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
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#415f76] py-6 text-base font-semibold text-white transition hover:bg-[#5a7890] hover:shadow-lg active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isSubmitting"
                            type="submit"
                        >
                            <AppIcon v-if="isSubmitting" name="LoaderCircle" class="size-5 animate-spin" />
                            <template v-else>
                                Entrar na Plataforma
                                <AppIcon name="ArrowRight" class="size-5" />
                            </template>
                        </button>
                    </form>

                    <footer class="border-t border-[#c2c7cd]/30 pt-6 text-center">
                        <p class="text-base leading-6 text-[#42474c]">
                            Ainda não faz parte da rede?
                            <button class="ml-1 font-semibold text-[#4c6455] hover:underline" type="button">Solicitar acesso</button>
                        </p>
                    </footer>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
.login-screen {
    background-color: #f9f9f8;
    background-image:
        radial-gradient(at 0% 0%, rgba(202, 230, 255, 0.15) 0, transparent 50%),
        radial-gradient(at 100% 100%, rgba(206, 233, 214, 0.15) 0, transparent 50%);
}
</style>
