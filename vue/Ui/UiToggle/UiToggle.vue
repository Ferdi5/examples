<template>
  <label class="ui-toggle">
    <input
      v-model="computedModelValue"
      type="checkbox"
    />
    <span class="switch round" />
  </label>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: [Boolean, Number],
    default: false
  }
});

const emit = defineEmits(['update:modelValue']);

const computedModelValue = computed({
  get() {
    return !!props.modelValue;
  },
  set(value) {
    emit('update:modelValue', value);
  }
});
</script>

<style scoped lang="scss" src="./UiToggle.scss" />
