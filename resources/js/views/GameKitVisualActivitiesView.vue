<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import AppIcon from '../components/base/AppIcon.vue';
import TemporaryLinkCard from '../components/gamekit/TemporaryLinkCard.vue';

const loading = ref(true);
const generating = ref(false);
const error = ref('');
const message = ref('');
const activities = ref([]);
const selectedActivity = ref(null);
const publicUrl = ref('');
const quota = ref({ used: 0, limit: 5, remaining: 5, resets_at: null });
const form = reactive({ type: 'coloring_cutting', style: 'intermediate', theme: '', therapeutic_goal: '' });
const imageObjectUrls = new Set();

const typeLabels = { coloring: 'Colorir', cutting: 'Recortar', coloring_cutting: 'Colorir + recortar' };
const styleLabels = { simple: 'Simples', intermediate: 'Intermediário', detailed: 'Detalhado' };
const quotaLabel = computed(() => `${quota.value.used} de ${quota.value.limit} imagens usadas nesta semana`);
const fail = (exception, fallback) => { error.value = exception?.response?.data?.message ?? fallback; };

const loadImagePreview = async (activity) => {
    if (!activity?.image_url || activity.preview_url) return activity;

    try {
        const { data } = await axios.get(activity.image_url, { responseType: 'blob' });
        activity.preview_url = URL.createObjectURL(data);
        imageObjectUrls.add(activity.preview_url);
    } catch {
        activity.preview_url = '';
    }

    return activity;
};

const load = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/gamekit/visual-activities');
        activities.value = await Promise.all((data.activities ?? []).map(loadImagePreview));
        quota.value = data.quota ?? quota.value;
    } catch (exception) {
        fail(exception, 'Não foi possível carregar as atividades visuais.');
    } finally {
        loading.value = false;
    }
};

const generate = async () => {
    error.value = '';
    message.value = '';
    publicUrl.value = '';
    if (quota.value.remaining <= 0) return;
    generating.value = true;
    try {
        const { data } = await axios.post('/api/gamekit/visual-activities/generate', form);
        selectedActivity.value = await loadImagePreview(data.activity);
        quota.value = data.quota ?? quota.value;
        activities.value.unshift(data.activity);
        message.value = 'Imagem gerada. Revise o material antes de compartilhar.';
    } catch (exception) {
        fail(exception, 'Não foi possível gerar a imagem agora.');
    } finally {
        generating.value = false;
    }
};

const publish = async () => {
    if (!selectedActivity.value) return;
    try {
        const { data } = await axios.post(`/api/gamekit/visual-activities/${selectedActivity.value.id}/publish`);
        publicUrl.value = data.url;
        selectedActivity.value.public_active = true;
        message.value = 'Link criado. Ele ficará disponível por 24 horas.';
    } catch (exception) {
        fail(exception, 'Não foi possível criar o link da atividade.');
    }
};

const archive = async (activity) => {
    try {
        await axios.patch(`/api/gamekit/visual-activities/${activity.id}/archive`);
        activities.value = activities.value.filter((item) => item.id !== activity.id);
        if (selectedActivity.value?.id === activity.id) selectedActivity.value = null;
        message.value = 'Atividade arquivada.';
    } catch (exception) {
        fail(exception, 'Não foi possível arquivar a atividade.');
    }
};

const printActivity = () => {
    const imageUrl = selectedActivity.value?.preview_url;
    if (!imageUrl) return;

    const printWindow = window.open('', '_blank', 'width=900,height=900');
    if (!printWindow) return;
    printWindow.opener = null;

    const document = printWindow.document;
    document.title = '';
    document.body.style.margin = '0';
    document.body.style.display = 'flex';
    document.body.style.alignItems = 'flex-start';
    document.body.style.justifyContent = 'center';
    document.body.style.padding = '12mm';

    const image = document.createElement('img');
    image.src = imageUrl;
    image.alt = '';
    image.style.maxWidth = '100%';
    image.style.maxHeight = 'calc(100vh - 24mm)';
    image.style.objectFit = 'contain';
    document.body.appendChild(image);

    const print = () => {
        printWindow.focus();
        printWindow.print();
        window.setTimeout(() => printWindow.close(), 300);
    };

    image.onload = print;
    if (image.complete) window.setTimeout(print, 50);
};
onMounted(load);
onBeforeUnmount(() => imageObjectUrls.forEach((url) => URL.revokeObjectURL(url)));
</script>

