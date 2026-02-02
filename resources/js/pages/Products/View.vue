<script setup lang="ts">
import AuditInfo from '@/components/AuditInfo.vue';
import BackButton from '@/components/BackButton.vue';
import ProductImageGallery from '@/components/products/ProductImageGallery.vue';
import LinkVariantDialog from '@/components/products/LinkVariantDialog.vue';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type HasAuditTrail, type Product } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';

interface Props {
    product: Product & HasAuditTrail;
}

const props = defineProps<Props>();

const { canAccessPage } = usePermissions();
const canViewCostPrice = computed(() =>
    canAccessPage('products.view_cost_price'),
);
const canEdit = computed(() => canAccessPage('products.edit'));
const canCreate = computed(() => canAccessPage('products.create'));

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

function navigateToParent() {
    if (props.product.parent_product_id) {
        router.get(`/products/${props.product.parent_product_id}`);
    }
}

function navigateToVariant(variantId: number) {
    router.get(`/products/${variantId}`);
}

function createVariant() {
    router.get(`/products/${props.product.id}/create-variant`);
}

// Link variant dialog
const showLinkVariantDialog = ref(false);

function onVariantLinked() {
    // Close dialog first
    showLinkVariantDialog.value = false;
    // Reload page to show the new variant
    router.visit(`/products/${props.product.id}`, {
        preserveScroll: true,
    });
}

function formatPrice(
    price: string | number | null | undefined,
    symbol: string = '',
): string {
    if (price === null || price === undefined) return '-';
    const num = typeof price === 'string' ? parseFloat(price) : price;
    return `${symbol}${num.toFixed(2)}`;
}
</script>

