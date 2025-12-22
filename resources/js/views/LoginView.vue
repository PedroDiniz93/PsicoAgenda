<script setup>
import { reactive, ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';

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
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
            <div class="mb-8 text-center">
                <p class="text-sm font-medium text-blue-600">Área restrita</p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-900">Entrar</h1>
                <p class="mt-1 text-sm text-slate-500">Use seu e-mail e senha cadastrados.</p>
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
                    class="flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-lg shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300 disabled:cursor-not-allowed disabled:bg-blue-400"
                    :disabled="isSubmitting"
                    type="submit"
                >
                    <svg
                        v-if="isSubmitting"
                        class="-ms-1 me-2 size-4 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                        role="presentation"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    Entrar
                </button>
            </form>
        </div>
    </div>
</template>
