<script setup lang="ts">
defineProps<{
    integrationItems: Array<any>;
    googleConnected: boolean;
    googleProcessing: boolean;
    googleError: string;
    googleStatusMessage: string;
    googleStatusType: string;
    reminderSettings: any;
    reminderMessage: string;
    reminderMessageType: string;
    reminderSaving: boolean;
}>();

defineEmits<{
    connectGoogle: [];
    disconnectGoogle: [];
    submitReminders: [];
}>();
</script>

<template>
    <section class="grid gap-4 lg:grid-cols-[0.8fr_1.2fr]">
        <aside class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">Status das integrações</h2>
            <div class="mt-4 space-y-3">
                <div v-for="item in integrationItems" :key="item.label" class="flex items-center justify-between rounded-lg border border-slate-100 px-4 py-3">
                    <span class="text-sm font-semibold text-slate-700">{{ item.label }}</span>
                    <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', item.class]">{{ item.status }}</span>
                </div>
            </div>
        </aside>

        <div class="space-y-4">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Google Calendar</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ googleConnected ? 'Conta conectada para sincronização de agenda.' : 'Conecte uma conta para sincronizar compromissos.' }}
                        </p>
                    </div>
                    <button
                        v-if="googleConnected"
                        class="inline-flex h-10 items-center justify-center rounded-lg border border-rose-200 px-4 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="googleProcessing"
                        @click="$emit('disconnectGoogle')"
                    >
                        <svg v-if="googleProcessing" class="mr-2 size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Desconectar
                    </button>
                    <button
                        v-else
                        class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="googleProcessing"
                        @click="$emit('connectGoogle')"
                    >
                        <svg v-if="googleProcessing" class="mr-2 size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Conectar
                    </button>
                </div>
                <p v-if="googleError" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ googleError }}
                </p>
                <p
                    v-if="googleStatusMessage"
                    class="mt-4 rounded-lg border px-4 py-3 text-sm"
                    :class="
                        googleStatusType === 'success'
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                            : 'border-cyan-200 bg-cyan-50 text-cyan-700'
                    "
                >
                    {{ googleStatusMessage }}
                </p>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Lembretes automáticos</h2>
                    <p class="mt-1 text-sm text-slate-500">Canais e antecedência para confirmação de sessões.</p>
                </div>

                <form class="mt-5 space-y-4" @submit.prevent="$emit('submitReminders')">
                    <label class="block text-sm font-semibold text-slate-700" for="reminder-days-before">
                        Dias antes
                        <input
                            id="reminder-days-before"
                            v-model.number="reminderSettings.daysBefore"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100 sm:max-w-xs"
                            type="number"
                            min="0"
                            max="30"
                        />
                    </label>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">
                            WhatsApp
                            <input v-model="reminderSettings.whatsappEnabled" class="size-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" type="checkbox" />
                        </label>
                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">
                            E-mail
                            <input v-model="reminderSettings.emailEnabled" class="size-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" type="checkbox" />
                        </label>
                    </div>

                    <p
                        v-if="reminderMessage"
                        class="rounded-lg border px-4 py-3 text-sm"
                        :class="
                            reminderMessageType === 'success'
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                : 'border-rose-200 bg-rose-50 text-rose-700'
                        "
                    >
                        {{ reminderMessage }}
                    </p>

                    <div class="flex justify-end">
                        <button
                            class="inline-flex h-10 items-center rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                            type="submit"
                            :disabled="reminderSaving"
                        >
                            <svg v-if="reminderSaving" class="mr-2 size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            Salvar preferências
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</template>
