<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import GameKitExpiredState from '../components/gamekit/GameKitExpiredState.vue';

const route = useRoute();
const session = ref(null);
const current = ref(0);
const participantKey = ref('');
const selected = ref('');
const finished = ref(false);
const error = ref('');
const loading = ref(true);
const card = computed(() => session.value?.cards?.[current.value] ?? null);

const submit = async () => {
    if (!card.value || !selected.value) return;
    try {
        const { data } = await axios.post('/api/gamekit/play/' + route.params.token + '/responses', {
            card_id: card.value.id, answer: selected.value, participant_key: participantKey.value || undefined,
        });
        participantKey.value = data.participant_key;
        selected.value = '';
        if (current.value >= session.value.cards.length - 1) finished.value = true;
        else current.value += 1;
    } catch (e) { error.value = e?.response?.data?.message ?? 'Não foi possível registrar esta resposta.'; }
};

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/gamekit/play/' + route.params.token);
        session.value = data.session;
    } catch { error.value = 'Esta atividade não está mais disponível.'; }
    finally { loading.value = false; }
});
</script>

<template>
    <main class="auth-screen min-h-screen px-4 py-8">
        <section class="w-full max-w-xl rounded-3xl border border-[var(--spa-border-soft)] bg-[var(--spa-surface)] p-6 shadow-[var(--spa-shadow)] sm:p-8">
            <div v-if="loading" class="py-16 text-center text-sm text-[var(--spa-ink-muted)]">Carregando atividade...</div>
            <GameKitExpiredState v-else-if="error" :message="error" />
            <div v-else-if="finished" class="py-12 text-center"><h1 class="text-2xl font-semibold text-[var(--spa-ink)]">Atividade concluída</h1><p class="mt-2 text-sm text-[var(--spa-ink-muted)]">Sua resposta foi enviada.</p></div>
            <div v-else-if="card">
                <div class="mt-2 flex items-center justify-between gap-4"><h1 class="text-2xl font-semibold text-[var(--spa-ink)]">{{ session.theme }}</h1><span class="text-sm text-[var(--spa-ink-muted)]">{{ current + 1 }}/{{ session.cards.length }}</span></div>
                <div class="mt-6 rounded-2xl bg-[var(--spa-surface-muted)] p-5"><p class="text-xs font-semibold uppercase tracking-wide text-[var(--spa-ink-muted)]">Contexto</p><p class="mt-2 text-base text-[var(--spa-ink)]">{{ card.context ?? card.prompt_a }}</p><p class="mt-5 text-xs font-semibold uppercase tracking-wide text-[var(--spa-ink-muted)]">Pergunta</p><p class="mt-2 text-lg font-semibold text-[var(--spa-ink)]">{{ card.question ?? card.prompt_b }}</p></div>
                <p class="mt-3 text-sm text-[var(--spa-ink-muted)]">Escolha uma das opções abaixo.</p>
                <div class="mt-4 grid gap-3"><button v-for="option in card.options" :key="option" class="rounded-2xl border px-4 py-4 text-left text-sm font-semibold transition" :class="selected === option ? 'border-[var(--spa-accent)] bg-[var(--spa-accent-soft)] text-[var(--spa-accent-hover)]' : 'border-[var(--spa-border-soft)] bg-[var(--spa-surface-raised)] text-[var(--spa-ink-soft)] hover:border-[var(--spa-accent)]'" type="button" @click="selected = option">{{ option }}</button></div>
                <button class="btn-primary mt-6 w-full justify-center" type="button" :disabled="!selected" @click="submit">Confirmar resposta</button>
            </div>
        </section>
    </main>
</template>
