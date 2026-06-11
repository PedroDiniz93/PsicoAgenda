<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import axios from 'axios';
import Alert from '../components/base/Alert.vue';
import Modal from '../components/base/Modal.vue';
import AppIcon from '../components/base/AppIcon.vue';
import { formatDateOnly, formatDateTime, formatMoney } from '../utils/formatters';

const toLocalMonth = (date = new Date()) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    return `${year}-${month}`;
};

const moneyNumber = (value: unknown) => Number(value ?? 0);

const formatDate = (value?: string) => {
    if (!value) return 'Sem vencimento';

    return formatDateOnly(value);
};

const toBrazilianDate = (value?: string) => {
    if (!value) return '';

    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return value;

    return `${match[3]}/${match[2]}/${match[1]}`;
};

const toIsoDate = (value?: string) => {
    if (!value) return '';

    const trimmed = String(value).trim();
    const brMatch = trimmed.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (brMatch) {
        return `${brMatch[3]}-${brMatch[2]}-${brMatch[1]}`;
    }

    return trimmed;
};

const applyBrazilianDateMask = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const digits = input.value.replace(/\D/g, '').slice(0, 8);
    const parts = [digits.slice(0, 2), digits.slice(2, 4), digits.slice(4, 8)].filter(Boolean);
    chargeForm.paymentDueAt = parts.join('/');
};

const selectedPaymentDueAtIso = computed({
    get: () => toIsoDate(chargeForm.paymentDueAt),
    set: (value: string) => {
        chargeForm.paymentDueAt = toBrazilianDate(value);
    },
});

const formatPercent = (value: number | null | undefined) => {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return 'Sem base';
    }

    return `${Math.round(Number(value) * 100)}%`;
};

const selectedMonth = ref(toLocalMonth());
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const activeSection = ref('billing');

const dashboard = ref<any>({
    period: { month: selectedMonth.value, label: '', from: '', to: '' },
    settings: {},
    summary: {},
    wallet: [],
    receivables: [],
    paid_appointments: [],
    forecast: [],
    recent_receipts: [],
    payment_methods: [],
});

const settingsForm = reactive({
    pixKeyType: '',
    pixKey: '',
    defaultPaymentLink: '',
    receiptPrefix: 'REC',
    paymentTermsDays: 0,
    chargeMessageTemplate: '',
});

const settingsErrors = reactive({
    pix_key_type: '',
    pix_key: '',
    default_payment_link: '',
    receipt_prefix: '',
    payment_terms_days: '',
    charge_message_template: '',
});

const settingsSaving = ref(false);

const actionModalOpen = ref(false);
const actionSaving = ref(false);
const receiptLoading = ref(false);
const selectedReceivable = ref<any>(null);
const receiptPreview = ref<any>(null);
const actionMessage = ref('');
const copyMessage = ref('');

const chargeForm = reactive({
    price: '',
    paymentDueAt: '',
    paymentMethod: 'pix',
    paymentLink: '',
    paymentNotes: '',
});

const billingFilters = reactive({
    patientName: '',
});

const pixKeyTypes = [
    { value: 'cpf', label: 'CPF' },
    { value: 'cnpj', label: 'CNPJ' },
    { value: 'email', label: 'E-mail' },
    { value: 'phone', label: 'Telefone' },
    { value: 'random', label: 'Chave aleatória' },
];

const clearSettingsErrors = () => {
    Object.keys(settingsErrors).forEach((key) => {
        settingsErrors[key] = '';
    });
};

const syncSettingsForm = () => {
    const settings = dashboard.value?.settings ?? {};
    settingsForm.pixKeyType = settings.pix_key_type ?? '';
    settingsForm.pixKey = settings.pix_key ?? '';
    settingsForm.defaultPaymentLink = settings.default_payment_link ?? '';
    settingsForm.receiptPrefix = settings.receipt_prefix ?? 'REC';
    settingsForm.paymentTermsDays = Number(settings.payment_terms_days ?? 0);
    settingsForm.chargeMessageTemplate = settings.charge_message_template ?? '';
};

