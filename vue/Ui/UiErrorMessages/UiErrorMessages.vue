<template>
  <div
    v-if="props.errors.length"
    class="text-red-500 whitespace-pre-wrap"
  >
    {{ props.errors.join('\r\n') }}
  </div>
</template>

<script setup>
const props = defineProps({
  errors: {
    type: Array,
    default: () => [],
    required: true
  }
});
</script>