<template>
    <main class="page-shell space-y-6">
        <header class="page-header">
            <div class="page-header__content">
                <p class="section-kicker">GameKit Psi · Material visual</p>
                <h1 class="page-header__title">Atividades visuais</h1>
                <p class="page-header__description">Crie materiais para colorir e recortar, revise o resultado e decida quando compartilhar.</p>
            </div>
        </header>

        <p v-if="message" class="rounded-2xl border border-[#c9d8cd] bg-[#edf4ef] px-4 py-3 text-sm text-[#365341]" role="status">{{ message }}</p>
        <p v-if="error" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ error }}</p>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,0.85fr)_minmax(420px,1.15fr)]">
            <form class="surface-panel space-y-5 p-5 sm:p-6" @submit.prevent="generate">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="section-kicker">Novo material</p>
                        <h2 class="mt-1 text-xl font-semibold text-[var(--spa-ink)]">Criar atividade visual</h2>
                        <p class="mt-1 text-sm leading-6 text-[var(--spa-ink-muted)]">A IA orienta a ilustração; a revisão final é sempre do psicólogo.</p>
                    </div>
                    <div class="rounded-2xl bg-[var(--spa-accent-soft)] px-3 py-2 text-center text-xs font-semibold text-[var(--spa-accent)]">
                        <span class="block text-lg">{{ quota.remaining }}</span>
                        <span>gerações restantes</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-[var(--spa-border-soft)] bg-[var(--spa-surface-muted)] px-4 py-3 text-sm text-[var(--spa-ink-soft)]">{{ quotaLabel }}. O limite é renovado semanalmente.</div>

                <div>
                    <label class="field-label">Tipo de atividade</label>
                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        <button v-for="(label, value) in typeLabels" :key="value" class="rounded-xl border px-3 py-3 text-left text-sm font-semibold transition" :class="form.type === value ? 'border-[var(--spa-accent)] bg-[var(--spa-accent-soft)] text-[var(--spa-accent)]' : 'border-[var(--spa-border)] bg-white text-[var(--spa-ink-soft)] hover:border-[var(--spa-accent)]'" type="button" @click="form.type = value">{{ label }}</button>
                    </div>
                </div>

                <label class="field-label">Tema
                    <input v-model="form.theme" class="field-input mt-1" maxlength="160" required placeholder="Ex.: animais da floresta" />
                </label>
                <label class="field-label">Objetivo terapêutico
                    <textarea v-model="form.therapeutic_goal" class="field-input mt-1 min-h-24" maxlength="500" required placeholder="Ex.: nomear emoções e praticar escolhas" />
                    <span class="mt-1 block text-xs font-normal text-[var(--spa-ink-muted)]">Usado para orientar a geração. Não inclua nome, CPF, diagnóstico ou dados do prontuário.</span>
                </label>

                <div>
                    <label class="field-label">Estilo visual</label>
                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        <button v-for="(label, value) in styleLabels" :key="value" class="rounded-xl border px-3 py-3 text-left text-sm font-semibold transition" :class="form.style === value ? 'border-[var(--spa-accent)] bg-[var(--spa-accent-soft)] text-[var(--spa-accent)]' : 'border-[var(--spa-border)] bg-white text-[var(--spa-ink-soft)] hover:border-[var(--spa-accent)]'" type="button" @click="form.style = value">{{ label }}</button>
                    </div>
                </div>

                <button class="btn-primary w-full justify-center" type="submit" :disabled="generating || quota.remaining <= 0">
                    <AppIcon v-if="!generating" name="Sparkles" class="size-4" />
                    {{ generating ? 'Gerando imagem...' : quota.remaining <= 0 ? 'Limite semanal atingido' : 'Gerar imagem · 1 crédito' }}
                </button>
            </form>

            <section class="surface-panel p-5 sm:p-6" aria-live="polite">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="section-kicker">Prévia</p>
                        <h2 class="mt-1 text-xl font-semibold text-[var(--spa-ink)]">Revise antes de compartilhar</h2>
                    </div>
                    <span v-if="selectedActivity" class="rounded-full bg-[#edf4ef] px-3 py-1 text-xs font-semibold text-[#365341]">Pronta</span>
                </div>
                <div v-if="selectedActivity?.preview_url" class="visual-print-target mt-5 overflow-hidden rounded-2xl border border-[var(--spa-border)] bg-white p-3">
                    <img :src="selectedActivity.preview_url" :alt="`Atividade visual sobre ${selectedActivity.theme}`" class="mx-auto aspect-square w-full max-w-lg object-contain" />
                </div>
                <div v-else class="mt-5 flex min-h-[360px] items-center justify-center rounded-2xl border border-dashed border-[var(--spa-border)] bg-[var(--spa-surface-muted)] p-8 text-center">
                    <div><AppIcon name="ImagePlus" class="mx-auto size-9 text-[var(--spa-accent)]" /><p class="mt-3 font-semibold text-[var(--spa-ink)]">A imagem aparecerá aqui</p><p class="mt-1 text-sm text-[var(--spa-ink-muted)]">Escolha as opções e gere uma atividade para revisar o material.</p></div>
                </div>
                <div v-if="selectedActivity" class="mt-5 flex flex-wrap gap-2">
                    <button class="btn-secondary" type="button" :disabled="generating || quota.remaining <= 0" @click="generate"><AppIcon name="RefreshCw" class="size-4" />Gerar outra</button>
                    <a class="btn-secondary" :href="selectedActivity.preview_url" download><AppIcon name="Download" class="size-4" />Baixar</a>
                    <button class="btn-secondary" type="button" @click="printActivity"><AppIcon name="Printer" class="size-4" />Imprimir</button>
                    <button class="btn-primary" type="button" @click="publish"><AppIcon name="Link" class="size-4" />Enviar ao GameKit</button>
                </div>
                <TemporaryLinkCard v-if="publicUrl" class="mt-4" :url="publicUrl" label="Link da atividade" />
            </section>
        </section>

        <section v-if="!loading && activities.length" class="surface-panel p-5 sm:p-6">
            <div><p class="section-kicker">Histórico</p><h2 class="mt-1 text-xl font-semibold text-[var(--spa-ink)]">Materiais recentes</h2></div>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <article v-for="activity in activities" :key="activity.id" class="overflow-hidden rounded-2xl border border-[var(--spa-border)] bg-white">
                    <button class="block w-full text-left" type="button" @click="selectedActivity = activity; publicUrl = ''">
                        <img v-if="activity.preview_url" :src="activity.preview_url" :alt="`Atividade visual sobre ${activity.theme}`" class="aspect-square w-full object-cover" />
                        <div v-else class="flex aspect-square items-center justify-center bg-red-50 p-4 text-center text-sm text-red-700">{{ activity.failure_reason || 'Falha na geração' }}</div>
                        <div class="p-4"><h3 class="truncate font-semibold text-[var(--spa-ink)]">{{ activity.theme }}</h3><p class="mt-1 text-xs text-[var(--spa-ink-muted)]">{{ typeLabels[activity.type] }} · {{ styleLabels[activity.style] }}</p></div>
                    </button>
                    <div class="flex items-center justify-between border-t border-[var(--spa-border-soft)] px-4 py-3"><span class="text-xs text-[var(--spa-ink-muted)]">{{ new Date(activity.created_at).toLocaleDateString('pt-BR') }}</span><button class="text-xs font-semibold text-red-700" type="button" @click="archive(activity)">Arquivar</button></div>
                </article>
            </div>
        </section>
    </main>
</template>

<style scoped>
@media print {
    :global(body *) {
        visibility: hidden !important;
    }

    .visual-print-target,
    .visual-print-target * {
        visibility: visible !important;
    }

    .visual-print-target {
        position: absolute !important;
        inset: 0 !important;
        display: flex !important;
        align-items: flex-start !important;
        justify-content: center !important;
        width: 100% !important;
        height: 100vh !important;
        border: 0 !important;
        padding: 12mm !important;
    }

    .visual-print-target img {
        max-width: 100% !important;
        max-height: calc(100vh - 24mm) !important;
        object-fit: contain !important;
    }
}
</style>