const summaryCards = computed(() => {
    const summary = dashboard.value.summary ?? {};

    return [
        {
            id: 'received',
            label: 'Recebido no mês',
            value: formatMoney(summary.received_value),
            detail: `${summary.received_count ?? 0} atendimentos pagos`,
            tone: 'border-emerald-200 bg-emerald-50 text-emerald-950',
            icon: 'CircleDollarSign',
        },
        {
            id: 'open',
            label: 'Em aberto',
            value: formatMoney(summary.pending_value),
            detail: `${summary.pending_count ?? 0} cobranças do mês`,
            tone: 'border-cyan-200 bg-cyan-50 text-cyan-950',
            icon: 'HandCoins',
        },
        {
            id: 'overdue',
            label: 'Inadimplência',
            value: formatMoney(summary.overdue_value),
            detail: `${summary.overdue_count ?? 0} cobranças atrasadas`,
            tone: 'border-rose-200 bg-rose-50 text-rose-950',
            icon: 'AlertTriangle',
        },
        {
            id: 'forecast',
            label: 'Previsão',
            value: formatMoney(summary.forecast_value),
            detail: `${summary.forecast_count ?? 0} sessões futuras no mês`,
            tone: 'border-violet-200 bg-violet-50 text-violet-950',
            icon: 'TrendingUp',
        },
    ];
});

const collectionRate = computed(() => dashboard.value.summary?.collection_rate ?? null);
const collectionWidth = computed(() => {
    const rate = Number(collectionRate.value ?? 0);
    return `${Math.max(0, Math.min(100, Math.round(rate * 100)))}%`;
});

const topReceivable = computed(() => {
    const list = dashboard.value.receivables ?? [];
    return list.find((item: any) => item.days_overdue > 0) ?? list[0] ?? null;
});

const normalizeText = (value: unknown) => String(value ?? '').trim().toLocaleLowerCase('pt-BR');

const billingItems = computed(() => {
    const receivables = (dashboard.value.receivables ?? []).map((item: any) => ({
        ...item,
        billingStatus: 'open',
        billingStatusLabel: receivableBadge(item).label,
        billingStatusClass: receivableBadge(item).class,
        billingDateLabel: `${formatDateTime(item.start_at)} · vence ${formatDate(item.due_at)}`,
        billingActionLabel: 'Ações',
        billingActionClass: 'hover:border-cyan-200 hover:bg-white hover:text-cyan-800',
        billingCardClass: 'hover:border-cyan-200 hover:bg-cyan-50/30',
        billingMeta: methodLabel(item.payment_method),
    }));

    const paid = (dashboard.value.paid_appointments ?? []).map((item: any) => ({
        ...item,
        billingStatus: 'paid',
        billingStatusLabel: 'Pago',
        billingStatusClass: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        billingDateLabel: `Sessão ${formatDateTime(item.start_at)} · pago ${formatDateTime(item.paid_at)}`,
        billingActionLabel: 'Recibo',
        billingActionClass: 'hover:border-emerald-200 hover:bg-white hover:text-emerald-800',
        billingCardClass: 'hover:border-emerald-200 hover:bg-emerald-50/30',
        billingMeta: item.receipt_number ? 'Recibo emitido' : 'Sem recibo',
    }));

    const patientName = normalizeText(billingFilters.patientName);

    return [...receivables, ...paid]
        .filter((item: any) => !patientName || normalizeText(item.patient?.name).includes(patientName))
        .sort((a: any, b: any) => {
            if (a.billingStatus !== b.billingStatus) {
                return a.billingStatus === 'open' ? -1 : 1;
            }

            return String(a.start_at ?? '').localeCompare(String(b.start_at ?? ''));
        });
});

const hasBillingItems = computed(() => billingItems.value.length > 0);
const hasWallet = computed(() => (dashboard.value.wallet ?? []).length > 0);

const financeSections = computed(() => [
    { id: 'overview', label: 'Resumo', count: null },
    { id: 'billing', label: 'Cobranças', count: billingItems.value.length },
    { id: 'wallet', label: 'Carteira', count: dashboard.value.wallet?.length ?? 0 },
    { id: 'settings', label: 'Configurações', count: null },
]);

const sectionTitle = computed(() => {
    const map: Record<string, { title: string; description: string }> = {
        billing: {
            title: 'Cobranças e pagamentos',
            description: 'Atendimentos em aberto e pagos no período selecionado.',
        },
        overview: {
            title: 'Resumo financeiro',
            description: 'Os sinais principais do mês e a próxima cobrança que precisa de atenção.',
        },
        wallet: {
            title: 'Carteira por paciente',
            description: 'Visão consolidada de quem está em dia, aberto ou atrasado.',
        },
        settings: {
            title: 'Configurações de recebimento',
            description: 'Pix, link padrão, prazo de vencimento, recibos e mensagem de cobrança.',
        },
    };

    return map[activeSection.value] ?? map.overview;
});

