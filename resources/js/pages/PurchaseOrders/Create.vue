<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Image from 'primevue/image';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { computed, ref, watch } from 'vue';

interface ProductSearchResult {
    id: number;
    product_name: string;
    product_number: string;
    variant_name: string | null;
    barcode: string | null;
    image_url: string | null;
}

interface Props {
    suppliers: Array<{ id: number; supplier_name: string }>;
    currencies: Array<{ id: number; code: string; name: string; symbol: string }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Purchase Orders', href: '/purchase-orders' },
    { title: 'Create' },
];

const form = useForm({
    supplier_id: null as number | null,
    notes: '',
    items: [] as Array<{
        product_id: number;
        currency_id: number | null;
        unit_cost: number | null;
        quantity: number;
        product_name: string;
        product_number: string;
        variant_name: string | null;
        image_url: string | null;
    }>,
});

const supplierOptions = computed(() =>
    props.suppliers.map((s) => ({ label: s.supplier_name, value: s.id })),
);

const currencyOptions = computed(() =>
    props.currencies.map((c) => ({ label: `${c.code} (${c.symbol})`, value: c.id })),
);

// Apply currency to all items helper
const bulkCurrency = ref<number | null>(null);

watch(bulkCurrency, (val) => {
    if (val) {
        form.items.forEach((item) => {
            item.currency_id = val;
        });
    }
});

// Product search
const searchQuery = ref('');
const searchResults = ref<ProductSearchResult[]>([]);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(searchQuery, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (!val || val.length < 2) {
        searchResults.value = [];
        return;
    }
    searchTimeout = setTimeout(() => searchProducts(val), 300);
});

async function searchProducts(query: string) {
    try {
        const response = await fetch(`/purchase-orders/search-products?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        const existingIds = form.items.map((i) => i.product_id);
        searchResults.value = (data as ProductSearchResult[]).filter(
            (p: ProductSearchResult) => !existingIds.includes(p.id),
        );
    } catch {
        searchResults.value = [];
    }
}

function addItem(product: ProductSearchResult) {
    form.items.push({
        product_id: product.id,
        currency_id: bulkCurrency.value ?? (props.currencies.length > 0 ? props.currencies[0].id : null),
        unit_cost: null,
        quantity: 1,
        product_name: product.product_name,
        product_number: product.product_number,
        variant_name: product.variant_name,
        image_url: product.image_url,
    });
    searchQuery.value = '';
    searchResults.value = [];
}

function removeItem(index: number) {
    form.items.splice(index, 1);
}

function submit() {
    form.post('/purchase-orders');
}
</script>

<template>
    <Head title="Create Purchase Order" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="heading-lg">Create Purchase Order</h1>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <!-- Supplier -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Supplier</label>
                        <Select
                            v-model="form.supplier_id"
                            :options="supplierOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select supplier"
                            size="small"
                            filter
                            showClear
                            :invalid="!!form.errors.supplier_id"
                        />
                        <small v-if="form.errors.supplier_id" class="text-red-500">{{ form.errors.supplier_id }}</small>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Apply Currency to All</label>
                        <Select
                            v-model="bulkCurrency"
                            :options="currencyOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select currency..."
                            size="small"
                            filter
                            showClear
                        />
                        <small class="text-muted-foreground">Sets currency for all items</small>
                    </div>
                </div>

                <!-- Notes -->
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Notes</label>
                    <Textarea
                        v-model="form.notes"
                        rows="2"
                        placeholder="Optional notes..."
                        class="w-full"
                    />
                </div>

                <!-- Product Search -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Items *</label>
                    <div class="relative">
                        <InputText
                            v-model="searchQuery"
                            placeholder="Search products by name, number, or barcode..."
                            size="small"
                            fluid
                        />
                        <div
                            v-if="searchResults.length > 0"
                            class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-border bg-background shadow-lg"
                        >
                            <div
                                v-for="product in searchResults"
                                :key="product.id"
                                class="flex cursor-pointer items-center gap-2 px-3 py-2 hover:bg-muted"
                                @click="addItem(product)"
                            >
                                <img v-if="product.image_url" :src="product.image_url" class="h-8 w-8 flex-shrink-0 rounded object-cover" alt="" />
                                <Avatar v-else :label="product.product_name?.charAt(0)" shape="square" class="!h-8 !w-8 flex-shrink-0 rounded bg-primary/10 text-primary" />
                                <div>
                                    <div class="font-medium">{{ product.product_name }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ product.product_number }}
                                        <span v-if="product.variant_name"> - {{ product.variant_name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <small v-if="form.errors.items" class="text-red-500">{{ form.errors.items }}</small>
                </div>

                <!-- Items Table -->
                <DataTable
                    v-if="form.items.length > 0"
                    :value="form.items"
                    dataKey="product_id"
                    size="small"
                    striped-rows
                    class="overflow-hidden rounded-lg border border-border"
                >
                    <Column header="Product">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <div @click.stop>
                                    <Image v-if="data.image_url" :src="data.image_url" alt="" image-class="h-8 w-8 rounded object-cover cursor-pointer" :pt="{ root: { class: 'rounded overflow-hidden flex-shrink-0' }, previewMask: { class: 'rounded' } }" preview />
                                    <Avatar v-else :label="data.product_name?.charAt(0)" shape="square" class="!h-8 !w-8 flex-shrink-0 rounded bg-primary/10 text-primary" />
                                </div>
                                <div>
                                    <div class="font-medium">{{ data.product_name }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ data.product_number }}
                                        <span v-if="data.variant_name"> - {{ data.variant_name }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Currency" class="w-36">
                        <template #body="{ index }">
                            <Select
                                v-model="form.items[index].currency_id"
                                :options="currencyOptions"
                                option-label="label"
                                option-value="value"
                                size="small"
                                filter
                                fluid
                            />
                        </template>
                    </Column>
                    <Column header="Unit Cost" class="w-32">
                        <template #body="{ index }">
                            <InputNumber
                                v-model="form.items[index].unit_cost"
                                :min="0"
                                :minFractionDigits="2"
                                :maxFractionDigits="4"
                                size="small"
                                fluid
                            />
                        </template>
                    </Column>
                    <Column header="Quantity" class="w-24">
                        <template #body="{ index }">
                            <InputNumber
                                v-model="form.items[index].quantity"
                                :min="1"
                                size="small"
                                fluid
                            />
                        </template>
                    </Column>
                    <Column header="" class="w-16">
                        <template #body="{ index }">
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="removeItem(index)"
                            />
                        </template>
                    </Column>
                </DataTable>

                <!-- Actions -->
                <div class="flex gap-2">
                    <Button
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="router.visit('/purchase-orders')"
                    />
                    <Button
                        label="Create Draft"
                        icon="pi pi-save"
                        size="small"
                        type="submit"
                        :loading="form.processing"
                        :disabled="form.items.length === 0"
                    />
                </div>
            </form>
        </div>
    </AppLayout>
</template>
