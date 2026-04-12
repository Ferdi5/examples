<template>
  <div
    v-click-outside="closeDropdown"
    class="ui-multiselect-search-input"
    @keydown.esc="blurAndCloseDropdown"
    @mousedown="setShowDropdown"
  >
    <div
      class="input-wrapper"
      :class="{ error: props.errors.length, disabled: props.disabled }"
      @click="focusContentEditable"
    >
      <UiIcon icon="fa-solid fa-angle-down icon-arrow-down" />
      <div class="items">
        <template v-for="(item, index) in computedModelValue">
          <div
            v-if="item?.name"
            :key="index"
            class="item"
            :class="{ 'fade-in-item': selectedItemId === item.id }"
          >
            <span class="truncate">{{ item.name }}</span>
            <UiIcon
              v-if="!props.disabled"
              icon="fa-solid fa-xmark icon-close"
              @mouseup="removeItemOnClick(item)"
              @mousedown.prevent
            />
          </div>
        </template>
        <div class="contenteditable-wrapper">
          <span
            ref="contentEditable"
            :contenteditable="!disabled"
            class="contenteditable"
            :class="{ 'contenteditable-placeholder': !computedModelValue?.length }"
            :placeholder="placeholder"
            @focus="openDropdown"
            @keyup="handleKeyup"
            @keydown.backspace="removeLastItem"
          />
        </div>
      </div>
    </div>
    <UiDropdown
      v-if="searchResults.length"
      :data="searchResults"
      :initial-selected-items="computedModelValue"
      :class="{ 'overflow-auto': searchResults.length > 8 }"
      class="w-100"
      @clicked-item="handleClickedItem"
    />
  </div>
</template>

<script setup>
import { sortBy } from 'lodash';
import { computed, ref } from 'vue';
import UiDropdown from '@/components/Ui/UiDropdown/UiDropdown.vue';
import UiIcon from '@/components/Ui/UiIcon/UiIcon.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({})
  },
  items: {
    type: Object,
    default: () => ({}),
    required: true
  },
  errors: {
    type: Array,
    default: () => []
  },
  initialPlaceholder: {
    type: String,
    default: ''
  },
  returnArrayWithIds: {
    type: Boolean,
    default: false
  },
  sortByName: {
    type: Boolean,
    default: true
  },
  disabled: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'add', 'remove']);

const searchResults = ref([]);
const placeholder = props.initialPlaceholder || 'Zoeken..';
const contentEditable = ref(null);
const showDropdown = ref(false);
const selectedItemId = ref(null);

const computedModelValue = computed({
  get() {
    if (props.returnArrayWithIds && props.modelValue) {
      const modelValue = props.modelValue.map((val) => {
        return {
          id: val,
          name: Object.entries(props.items).find(
            (entity) => parseInt(entity[0]) === val || entity[0] === val
          )?.[1]
        };
      });

      return modelValue.filter((item) => item?.name);
    }

    return props.modelValue;
  },
  set(value) {
    if (props.returnArrayWithIds) {
      value = value.map((val) => val.id);
    }
    emit('update:modelValue', value);
  }
});

const computedItems = computed(() => {
  const items = Object.entries(props.items).map((item) => {
    return {
      id: parseInt(item[0]) || item[0],
      name: item[1]
    };
  });

  if (props.sortByName) {
    return sortBy(items, 'name');
  }

  return items;
});

function handleKeyup(event) {
  searchItems(event.target.innerText);
}

function searchItems(value) {
  searchResults.value = filteredList(value);
}

function filteredList(val) {
  return computedItems.value?.filter((entity) =>
    entity.name.toLowerCase().includes(val.toLowerCase())
  );
}

function handleClickedItem(selectedItem) {
  if (props.disabled) {
    return;
  }

  selectedItemId.value = selectedItem.id;

  if (!isNaN(parseInt(selectedItem.id))) {
    selectedItem.id = parseInt(selectedItem.id);
  }

  const index = computedModelValue.value.findIndex((item) => {
    return item.id === selectedItem.id;
  });

  if (index !== -1) {
    removeItem(selectedItem.id);

    return;
  }

  computedModelValue.value = [
    ...computedModelValue.value,
    { id: selectedItem.id, name: selectedItem.name }
  ];

  contentEditable.value.innerText = '';
  searchItems(contentEditable.value.innerText);
  emit('add', selectedItem.id);
}

function removeItem(id) {
  if (props.disabled) {
    return;
  }

  if (!isNaN(parseInt(id))) {
    id = parseInt(id);
  }

  computedModelValue.value = computedModelValue.value.filter((item) => {
    return item.id !== id;
  });

  emit('remove', id);
}

function removeItemOnClick(selectedItem) {
  showDropdown.value = false;
  removeItem(selectedItem.id);
}

function removeLastItem() {
  if (contentEditable.value.innerText || !Object.keys(computedModelValue.value)?.length) {
    return;
  }

  removeItem(computedModelValue.value[computedModelValue.value.length - 1]?.id);
}

function setShowDropdown() {
  showDropdown.value = true;
}

function openDropdown() {
  if (showDropdown.value) {
    searchResults.value = filteredList(contentEditable.value.innerText);
  }
}

function closeDropdown() {
  if (!showDropdown.value) {
    searchResults.value.length = 0;

    return;
  }

  searchResults.value.length = 0;
  contentEditable.value.innerText = '';
  showDropdown.value = false;
}

function blurAndCloseDropdown() {
  closeDropdown();
  contentEditable.value.blur();
}

function focusContentEditable() {
  contentEditable.value.focus();
}
</script>

<style scoped lang="scss" src="./UiMultiSelectSearchInput.scss" />
