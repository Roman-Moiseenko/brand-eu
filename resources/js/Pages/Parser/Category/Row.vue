<template>
    <div class="bg-white rounded-md flex items-center mb-1 p-2 border border-slate-200">
        <div>
            <Active :active="category.active"/>
        </div>
        <div class="ml-4" style="width: 300px;">
            <Link type="primary" :href="route('admin.parser.category.show', {category_parser: category.id})">{{
                    category.name + ' (' + category.code + ')'
                }}
            </Link>
        </div>

        <div class="ml-4" style="width: 350px;">
            <span class="text-cyan-800">{{ category.url }}</span>
        </div>

        <div class="ml-4 flex items-center" style="gap: 8px;">
            <el-select
                v-model="selectedCategoryId"
                placeholder="Категория каталога"
                filterable
                clearable
                size="small"
                style="width: 220px;"
                @change="onCategoryChange"
            >
                <el-option
                    v-for="item in catalogStore.categories"
                    :key="item.id"
                    :label="item.name"
                    :value="item.id"
                />
            </el-select>

            <el-select
                v-model="selectedSizeGridId"
                placeholder="Размерная сетка"
                filterable
                clearable
                size="small"
                style="width: 220px;"
                @change="onSizeGridChange"
            >
                <el-option
                    v-for="item in catalogStore.sizeGrids"
                    :key="item.id"
                    :label="item.name"
                    :value="item.id"
                />
            </el-select>
        </div>

        <div class="ml-5 text-center" style="width: 150px;">
            <span v-if="isChildren">
                {{ category.children.length }}
                <el-button type="info" size="small" class="ml-2" plain @click="checkChildren = !checkChildren">
                    <i v-if="checkChildren" class="fa-regular fa-chevron-up"></i>
                    <i v-else class="fa-regular fa-chevron-down"></i>
                </el-button>
            </span>
        </div>
        <div class="flex ml-auto">
            <el-button v-if="category.active" size="small"
                       type="warning"
                       @click.stop="onToggle()"
            >
                No Parse
            </el-button>
            <el-button v-if="!category.active" size="small"
                       type="primary"
                       @click.stop="onToggle()"
            >
                To Parse
            </el-button>

            <el-button size="small"
                       type="danger"
                       @click.stop="handleDeleteEntity"
            >
                Delete
            </el-button>
        </div>
    </div>
    <div v-if="showChildren" class="pl-5 ml-2 mb-5 pb-2 pt-2">
        <!--CategoryRow v-for="item in category.children" :category="item" @delete:category="handleDeleteEntity" /-->
        <CategoryChildren :category="category" @delete:category="handleDeleteEntity"
                          :product_categories="product_categories"/>
    </div>
</template>

<script setup lang="ts">
import {router, Link} from "@inertiajs/vue3";
import {computed, inject, onActivated, reactive, ref} from "vue";
import axios from "axios";
import {ElMessage} from "element-plus";
import CategoryChildren from "./Children.vue";
import Active from "@Comp/Elements/Active.vue";
import {route} from "ziggy-js";
import {useCatalogStore} from "@Res/catalogStore.ts";

const props = defineProps({
    category: Object,
    product_categories: Array,
})
const $emit = defineEmits(['delete:category'])

const catalogStore = useCatalogStore()

const visible_create = ref(false)
const form = reactive({
    name: null,
    parent_id: props.category.id,
})
const checkChildren = ref(false)
const isChildren = ref(props.category.children.length > 0)

const showChildren = computed(() => {
    return isChildren && checkChildren.value
})

const selectedCategoryId = computed({
    get: () => props.category.categoryId ?? null,
    set: (value) => {
        props.category.categoryId = value
    },
})

const selectedSizeGridId = computed({
    get: () => props.category.sizeGridId ?? null,
    set: (value) => {
        props.category.sizeGridId = value
    },
})

function applyCategoryToTree(node, value) {
    if (!node) return
    node.categoryId = value
    if (Array.isArray(node.children)) {
        node.children.forEach(child => applyCategoryToTree(child, value))
    }
}

function applySizeGridToTree(node, value) {
    if (!node) return
    node.sizeGridId = value
    if (Array.isArray(node.children)) {
        node.children.forEach(child => applySizeGridToTree(child, value))
    }
}

function onToggle() {
    router.visit(route('admin.parser.category.toggle', {id: props.category.id}), {
        method: "post",
        preserveScroll: true,
        preserveState: true,
        onSuccess: page => {
            console.log(page.props)
        }
    })
}

function onCategoryChange(value) {
    const categoryId = value || null
    applyCategoryToTree(props.category, categoryId)

    axios.post(route('admin.parser.category.catalog', {category_parser: props.category.id}), {
        category_id: categoryId,
    }).then(() => {
        ElMessage.success('Сохранено')
    }).catch(() => {
        ElMessage.error('Ошибка сохранения')
    })
}

function onSizeGridChange(value) {
    const sizeGridId = value || null
    applySizeGridToTree(props.category, sizeGridId)

    axios.post(route('admin.parser.category.size-grid', {category_parser: props.category.id}), {
        size_grid_id: sizeGridId,
    }).then(() => {
        ElMessage.success('Сохранено')
    }).catch(() => {
        ElMessage.error('Ошибка сохранения')
    })
}

function handleDeleteEntity() {
    $emit('delete:category', props.category.id)
}



</script>
