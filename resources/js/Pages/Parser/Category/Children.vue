<template>
    <CategoryRow
        v-for="item in children"
        :key="item.id"
        :category="item"
        :expand-all="expandAll"
        @delete:category="handleDeleteEntity"
    />
</template>
<script setup lang="ts">
import CategoryRow from "./Row.vue";
import {computed, defineEmits, defineProps, ref} from "vue";

const props = defineProps({
    category: {
        type: Object,
        default: null,
    },
    categories: {
        type: Object,
        default: null,
    },
    expandAll: {
        type: Boolean,
        default: false,
    },
})
const $emit = defineEmits(['delete:category'])
const children = computed(() => {
    return props.category === null ? props.categories : props.category.children
})
function handleDeleteEntity(id) {
    $emit('delete:category', id)
}

</script>
