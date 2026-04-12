<template>
  <div
    v-show="toasts.length"
    class="ui-toast-container"
  >
    <TransitionGroup name="toast">
      <UiToast
        v-for="(toast, key) in toasts"
        :key="key"
        :toast-key="key"
        :type="toast.type"
        :message="toast.message"
        :class="{ 'visibility-hidden': !toast.show }"
        @mount="toggleToast"
      />
    </TransitionGroup>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import UiToast from '@/components/Ui/UiToast/UiToast.vue';
import { useGetToasts, useSetToasts } from '@/components/Ui/UiToastContainer/UiToastContainer.js';

const toasts = ref(useGetToasts());

function toggleToast(key) {
  toasts.value[key].show = true;

  setTimeout(() => {
    toasts.value[key].show = false;
  }, 3000);

  setTimeout(() => {
    const toastShowIndex = toasts.value.findIndex((toast) => toast.show);

    if (toastShowIndex === -1) {
      useSetToasts([]);
    }
  }, 3300);
}
</script>

<style scoped lang="scss" src="./UiToastContainer.scss" />
