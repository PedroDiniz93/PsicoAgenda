<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import AppIcon from '../components/base/AppIcon.vue';
import TemporaryLinkCard from '../components/gamekit/TemporaryLinkCard.vue';

const router = useRouter();
const mode = ref('computer'), link = ref(''), loading = ref(false), error = ref(''), message = ref('');
const createLink = async () => { loading.value = true; error.value = ''; try { const session = (await axios.post('/api/gamekit/tictactoe/sessions', { mode: mode.value })).data; link.value = (await axios.post('/api/gamekit/tictactoe/sessions/' + session.id + '/link')).data.url; await navigator.clipboard?.writeText(link.value); message.value = 'Link copiado. Envie para o paciente.'; } catch (e) { error.value = e?.response?.data?.message ?? 'Não foi possível criar a partida.'; } finally { loading.value = false; } };
</script>
<template>
    <main class="page-shell space-y-6"><header class="page-header"><div class="page-header__content"><button class="btn-secondary mb-4" type="button" @click="router.push('/gamekit')"><AppIcon name="ArrowLeft" class="size-4" />Voltar para o GameKit</button><p class="section-kicker">GameKit Psi · Jogo da velha</p><h1 class="page-header__title">Jogo da Velha Mutante</h1><p class="page-header__description">Cada jogador mantém três peças. Quando a quarta entra, a mais antiga desaparece.</p></div></header>
        <p v-if="message" class="rounded-2xl border border-[#c9d8cd] bg-[#edf4ef] px-4 py-3 text-sm text-[#365341]">{{ message }}</p><p v-if="error" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</p>
        <section class="mx-auto w-full max-w-2xl surface-panel p-6 sm:p-8"><div class="flex items-start gap-4"><span class="flex size-12 items-center justify-center rounded-2xl bg-[var(--spa-accent-soft)] text-[var(--spa-accent)]"><AppIcon name="Grid3X3" class="size-6" /></span><div><h2 class="text-xl font-semibold text-[var(--spa-ink)]">Prepare uma partida</h2><p class="mt-1 text-sm leading-6 text-[var(--spa-ink-muted)]">Uma versão dinâmica e infantil do jogo clássico, feita para jogar no celular ou tablet.</p></div></div><label class="field-label mt-6 block">Modo de jogo<select v-model="mode" class="field-input mt-1"><option value="computer">Paciente contra o computador</option><option value="local">Duas pessoas no mesmo dispositivo</option></select></label><button class="btn-primary mt-5 w-full justify-center" type="button" :disabled="loading" @click="createLink"><AppIcon name="Link" class="size-4" />{{ loading ? 'Criando partida...' : 'Criar link do paciente' }}</button><TemporaryLinkCard v-if="link" class="mt-5" :url="link" label="Link da partida" /></section>
    </main>
</template>
