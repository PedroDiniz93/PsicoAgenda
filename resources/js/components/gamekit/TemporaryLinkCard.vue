<script setup>
import { ref } from 'vue';
import AppIcon from '../base/AppIcon.vue';

const props = defineProps({ url: { type: String, required: true }, label: { type: String, default: 'Link temporário' } });
const copied = ref(false);
const copy = async () => { try { await navigator.clipboard?.writeText(props.url); copied.value = true; window.setTimeout(() => { copied.value = false; }, 1800); } catch { copied.value = false; } };
</script>

<template>
    <article class="overflow-hidden rounded-2xl border border-[var(--spa-border)] bg-white shadow-sm">
        <div class="flex items-center justify-between gap-3 border-b border-[var(--spa-border)] bg-[var(--spa-surface-muted)] px-4 py-3">
            <div class="flex min-w-0 items-center gap-3"><span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[var(--spa-accent-soft)] text-[var(--spa-accent)]"><AppIcon name="Link2" class="size-4" /></span><div class="min-w-0"><p class="text-xs font-bold uppercase tracking-wide text-[var(--spa-ink-muted)]">{{ label }}</p><p class="mt-0.5 text-xs text-[var(--spa-ink-muted)]">Válido por tempo limitado</p></div></div><span v-if="copied" class="flex shrink-0 items-center gap-1 text-xs font-semibold text-emerald-700"><AppIcon name="Check" class="size-4" />Copiado</span>
        </div>
        <div class="flex items-center gap-2 p-3"><button class="min-w-0 flex-1 truncate rounded-xl border border-[var(--spa-border)] bg-[var(--spa-surface-muted)] px-3 py-2.5 text-left text-xs text-[var(--spa-ink)] transition hover:border-[var(--spa-accent)]" type="button" :title="url" @click="copy">{{ url }}</button><button class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-[var(--spa-border)] text-[var(--spa-accent)] transition hover:bg-[var(--spa-accent-soft)]" type="button" :aria-label="copied ? 'Link copiado' : 'Copiar link'" :title="copied ? 'Link copiado' : 'Copiar link'" @click="copy"><AppIcon :name="copied ? 'Check' : 'Copy'" class="size-4" /></button><a class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-[var(--spa-border)] text-[var(--spa-ink-muted)] transition hover:bg-[var(--spa-surface-muted)]" :href="url" target="_blank" rel="noopener" aria-label="Abrir link" title="Abrir link"><AppIcon name="ExternalLink" class="size-4" /></a></div>
    </article>
</template>
