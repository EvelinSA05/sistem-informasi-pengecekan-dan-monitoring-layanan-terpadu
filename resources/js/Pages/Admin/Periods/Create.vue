<script setup>
import Form from './Form.vue';

const props = defineProps({
    testers:      { type: Array, default: () => [] },
    services:     { type: Array, default: () => [] },
    monthOptions: { type: Array, default: () => [] },
    currentMonth: { type: Number, default: 8 },
    currentYear:  { type: Number, default: 2026 },
});
</script>

<template>
    <Form
        :is-edit="false"
        :period="null"
        :testers="testers"
        :services="services"
        :month-options="monthOptions"
        :initial-month="currentMonth"
        :initial-year="currentYear"
        :initial-tester-ids="[]"
    />
</template>