const receivableBadge = (item: any) => {
    if (item.is_paid) {
        return { label: 'Pago', class: 'border-emerald-200 bg-emerald-50 text-emerald-700' };
    }

    if (item.days_overdue > 0) {
        return { label: `${item.days_overdue} dia(s) atrasado`, class: 'border-rose-200 bg-rose-50 text-rose-700' };
    }

    return { label: 'Em aberto', class: 'border-amber-200 bg-amber-50 text-amber-700' };
};

const walletBadge = (status: string) => {
    if (status === 'paid') {
        return { label: 'Em dia', class: 'border-emerald-200 bg-emerald-50 text-emerald-700' };
    }

    if (status === 'overdue') {
        return { label: 'Atrasado', class: 'border-rose-200 bg-rose-50 text-rose-700' };
    }

    return { label: 'Aberto', class: 'border-amber-200 bg-amber-50 text-amber-700' };
};

const methodLabel = (value?: string) => {
    return dashboard.value.payment_methods?.find((method: any) => method.value === value)?.label ?? 'Não informado';
};

const fetchDashboard = async () => {
    loading.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const { data } = await axios.get('/api/finance/dashboard', {
            params: { month: selectedMonth.value },
        });
        dashboard.value = data;
        selectedMonth.value = data?.period?.month ?? selectedMonth.value;
        syncSettingsForm();
    } catch (error: any) {
        errorMessage.value = error?.response?.data?.message ?? 'Não foi possível carregar o financeiro.';
    } finally {
        loading.value = false;
    }
};

const saveSettings = async () => {
    clearSettingsErrors();
    settingsSaving.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const { data } = await axios.put('/api/finance/settings', {
            pix_key_type: settingsForm.pixKeyType || null,
            pix_key: settingsForm.pixKey || null,
            default_payment_link: settingsForm.defaultPaymentLink || null,
            receipt_prefix: settingsForm.receiptPrefix || 'REC',
            payment_terms_days: Number(settingsForm.paymentTermsDays ?? 0),
            charge_message_template: settingsForm.chargeMessageTemplate || null,
        });
        dashboard.value.settings = data.settings;
        syncSettingsForm();
        successMessage.value = 'Configurações financeiras salvas.';
    } catch (error: any) {
        if (error?.response?.status === 422) {
            const errors = error.response.data.errors ?? {};
            Object.entries(errors).forEach(([field, messages]: [string, any]) => {
                if (field in settingsErrors) {
                    settingsErrors[field as keyof typeof settingsErrors] = messages[0];
                }
            });
        }
        errorMessage.value = error?.response?.data?.message ?? 'Não foi possível salvar as configurações.';
    } finally {
        settingsSaving.value = false;
    }
};

const openReceivable = (item: any) => {
    selectedReceivable.value = item;
    receiptPreview.value = null;
    actionMessage.value = '';
    copyMessage.value = '';
    chargeForm.price = item.price ?? '';
    chargeForm.paymentDueAt = toBrazilianDate(item.payment_due_at ?? item.due_at ?? '');
    chargeForm.paymentMethod = item.payment_method ?? 'pix';
    chargeForm.paymentLink = item.payment_link ?? item.effective_payment_link ?? '';
    chargeForm.paymentNotes = item.payment_notes ?? '';
    actionModalOpen.value = true;
};

const closeReceivable = () => {
    actionModalOpen.value = false;
    selectedReceivable.value = null;
    receiptPreview.value = null;
    actionMessage.value = '';
    copyMessage.value = '';
};

const paymentPayload = (paid?: boolean) => {
    const payload: any = {
        price: chargeForm.price === '' ? null : Number(chargeForm.price),
        payment_due_at: toIsoDate(chargeForm.paymentDueAt) || null,
        payment_method: chargeForm.paymentMethod || null,
        payment_link: chargeForm.paymentLink || null,
        payment_notes: chargeForm.paymentNotes || null,
    };

    if (typeof paid === 'boolean') {
        payload.paid = paid;
        if (paid) {
            payload.paid_at = new Date().toISOString();
        }
    }

    return payload;
};

