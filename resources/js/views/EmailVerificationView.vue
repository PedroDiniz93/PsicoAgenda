<script setup>
import { computed, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const auth = useAuthStore();

const form = reactive({
    code: '',
});

const message = ref('');
const messageType = ref('success');
const isSubmitting = computed(() => auth.loading);
const email = computed(() => auth.user?.email ?? '');

const submitCode = async () => {
    message.value = '';

    try {
        await auth.verifyEmail(form.code);
        router.push({ name: 'home' });
    } catch (error) {
        messageType.value = 'error';
        message.value = error?.message ?? 'Não foi possível validar o código.';
    }
};

const resendCode = async () => {
    message.value = '';

    try {
        messageType.value = 'success';
        message.value = await auth.resendEmailVerification();
    } catch (error) {
        messageType.value = 'error';
        message.value = error?.message ?? 'Não foi possível reenviar o código.';
    }
};

const logout = async () => {
    await auth.logout();
    router.push({ name: 'login' });
};
</script>

<template>
    <div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-xl">
            <div class="mb-8">
                <p class="text-sm font-medium text-cyan-700">Validação de e-mail</p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-950">Digite o código recebido</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Enviamos um código de 6 dígitos para {{ email }}. Ele expira em 24 horas.
                </p>
            </div>

            <form class="space-y-5" @submit.prevent="submitCode">
                <label class="block text-sm font-semibold text-slate-700" for="code">
                    Código de confirmação
                    <input
                        id="code"
                        v-model="form.code"
                        class="mt-2 h-12 w-full rounded-lg border border-slate-200 px-4 text-center text-lg font-semibold tracking-[0.25em] text-slate-950 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                        inputmode="numeric"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        placeholder="000000"
                        required
                        autocomplete="one-time-code"
                    />
                </label>

                <p
                    v-if="message"
                    class="rounded-lg border px-4 py-3 text-sm"
                    :class="messageType === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700'"
                >
                    {{ message }}
                </p>

                <button
                    class="flex h-11 w-full items-center justify-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isSubmitting"
                    type="submit"
                >
                    {{ isSubmitting ? 'Validando...' : 'Validar e-mail' }}
                </button>
            </form>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 text-sm">
                <button
                    class="font-semibold text-cyan-700 transition hover:text-cyan-900 disabled:cursor-not-allowed disabled:opacity-60"
                    type="button"
                    :disabled="isSubmitting"
                    @click="resendCode"
                >
                    Reenviar código
                </button>
                <button class="font-semibold text-slate-500 transition hover:text-slate-800" type="button" @click="logout">
                    Sair
                </button>
            </div>
        </div>
    </div>
</template>
