<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import Tag from 'primevue/tag';
import { computed } from 'vue';
import AuditInfo from '@/components/AuditInfo.vue';
import BackButton from '@/components/BackButton.vue';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type HasAuditTrail, type Product } from '@/types';

interface Props {
    product: Product & HasAuditTrail;
}

const props = defineProps<Props>();

const { canAccessPage } = usePermissions();
const canViewCostPrice = computed(() => canAccessPage('products.view_cost_price'));
const canEdit = computed(() => canAccessPage('products.edit'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products', href: '/products' },
    { title: props.product.product_name },
];

function getInitials(): string {
    const words = props.product.product_name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return props.product.product_name.substring(0, 2).toUpperCase();
}

function navigateToEdit() {
    router.get(`/products/${props.product.id}/edit`);
}

function formatPrice(price: string | number | null | undefined, symbol: string = ''): string {
    if (price === null || price === undefined) return '-';
    const num = typeof price === 'string' ? parseFloat(price) : price;
    return `${symbol}${num.toFixed(2)}`;
}
</script>

<template>
    <Head :title="product.product_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <BackButton fallback-url="/products" />
                    <h1 class="heading-lg">{{ product.product_name }}</h1>
                    <Tag
                        :value="product.is_active ? 'Active' : 'Inactive'"
                        :severity="product.is_active ? 'success' : 'danger'"
                    />
                </div>
                <Button
                    v-if="canEdit"
                    label="Edit"
                    icon="pi pi-pencil"
                    size="small"
                    @click="navigateToEdit"
                />
            </div>

            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <template #content>
                        <div class="flex flex-col gap-6">
                            <!-- Product Header -->
                            <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                                <Image
                                    v-if="product.image_url"
                                    :src="product.image_url"
                                    :alt="product.product_name"
                                    image-class="!h-24 !w-24 rounded-lg object-cover cursor-pointer"
                                    :pt="{ root: { class: 'rounded-lg overflow-hidden' }, previewMask: { class: 'rounded-lg' } }"
                                    preview
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials()"
                                    shape="square"
                                    class="!h-24 !w-24 rounded-lg bg-primary/10 text-3xl text-primary"
                                />
                                <div class="flex flex-col gap-1 text-center sm:text-left">
                                    <h2 class="text-xl font-semibold">{{ product.product_name }}</h2>
                                    <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                                        <Tag :value="product.product_number" severity="secondary" />
                                        <Tag v-if="product.barcode" :value="product.barcode" severity="info" />
                                    </div>
                                    <p v-if="product.brand_name" class="text-muted-foreground">
                                        {{ product.brand_name }}
                                    </p>
                                </div>
                            </div>

                            <Divider />

                            <!-- Classification -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Classification</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Brand</span>
                                        <span>{{ product.brand_name ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Supplier</span>
                                        <span>{{ product.supplier_name ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Category</span>
                                        <span>{{ product.category_name ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Subcategory</span>
                                        <span>{{ product.subcategory_name ?? '-' }}</span>
                                    </div>
                                    <div v-if="product.supplier_number" class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Supplier Number</span>
                                        <span>{{ product.supplier_number }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Base Prices -->
                            <template v-if="product.prices && product.prices.length > 0">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Base Prices</h3>
                                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        <div
                                            v-for="price in product.prices"
                                            :key="price.id"
                                            class="rounded-lg border border-border p-4"
                                        >
                                            <div class="mb-2 flex items-center gap-2">
                                                <span class="font-medium">{{ price.currency?.code }}</span>
                                                <span class="text-sm text-muted-foreground">
                                                    ({{ price.currency?.name }})
                                                </span>
                                            </div>
                                            <div class="flex flex-col gap-1">
                                                <div v-if="canViewCostPrice" class="flex justify-between">
                                                    <span class="text-sm text-muted-foreground">Cost Price</span>
                                                    <span>{{ formatPrice(price.cost_price, price.currency?.symbol) }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-muted-foreground">Unit Price</span>
                                                    <span class="font-medium">
                                                        {{ formatPrice(price.unit_price, price.currency?.symbol) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Store Assignments -->
                            <template v-if="product.product_stores && product.product_stores.length > 0">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Store Assignments</h3>
                                    <div class="flex flex-col gap-4">
                                        <div
                                            v-for="productStore in product.product_stores"
                                            :key="productStore.id"
                                            class="rounded-lg border border-border p-4"
                                        >
                                            <div class="mb-3 flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium">{{ productStore.store?.store_name }}</span>
                                                    <Tag :value="productStore.store?.store_code" severity="secondary" class="!text-xs" />
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <Tag
                                                        :value="productStore.is_active ? 'Active' : 'Inactive'"
                                                        :severity="productStore.is_active ? 'success' : 'danger'"
                                                        class="!text-xs"
                                                    />
                                                    <span class="text-sm text-muted-foreground">
                                                        Qty: <span class="font-medium text-foreground">{{ productStore.quantity }}</span>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Store-specific prices -->
                                            <div v-if="productStore.store_prices && productStore.store_prices.length > 0" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                <div
                                                    v-for="storePrice in productStore.store_prices"
                                                    :key="storePrice.id"
                                                    class="rounded border border-border/50 bg-muted/20 p-3"
                                                >
                                                    <div class="mb-1 text-sm font-medium">
                                                        {{ storePrice.currency?.code }}
                                                    </div>
                                                    <div class="flex flex-col gap-0.5 text-sm">
                                                        <div v-if="canViewCostPrice" class="flex justify-between">
                                                            <span class="text-muted-foreground">Cost</span>
                                                            <span :class="{ 'text-muted-foreground/50': storePrice.cost_price === null }">
                                                                {{ storePrice.cost_price !== null
                                                                    ? formatPrice(storePrice.cost_price, storePrice.currency?.symbol)
                                                                    : formatPrice(storePrice.effective_cost_price, storePrice.currency?.symbol) + ' (base)'
                                                                }}
                                                            </span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-muted-foreground">Unit</span>
                                                            <span :class="{ 'text-muted-foreground/50': storePrice.unit_price === null }" class="font-medium">
                                                                {{ storePrice.unit_price !== null
                                                                    ? formatPrice(storePrice.unit_price, storePrice.currency?.symbol)
                                                                    : formatPrice(storePrice.effective_unit_price, storePrice.currency?.symbol) + ' (base)'
                                                                }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Physical Attributes -->
                            <template v-if="product.weight || product.weight_unit">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Physical Attributes</h3>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-sm text-muted-foreground">Weight</span>
                                            <span>{{ product.weight_display ?? '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Tags -->
                            <template v-if="product.tags && product.tags.length > 0">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Tags</h3>
                                    <div class="flex flex-wrap gap-2">
                                        <Tag
                                            v-for="tag in product.tags"
                                            :key="tag"
                                            :value="tag"
                                            severity="secondary"
                                        />
                                    </div>
                                </div>
                            </template>

                            <!-- Description -->
                            <template v-if="product.description">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Description</h3>
                                    <div class="prose prose-sm max-w-none dark:prose-invert" v-html="product.description"></div>
                                </div>
                            </template>

                            <!-- Cost Price Remarks -->
                            <template v-if="canViewCostPrice && product.cost_price_remarks">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Cost Price Notes</h3>
                                    <p>{{ product.cost_price_remarks }}</p>
                                </div>
                            </template>

                            <Divider />

                            <!-- Audit Info -->
                            <AuditInfo
                                :created-by="product.created_by"
                                :updated-by="product.updated_by"
                                :previous-updated-by="product.previous_updated_by"
                                :created-at="product.created_at"
                                :updated-at="product.updated_at"
                                :previous-updated-at="product.previous_updated_at"
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