const savePayment = async (paid?: boolean) => {
    if (!selectedReceivable.value?.id) return;

    actionSaving.value = true;
    actionMessage.value = '';
    copyMessage.value = '';

    try {
        const { data } = await axios.patch(`/api/finance/appointments/${selectedReceivable.value.id}/payment`, paymentPayload(paid));
        await fetchDashboard();
        actionMessage.value = paid ? 'Pagamento marcado como recebido.' : 'Cobrança atualizada.';

        const updated = (dashboard.value.receivables ?? []).find((item: any) => item.id === selectedReceivable.value.id);
        if (paid && data?.appointment) {
            selectedReceivable.value = data.appointment;
        } else if (updated) {
            selectedReceivable.value = updated;
        } else {
            closeReceivable();
        }
    } catch (error: any) {
        actionMessage.value = error?.response?.data?.message ?? 'Não foi possível atualizar a cobrança.';
    } finally {
        actionSaving.value = false;
    }
};

const issueReceipt = async () => {
    if (!selectedReceivable.value?.id) return;

    receiptLoading.value = true;
    actionMessage.value = '';

    try {
        const { data } = await axios.post(`/api/finance/appointments/${selectedReceivable.value.id}/receipt`);
        receiptPreview.value = data.receipt;
        await fetchDashboard();
    } catch (error: any) {
        actionMessage.value = error?.response?.data?.message ?? 'Não foi possível emitir o recibo.';
    } finally {
        receiptLoading.value = false;
    }
};

const copyText = async (text: string, label: string) => {
    if (!text) return;

    try {
        await navigator.clipboard.writeText(text);
        copyMessage.value = `${label} copiado.`;
    } catch {
        copyMessage.value = 'Não foi possível copiar automaticamente.';
    }
};

const chargeMessage = computed(() => {
    const item = selectedReceivable.value;
    if (!item) return '';

    const defaultLines = [
        `Olá ${item.patient?.name ?? ''}, tudo bem?`,
        `Estou enviando a cobrança da sessão de ${formatDateTime(item.start_at)} no valor de ${formatMoney(chargeForm.price)}.`,
    ];

    if (chargeForm.paymentLink) {
        defaultLines.push(`Link para pagamento: ${chargeForm.paymentLink}`);
    }

    if (dashboard.value.settings?.pix_key) {
        defaultLines.push(`Pix: ${dashboard.value.settings.pix_key}`);
    }

    defaultLines.push('Obrigado(a).');

    const template = dashboard.value.settings?.charge_message_template;
    if (!template) {
        return defaultLines.join('\n');
    }

    const replacements: Record<string, string> = {
        paciente: item.patient?.name ?? '',
        data_hora: formatDateTime(item.start_at),
        valor: formatMoney(chargeForm.price),
        link_pagamento: chargeForm.paymentLink ?? '',
        pix: dashboard.value.settings?.pix_key ?? '',
    };

    return template.replace(/\{\{\s*(paciente|data_hora|valor|link_pagamento|pix)\s*\}\}/g, (_match: string, key: string) => replacements[key] ?? '');
});

