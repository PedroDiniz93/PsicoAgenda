<script setup lang="ts">
defineProps<{
    adminForm: any;
    adminErrors: any;
    adminMessage: string;
    adminMessageType: string;
    adminLoading: boolean;
    adminSaving: boolean;
    adminResendingId: number | null;
    adminPsychologists: Array<any>;
    editingPsychologistId: number | null;
    timezoneOptions: Array<{ label: string; value: string }>;
    sessionDurationOptions: Array<{ label: string; value: number }>;
}>();

defineEmits<{
    submit: [];
    edit: [psychologist: any];
    resendEmailVerification: [psychologist: any];
    reset: [];
    refresh: [];
}>();
</script>

<template>
    <section class="grid gap-5 lg:grid-cols-[0.95fr_1.05fr]">
        <form class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="$emit('submit')">
            <div class="flex flex-col gap-1 border-b border-slate-100 pb-5">
                <p class="text-sm font-semibold text-cyan-700">Administração</p>
                <h2 class="text-xl font-semibold text-slate-950">
                    {{ editingPsychologistId ? 'Editar psicólogo' : 'Novo psicólogo' }}
                </h2>
                <p class="text-sm text-slate-500">Crie o acesso e o perfil profissional usados na aplicação.</p>
            </div>

            <div class="mt-5 space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        Nome completo
                        <input
                            v-model="adminForm.name"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            type="text"
                            required
                        />
                        <span v-if="adminErrors.name" class="mt-1 block text-xs text-rose-600">{{ adminErrors.name }}</span>
                    </label>

                    <label class="block text-sm font-semibold text-slate-700">
                        E-mail de login
                        <input
                            v-model="adminForm.userEmail"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            type="email"
                            required
                        />
                        <span v-if="adminErrors.userEmail" class="mt-1 block text-xs text-rose-600">{{ adminErrors.userEmail }}</span>
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        Senha
                        <input
                            v-model="adminForm.password"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            type="password"
                            :required="!editingPsychologistId"
                            :placeholder="editingPsychologistId ? 'Deixe vazio para manter' : ''"
                        />
                        <span v-if="adminErrors.password" class="mt-1 block text-xs text-rose-600">{{ adminErrors.password }}</span>
                    </label>

                    <label class="block text-sm font-semibold text-slate-700">
                        Nível
                        <select
                            v-model="adminForm.role"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                        >
                            <option value="psychologist">Psicólogo</option>
                            <option value="admin">Admin</option>
                        </select>
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        E-mail profissional
                        <input
                            v-model="adminForm.email"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            type="email"
                        />
                        <span v-if="adminErrors.email" class="mt-1 block text-xs text-rose-600">{{ adminErrors.email }}</span>
                    </label>

                    <label class="block text-sm font-semibold text-slate-700">
                        Telefone
                        <input
                            v-model="adminForm.phone"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            type="tel"
                        />
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        Fuso horário
                        <select
                            v-model="adminForm.timezone"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            required
                        >
                            <option v-for="option in timezoneOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </label>

                    <label class="block text-sm font-semibold text-slate-700">
                        Duração padrão
                        <select
                            v-model.number="adminForm.sessionDuration"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                            required
                        >
                            <option v-for="option in sessionDurationOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </label>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">
                        Atende online
                        <input v-model="adminForm.allowOnline" class="size-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" type="checkbox" />
                    </label>
                    <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">
                        Atende presencial
                        <input v-model="adminForm.allowInPerson" class="size-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" type="checkbox" />
                    </label>
                </div>

                <p
                    v-if="adminMessage"
                    class="rounded-lg border px-4 py-3 text-sm"
                    :class="adminMessageType === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700'"
                >
                    {{ adminMessage }}
                </p>

                <div class="flex flex-wrap justify-end gap-3">
                    <button
                        v-if="editingPsychologistId"
                        class="inline-flex h-10 items-center rounded-lg border border-slate-200 px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        type="button"
                        @click="$emit('reset')"
                    >
                        Cancelar edição
                    </button>
                    <button
                        class="inline-flex h-10 items-center rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        type="submit"
                        :disabled="adminSaving"
                    >
                        {{ adminSaving ? 'Salvando...' : editingPsychologistId ? 'Salvar alterações' : 'Criar psicólogo' }}
                    </button>
                </div>
            </div>
        </form>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Perfis cadastrados</h2>
                    <p class="text-sm text-slate-500">{{ adminPsychologists.length }} perfil(is)</p>
                </div>
                <button
                    class="inline-flex h-10 items-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    type="button"
                    :disabled="adminLoading"
                    @click="$emit('refresh')"
                >
                    Atualizar
                </button>
            </div>

            <div v-if="adminLoading" class="px-5 py-12 text-center text-sm text-slate-500">
                Carregando perfis...
            </div>
            <div v-else-if="!adminPsychologists.length" class="px-5 py-12 text-center text-sm text-slate-500">
                Nenhum psicólogo cadastrado.
            </div>
            <div v-else class="divide-y divide-slate-100">
                <article
                    v-for="psychologist in adminPsychologists"
                    :key="psychologist.id"
                    class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-slate-950">{{ psychologist.name }}</h3>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="psychologist.user?.role === 'admin' ? 'bg-cyan-50 text-cyan-700' : 'bg-slate-100 text-slate-600'"
                            >
                                {{ psychologist.user?.role === 'admin' ? 'Admin' : 'Psicólogo' }}
                            </span>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="psychologist.user?.email_verified_at ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                            >
                                {{ psychologist.user?.email_verified_at ? 'E-mail validado' : 'Validação pendente' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ psychologist.user?.email }}</p>
                        <p class="text-xs text-slate-400">{{ psychologist.timezone }} · {{ psychologist.session_duration }} min</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="!psychologist.user?.email_verified_at"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-amber-200 px-4 text-sm font-semibold text-amber-700 transition hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-60"
                            type="button"
                            :disabled="adminResendingId === psychologist.id"
                            @click="$emit('resendEmailVerification', psychologist)"
                        >
                            {{ adminResendingId === psychologist.id ? 'Reenviando...' : 'Reenviar validação' }}
                        </button>
                        <button
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-800"
                            type="button"
                            @click="$emit('edit', psychologist)"
                        >
                            Editar
                        </button>
                    </div>
                </article>
            </div>
        </section>
    </section>
</template>
