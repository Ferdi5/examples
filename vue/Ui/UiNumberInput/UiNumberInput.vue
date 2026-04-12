<template>
  <div
    v-if="props.innerLabel"
    class="relative w-full flex items-center"
  >
    <span class="absolute ml-2 pr-2 select-none font-medium text-gray-600 bg-white">
      {{ props.innerLabel }}
    </span>
    <UiInput
      v-model="computedModelValue"
      class="text-right"
      @focus="onFocus"
      @blur="onBlur"
      @keydown="onKeyDown"
      @keydown.enter="onKeyDownEnter"
      @paste="onPaste"
    />
  </div>
  <UiInput
    v-else
    v-model="computedModelValue"
    class="text-right"
    @focus="onFocus"
    @blur="onBlur"
    @keydown="onKeyDown"
    @keydown.enter="onKeyDownEnter"
    @paste="onPaste"
  />
</template>

<script setup>
import { computed, ref } from 'vue';
import UiInput from '@/components/Ui/UiInput/UiInput.vue';
import {
  useOnFocus,
  useOnKeyDown,
  useOnPaste
} from '@/components/Ui/UiNumberInput/UiNumberInput.js';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  innerLabel: {
    type: String,
    default: '',
    validator(value) {
      return ['', 'EUR', 'USD', 'GBP', '%'].indexOf(value) !== -1;
    }
  },
  decimals: {
    type: Number,
    default: 2
  },
  allowNegativeNumber: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'enter']);
const hasFocus = ref(false);

const formatOptions = {
  style: 'decimal',
  minimumFractionDigits: props.decimals,
  maximumFractionDigits: props.decimals
};

const computedModelValue = computed({
  get() {
    if (props.modelValue) {
      if (!hasFocus.value) {
        return formatNumber(props.modelValue.replace('.', ','), formatOptions);
      }

      return props.modelValue.replace('.', ',');
    }

    return props.modelValue;
  },
  set(value) {
    if (!value) {
      emit('update:modelValue');

      return;
    }

    if (!hasFocus.value) {
      value = formatNumber(value.replace('.', ','), formatOptions)
        .replaceAll('.', '')
        .replace(',', '.');

      if (isNaN(value)) {
        value = '';
      }
    } else {
      value = value.replaceAll('.', '').replace(',', '.');
    }

    emit('update:modelValue', value);
  }
});

function formatNumber(value) {
  value = value.replaceAll('.', '').replace(',', '.');

  return new Intl.NumberFormat('nl-NL', formatOptions).format(value);
}

function onBlur(event) {
  hasFocus.value = false;
  computedModelValue.value = event.target.value;
}

function onFocus(event) {
  useOnFocus(event);
  hasFocus.value = true;
}

function onPaste(event) {
  const validatedText = useOnPaste(event, props.decimals, props.allowNegativeNumber ? ['-'] : []);

  if (validatedText) {
    computedModelValue.value = validatedText;
  }
}

function onKeyDown(event) {
  computedModelValue.value = useOnKeyDown(
    event,
    props.decimals,
    props.allowNegativeNumber ? ['-'] : []
  );
}

function onKeyDownEnter(event) {
  hasFocus.value = false;
  computedModelValue.value = event.target.value;
  hasFocus.value = true;
  emit('enter');
}
</script>
