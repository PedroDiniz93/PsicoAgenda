<script setup lang="ts">
import AppIcon from '../base/AppIcon.vue';

defineProps<{
    profileForm: any;
    profileErrors: any;
    profileLoading: boolean;
    profileSaving: boolean;
    profileMessage: string;
    profileMessageType: string;
    timezoneOptions: Array<{ label: string; value: string }>;
    sessionDurationOptions: Array<{ label: string; value: number }>;
}>();

defineEmits<{
    submit: [];
}>();
</script>

<template>
    <section class="rounded-2xl border border-[#e2ddd3] bg-white/95 p-6 shadow-sm">
        <div class="flex flex-col gap-1 border-b border-slate-100 pb-5">
            <h2 class="text-xl font-semibold text-slate-950">Perfil profissional</h2>
            <p class="text-sm text-[#58635f]">Dados usados nos agendamentos e comunicações com pacientes.</p>
        </div>

        <div v-if="profileLoading" class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
            Carregando informações do perfil...
        </div>

        <form v-else class="mt-5 space-y-5" @submit.prevent="$emit('submit')">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block text-sm font-semibold text-slate-700" for="psychologist-name">
                    Nome completo
                    <input
                        id="psychologist-name"
                        v-model="profileForm.name"
                        class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                        type="text"
                        placeholder="Nome do psicólogo"
                        required
                    />
                    <span v-if="profileErrors.name" class="mt-1 block text-xs text-rose-600">{{ profileErrors.name }}</span>
                </label>

                <label class="block text-sm font-semibold text-slate-700" for="psychologist-email">
                    E-mail profissional
                    <input
                        id="psychologist-email"
                        v-model="profileForm.email"
                        class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                        type="email"
                        placeholder="email@clinica.com"
                    />
                    <span v-if="profileErrors.email" class="mt-1 block text-xs text-rose-600">{{ profileErrors.email }}</span>
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <label class="block text-sm font-semibold text-slate-700" for="psychologist-phone">
                    Telefone
                    <input
                        id="psychologist-phone"
                        v-model="profileForm.phone"
                        class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                        type="tel"
                        placeholder="(00) 00000-0000"
                    />
                    <span v-if="profileErrors.phone" class="mt-1 block text-xs text-rose-600">{{ profileErrors.phone }}</span>
                </label>

                <label class="block text-sm font-semibold text-slate-700" for="psychologist-timezone">
                    Fuso horário
                    <select
                        id="psychologist-timezone"
                        v-model="profileForm.timezone"
                        class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                        required
                    >
                        <option v-for="option in timezoneOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <span v-if="profileErrors.timezone" class="mt-1 block text-xs text-rose-600">{{ profileErrors.timezone }}</span>
                </label>

                <label class="block text-sm font-semibold text-slate-700" for="session-duration">
                    Duração padrão
                    <select
                        id="session-duration"
                        v-model.number="profileForm.sessionDuration"
                        class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                        required
                    >
                        <option v-for="option in sessionDurationOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <span v-if="profileErrors.sessionDuration" class="mt-1 block text-xs text-rose-600">
                        {{ profileErrors.sessionDuration }}
                    </span>
                </label>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">
                    Atende online
                    <input v-model="profileForm.allowOnline" class="size-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" type="checkbox" />
                </label>
                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">
                    Atende presencial
                    <input v-model="profileForm.allowInPerson" class="size-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" type="checkbox" />
                </label>
            </div>

            <p
                v-if="profileMessage"
                class="rounded-lg border px-4 py-3 text-sm"
                :class="profileMessageType === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700'"
            >
                {{ profileMessage }}
            </p>

            <div class="flex justify-end">
                <button
                    class="inline-flex h-10 items-center rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                    type="submit"
                    :disabled="profileSaving"
                >
                    <AppIcon v-if="profileSaving" name="LoaderCircle" class="mr-2 size-4 animate-spin" />
                    Salvar perfil
                </button>
            </div>
        </form>
    </section>
</template>
