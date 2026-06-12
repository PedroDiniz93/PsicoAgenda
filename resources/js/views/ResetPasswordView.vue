<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import AppIcon from '../components/base/AppIcon.vue';

const route = useRoute();
const router = useRouter();

const form = reactive({
    email: '',
    token: '',
    password: '',
    password_confirmation: '',
});

const loading = ref(false);
const message = ref('');
const errorMessage = ref('');
const showPassword = ref(false);
const showConfirmation = ref(false);

const canSubmit = computed(() =>
    form.email.trim().length > 0 &&
    form.token.trim().length > 0 &&
    form.password.length >= 8 &&
    form.password_confirmation.length >= 8 &&
    !loading.value
);

onMounted(() => {
    form.email = typeof route.query.email === 'string' ? route.query.email : '';
    form.token = typeof route.query.token === 'string' ? route.query.token : '';
});

const submit = async () => {
    loading.value = true;
    message.value = '';
    errorMessage.value = '';

    try {
        const { data } = await axios.post('/api/auth/reset-password', form);
        message.value = data.message ?? 'Senha redefinida com sucesso.';
        setTimeout(() => router.push({ name: 'login' }), 1200);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message ?? 'Não foi possível redefinir a senha.';
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
                <h1 class="font-display text-[32px] font-semibold leading-[1.3] text-[#1a1c1c]">Criar nova senha</h1>
                <p class="text-base leading-6 text-[#42474c]">Escolha uma senha com pelo menos 8 caracteres.</p>
            </header>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <input v-model="form.token" type="hidden" />

                <div class="space-y-1">
                    <label class="text-sm font-semibold leading-5 text-[#42474c]" for="reset-email">E-mail Profissional</label>
                    <input
                        id="reset-email"
                        v-model="form.email"
                        class="w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-4 py-4 text-base text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#415f76]/20"
                        type="email"
                        required
                        autocomplete="email"
                    />
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-semibold leading-5 text-[#42474c]" for="reset-password">Nova senha</label>
                    <div class="relative">
                        <input
                            id="reset-password"
                            v-model="form.password"
                            class="w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-4 py-4 pr-12 text-base text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#415f76]/20"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        />
                        <button class="absolute right-4 top-1/2 -translate-y-1/2 text-[#73787d] hover:text-[#415f76]" type="button" @click="showPassword = !showPassword">
                            <AppIcon :name="showPassword ? 'EyeOff' : 'Eye'" class="size-5" />
                        </button>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-semibold leading-5 text-[#42474c]" for="reset-password-confirmation">Confirmar senha</label>
                    <div class="relative">
                        <input
                            id="reset-password-confirmation"
                            v-model="form.password_confirmation"
                            class="w-full rounded-lg border border-[#c2c7cd] bg-[#f3f4f3] px-4 py-4 pr-12 text-base text-[#1a1c1c] outline-none transition focus:border-[#415f76] focus:ring-2 focus:ring-[#415f76]/20"
                            :type="showConfirmation ? 'text' : 'password'"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        />
                        <button class="absolute right-4 top-1/2 -translate-y-1/2 text-[#73787d] hover:text-[#415f76]" type="button" @click="showConfirmation = !showConfirmation">
                            <AppIcon :name="showConfirmation ? 'EyeOff' : 'Eye'" class="size-5" />
                        </button>
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
                    <template v-else>Redefinir senha</template>
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