const printReceipt = () => {
    if (!receiptPreview.value) return;

    const receipt = receiptPreview.value;
    const receiptWindow = window.open('', '_blank', 'width=760,height=860');
    if (!receiptWindow) {
        actionMessage.value = 'Não foi possível abrir a janela de impressão.';
        return;
    }

    const escapeHtml = (value: unknown) =>
        String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

    receiptWindow.document.write(`
        <html>
            <head>
                <title>Recibo ${escapeHtml(receipt.number)}</title>
                <style>
                    body { font-family: Arial, sans-serif; color: #0f172a; padding: 32px; }
                    .receipt { border: 1px solid #cbd5e1; border-radius: 12px; padding: 28px; }
                    h1 { margin: 0 0 6px; font-size: 24px; }
                    .muted { color: #64748b; font-size: 13px; }
                    .amount { font-size: 32px; font-weight: 700; margin: 24px 0; }
                    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 18px; }
                    .box { border-top: 1px solid #e2e8f0; padding-top: 12px; }
                    .label { color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
                    .value { margin-top: 4px; font-size: 15px; }
                    .signature { margin-top: 54px; border-top: 1px solid #94a3b8; width: 260px; padding-top: 8px; text-align: center; }
                </style>
            </head>
            <body>
                <main class="receipt">
                    <p class="muted">Recibo número ${escapeHtml(receipt.number)}</p>
                    <h1>Recibo de pagamento</h1>
                    <p class="muted">Emitido em ${escapeHtml(formatDateTime(receipt.issued_at))}</p>
                    <div class="amount">${escapeHtml(formatMoney(receipt.amount))}</div>
                    <p>Recebemos de <strong>${escapeHtml(receipt.patient?.name)}</strong> o valor referente a ${escapeHtml(receipt.description)}.</p>
                    <section class="grid">
                        <div class="box">
                            <div class="label">Paciente</div>
                            <div class="value">${escapeHtml(receipt.patient?.name)}</div>
                            <div class="muted">${escapeHtml(receipt.patient?.cpf || receipt.patient?.email || '')}</div>
                        </div>
                        <div class="box">
                            <div class="label">Profissional</div>
                            <div class="value">${escapeHtml(receipt.psychologist?.name)}</div>
                            <div class="muted">${escapeHtml(receipt.psychologist?.email || '')}</div>
                        </div>
                        <div class="box">
                            <div class="label">Atendimento</div>
                            <div class="value">${escapeHtml(formatDateTime(receipt.appointment?.start_at))}</div>
                        </div>
                        <div class="box">
                            <div class="label">Forma de pagamento</div>
                            <div class="value">${escapeHtml(receipt.payment_method_label || 'Não informada')}</div>
                        </div>
                    </section>
                    <div class="signature">${escapeHtml(receipt.psychologist?.name)}</div>
                </main>
                <script>window.print();<\/script>
            </body>
        </html>
    `);
    receiptWindow.document.close();
};

onMounted(() => {
    fetchDashboard();
});
</script>

