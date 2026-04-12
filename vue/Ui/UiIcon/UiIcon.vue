<template>
  <i
    class="text-gray-600"
    :class="[
      icon,
      {
        'text-gray-400': props.disabled,
        'hover:text-gray-700': !props.disabled,
        'cursor-not-allowed': props.disabled
      }
    ]"
  />
</template>

<script setup>
const props = defineProps({
  icon: {
    type: String,
    default: null
  },
  disabled: {
    type: Boolean,
    default: false
  }
});
</script>
