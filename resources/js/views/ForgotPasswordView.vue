<script setup>
import { reactive, ref, computed } from 'vue';
import { RouterLink } from 'vue-router';
import axios from 'axios';
import AppIcon from '../components/base/AppIcon.vue';

const form = reactive({ email: '' });
const loading = ref(false);
const message = ref('');
const errorMessage = ref('');
const canSubmit = computed(() => form.email.trim().length > 0 && !loading.value);

const submit = async () => {
    loading.value = true;
    message.value = '';
    errorMessage.value = '';

    try {
        const { data } = await axios.post('/api/auth/forgot-password', { email: form.email });
        message.value = data.message ?? 'Se o e-mail existir, enviaremos as instruções de recuperação.';
    } catch (error) {
        errorMessage.value = error?.response?.data?.message ?? 'Não foi possível solicitar a redefinição agora.';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="login-screen flex min-h-screen items-center justify-center px-4 py-8 text-[#1a1c1c]">
        <main class="w-full max-w-md rounded-xl border border-[#e2e2e2] bg-white p-8 shadow-[0_20px_40px_-10px_rgba(93,123,147,0.08)]">
            <RouterLink class="mb-8 inline-flex items-center gap-2 text-sm font-semibold text-[#415f76] hover:underline" :to="{ name: 'login' }">
                <AppIcon name="ArrowLeft" class="size-4" />
                Voltar ao login
            </RouterLink>

            <header class="space-y-2">
                <h1 class="font-display text-[32px] font-semibold leading-[1.3] text-[#1a1c1c]">Recuperar senha</h1>
                <p class="text-base leading-6 text-[#42474c]">Informe seu e-mail profissional para receber o link de redefinição.</p>
            </header>

            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="space-y-1">
                    <label class="text-sm font-semibold leading-5 text-[#42474c]" for="forgot-email">E-mail Profissional</label>
                    <div class="relative">
                        <AppIcon name="Mail" class="absolute left-4 top-1/2 size-5 -translate-y-1/2 text-[#73787d]" />
                        <input
                            id="forgot-email"
                            v-model="form.email"
                            class="w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-4 py-4 pl-12 text-base text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#415f76]/20"
                            type="email"
                            placeholder="exemplo@terapia.com"
                            required
                            autocomplete="email"
                        />
                    </div>
                </div>

                <p v-if="message" class="rounded-lg border border-[#b2cdbb] bg-[#cbe6d4] px-4 py-3 text-sm font-medium text-[#4c6455]">{{ message }}</p>
                <p v-if="errorMessage" class="rounded-lg border border-[#ffb4ab] bg-[#ffdad6] px-4 py-3 text-sm font-medium text-[#ba1a1a]">{{ errorMessage }}</p>

                <button
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#415f76] py-4 text-base font-semibold text-white transition hover:bg-[#5a7890] disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!canSubmit"
                    type="submit"
                >
                    <AppIcon v-if="loading" name="LoaderCircle" class="size-5 animate-spin" />
                    <template v-else>Enviar link de recuperação</template>
                </button>
            </form>
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
