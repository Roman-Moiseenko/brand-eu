<template>
    <el-tab-pane>
        <template #label>
            <span class="custom-tabs-label">
                <i class="fa-light fa-box-open"></i>
                <span> Товары</span>
            </span>
        </template>

        <el-table
            :data="[...tableData]"
            header-cell-class-name="nordihome-header"
            style="width: 100%;"
            row-key="id"
            v-loading="loading"
        >
            <el-table-column type="expand">
                <template #default="scope">
                    <div class="p-4">
                        <el-table :data="scope.row.sizes" border size="small">
                            <el-table-column prop="size" label="Размер" width="120"/>
                            <el-table-column prop="sizeOriginal" label="Оригинальный размер" width="160"/>
                            <el-table-column prop="sku" label="SKU" width="160"/>
                            <el-table-column prop="url" label="URL" show-overflow-tooltip/>
                            <el-table-column prop="price" label="Цена" width="100"/>
                            <el-table-column prop="available" label="Доступен" width="100" align="center">
                                <template #default="scope">
                                    <Active :active="scope.row.available"/>
                                </template>
                            </el-table-column>
                            <el-table-column prop="values" label="Значения">
                                <template #default="scope">
                                    {{ formatValues(scope.row.values) }}
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                </template>
            </el-table-column>

            <el-table-column prop="code" label="Артикул" width="140"/>
            <el-table-column prop="name" label="Товар" show-overflow-tooltip/>
            <el-table-column prop="url" label="URL" width="200" show-overflow-tooltip/>
            <el-table-column prop="makerId" label="ID в магазине бренда" width="160" show-overflow-tooltip/>
            <el-table-column prop="brandId" label="Бренд ID" width="90" align="center"/>
            <el-table-column prop="priceSell" label="Цена продажи" width="110"/>
            <el-table-column prop="priceBase" label="Цена базовая" width="110"/>
            <el-table-column prop="availability" label="Доступен" width="110">
                <template #default="scope">
                    <Active :active="scope.row.availability"/>
                </template>
            </el-table-column>
            <el-table-column prop="productId" label="Есть в каталоге" width="130">
                <template #default="scope">
                    <Active :active="scope.row.productId !== null"/>
                </template>
            </el-table-column>

            <el-table-column label="Действия" fixed="right" width="260">
                <template #default="scope">
                    <el-button :type="scope.row.availability ? 'info' : 'success'" size="small"
                               @click.stop="onAvailable(scope.row)">
                        {{ scope.row.availability ? 'Недоступен' : 'Доступен' }}
                    </el-button>

                    <el-button type="success" size="small" @click.stop="onParser(scope.row)">
                        Спарсить
                    </el-button>

                    <el-button v-if="scope.row.productId === null" type="success" size="small" plain @click="toCatalog(scope.row)">
                        В Каталог
                    </el-button>
                </template>
            </el-table-column>
        </el-table>
        <div class="flex justify-center mt-4" v-if="pagination.last_page > 1">
            <el-pagination
                :current-page="pagination.current_page"
                :page-size="pagination.per_page"
                :total="pagination.total"
                layout="prev, pager, next"
                @current-change="onPageChange"
            />
        </div>
    </el-tab-pane>
</template>

<script setup lang="ts">
import {defineProps, onMounted, reactive, ref} from "vue";
import Active from "@Comp/Elements/Active.vue";
import {router} from "@inertiajs/vue3";
import axios from "axios";
import {route} from "ziggy-js";
import api from "@Res/api";

const props = defineProps({
    categoryId: Number,
})

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
    data: Product[]
}

interface Product {
    id: number
    code: string
    name: string
    url: string
    availability: boolean
    productId: number | null
    brandId: number | null
    makerId: string
    priceSell: number
    priceBase: number
    sizes: any[]
}

const tableData = ref<Product[]>([])
const loading = ref(false)
const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    data: [],
})
const currentPage = ref(1);

onMounted(() => {
    fetchProducts(1)
})

function fetchProducts(page: number = 1) {
    currentPage.value = page
    loading.value = true
    axios.get(route(`admin.parser.category.products`, {id: props.categoryId, page}))
        .then(response => {
            pagination.value = response.data
            tableData.value = response.data.data
        })
        .finally(() => {
            loading.value = false
        })
}

function onPageChange(page: number) {
    fetchProducts(page)
}

function onParser(row) {
    api.post(route('admin.parser.product.parser', {id: row.id}))
}

function onAvailable(row) {
    router.visit(route('admin.parser.product.available', {id: row.id}), {
        method: "post",
        preserveScroll: true,
        preserveState: true,
        onSuccess: page => {
            fetchProducts(currentPage.value)
        }
    })
}

function toCatalog(row) {
    router.post(route('admin.parser.product.to-catalog', {id: row.id}))
}

function formatValues(value: any): string {
    if (Array.isArray(value)) {
        if (value.length === 0) return ''
        if (value.every(item => typeof item === 'string' || typeof item === 'number')) {
            return value.join(', ')
        }
        return value.map(item => JSON.stringify(item)).join(', ')
    }
    if (value && typeof value === 'object') {
        return Object.entries(value).map(([k, v]) => `${k}: ${Array.isArray(v) ? v.join(', ') : v}`).join('; ')
    }
    return value ? String(value) : ''
}
</script>
