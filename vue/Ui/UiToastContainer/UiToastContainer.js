import { ref } from 'vue';

const toasts = ref([]);
const toast = {
  show: false,
  type: '',
  message: ''
};

export function useSetToast(type, message) {
  toast.type = type;
  toast.message = message;
  toast.show = true;

  addToast();

  return toast;
}

export function useSetToasts(value) {
  toasts.value = value;

  return toasts;
}

export function useGetToasts() {
  return toasts;
}

function addToast() {
  toasts.value = [...toasts.value, structuredClone(toast)];
}
