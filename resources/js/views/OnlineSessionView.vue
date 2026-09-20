<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import AppIcon from '../components/base/AppIcon.vue';
import { useOnlineSession } from '../composables/useOnlineSession';

const route = useRoute();
const router = useRouter();
const isPatient = computed(() => Boolean(route.params.token));
const loading = ref(true);
const ending = ref(false);
const session = ref(null);
const patient = ref(null);
const chatInput = ref('');
const copyMessage = ref('');

const online = useOnlineSession({
    role: isPatient.value ? 'patient' : 'psychologist',
    token: route.params.token,
    sessionId: route.params.id,
});

const load = async () => {
    try {
        const endpoint = isPatient.value
            ? '/api/online-sessions/join/' + route.params.token
            : '/api/online-sessions/' + route.params.id;
        const { data } = await axios.get(endpoint);
        session.value = data;

        if (!isPatient.value && data.patient_id) {
            const patientResponse = await axios.get('/api/patients/' + data.patient_id);
            patient.value = patientResponse.data;
        }

        await online.start(data);
    } catch (cause) {
        online.error.value = cause?.response?.data?.message ?? 'Não foi possível acessar esta sala.';
    } finally {
        loading.value = false;
    }
};

const submitChat = async () => {
    try {
        await online.sendChat(chatInput.value);
        chatInput.value = '';
    } catch {
        online.error.value = 'Não foi possível enviar a mensagem.';
    }
};

const retryMedia = async () => {
    if (!session.value) return;
    online.stop();
    await online.start(session.value);
};

const endSession = async () => {
    if (isPatient.value || !session.value?.id) return;
    ending.value = true;
    try {
        await axios.post('/api/online-sessions/' + session.value.id + '/end');
        online.stop();
        router.push({ name: 'schedule' });
    } finally {
        ending.value = false;
    }
};

const copyPatientLink = async () => {
    if (!route.query.patientToken) return;
    await navigator.clipboard.writeText(window.location.origin + '/online-session/join/' + route.query.patientToken);
    copyMessage.value = 'Link copiado.';
    window.setTimeout(() => { copyMessage.value = ''; }, 2000);
};

onMounted(load);
</script>

<template>
    <main class="min-h-screen bg-[#f4f1e9] px-4 py-6 text-[#26332d] sm:px-6">
        <div class="mx-auto max-w-7xl">
            <header class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#748077]">Sala de atendimento</p>
                    <h1 class="mt-1 text-2xl font-semibold">{{ isPatient ? 'Atendimento online' : (patient?.name ?? 'Atendimento online') }}</h1>
                </div>
                <button v-if="!isPatient && route.query.patientToken" class="btn-secondary" type="button" @click="copyPatientLink">
                    <AppIcon name="Copy" class="size-4" />
                    Copiar link do paciente
                </button>
            </header>

            <p v-if="copyMessage" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ copyMessage }}</p>
            <div v-if="online.error" class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <span>{{ online.error }}</span>
                <button class="rounded-lg border border-red-300 px-3 py-1.5 font-semibold hover:bg-red-100" type="button" @click="retryMedia">Tentar novamente</button>
            </div>

            <div v-if="loading" class="rounded-3xl border border-[#ded8cc] bg-white p-10 text-center">Carregando sala...</div>
            <div v-else class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <section class="rounded-3xl border border-[#ded8cc] bg-[#202923] p-3 shadow-lg">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="relative aspect-video overflow-hidden rounded-2xl bg-[#101613]">
                            <video ref="remoteVideo" class="size-full object-cover" autoplay playsinline />
                            <span class="absolute left-3 top-3 rounded-full bg-black/45 px-3 py-1 text-xs text-white">{{ isPatient ? 'Psicólogo' : 'Paciente' }}</span>
                        </div>
                        <div class="relative aspect-video overflow-hidden rounded-2xl bg-[#101613]">
                            <video ref="localVideo" class="size-full object-cover" autoplay muted playsinline />
                            <span class="absolute left-3 top-3 rounded-full bg-black/45 px-3 py-1 text-xs text-white">Você</span>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3 text-sm text-white">
                        <span>{{ online.status === 'active' ? 'Conexão ativa' : 'Aguardando conexão...' }}</span>
                        <button v-if="!isPatient" class="inline-flex items-center gap-2 rounded-xl bg-[#a8525d] px-4 py-2 font-semibold hover:bg-[#914650] disabled:opacity-60" type="button" :disabled="ending" @click="endSession">
                            <AppIcon name="PhoneOff" class="size-4" />
                            Encerrar atendimento
                        </button>
                    </div>
                </section>

                <aside class="rounded-3xl border border-[#ded8cc] bg-white p-5">
                    <div v-if="!isPatient" class="mb-5 border-b border-[#eee9df] pb-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#748077]">Prontuário</p>
                        <p class="mt-1 font-semibold">{{ patient?.name ?? 'Paciente' }}</p>
                        <p class="mt-2 text-sm text-[#65736b]">O prontuário permanece visível somente para você nesta tela.</p>
                        <button v-if="patient?.id" class="mt-3 text-sm font-semibold text-[#8f4a54] underline" type="button" @click="router.push({ name: 'patient-records', params: { id: patient.id } })">
                            Abrir prontuário completo
                        </button>
                    </div>
                    <div class="flex items-center gap-2 border-b border-[#eee9df] pb-3">
                        <AppIcon name="MessageCircle" class="size-4 text-[#8f4a54]" />
                        <h2 class="font-semibold">Chat da sessão</h2>
                    </div>
                    <div class="mt-4 flex min-h-48 flex-col gap-2 overflow-y-auto text-sm">
                        <p v-if="!online.messages.length" class="text-[#7d8881]">Nenhuma mensagem ainda.</p>
                        <p v-for="(item, index) in online.messages" :key="index" class="rounded-xl bg-[#f5f1e9] px-3 py-2"><strong>{{ item.author }}:</strong> {{ item.message }}</p>
                    </div>
                    <form class="mt-4 flex gap-2" @submit.prevent="submitChat">
                        <input v-model="chatInput" class="field-input min-w-0 flex-1" placeholder="Escreva uma mensagem" aria-label="Mensagem" />
                        <button class="btn-primary px-3" type="submit" aria-label="Enviar mensagem">
                            <AppIcon name="ArrowRight" class="size-4" />
                        </button>
                    </form>
                </aside>
            </div>
        </div>
    </main>
</template>
