<template>
  <div
    v-click-outside="closeDropdown"
    class="ui-select-search-input"
    @keydown.esc="closeDropdown"
  >
    <div class="input-wrapper">
      <UiInput
        v-model="computedModelValue"
        :placeholder="placeholder"
        :errors="props.errors"
        @focus="onFocus"
        @blur="onBlur"
      />
    </div>
    <UiDropdown
      v-if="showDropdown"
      :data="computedSearchResults"
      :initial-selected-items="selectedItem"
      @clicked-item="handleClickedItem"
    />
  </div>
</template>

<script setup>
import { isObject, sortBy } from 'lodash';
import { computed, onBeforeMount, ref } from 'vue';
import UiDropdown from '@/components/Ui/UiDropdown/UiDropdown.vue';
import UiInput from '@/components/Ui/UiInput/UiInput.vue';

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null
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
  fieldName: {
    type: String,
    default: 'id',
    validator: (val) => ['id', 'name'].includes(val)
  },
  allowCustomEntry: {
    type: Boolean,
    default: false
  },
  searchNameAndKey: {
    type: Boolean,
    default: false
  },
  sortByName: {
    type: Boolean,
    default: true
  },
  itemKey: {
    type: Number,
    default: null
  },
  itemId: {
    type: Number,
    default: null
  }
});
const emit = defineEmits(['update:modelValue', 'focus', 'selectItem']);

const _ = { sortBy, isObject };
const placeholder = ref('');
const showDropdown = ref(false);
const originalModelValue = ref(null);
const selectedItem = ref(null);

const computedModelValue = computed({
  get() {
    if (!Object.keys(props.items).length) {
      if (Number.isInteger(props.modelValue)) {
        return null;
      }

      return props.modelValue;
    }

    if (props.itemId || props.itemId === 0) {
      const item = getSelectedItemById(props.itemId);

      if (item?.name === props.modelValue) {
        return item?.name;
      }
    }

    if (Number.isInteger(props.modelValue)) {
      const item = getSelectedItemById(props.modelValue);

      return item?.name;
    }

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

const computedSearchResults = computed(() => {
  return filteredList();
});

const computedEntities = computed(() => {
  if (!Object.entries(props.items).length && !props.items?.length) {
    return props.items;
  }

  let items = props.items;

  if (_.isObject(props.items) && !Array.isArray(props.items)) {
    items = Object.entries(props.items).map((entry) => {
      return { id: parseInt(entry[0]), name: entry[1] };
    });
  }

  if (props.sortByName) {
    items = _.sortBy(items, 'name');
  }

  return items;
});

onBeforeMount(() => {
  if (props.initialPlaceholder) {
    placeholder.value = props.initialPlaceholder;

    return;
  }

  placeholder.value = !props.allowCustomEntry ? 'Zoeken..' : 'Zoeken of toevoegen..';
});

function filteredList() {
  if (!computedModelValue.value) {
    return computedEntities.value;
  }
  const filteredArray = [];

  for (let i = 0, cachedLength = computedEntities.value.length; i < cachedLength; ++i) {
    if (
      computedEntities.value[i].name
        .toLowerCase()
        .includes(computedModelValue.value.toLowerCase()) ||
      (props.searchNameAndKey &&
        computedEntities.value[i].id.toString().includes(computedModelValue.value))
    ) {
      filteredArray.push(computedEntities.value[i]);
    }
  }

  return filteredArray;
}

function handleClickedItem(item) {
  if (selectedItem.value === item?.id) {
    setSelectedItemOnClick(null);

    return;
  }

  if (item?.name && props.fieldName === 'name') {
    setSelectedItemOnClick(item.name);
    closeDropdown();

    return;
  }

  if (item?.id) {
    setSelectedItemOnClick(item.id);
    closeDropdown();
  }
}

function onBlur(event) {
  if (!event?.relatedTarget?.classList.contains('item')) {
    if (!computedModelValue.value) {
      setSelectedItemOnBlur(null);

      return;
    }

    if (computedModelValue.value === originalModelValue.value) {
      return;
    }

    let item;

    if (typeof props.modelValue === 'string') {
      item = getSelectedItemByName(props.modelValue);
    }

    if (item?.id) {
      if (props.fieldName === 'id') {
        computedModelValue.value = item.id;
      } else {
        computedModelValue.value = item?.name || null;
      }
      setSelectedItemOnBlur(item.id);

      return;
    }

    if (!props.allowCustomEntry && (props.itemId || props.itemId === 0)) {
      item = getSelectedItemById(props.itemId);
      setSelectedItemOnBlur(item?.id || null);

      return;
    }

    if (Number.isInteger(props.modelValue)) {
      item = getSelectedItemById(props.modelValue);
      setSelectedItemOnBlur(item?.id || null);

      return;
    }

    if (!props.allowCustomEntry && !item?.id) {
      item = getSelectedItemById(selectedItem.value);

      if (props.fieldName === 'id') {
        computedModelValue.value = item?.id || null;
      } else {
        computedModelValue.value = item?.name || null;
      }
      setSelectedItemOnBlur(item?.id || null);
    }

    if (props.allowCustomEntry && !item?.id) {
      setSelectedItemOnBlur(null);
    }

    closeDropdown();
  }
}

function onFocus() {
  originalModelValue.value = structuredClone(computedModelValue.value);

  if (!computedModelValue.value) {
    setSelectedItemOnFocus(null);

    return;
  }

  if (props.itemId || props.itemId === 0) {
    setSelectedItemOnFocus(props.itemId);

    return;
  }

  if (Number.isInteger(props.modelValue)) {
    setSelectedItemOnFocus(props.modelValue);

    return;
  }

  if (typeof props.modelValue === 'string' && !getSelectedItemByName(props.modelValue)) {
    openDropdown();

    return;
  }

  if (Object.entries(props.items).length && computedModelValue.value) {
    const item = getSelectedItemByName(computedModelValue.value);

    setSelectedItemOnFocus(item?.id);
  }

  openDropdown();
}

function setSelectedItemOnClick(value) {
  computedModelValue.value = value;
  selectedItem.value = value;
  emit('selectItem', value, props.itemKey);
}

function setSelectedItemOnFocus(value) {
  selectedItem.value = value;
  emit('focus');
  openDropdown();
}

function setSelectedItemOnBlur(value) {
  selectedItem.value = value;
  emit('selectItem', value, props.itemKey);
  closeDropdown();
}

function getSelectedItemByName(value) {
  if (!Object.keys(computedEntities.value)?.length) {
    return null;
  }

  return computedEntities.value.find((entity) => {
    if (entity.uniqueIdentifier) {
      return (
        entity?.id === selectedItem.value && entity?.name?.toLowerCase() === value?.toLowerCase()
      );
    }

    return entity?.name?.toLowerCase() === value?.toLowerCase();
  });
}

function getSelectedItemById(value) {
  return computedEntities.value.find((entity) => {
    return entity.id === value;
  });
}

function openDropdown() {
  showDropdown.value = true;
}

function closeDropdown() {
  showDropdown.value = false;
}
</script>

<style scoped lang="scss" src="./UiSelectSearchInput.scss" />