<template>
    <div class="page-shell">
        <header class="mb-5 rounded-2xl border border-[#e2ddd3] bg-white/95 p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="section-kicker">Financeiro</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-normal text-slate-950">Recebimentos, cobranças e previsão</h1>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-[#58635f]">
                        Controle pagamentos por sessão, acompanhe inadimplência e emita recibos sem sair do fluxo da agenda.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <RouterLink
                        :to="{ name: 'home' }"
                        class="btn-secondary"
                    >
                        <AppIcon name="ChevronLeft" class="size-4" />
                        Dashboard
                    </RouterLink>
                    <button
                        class="btn-primary disabled:opacity-60"
                        type="button"
                        :disabled="loading"
                        @click="fetchDashboard"
                    >
                        <AppIcon name="RefreshCcw" class="size-4" :class="{ 'animate-spin': loading }" />
                        Atualizar
                    </button>
                </div>
            </div>
        </header>

        <div class="space-y-5">
            <Alert v-if="errorMessage" status="error">{{ errorMessage }}</Alert>
            <Alert v-if="successMessage" status="success">{{ successMessage }}</Alert>

            <section v-if="loading" class="rounded-lg border border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500 shadow-sm">
                Carregando painel financeiro...
            </section>

            <template v-else>
                <section class="sticky top-0 z-10 -mx-4 border-y border-[#e2ddd3] bg-[#f4f1eb]/95 px-4 py-3 backdrop-blur lg:static lg:mx-0 lg:rounded-2xl lg:border lg:bg-white/95 lg:shadow-sm">
                    <div class="flex gap-2 overflow-x-auto pb-1 lg:pb-0" role="tablist" aria-label="Categorias financeiras">
                        <button
                            v-for="section in financeSections"
                            :key="section.id"
                            class="inline-flex shrink-0 items-center gap-2 rounded-lg border px-4 py-2 text-sm font-semibold transition"
                            :class="activeSection === section.id
                                ? 'border-[#3f4f46] bg-[#3f4f46] text-white shadow-sm'
                                : 'border-[#e2ddd3] bg-white text-[#58635f] hover:border-[#c9c1b3] hover:bg-[#f8f5ef] hover:text-[#1f2522]'"
                            type="button"
                            role="tab"
                            :aria-selected="activeSection === section.id"
                            @click="activeSection = section.id"
                        >
                            {{ section.label }}
                            <span
                                v-if="section.count !== null"
                                class="rounded-full px-2 py-0.5 text-xs"
                                :class="activeSection === section.id ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                            >
                                {{ section.count }}
                            </span>
                        </button>
                    </div>
                </section>

                <section class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-5 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-lg font-semibold text-slate-950">{{ sectionTitle.title }}</p>
                            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">{{ sectionTitle.description }}</p>
                        </div>
                        <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            {{ dashboard.period?.label }}
                        </span>
                    </div>
                </section>

                <template v-if="activeSection === 'overview'">
                    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <article
                            v-for="card in summaryCards"
                            :key="card.id"
                            :class="['rounded-xl border p-4 shadow-sm', card.tone]"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase text-current/60">{{ card.label }}</p>
                                    <p class="mt-2 text-2xl font-semibold tracking-normal sm:text-3xl">{{ card.value }}</p>
                                    <p class="mt-2 text-sm text-current/70">{{ card.detail }}</p>
                                </div>
                                <AppIcon :name="card.icon" class="size-8 shrink-0 text-current/70" :stroke-width="1.7" />
                            </div>
                        </article>
                    </section>

                    <section>
                        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-950">Saúde financeira</p>
                                    <p class="mt-1 text-sm text-slate-500">Recebimento sobre o valor previsto do mês.</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
                                    {{ formatPercent(collectionRate) }}
                                </span>
                            </div>
                            <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: collectionWidth }"></div>
                            </div>
                            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <dt class="text-xs font-semibold uppercase text-slate-500">Previsto</dt>
                                    <dd class="mt-1 font-semibold text-slate-950">{{ formatMoney(dashboard.summary?.expected_value) }}</dd>
                                </div>
                                <div class="rounded-lg bg-emerald-50 p-3">
                                    <dt class="text-xs font-semibold uppercase text-emerald-700">Recebido</dt>
                                    <dd class="mt-1 font-semibold text-emerald-950">{{ formatMoney(dashboard.summary?.received_value) }}</dd>
                                </div>
                                <div class="rounded-lg bg-amber-50 p-3">
                                    <dt class="text-xs font-semibold uppercase text-amber-700">Pendente</dt>
                                    <dd class="mt-1 font-semibold text-amber-950">{{ formatMoney(dashboard.summary?.pending_value) }}</dd>
                                </div>
                            </dl>
                        </article>
                    </section>
                </template>

                <section v-else-if="activeSection === 'billing'" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 grid gap-3 md:grid-cols-[1fr_220px_auto] md:items-end">
                        <label class="space-y-1.5">
                            <span class="text-sm font-medium text-slate-700">Filtrar por paciente</span>
                            <input
                                v-model="billingFilters.patientName"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                placeholder="Nome do paciente"
                                type="search"
                            />
                        </label>

                        <label class="space-y-1.5">
                            <span class="text-sm font-medium text-slate-700">Mês e ano</span>
                            <input
                                v-model="selectedMonth"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-800 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                type="month"
                                @change="fetchDashboard"
                            />
                        </label>

                        <button
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-800"
                            type="button"
                            @click="billingFilters.patientName = ''; fetchDashboard()"
                        >
                            Limpar filtros
                        </button>
                    </div>

                    <div v-if="hasBillingItems" class="space-y-3">
                        <article
                            v-for="item in billingItems"
                            :key="`${item.billingStatus}-${item.id}`"
                            :class="['rounded-lg border border-slate-200 p-4 transition', item.billingCardClass]"
                        >
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-slate-950">{{ item.patient.name }}</p>
                                        <span :class="['rounded-full border px-2 py-0.5 text-xs font-semibold', item.billingStatusClass]">
                                            {{ item.billingStatusLabel }}
                                        </span>
                                        <span
                                            v-if="item.receipt_number"
                                            class="rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-600"
                                        >
                                            {{ item.receipt_number }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">{{ item.billingDateLabel }}</p>
                                </div>
                                <div class="flex items-center justify-between gap-3 md:justify-end md:text-right">
                                    <div>
                                        <p class="text-lg font-semibold text-slate-950">{{ formatMoney(item.price) }}</p>
                                        <p class="text-xs text-slate-500">{{ item.billingMeta }}</p>
                                    </div>
                                    <button
                                        :class="['inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition', item.billingActionClass]"
                                        type="button"
                                        @click="openReceivable(item)"
                                    >
                                        {{ item.billingActionLabel }}
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                    <p v-else class="rounded-lg border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                        Nenhuma cobrança ou consulta paga encontrada para os filtros selecionados.
                    </p>
                </section>

                <section v-else-if="activeSection === 'wallet'" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div v-if="hasWallet" class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-3 py-3 font-semibold">Paciente</th>
                                    <th class="px-3 py-3 font-semibold">Status</th>
                                    <th class="px-3 py-3 text-right font-semibold">Aberto</th>
                                    <th class="px-3 py-3 text-right font-semibold">Recebido</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in dashboard.wallet" :key="item.patient.id ?? item.patient.name" class="hover:bg-slate-50">
                                    <td class="px-3 py-3">
                                        <p class="font-medium text-slate-950">{{ item.patient.name }}</p>
                                        <p class="text-xs text-slate-500">{{ item.appointments }} atendimento(s)</p>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span :class="['inline-flex rounded-full border px-2 py-1 text-xs font-semibold', walletBadge(item.status).class]">
                                            {{ walletBadge(item.status).label }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right font-semibold text-slate-950">{{ formatMoney(item.open_value) }}</td>
                                    <td class="px-3 py-3 text-right text-slate-600">{{ formatMoney(item.received_value) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="rounded-lg border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                        Sem movimentação financeira no período.
                    </p>
                </section>

                <section v-else class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <form @submit.prevent="saveSettings">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium text-slate-700">Tipo da chave Pix</span>
                                <select
                                    v-model="settingsForm.pixKeyType"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                >
                                    <option value="">Não informado</option>
                                    <option v-for="option in pixKeyTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </label>

                            <label class="space-y-1.5 xl:col-span-2">
                                <span class="text-sm font-medium text-slate-700">Chave Pix</span>
                                <input
                                    v-model="settingsForm.pixKey"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                    placeholder="exemplo@email.com"
                                />
                            </label>

                            <label class="space-y-1.5">
                                <span class="text-sm font-medium text-slate-700">Prazo</span>
                                <input
                                    v-model.number="settingsForm.paymentTermsDays"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                    min="0"
                                    max="90"
                                    type="number"
                                />
                            </label>

                            <label class="space-y-1.5">
                                <span class="text-sm font-medium text-slate-700">Prefixo recibo</span>
                                <input
                                    v-model="settingsForm.receiptPrefix"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm uppercase focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                    maxlength="20"
                                />
                            </label>

                            <label class="space-y-1.5 md:col-span-2 xl:col-span-5">
                                <span class="text-sm font-medium text-slate-700">Link padrão de pagamento</span>
                                <input
                                    v-model="settingsForm.defaultPaymentLink"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                    placeholder="https://..."
                                    type="url"
                                />
                            </label>

                            <div class="space-y-3 md:col-span-2 xl:col-span-5">
                                <label class="block space-y-1.5">
                                    <span class="text-sm font-medium text-slate-700">Template da mensagem de cobrança</span>
                                    <textarea
                                        v-model="settingsForm.chargeMessageTemplate"
                                        class="min-h-36 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                    ></textarea>
                                </label>

                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-800">Exemplo padrão</p>
                                    <p v-pre class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">Olá {{paciente}}, tudo bem?
Estou enviando a cobrança da sessão de {{data_hora}} no valor de {{valor}}.
Link para pagamento: {{link_pagamento}}
Pix: {{pix}}
Obrigado(a).</p>
                                </div>

                                <p v-pre class="text-xs leading-5 text-slate-500">Campos disponíveis: {{paciente}}, {{data_hora}}, {{valor}}, {{link_pagamento}} e {{pix}}. Se ficar em branco, a mensagem padrão atual será usada.</p>
                            </div>
                        </div>

                        <div class="mt-3 grid gap-2 text-xs text-rose-600 md:grid-cols-2">
                            <p v-for="(message, key) in settingsErrors" v-show="message" :key="key">{{ message }}</p>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button
                                class="btn-primary disabled:opacity-60"
                                type="submit"
                                :disabled="settingsSaving"
                            >
                                <AppIcon v-if="settingsSaving" name="LoaderCircle" class="size-4 animate-spin" />
                                Salvar recebimento
                            </button>
                        </div>
                    </form>
                </section>
            </template>
        </div>

        <Modal v-model="actionModalOpen">
            <div v-if="selectedReceivable" class="max-h-[86vh] overflow-y-auto p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-cyan-700">Cobrança</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-950">{{ selectedReceivable.patient.name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ formatDateTime(selectedReceivable.start_at) }}</p>
                    </div>
                    <button
                        class="rounded-full border border-slate-200 p-2 text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                        type="button"
                        @click="closeReceivable"
                    >
                        <AppIcon name="X" class="size-5" />
                    </button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">Valor</p>
                        <p class="mt-1 text-lg font-semibold text-slate-950">{{ formatMoney(chargeForm.price) }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">Vencimento</p>
                        <p class="mt-1 text-lg font-semibold text-slate-950">{{ formatDate(chargeForm.paymentDueAt) }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">Status</p>
                        <p class="mt-1 text-lg font-semibold text-slate-950">{{ receivableBadge(selectedReceivable).label }}</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="space-y-1.5">
                        <span class="text-sm font-medium text-slate-700">Valor da cobrança</span>
                        <input
                            v-model="chargeForm.price"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                            min="0"
                            step="0.01"
                            type="number"
                        />
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-sm font-medium text-slate-700">Data de vencimento</span>
                        <div class="flex gap-2">
                            <input
                                v-model="chargeForm.paymentDueAt"
                                class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                inputmode="numeric"
                                maxlength="10"
                                placeholder="dd/mm/aaaa"
                                type="text"
                                @input="applyBrazilianDateMask"
                            />
                            <input
                                v-model="selectedPaymentDueAtIso"
                                aria-label="Selecionar data de vencimento"
                                class="w-12 rounded-lg border border-slate-200 px-2 py-2 text-sm text-transparent focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                lang="pt-BR"
                                type="date"
                            />
                        </div>
                    </label>
                    <label class="space-y-1.5 sm:col-span-2">
                        <span class="text-sm font-medium text-slate-700">Observações internas</span>
                        <textarea
                            v-model="chargeForm.paymentNotes"
                            class="min-h-20 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                            placeholder="Ex.: combinado para pagar por Pix após a sessão."
                        ></textarea>
                    </label>
                </div>

                <div class="mt-5 rounded-lg border border-cyan-100 bg-cyan-50 p-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-cyan-950">Mensagem pronta para cobrança</p>
                            <p class="mt-1 whitespace-pre-line text-sm leading-6 text-cyan-900">{{ chargeMessage }}</p>
                        </div>
                        <button
                            class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-cyan-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-cyan-800"
                            type="button"
                            @click="copyText(chargeMessage, 'Mensagem')"
                        >
                            Copiar
                        </button>
                    </div>
                </div>

                <p v-if="actionMessage" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ actionMessage }}
                </p>
                <p v-if="copyMessage" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ copyMessage }}
                </p>

                <div class="mt-5 flex flex-wrap justify-between gap-3 border-t border-slate-100 pt-5">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="dashboard.settings?.pix_key"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-800"
                            type="button"
                            @click="copyText(dashboard.settings.pix_key, 'Chave Pix')"
                        >
                            Copiar Pix
                        </button>
                        <button
                            v-if="chargeForm.paymentLink"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-800"
                            type="button"
                            @click="copyText(chargeForm.paymentLink, 'Link')"
                        >
                            Copiar link
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                            type="button"
                            :disabled="actionSaving"
                            @click="savePayment(false)"
                        >
                            Salvar cobrança
                        </button>
                        <button
                            v-if="!selectedReceivable.is_paid"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60"
                            type="button"
                            :disabled="actionSaving"
                            @click="savePayment(true)"
                        >
                            Marcar recebido
                        </button>
                        <button
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-60"
                            type="button"
                            :disabled="receiptLoading"
                            @click="issueReceipt"
                        >
                            {{ selectedReceivable.receipt_number ? 'Abrir recibo' : 'Emitir recibo' }}
                        </button>
                    </div>
                </div>

                <section v-if="receiptPreview" class="mt-5 rounded-lg border border-slate-200 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500">Recibo {{ receiptPreview.number }}</p>
                            <p class="mt-2 text-lg font-semibold text-slate-950">{{ receiptPreview.patient.name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ formatDateTime(receiptPreview.issued_at) }}</p>
                        </div>
                        <p class="text-2xl font-semibold text-emerald-700">{{ formatMoney(receiptPreview.amount) }}</p>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-800"
                            type="button"
                            @click="printReceipt"
                        >
                            Imprimir recibo
                        </button>
                    </div>
                </section>
            </div>
        </Modal>
    </div>
</template>
