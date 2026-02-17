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
    store_ids: number[];
}

interface Props {
    stores: Array<{ id: number; store_name: string; store_code: string }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Delivery Orders', href: '/delivery-orders' },
    { title: 'Create' },
];

const form = useForm({
    store_id_from: null as number | null,
    store_id_to: null as number | null,
    notes: '',
    items: [] as Array<{ product_id: number; quantity: number; product_name: string; product_number: string; variant_name: string | null; image_url: string | null; not_at_destination: boolean }>,
});

const storeOptions = computed(() =>
    props.stores.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: s.id,
    })),
);

const toStoreOptions = computed(() =>
    storeOptions.value.filter((s) => s.value !== form.store_id_from),
);

// Product search
const searchQuery = ref('');
const searchResults = ref<ProductSearchResult[]>([]);
const searchLoading = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(() => form.store_id_from, () => {
    form.items = [];
    searchResults.value = [];
    if (form.store_id_to === form.store_id_from) {
        form.store_id_to = null;
    }
});

watch(searchQuery, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (!val || val.length < 2 || !form.store_id_from) {
        searchResults.value = [];
        return;
    }
    searchTimeout = setTimeout(() => searchProducts(val), 300);
});

async function searchProducts(query: string) {
    if (!form.store_id_from) return;
    searchLoading.value = true;
    try {
        // We need a temporary DO to search products at that store
        // Use a direct fetch since we don't have an order yet
        const response = await fetch(`/products/search?q=${encodeURIComponent(query)}&store_id=${form.store_id_from}`);
        const data = await response.json();
        const existingIds = form.items.map((i) => i.product_id);
        searchResults.value = (data.data as ProductSearchResult[]).filter(
            (p: ProductSearchResult) => !existingIds.includes(p.id),
        );
    } catch {
        searchResults.value = [];
    } finally {
        searchLoading.value = false;
    }
}

function addItem(product: ProductSearchResult) {
    const notAtDest = form.store_id_to ? !product.store_ids.includes(form.store_id_to) : false;
    form.items.push({
        product_id: product.id,
        quantity: 1,
        product_name: product.product_name,
        product_number: product.product_number,
        variant_name: product.variant_name,
        image_url: product.image_url,
        not_at_destination: notAtDest,
    });
    searchQuery.value = '';
    searchResults.value = [];
}

function removeItem(index: number) {
    form.items.splice(index, 1);
}

function submit() {
    form.post('/delivery-orders');
}
</script>

<template>
    <Head title="Create Delivery Order" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-3">
                <Button
                    icon="pi pi-arrow-left"
                    text
                    size="small"
                    severity="secondary"
                    @click="router.visit('/delivery-orders')"
                    v-tooltip.top="'Back'"
                />
                <h1 class="heading-lg">Create Delivery Order</h1>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <!-- Store Selection -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">From Store *</label>
                        <Select
                            v-model="form.store_id_from"
                            :options="storeOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select source store"
                            size="small"
                            filter
                            :invalid="!!form.errors.store_id_from"
                        />
                        <small v-if="form.errors.store_id_from" class="text-red-500">{{ form.errors.store_id_from }}</small>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">To Store *</label>
                        <Select
                            v-model="form.store_id_to"
                            :options="toStoreOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select destination store"
                            size="small"
                            filter
                            :disabled="!form.store_id_from"
                            :invalid="!!form.errors.store_id_to"
                        />
                        <small v-if="form.errors.store_id_to" class="text-red-500">{{ form.errors.store_id_to }}</small>
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
                    <div v-if="form.store_id_from" class="relative">
                        <InputText
                            v-model="searchQuery"
                            placeholder="Search products by name, number, or barcode..."
                            size="small"
                            fluid
                        />
                        <!-- Search dropdown -->
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
                    <p v-else class="text-sm text-muted-foreground">Select a source store first to search for products.</p>
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
                                    <div v-if="data.not_at_destination" class="mt-1 text-xs text-orange-600">
                                        Not assigned to destination store — will be auto-assigned on approval
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Quantity" class="w-32">
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
                        @click="router.visit('/delivery-orders')"
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
