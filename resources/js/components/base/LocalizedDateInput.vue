<script setup>
import { ref, watch } from 'vue';
import AppIcon from './AppIcon.vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: String, default: '' },
    mode: { type: String, default: 'date' },
    min: { type: String, default: '' },
    max: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);
const displayValue = ref('');
const nativeInput = ref(null);

const isValidDateParts = (year, month, day) => {
    const date = new Date(year, month - 1, day);
    return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day;
};

const isValidTimeParts = (hours, minutes) => hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59;

const formatValue = (value) => {
    if (!value) return '';
    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?/);
    if (!match) return value;

    const [, year, month, day, hours, minutes] = match;
    if (!isValidDateParts(Number(year), Number(month), Number(day))) return value;

    return props.mode === 'datetime' && hours
        ? `${day}/${month}/${year} ${hours}:${minutes}`
        : `${day}/${month}/${year}`;
};

const parseValue = (value) => {
    const normalized = String(value ?? '').trim();
    if (!normalized) return '';

    const dateTimeMatch = normalized.match(/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})$/);
    if (props.mode === 'datetime') {
        if (!dateTimeMatch) return null;

        const [, day, month, year, hours, minutes] = dateTimeMatch;
        if (!isValidDateParts(Number(year), Number(month), Number(day))) return null;
        if (!isValidTimeParts(Number(hours), Number(minutes))) return null;

        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    const dateMatch = normalized.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!dateMatch) return null;

    const [, day, month, year] = dateMatch;
    if (!isValidDateParts(Number(year), Number(month), Number(day))) return null;

    return `${year}-${month}-${day}`;
};

const toNativeValue = (value) => {
    if (!value) return '';
    const match = String(value).match(/^(\d{4}-\d{2}-\d{2})(?:T(\d{2}:\d{2}))?/);
    if (!match) return '';
    return props.mode === 'datetime' && match[2] ? `${match[1]}T${match[2]}` : match[1];
};

watch(
    () => props.modelValue,
    (value) => {
        const formatted = formatValue(value);
        if (formatted !== displayValue.value) displayValue.value = formatted;
        if (nativeInput.value && nativeInput.value.value !== toNativeValue(value)) {
            nativeInput.value.value = toNativeValue(value);
        }
    },
    { immediate: true }
);

const handleInput = (event) => {
    displayValue.value = event.target.value;
    const parsed = parseValue(displayValue.value);
    emit('update:modelValue', parsed ?? '');
};

const openPicker = () => {
    if (!nativeInput.value) return;

    if (typeof nativeInput.value.showPicker === 'function') {
        try {
            nativeInput.value.showPicker();
            return;
        } catch {
            // Fallback for browsers that expose showPicker but block it for this input.
        }
    }

    nativeInput.value.click();
};

const handleNativeChange = (event) => {
    const value = event.target.value;
    if (!value) {
        emit('update:modelValue', '');
        return;
    }

    const normalized = props.mode === 'datetime' ? value : value.slice(0, 10);
    displayValue.value = formatValue(normalized);
    emit('update:modelValue', normalized);
};
</script>

<template>
    <div class="relative flex items-center">
        <input
            v-bind="$attrs"
            :value="displayValue"
            :placeholder="mode === 'datetime' ? 'dd/mm/aaaa hh:mm' : 'dd/mm/aaaa'"
            class="w-full pe-11"
            inputmode="numeric"
            type="text"
            @input="handleInput"
        />
        <button
            class="absolute end-2 inline-flex size-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1"
            type="button"
            :aria-label="mode === 'datetime' ? 'Escolher data e horário' : 'Escolher data'"
            :title="mode === 'datetime' ? 'Escolher data e horário' : 'Escolher data'"
            @click="openPicker"
        >
            <AppIcon name="CalendarDays" class="size-4" :aria-hidden="false" />
        </button>
        <input
            ref="nativeInput"
            class="pointer-events-none absolute h-px w-px opacity-0"
            :min="min"
            :max="max"
            :value="toNativeValue(modelValue)"
            :aria-label="mode === 'datetime' ? 'Seletor de data e horário' : 'Seletor de data'"
            :type="mode === 'datetime' ? 'datetime-local' : 'date'"
            tabindex="-1"
            @change="handleNativeChange"
        />
    </div>
</template>
