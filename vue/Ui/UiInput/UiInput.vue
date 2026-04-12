<template>
  <input
    v-model="computedModelValue"
    type="text"
    class="ui-input truncate"
    :class="[
      {
        'fade-in-text': computedModelValue,
        error: props.errors && props.errors.length
      }
    ]"
    @focus="onFocus"
    @blur="onBlur"
  />
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null
  },
  errors: {
    type: Array,
    default: () => []
  }
});
const emit = defineEmits(['update:modelValue', 'focus', 'blur']);

const computedModelValue = computed({
  get() {
    return props.modelValue;
  },
  set(value) {
    if (!value) {
      emit('update:modelValue', null);

      return;
    }
    emit('update:modelValue', value);
  }
});

function onFocus(event) {
  emit('focus', event);
}

function onBlur(event) {
  emit('blur', event);
}
</script>

<style scoped lang="scss" src="./UiInput.scss" />
