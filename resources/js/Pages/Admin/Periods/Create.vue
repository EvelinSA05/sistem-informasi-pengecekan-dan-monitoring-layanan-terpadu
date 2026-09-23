<script setup>
import Form from './Form.vue';

const props = defineProps({
 testers: { type: Array, default: () => [] },
 services: { type: Array, default: () => [] },
 initialDate: { type: String, default: '' },
});
</script>

<template>
 <Form
 :is-edit="false"
 :period="null"
 :testers="testers"
 :services="services"
 :initial-date="initialDate"
 :initial-tester-ids="[]"
 />
</template>
