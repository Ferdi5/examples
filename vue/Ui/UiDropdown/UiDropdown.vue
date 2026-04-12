<template>
  <div class="ui-dropdown">
    <div
      v-for="(item, index) in computedData"
      :key="index"
      tabindex="0"
      class="item truncate"
      :class="{ selected: selectedItemIds.includes(item.id) }"
      @click="handleClickedItem(item)"
      @keydown.enter="handleClickedItem(item)"
    >
      <div class="truncate">{{ item.name }}</div>
      <div
        v-if="item?.uniqueIdentifier"
        class="identifier"
      >
        {{ item.uniqueIdentifier }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: {
    type: Object,
    default: () => ({}),
    required: true
  },
  initialSelectedItems: {
    type: [Array, Number],
    default: () => []
  }
});
const emit = defineEmits(['clickedItem']);

const computedData = computed(() => {
  return props.data;
});

const selectedItemIds = computed(() => {
  if (Number.isInteger(props.initialSelectedItems)) {
    return [props.initialSelectedItems];
  }

  if (!props.initialSelectedItems?.length) {
    return [];
  }

  return props.initialSelectedItems.map((selectedItem) => {
    if (!isNaN(parseInt(selectedItem.id))) {
      return parseInt(selectedItem.id);
    }

    return selectedItem.id;
  });
});

function handleClickedItem(item) {
  emit('clickedItem', item);
}
</script>

<style scoped lang="scss" src="./UiDropdown.scss" />
