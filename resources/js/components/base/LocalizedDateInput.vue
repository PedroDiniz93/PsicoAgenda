<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    mode: { type: String, default: 'date' },
});

const emit = defineEmits(['update:modelValue']);
const displayValue = ref('');

const pad = (value) => String(value).padStart(2, '0');

const formatValue = (value) => {
    if (!value) return '';
    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?/);
    if (!match) return value;
    const [, year, month, day, hours, minutes] = match;
    return props.mode === 'datetime' && hours
        ? `${day}/${month}/${year} ${hours}:${minutes}`
        : `${day}/${month}/${year}`;
};

const parseValue = (value) => {
    const normalized = String(value ?? '').trim();
    if (!normalized) return '';
    const dateMatch = normalized.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!dateMatch) return null;
    const [, day, month, year] = dateMatch;
    if (props.mode === 'date') return `${year}-${month}-${day}`;

    const dateTimeMatch = normalized.match(/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})$/);
    if (!dateTimeMatch) return null;
    const [, dateDay, dateMonth, dateYear, hours, minutes] = dateTimeMatch;
    if (Number(hours) > 23 || Number(minutes) > 59) return null;
    return `${dateYear}-${dateMonth}-${dateDay}T${hours}:${minutes}`;
};

watch(
    () => props.modelValue,
    (value) => {
        const formatted = formatValue(value);
        if (formatted !== displayValue.value) displayValue.value = formatted;
    },
    { immediate: true }
);

const handleInput = (event) => {
    displayValue.value = event.target.value;
    const parsed = parseValue(displayValue.value);
    if (parsed !== null) emit('update:modelValue', parsed);
};
</script>

<template>
    <input
        v-bind="$attrs"
        :value="displayValue"
        :placeholder="mode === 'datetime' ? 'dd/mm/aaaa hh:mm' : 'dd/mm/aaaa'"
        inputmode="numeric"
        type="text"
        @input="handleInput"
    />
</template>
