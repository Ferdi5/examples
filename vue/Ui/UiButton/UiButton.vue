<template>
  <button
    :type="props.type"
    :disabled="props.disabled"
    class="ui-button"
    :class="buttonStyle"
  >
    <slot />
  </button>
</template>

<script setup>
const props = defineProps({
  buttonStyle: {
    type: String,
    default: 'primary',
    validator(value) {
      return ['primary', 'secondary', 'primary-alt', 'danger'].indexOf(value) !== -1;
    },
    required: true
  },
  type: {
    type: String,
    default: 'button'
  },
  disabled: {
    type: Boolean,
    default: false
  }
});
</script>

<style scoped lang="scss" src="./UiButton.scss" />
