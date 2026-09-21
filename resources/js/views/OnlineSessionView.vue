<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import AppIcon from '../components/base/AppIcon.vue';
import { useOnlineSession } from '../composables/useOnlineSession';
import { formatDateTime } from '../utils/formatters';

const route = useRoute();
const router = useRouter();
const isPatient = computed(() => Boolean(route.params.token));
const loading = ref(true);
const ending = ref(false);
const session = ref(null);
const patient = ref(null);
const chatInput = ref('');
const copyMessage = ref('');
const retrying = ref(false);
const swappedVideos = ref(false);
const isFullscreen = ref(false);
const callPanel = ref(null);
const recordOpen = ref(false);
const recordLoading = ref(false);
const recordError = ref('');
const patientRecords = ref([]);
const recordLoaded = ref(false);
const fallbackErrorMessage = 'Não foi possível iniciar a sala. Verifique sua conexão e tente novamente.';

const online = useOnlineSession({
    role: isPatient.value ? 'patient' : 'psychologist',
    token: route.params.token,
    sessionId: route.params.id,
});
const localVideo = online.localVideo;
const remoteVideo = online.remoteVideo;
const status = online.status;
const error = online.error;
const mediaWarning = online.mediaWarning;
const controlError = online.controlError;
const microphoneEnabled = online.microphoneEnabled;
const cameraEnabled = online.cameraEnabled;
const remoteMicrophoneEnabled = online.remoteMicrophoneEnabled;
const remoteCameraEnabled = online.remoteCameraEnabled;
const audioInputs = online.audioInputs;
const videoInputs = online.videoInputs;
const messages = online.messages;
const connectionNotice = online.connectionNotice;
const participantNotice = online.participantNotice;
const chatUnread = online.chatUnread;
const sessionSeconds = online.sessionSeconds;
const entryRequest = online.entryRequest;
const waitingForApproval = online.waitingForApproval;
const entryApproved = online.entryApproved;
const entryBlocked = online.entryBlocked;

const errorMessage = computed(() => String(error.value ?? '').trim() || fallbackErrorMessage);
const statusLabel = computed(() => ({
    connecting: 'Conectando...',
    waiting: isPatient.value ? 'Aguardando psicólogo...' : 'Aguardando paciente...',
    active: 'Conexão ativa',
    reconnecting: 'Reconectando...',
    error: 'Falha na conexão',
}[status.value] ?? 'Conectando...'));
const formattedSessionTime = computed(() => {
    const minutes = Math.floor(sessionSeconds.value / 60).toString().padStart(2, '0');
    const seconds = (sessionSeconds.value % 60).toString().padStart(2, '0');
    return `${minutes}:${seconds}`;
});
const approvalWaiting = computed(() => isPatient.value && waitingForApproval.value && !entryApproved.value && status.value !== 'error');

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

        loading.value = false;
        await nextTick();
        if (isPatient.value) {
            await online.startWaitingRoom(data);
        } else {
            await online.start(data);
        }
    } catch (cause) {
        console.error('[online-session] load failed', {
            name: cause?.name,
            code: cause?.code,
            status: cause?.response?.status,
        });
        online.error.value = cause?.response?.data?.message || 'Não foi possível acessar esta sala. Tente novamente.';
        online.status.value = 'error';
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
    retrying.value = true;
    online.error.value = '';
    console.info('[online-session] retry');

    try {
        if (session.value) {
            await nextTick();
            if (isPatient.value && waitingForApproval.value) {
                await online.startWaitingRoom(session.value);
            } else {
                await online.start(session.value);
            }
        } else {
            await load();
        }
    } finally {
        retrying.value = false;
    }
};

const changeAudioDevice = (event) => online.replaceAudioTrack(event.target.value);
const changeVideoDevice = (event) => online.replaceVideoTrack(event.target.value);
const clearChatUnread = () => online.clearChatUnread();

