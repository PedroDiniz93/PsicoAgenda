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
});

const formError = ref('');
const isSubmitting = computed(() => auth.loading);
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
    <div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md rounded-3xl border border-[#e2ddd3] bg-white/95 p-8 shadow-xl">
            <div class="mb-8 text-center">
                <p class="section-kicker">Área restrita</p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-900">Entrar</h1>
                <p class="mt-1 text-sm text-[#58635f]">Use seu e-mail e senha cadastrados.</p>
            </div>

            <p
                v-if="sessionMessage"
                class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-700"
            >
                {{ sessionMessage }}
            </p>

            <form class="space-y-5" @submit.prevent="onSubmit">
                <div>
                    <label class="block text-sm font-medium text-slate-700" for="email">E-mail</label>
                    <input
                        id="email"
                        v-model="form.email"
                        class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        type="email"
                        placeholder="seu@email.com"
                        required
                        autocomplete="email"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700" for="password">Senha</label>
                    <input
                        id="password"
                        v-model="form.password"
                        class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        type="password"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    />
                </div>

                <p v-if="formError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-600">
                    {{ formError }}
                </p>

                <button
                    class="btn-primary w-full py-2.5 shadow-lg shadow-[#3f4f46]/25 focus-visible:ring-[#d4ddd5] disabled:bg-[#aab5ae]"
                    :disabled="isSubmitting"
                    type="submit"
                >
                    <AppIcon v-if="isSubmitting" name="LoaderCircle" class="-ms-1 me-2 size-4 animate-spin" />
                    Entrar
                </button>
            </form>
        </div>
    </div>
</template>
