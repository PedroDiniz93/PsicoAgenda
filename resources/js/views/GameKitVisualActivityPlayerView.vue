<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const activity = ref(null);
const loading = ref(true);
const error = ref('');
const printActivity = () => {
    if (!activity.value?.image_url) return;

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
    image.src = activity.value.image_url;
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

onMounted(async () => {
    try {
        activity.value = (await axios.get(`/api/gamekit/visual/play/${route.params.token}`)).data.activity;
    } catch (exception) {
        error.value = exception?.response?.data?.message ?? 'Esta atividade não está mais disponível.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <main class="min-h-screen bg-[var(--spa-bg)] px-4 py-8 sm:px-6 sm:py-12">
        <section class="mx-auto w-full max-w-3xl rounded-3xl bg-white p-6 text-center shadow-sm sm:p-10">
            <div v-if="loading" class="py-16 text-sm text-[var(--spa-ink-muted)]">Carregando atividade...</div>
            <div v-else-if="error" class="py-16"><h1 class="text-2xl font-semibold text-[var(--spa-ink)]">Atividade indisponível</h1><p class="mt-3 text-sm text-[var(--spa-ink-muted)]">{{ error }}</p></div>
            <div v-else-if="activity"><p class="section-kicker">GameKit Psi</p><h1 class="mt-1 text-3xl font-semibold text-[var(--spa-ink)]">{{ activity.theme }}</h1><p class="mt-3 text-base text-[var(--spa-ink-soft)]">{{ activity.instruction }}</p><div class="visual-print-target"><img :src="activity.image_url" :alt="`Atividade visual sobre ${activity.theme}`" class="mx-auto mt-8 max-h-[70vh] w-full object-contain" /></div><button class="btn-primary mx-auto mt-8" type="button" @click="printActivity">Imprimir atividade</button></div>
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
        padding: 12mm !important;
    }

    .visual-print-target img {
        max-width: 100% !important;
        max-height: calc(100vh - 24mm) !important;
        object-fit: contain !important;
    }
}
</style>