const toggleFullscreen = async () => {
    if (!document.fullscreenElement) {
        await callPanel.value?.requestFullscreen?.();
    } else {
        await document.exitFullscreen?.();
    }
};

const handleFullscreenChange = () => {
    isFullscreen.value = document.fullscreenElement === callPanel.value;
};

const togglePatientRecord = async () => {
    recordOpen.value = !recordOpen.value;
    if (!recordOpen.value || recordLoaded.value || !patient.value?.id) return;

    recordLoading.value = true;
    recordError.value = '';
    try {
        const { data } = await axios.get(`/api/patients/${patient.value.id}/records`, {
            params: { per_page: 20 },
        });
        patientRecords.value = Array.isArray(data?.data) ? data.data : [];
        recordLoaded.value = true;
    } catch (cause) {
        recordError.value = cause?.response?.data?.message ?? 'Não foi possível carregar o prontuário.';
    } finally {
        recordLoading.value = false;
    }
};

const endSession = async () => {
    const confirmation = isPatient.value
        ? 'Deseja sair da sala de atendimento?'
        : 'Deseja encerrar este atendimento? O paciente será desconectado.';
    if (!window.confirm(confirmation)) return;

    if (isPatient.value) {
        online.stop();
        router.back();
        return;
    }

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
onMounted(() => document.addEventListener('fullscreenchange', handleFullscreenChange));
onBeforeUnmount(() => document.removeEventListener('fullscreenchange', handleFullscreenChange));
</script>

<template>
    <main class="min-h-screen bg-[radial-gradient(circle_at_top,#fffdf8_0,#f7f3eb_42%,#f0eee6_100%)] px-4 py-5 text-[#26332d] sm:px-6 lg:py-8">
        <div class="mx-auto max-w-[1380px]">
            <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#8d6657]">
                        <span class="size-2 rounded-full bg-[#5f9b76] shadow-[0_0_0_4px_#dcebe2]" />
                        Sala de atendimento
                    </div>
                    <h1 class="mt-2 text-3xl font-semibold tracking-[-0.04em] text-[#17322c] sm:text-4xl">{{ isPatient ? 'Atendimento online' : (patient?.name ?? 'Atendimento online') }}</h1>
                    <p class="mt-1 text-sm text-[#718078]">Sessão protegida e privada</p>
                </div>
                <button v-if="!isPatient && route.query.patientToken" class="inline-flex items-center gap-2 rounded-xl border border-[#d9dcd3] bg-white/80 px-4 py-2.5 text-sm font-semibold text-[#315648] shadow-sm transition hover:-translate-y-0.5 hover:border-[#b6cbbd] hover:bg-white" type="button" @click="copyPatientLink">
                    <AppIcon name="Copy" class="size-4" />
                    Copiar link do paciente
                </button>
            </header>

            <p v-if="copyMessage" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ copyMessage }}</p>
            <p v-if="mediaWarning" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">{{ mediaWarning }}</p>
            <p v-if="controlError" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">{{ controlError }}</p>
            <p v-if="connectionNotice" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">{{ connectionNotice }}</p>
            <div v-if="status === 'error' && !entryBlocked" class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <span>{{ errorMessage }}</span>
                <button class="rounded-lg border border-red-300 px-3 py-1.5 font-semibold hover:bg-red-100 disabled:cursor-wait disabled:opacity-60" type="button" :disabled="retrying" @click="retryMedia">
                    {{ retrying ? 'Tentando conectar...' : 'Tentar novamente' }}
                </button>
            </div>
            <div v-if="!isPatient && entryRequest" class="mb-4 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#d9e8dc] bg-[#f0f8f1] px-4 py-4 text-[#315648] shadow-sm" role="status">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-[#dcebe2] text-[#315648]"><AppIcon name="UserCheck" class="size-5" /></div>
                    <div>
                        <p class="font-semibold">Paciente aguardando entrada</p>
                        <p class="mt-0.5 text-sm text-[#657d6e]">A chamada só será liberada depois da sua autorização.</p>
                    </div>
                </div>
                <button class="inline-flex items-center gap-2 rounded-xl bg-[#315f4d] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#234b3b]" type="button" @click="online.approvePatient">
                    <AppIcon name="Check" class="size-4" />
                    Aceitar paciente
                </button>
            </div>

            <div v-if="loading" class="rounded-3xl border border-[#ded8cc] bg-white p-10 text-center">Carregando sala...</div>
            <section v-else-if="isPatient && waitingForApproval" class="mx-auto max-w-xl rounded-[1.75rem] border border-[#dfe3da] bg-white p-8 text-center shadow-[0_12px_40px_rgba(48,67,56,0.07)] sm:p-12">
                <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-[#edf4ef] text-[#315f4d]">
                    <AppIcon name="Clock3" class="size-8" />
                </div>
                <p class="mt-5 text-xs font-bold uppercase tracking-[0.16em] text-[#8d6657]">Sala privada</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em] text-[#17322c]">Aguardando autorização</h2>
                <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-[#65736b]">O psicólogo foi avisado da sua chegada. Câmera e microfone serão solicitados somente depois que ele autorizar sua entrada.</p>
                <div class="mx-auto mt-6 inline-flex items-center gap-2 rounded-full bg-[#f2f8f3] px-3 py-2 text-xs font-semibold text-[#527363]">
                    <span class="size-2 animate-pulse rounded-full bg-[#6b9b7b]" />
                    Aguardando o psicólogo...
                </div>
            </section>
            <section v-else-if="isPatient && entryBlocked" class="mx-auto max-w-xl rounded-[1.75rem] border border-[#ead7d2] bg-white p-8 text-center shadow-[0_12px_40px_rgba(48,67,56,0.07)] sm:p-12">
                <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-[#fbeceb] text-[#a8525d]"><AppIcon name="Ban" class="size-8" /></div>
                <p class="mt-5 text-xs font-bold uppercase tracking-[0.16em] text-[#a8525d]">Sala em uso</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em] text-[#17322c]">Este link já está conectado</h2>
                <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-[#65736b]">Por segurança, apenas um paciente pode usar este link por vez. Feche a outra aba ou aguarde a liberação da sala.</p>
            </section>
            <div v-else class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <section ref="callPanel" class="overflow-hidden rounded-[1.75rem] border border-[#d7ddd5] bg-[#17231d] p-3 shadow-[0_20px_60px_rgba(32,57,45,0.14)] sm:p-4" :class="isFullscreen ? 'flex min-h-screen flex-col rounded-none border-0 shadow-none' : ''">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3 px-1 text-white">
                        <div class="flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-2xl bg-[#dcebe2] text-sm font-bold text-[#315648]">{{ (isPatient ? 'P' : (patient?.name ?? 'P')).slice(0, 1).toUpperCase() }}</div>
                            <div>
                                <p class="text-sm font-semibold">{{ isPatient ? 'Atendimento com seu psicólogo' : 'Atendimento com paciente' }}</p>
                                <p class="mt-0.5 text-xs text-[#aebdb3]">{{ status === 'active' ? 'Conversa em andamento' : 'Sala privada' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.06] px-3 py-1.5 text-xs text-[#c8d1ca]">
                                <span class="size-1.5 rounded-full" :class="status === 'active' ? 'bg-[#77c697]' : 'bg-[#d5b26b]'" />
                                {{ statusLabel }}
                            </span>
                            <button v-if="isFullscreen" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/25" type="button" @click="toggleFullscreen">
                                <AppIcon name="Minimize" class="size-4" />
                                <span class="hidden sm:inline">Voltar ao tamanho normal</span>
                                <span class="sm:hidden">Sair</span>
                            </button>
                        </div>
                    </div>
                    <div class="relative min-h-[20rem] overflow-hidden rounded-[1.35rem] bg-[#101613] shadow-inner sm:min-h-[30rem]" :class="isFullscreen ? 'flex-1 rounded-2xl' : 'aspect-video'">
                        <div class="absolute overflow-hidden rounded-2xl bg-[#101613] transition-all duration-300" :class="swappedVideos ? 'bottom-3 right-3 z-10 aspect-video w-32 rounded-xl border-2 border-white/70 shadow-xl sm:bottom-4 sm:right-4 sm:w-48' : 'inset-0 z-0'">
                            <video ref="remoteVideo" class="size-full object-cover" autoplay playsinline />
                            <span class="absolute left-3 top-3 rounded-full bg-black/45 px-3 py-1 text-xs text-white">{{ isPatient ? 'Psicólogo' : 'Paciente' }}</span>
                            <span v-if="!remoteMicrophoneEnabled || !remoteCameraEnabled" class="absolute right-3 top-3 flex items-center gap-1 rounded-full bg-black/60 px-2 py-1 text-white" :aria-label="`${!remoteMicrophoneEnabled ? 'Microfone desligado' : ''}${!remoteMicrophoneEnabled && !remoteCameraEnabled ? ' e ' : ''}${!remoteCameraEnabled ? 'Câmera desligada' : ''}`">
                                <AppIcon v-if="!remoteMicrophoneEnabled" name="MicOff" class="size-3.5" />
                                <AppIcon v-if="!remoteCameraEnabled" name="VideoOff" class="size-3.5" />
                            </span>
                        </div>
                        <div class="absolute overflow-hidden rounded-2xl bg-[#101613] transition-all duration-300" :class="swappedVideos ? 'inset-0 z-0' : 'bottom-3 right-3 z-10 aspect-video w-32 rounded-xl border-2 border-white/70 shadow-xl sm:bottom-4 sm:right-4 sm:w-48'">
                            <video ref="localVideo" class="size-full object-cover" autoplay muted playsinline />
                            <span class="absolute left-3 top-3 rounded-full bg-black/45 px-3 py-1 text-xs text-white">Você</span>
                            <span v-if="!microphoneEnabled || !cameraEnabled" class="absolute right-3 top-3 flex items-center gap-1 rounded-full bg-black/60 px-2 py-1 text-white" :aria-label="`${!microphoneEnabled ? 'Microfone desligado' : ''}${!microphoneEnabled && !cameraEnabled ? ' e ' : ''}${!cameraEnabled ? 'Câmera desligada' : ''}`">
                                <AppIcon v-if="!microphoneEnabled" name="MicOff" class="size-3.5" />
                                <AppIcon v-if="!cameraEnabled" name="VideoOff" class="size-3.5" />
                            </span>
                        </div>
                    </div>
                    <p v-if="participantNotice" class="mt-3 rounded-xl border border-emerald-300/20 bg-emerald-300/10 px-3 py-2 text-sm text-emerald-100" role="status">{{ participantNotice }}</p>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-white">
                        <span v-if="status === 'active'" class="rounded-full bg-white/10 px-2 py-1 font-mono text-xs text-[#c8d1ca]">{{ formattedSessionTime }}</span>
                        <div class="flex items-center gap-2">
                            <button class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/20" type="button" @click="swappedVideos = !swappedVideos">
                                <AppIcon name="RefreshCw" class="size-4" />
                                <span class="hidden sm:inline">Trocar destaque</span>
                            </button>
                            <button class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/20" type="button" @click="toggleFullscreen">
                                <AppIcon :name="isFullscreen ? 'Minimize' : 'Maximize'" class="size-4" />
                                <span class="hidden sm:inline">{{ isFullscreen ? 'Sair da tela cheia' : 'Tela cheia' }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2 rounded-[1.25rem] border border-white/10 bg-[#111a15] p-2.5">
                        <button
                            class="inline-flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                            :class="microphoneEnabled ? 'bg-white/10 text-white hover:bg-white/20' : 'bg-[#a8525d] text-white'"
                            type="button"
                            :aria-pressed="microphoneEnabled"
                            :aria-label="microphoneEnabled ? 'Desmutar microfone' : 'Mutar microfone'"
                            @click="online.toggleMicrophone"
                        >
                            <AppIcon :name="microphoneEnabled ? 'Mic' : 'MicOff'" class="size-4" />
                            <span class="hidden sm:inline">{{ microphoneEnabled ? 'Microfone' : 'Microfone desligado' }}</span>
                        </button>
                        <button
                            class="inline-flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                            :class="cameraEnabled ? 'bg-white/10 text-white hover:bg-white/20' : 'bg-[#a8525d] text-white'"
                            type="button"
                            :aria-pressed="cameraEnabled"
                            :aria-label="cameraEnabled ? 'Desligar câmera' : 'Ligar câmera'"
                            @click="online.toggleCamera"
                        >
                            <AppIcon :name="cameraEnabled ? 'Video' : 'VideoOff'" class="size-4" />
                            <span class="hidden sm:inline">{{ cameraEnabled ? 'Câmera' : 'Câmera desligada' }}</span>
                        </button>
                        <label v-if="audioInputs.length" class="ml-auto flex items-center gap-2 text-xs text-[#c8d1ca]">
                            <span class="sr-only">Microfone</span>
                            <select class="max-w-44 rounded-lg border border-white/10 bg-[#202923] px-2 py-2 text-xs text-white" aria-label="Selecionar microfone" @change="changeAudioDevice">
                                <option v-for="device in audioInputs" :key="device.id" :value="device.id">{{ device.label }}</option>
                            </select>
                        </label>
                        <label v-if="videoInputs.length" class="flex items-center gap-2 text-xs text-[#c8d1ca]">
                            <span class="sr-only">Câmera</span>
                            <select class="max-w-44 rounded-lg border border-white/10 bg-[#202923] px-2 py-2 text-xs text-white" aria-label="Selecionar câmera" @change="changeVideoDevice">
                                <option v-for="device in videoInputs" :key="device.id" :value="device.id">{{ device.label }}</option>
                            </select>
                        </label>
                        <button class="ml-auto inline-flex items-center gap-2 rounded-xl bg-[#bd5f64] px-3 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#a95158] disabled:opacity-60" type="button" :disabled="ending" @click="endSession">
                            <AppIcon :name="isPatient ? 'LogOut' : 'PhoneOff'" class="size-4" />
                            {{ isPatient ? 'Sair da sala' : 'Encerrar atendimento' }}
                        </button>
                    </div>
                </section>

                <aside class="flex flex-col rounded-[1.75rem] border border-[#dfe3da] bg-white/90 p-5 shadow-[0_12px_40px_rgba(48,67,56,0.07)] xl:sticky xl:top-5 xl:max-h-[calc(100vh-2.5rem)]">
                    <div v-if="!isPatient" class="mb-5 rounded-2xl border border-[#dcebe2] bg-[#f2f8f3] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#6f8879]">Prontuário</p>
                                <p class="mt-1 font-semibold text-[#24483a]">{{ patient?.name ?? 'Paciente' }}</p>
                            </div>
                            <AppIcon name="ClipboardList" class="size-5 text-[#6b9b7b]" />
                        </div>
                        <p class="mt-2 text-xs leading-5 text-[#65736b]">Acesso privado para você, sem sair da chamada.</p>
                        <button v-if="patient?.id" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-[#8f4a54] transition hover:text-[#6e3540]" type="button" :aria-expanded="recordOpen" @click="togglePatientRecord">
                            {{ recordOpen ? 'Ocultar prontuário' : 'Exibir prontuário' }}
                            <AppIcon :name="recordOpen ? 'ChevronUp' : 'ChevronDown'" class="size-4" />
                        </button>
                    </div>
                    <div class="flex items-center gap-2 border-b border-[#eee9df] pb-3">
                        <AppIcon name="MessageCircle" class="size-4 text-[#8f4a54]" />
                        <div>
                            <h2 class="font-semibold">Chat da sessão</h2>
                            <p class="mt-0.5 text-xs text-[#8a958e]">Mensagens privadas · notificações sonoras ativas</p>
                        </div>
                    </div>
                    <div class="mt-4 flex min-h-48 flex-1 flex-col gap-2 overflow-y-auto text-sm xl:min-h-0">
                        <p v-if="!messages.length" class="text-[#7d8881]">Nenhuma mensagem ainda.</p>
                        <p v-for="(item, index) in messages" :key="index" class="rounded-2xl bg-[#f5f1e9] px-3 py-2.5 leading-5"><strong class="text-[#40574b]">{{ item.author }}:</strong> {{ item.message }}</p>
                    </div>
                    <form class="mt-4 flex gap-2 border-t border-[#eee9df] pt-4" @submit.prevent="submitChat">
                        <input v-model="chatInput" class="min-w-0 flex-1 rounded-xl border border-[#dfe3da] bg-[#fbfcf9] px-3 py-2.5 text-sm outline-none transition placeholder:text-[#9aa49e] focus:border-[#8db29a] focus:ring-2 focus:ring-[#dcebe2]" placeholder="Escreva uma mensagem" aria-label="Mensagem" @focus="clearChatUnread" />
                        <button class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#315f4d] text-white shadow-sm transition hover:bg-[#234b3b]" type="submit" aria-label="Enviar mensagem">
                            <AppIcon name="ArrowRight" class="size-4" />
                        </button>
                    </form>
                </aside>
                <section v-if="!isPatient && patient?.id && recordOpen" class="xl:col-span-2 rounded-[1.75rem] border border-[#dfe3da] bg-white p-5 shadow-[0_12px_40px_rgba(48,67,56,0.07)]">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#eee9df] pb-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#748077]">Prontuário durante o atendimento</p>
                            <h2 class="mt-1 text-lg font-semibold">{{ patient.name }}</h2>
                        </div>
                        <button class="btn-secondary" type="button" @click="togglePatientRecord">Ocultar</button>
                    </div>
                    <div v-if="recordLoading" class="py-8 text-center text-sm text-[#65736b]">Carregando prontuário...</div>
                    <p v-else-if="recordError" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ recordError }}</p>
                    <p v-else-if="!patientRecords.length" class="mt-4 rounded-xl bg-[#f5f1e9] px-4 py-5 text-sm text-[#65736b]">Nenhum registro clínico encontrado.</p>
                    <div v-else class="mt-4 grid gap-3 md:grid-cols-2">
                        <article v-for="record in patientRecords" :key="record.id" class="rounded-2xl border border-[#eee9df] bg-[#fbfaf7] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="font-semibold text-[#26332d]">{{ record.title || 'Registro clínico' }}</h3>
                                <time class="shrink-0 text-xs text-[#748077]">{{ formatDateTime(record.recorded_at || record.created_at) }}</time>
                            </div>
                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#4f5e55]">{{ record.notes || 'Sem observações neste registro.' }}</p>
                            <div v-if="record.treatment_objectives?.length || record.techniques?.length" class="mt-3 flex flex-wrap gap-2 text-xs text-[#65736b]">
                                <span v-for="objective in record.treatment_objectives ?? []" :key="`objective-${objective}`" class="rounded-full bg-[#edf4ef] px-2 py-1">{{ objective }}</span>
                                <span v-for="technique in record.techniques ?? []" :key="`technique-${technique}`" class="rounded-full bg-[#f5f1e9] px-2 py-1">{{ technique }}</span>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </main>
</template>