<template>
    <Head :title="product.product_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-4">
                    <BackButton fallback-url="/products" />
                    <h1 class="heading-lg">View Product</h1>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="canCreate && !product.is_variant"
                        label="Create Variant"
                        icon="pi pi-plus"
                        size="small"
                        severity="secondary"
                        @click="createVariant"
                    />
                    <Button
                        v-if="canEdit"
                        label="Edit"
                        icon="pi pi-pencil"
                        size="small"
                        @click="navigateToEdit"
                    />
                </div>
            </div>

            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <template #content>
                        <div class="flex flex-col gap-6">
                            <!-- Product Header -->
                            <div class="flex flex-col gap-4">
                                <div
                                    class="flex flex-col items-center gap-4 sm:flex-row sm:items-start"
                                >
                                    <Avatar
                                        v-if="
                                            !product.image_url &&
                                            (!product.images ||
                                                product.images.length === 0)
                                        "
                                        :label="getInitials()"
                                        shape="square"
                                        class="!h-24 !w-24 rounded-lg bg-primary/10 text-3xl text-primary"
                                    />
                                    <div
                                        class="flex flex-col gap-1 text-center sm:text-left"
                                    >
                                        <h2 class="text-xl font-semibold">
                                            {{ product.product_name }}
                                        </h2>
                                        <div
                                            class="flex flex-wrap items-center justify-center gap-2 sm:justify-start"
                                        >
                                            <Tag
                                                :value="product.product_number"
                                                severity="secondary"
                                            />
                                            <Tag
                                                v-if="product.barcode"
                                                :value="product.barcode"
                                                severity="info"
                                            />
                                            <Tag
                                                :value="
                                                    product.is_active
                                                        ? 'Active'
                                                        : 'Inactive'
                                                "
                                                :severity="
                                                    product.is_active
                                                        ? 'success'
                                                        : 'danger'
                                                "
                                            />
                                            <Tag
                                                v-if="product.is_variant"
                                                value="Variant"
                                                severity="warn"
                                            />
                                        </div>
                                        <p
                                            v-if="product.brand_name"
                                            class="text-muted-foreground"
                                        >
                                            {{ product.brand_name }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Image Gallery -->
                                <ProductImageGallery
                                    v-if="
                                        product.image_url ||
                                        (product.images &&
                                            product.images.length > 0)
                                    "
                                    :product-id="product.id"
                                    :cover-image-url="product.image_url"
                                    :images="product.images ?? []"
                                    :editable="false"
                                />
                            </div>

                            <Divider />

                            <!-- Classification -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Classification
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Brand</span
                                        >
                                        <span>{{
                                            product.brand_name ?? '-'
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Supplier</span
                                        >
                                        <span>{{
                                            product.supplier_name ?? '-'
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Category</span
                                        >
                                        <span>{{
                                            product.category_name ?? '-'
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Subcategory</span
                                        >
                                        <span>{{
                                            product.subcategory_name ?? '-'
                                        }}</span>
                                    </div>
                                    <div
                                        v-if="product.supplier_number"
                                        class="flex flex-col gap-1"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Supplier Number</span
                                        >
                                        <span>{{
                                            product.supplier_number
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Parent Product Link (if this is a variant) -->
                            <template v-if="product.is_variant && product.parent">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">
                                        Parent Product
                                    </h3>
                                    <div
                                        class="flex cursor-pointer items-center gap-3 rounded-lg border border-border p-4 transition-colors hover:bg-muted/50"
                                        @click="navigateToParent"
                                    >
                                        <div class="flex flex-col gap-1">
                                            <span class="font-medium">{{
                                                product.parent.product_name
                                            }}</span>
                                            <span
                                                class="text-sm text-muted-foreground"
                                                >{{
                                                    product.parent.product_number
                                                }}</span
                                            >
                                        </div>
                                        <i
                                            class="pi pi-chevron-right ml-auto text-muted-foreground"
                                        ></i>
                                    </div>
                                </div>
                            </template>

                            <!-- Variants Section (if this product has variants or can have variants) -->
                            <template v-if="!product.is_variant">
                                <Divider />
                                <div>
                                    <div
                                        class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <h3 class="text-lg font-medium">
                                            Variants
                                            <span
                                                v-if="
                                                    product.variants &&
                                                    product.variants.length > 0
                                                "
                                                >({{
                                                    product.variants.length
                                                }})</span
                                            >
                                        </h3>
                                        <div
                                            v-if="canCreate || canEdit"
                                            class="flex gap-2"
                                        >
                                            <Button
                                                v-if="canEdit"
                                                label="Link Existing"
                                                icon="pi pi-link"
                                                size="small"
                                                severity="secondary"
                                                outlined
                                                @click="
                                                    showLinkVariantDialog = true
                                                "
                                            />
                                            <Button
                                                v-if="canCreate"
                                                label="Create New"
                                                icon="pi pi-plus"
                                                size="small"
                                                severity="secondary"
                                                @click="createVariant"
                                            />
                                        </div>
                                    </div>
                                    <!-- Variants list -->
                                    <div
                                        v-if="
                                            product.variants &&
                                            product.variants.length > 0
                                        "
                                        class="flex flex-col gap-2"
                                    >
                                        <div
                                            v-for="variant in product.variants"
                                            :key="variant.id"
                                            class="flex cursor-pointer items-center gap-3 rounded-lg border border-border p-3 transition-colors hover:bg-muted/50"
                                            @click="navigateToVariant(variant.id)"
                                        >
                                            <img
                                                v-if="variant.image_url"
                                                :src="variant.image_url"
                                                :alt="variant.variant_name || variant.product_name"
                                                class="h-10 w-10 flex-shrink-0 rounded object-cover"
                                            />
                                            <Avatar
                                                v-else
                                                :label="(variant.variant_name || variant.product_name || 'V').substring(0, 2).toUpperCase()"
                                                shape="square"
                                                class="!h-10 !w-10 flex-shrink-0 rounded bg-primary/10 text-sm text-primary"
                                            />
                                            <div class="flex flex-1 flex-col">
                                                <span class="font-medium">{{
                                                    variant.variant_name
                                                }}</span>
                                                <span
                                                    class="text-xs text-muted-foreground"
                                                    >{{
                                                        variant.product_number
                                                    }}</span
                                                >
                                            </div>
                                            <Tag
                                                :value="
                                                    variant.is_active
                                                        ? 'Active'
                                                        : 'Inactive'
                                                "
                                                :severity="
                                                    variant.is_active
                                                        ? 'success'
                                                        : 'danger'
                                                "
                                                class="!text-xs"
                                            />
                                            <i
                                                class="pi pi-chevron-right text-muted-foreground"
                                            ></i>
                                        </div>
                                    </div>
                                    <!-- Empty state -->
                                    <div
                                        v-else
                                        class="rounded-lg border border-dashed border-border p-6 text-center"
                                    >
                                        <i
                                            class="pi pi-box mb-2 text-2xl text-muted-foreground"
                                        ></i>
                                        <p class="text-sm text-muted-foreground">
                                            No variants yet. Create a new variant
                                            or link an existing product.
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <!-- Base Prices -->
                            <template
                                v-if="
                                    product.prices && product.prices.length > 0
                                "
                            >
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">
                                        Base Prices
                                    </h3>
                                    <div
                                        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                                    >
                                        <div
                                            v-for="price in product.prices"
                                            :key="price.id"
                                            class="rounded-lg border border-border p-4"
                                        >
                                            <div
                                                class="mb-2 flex items-center gap-2"
                                            >
                                                <span class="font-medium">{{
                                                    price.currency?.code
                                                }}</span>
                                                <span
                                                    class="text-sm text-muted-foreground"
                                                >
                                                    ({{ price.currency?.name }})
                                                </span>
                                            </div>
                                            <div class="flex flex-col gap-1">
                                                <div
                                                    v-if="canViewCostPrice"
                                                    class="flex justify-between"
                                                >
                                                    <span
                                                        class="text-sm text-muted-foreground"
                                                        >Cost Price</span
                                                    >
                                                    <span>{{
                                                        formatPrice(
                                                            price.cost_price,
                                                            price.currency
                                                                ?.symbol,
                                                        )
                                                    }}</span>
                                                </div>
                                                <div
                                                    class="flex justify-between"
                                                >
                                                    <span
                                                        class="text-sm text-muted-foreground"
                                                        >Unit Price</span
                                                    >
                                                    <span class="font-medium">
                                                        {{
                                                            formatPrice(
                                                                price.unit_price,
                                                                price.currency
                                                                    ?.symbol,
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Store Assignments -->
                            <template
                                v-if="
                                    product.product_stores &&
                                    product.product_stores.length > 0
                                "
                            >
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">
                                        Store Assignments
                                    </h3>
                                    <div class="flex flex-col gap-4">
                                        <div
                                            v-for="productStore in product.product_stores"
                                            :key="productStore.id"
                                            class="rounded-lg border border-border p-4"
                                        >
                                            <div
                                                class="mb-3 flex items-center justify-between"
                                            >
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <span class="font-medium">{{
                                                        productStore.store
                                                            ?.store_name
                                                    }}</span>
                                                    <Tag
                                                        :value="
                                                            productStore.store
                                                                ?.store_code
                                                        "
                                                        severity="secondary"
                                                        class="!text-xs"
                                                    />
                                                </div>
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <Tag
                                                        :value="
                                                            productStore.is_active
                                                                ? 'Active'
                                                                : 'Inactive'
                                                        "
                                                        :severity="
                                                            productStore.is_active
                                                                ? 'success'
                                                                : 'danger'
                                                        "
                                                        class="!text-xs"
                                                    />
                                                    <span
                                                        class="text-sm text-muted-foreground"
                                                    >
                                                        Qty:
                                                        <span
                                                            class="font-medium text-foreground"
                                                            >{{
                                                                productStore.quantity
                                                            }}</span
                                                        >
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Store-specific prices -->
                                            <div
                                                v-if="
                                                    productStore.store_prices &&
                                                    productStore.store_prices
                                                        .length > 0
                                                "
                                                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                                            >
                                                <div
                                                    v-for="storePrice in productStore.store_prices"
                                                    :key="storePrice.id"
                                                    class="rounded border border-border/50 bg-muted/20 p-3"
                                                >
                                                    <div
                                                        class="mb-1 text-sm font-medium"
                                                    >
                                                        {{
                                                            storePrice.currency
                                                                ?.code
                                                        }}
                                                    </div>
                                                    <div
                                                        class="flex flex-col gap-0.5 text-sm"
                                                    >
                                                        <div
                                                            v-if="
                                                                canViewCostPrice
                                                            "
                                                            class="flex justify-between"
                                                        >
                                                            <span
                                                                class="text-muted-foreground"
                                                                >Cost</span
                                                            >
                                                            <span
                                                                :class="{
                                                                    'text-muted-foreground/50':
                                                                        storePrice.cost_price ===
                                                                        null,
                                                                }"
                                                            >
                                                                {{
                                                                    storePrice.cost_price !==
                                                                    null
                                                                        ? formatPrice(
                                                                              storePrice.cost_price,
                                                                              storePrice
                                                                                  .currency
                                                                                  ?.symbol,
                                                                          )
                                                                        : formatPrice(
                                                                              storePrice.effective_cost_price,
                                                                              storePrice
                                                                                  .currency
                                                                                  ?.symbol,
                                                                          ) +
                                                                          ' (base)'
                                                                }}
                                                            </span>
                                                        </div>
                                                        <div
                                                            class="flex justify-between"
                                                        >
                                                            <span
                                                                class="text-muted-foreground"
                                                                >Unit</span
                                                            >
                                                            <span
                                                                :class="{
                                                                    'text-muted-foreground/50':
                                                                        storePrice.unit_price ===
                                                                        null,
                                                                }"
                                                                class="font-medium"
                                                            >
                                                                {{
                                                                    storePrice.unit_price !==
                                                                    null
                                                                        ? formatPrice(
                                                                              storePrice.unit_price,
                                                                              storePrice
                                                                                  .currency
                                                                                  ?.symbol,
                                                                          )
                                                                        : formatPrice(
                                                                              storePrice.effective_unit_price,
                                                                              storePrice
                                                                                  .currency
                                                                                  ?.symbol,
                                                                          ) +
                                                                          ' (base)'
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
                            <template
                                v-if="product.weight || product.weight_unit"
                            >
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">
                                        Physical Attributes
                                    </h3>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-1">
                                            <span
                                                class="text-sm text-muted-foreground"
                                                >Weight</span
                                            >
                                            <span>{{
                                                product.weight_display ?? '-'
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Tags -->
                            <template
                                v-if="product.tags && product.tags.length > 0"
                            >
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">
                                        Tags
                                    </h3>
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
                                    <h3 class="mb-4 text-lg font-medium">
                                        Description
                                    </h3>
                                    <div
                                        class="prose prose-sm dark:prose-invert max-w-none"
                                        v-html="product.description"
                                    ></div>
                                </div>
                            </template>

                            <!-- Cost Price Remarks -->
                            <template
                                v-if="
                                    canViewCostPrice &&
                                    product.cost_price_remarks
                                "
                            >
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">
                                        Cost Price Notes
                                    </h3>
                                    <p>{{ product.cost_price_remarks }}</p>
                                </div>
                            </template>

                            <Divider />

                            <!-- Audit Info -->
                            <AuditInfo
                                :created-by="product.created_by"
                                :updated-by="product.updated_by"
                                :previous-updated-by="
                                    product.previous_updated_by
                                "
                                :created-at="product.created_at"
                                :updated-at="product.updated_at"
                                :previous-updated-at="
                                    product.previous_updated_at
                                "
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </div>
        <!-- Link Variant Dialog -->
        <LinkVariantDialog
            v-model:visible="showLinkVariantDialog"
            :product-id="product.id"
            @linked="onVariantLinked"
        />
    </AppLayout>
</template>
