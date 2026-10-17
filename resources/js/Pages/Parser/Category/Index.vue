<template>
    <Head><title>{{ title }}</title></Head>
    <el-config-provider :locale="ru">
        <h1 class="font-medium text-xl">Категории парсера</h1>

        <div class="mt-4 mb-4 flex items-center">
            <span class="mr-3 text-slate-600">Бренд:</span>
            <el-select
                v-model="selectedBrand"
                placeholder="Выберите бренд"
                filterable
                clearable
                style="width: 300px;"
                @change="onBrandChange"
            >
                <el-option
                    v-for="brand in brands"
                    :key="brand.id"
                    :label="brand.name"
                    :value="brand.slug"
                />
            </el-select>
        </div>

        <div v-if="selectedBrand === null" class="text-slate-400">
            Выберите бренд, чтобы увидеть категории.
        </div>

        <CategoryChildren :categories="categories" @delete:category="handleDeleteEntity"/>
        <DeleteEntityModal name_entity="Категорию" />
    </el-config-provider>
</template>

<script setup lang="ts">

import {Head, router} from "@inertiajs/vue3";
import {computed, inject, ref} from "vue";
import ru from 'element-plus/dist/locale/ru.mjs'
import CategoryChildren from "./Children.vue";
import {route} from "ziggy-js";

const props = defineProps({
    brands: Array,
    categories: {
        type: Array,
        default: () => [],
    },
    selectedBrand: {
        type: String,
        default: null,
    },
    title: {
        type: String,
        default: 'Категории парсера',
    },
})
const $delete_entity = inject("$delete_entity")

const selectedBrand = ref(props.selectedBrand ?? null)
const categories = computed(() => props.categories ?? [])

function onBrandChange(slug) {
    router.get(
        route('admin.parser.category.index'),
        slug ? {brand: slug} : {},
        {preserveScroll: true, replace: true},
    )
}

function handleDeleteEntity(id) {
    $delete_entity.show(route('admin.parser.category.destroy', {category_parser: id}));
}

</script>
