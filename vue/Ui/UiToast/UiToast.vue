<template>
  <div
    v-safe-html="props.message"
    class="toast"
    :class="`toast-${props.type}`"
  />
</template>

<script setup>
import { onMounted } from 'vue';

const props = defineProps({
  toastKey: {
    type: Number,
    default: null
  },
  type: {
    type: String,
    default: 'success',
    validator(value) {
      return ['success', 'error'].indexOf(value) !== -1;
    }
  },
  message: {
    type: String,
    default: ''
  }
});
const emit = defineEmits(['mount']);

onMounted(() => {
  emit('mount', props.toastKey);
});
</script>

<style scoped lang="scss" src="./UiToast.scss" />
