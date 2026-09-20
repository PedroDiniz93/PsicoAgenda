<script setup>
import AppIcon from '../base/AppIcon.vue';

defineProps({
    message: {
        type: String,
        default: 'Este link de atividade expirou ou já foi utilizado.',
    },
});

const goBack = () => {
    if (window.history.length > 1) window.history.back();
    else window.location.href = '/';
};
</script>

<template>
    <section class="rounded-3xl border border-[var(--spa-border-soft)] bg-white p-8 text-center shadow-[var(--spa-shadow)] sm:p-12">
        <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-[var(--spa-accent-soft)] text-[var(--spa-accent)]">
            <AppIcon name="Link2" class="size-7" />
        </span>
        <p class="section-kicker mt-5">GameKit Psi</p>
        <h1 class="mt-1 text-2xl font-semibold text-[var(--spa-ink)]">Atividade indisponível</h1>
        <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-[var(--spa-ink-muted)]">{{ message }}</p>
        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-[var(--spa-ink-muted)]">Peça um novo link ao seu psicólogo para continuar a atividade.</p>
        <button class="btn-primary mt-6" type="button" @click="goBack">Voltar</button>
    </section>
</template>
