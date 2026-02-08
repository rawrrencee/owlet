<script setup lang="ts">
import { usePermissions } from '@/composables/usePermissions';
import { useSmartBack } from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Offer } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';

interface Props {
    offer: Offer;
}

const props = defineProps<Props>();

const { goBack } = useSmartBack('/offers');
const { canAccessPage } = usePermissions();
const canManage = computed(() => canAccessPage('offers.manage'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Offers', href: '/offers' },
    { title: props.offer.name },
];

function getStatusSeverity(status: string): string {
    switch (status) {
        case 'draft': return 'secondary';
        case 'scheduled': return 'info';
        case 'active': return 'success';
        case 'expired': return 'warn';
        case 'disabled': return 'danger';
        default: return 'info';
    }
}

function getTypeSeverity(type: string): string {
    switch (type) {
        case 'product': return 'info';
        case 'bundle': return 'warn';
        case 'minimum_spend': return 'success';
        case 'category': return 'secondary';
        case 'brand': return 'contrast';
        default: return 'info';
    }
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatDiscount(): string {
    if (props.offer.discount_type === 'percentage') {
        return `${props.offer.discount_percentage}% off`;
    }
    if (props.offer.amounts && props.offer.amounts.length > 0) {
        return props.offer.amounts
            .filter((a) => a.discount_amount)
            .map((a) => `${a.currency?.symbol ?? ''}${Number(a.discount_amount).toFixed(2)} off`)
            .join(', ');
    }
    return '-';
}

const canActivate = computed(() =>
    canManage.value && ['draft', 'disabled', 'scheduled'].includes(props.offer.status),
);
const canDisable = computed(() =>
    canManage.value && ['active', 'scheduled'].includes(props.offer.status),
);
const canEdit = computed(() =>
    canManage.value && ['draft', 'disabled'].includes(props.offer.status),
);
const canDelete = computed(() =>
    canManage.value && ['draft', 'disabled'].includes(props.offer.status),
);

// Dialogs
const activateDialogVisible = ref(false);
const disableDialogVisible = ref(false);
const deleteDialogVisible = ref(false);

function activateOffer() {
    router.post(`/offers/${props.offer.id}/activate`, {}, {
        onSuccess: () => { activateDialogVisible.value = false; },
    });
}

function disableOffer() {
    router.post(`/offers/${props.offer.id}/disable`, {}, {
        onSuccess: () => { disableDialogVisible.value = false; },
    });
}

function deleteOffer() {
    router.delete(`/offers/${props.offer.id}`, {
        onSuccess: () => { deleteDialogVisible.value = false; },
    });
}
</script>

<template>
    <Head :title="offer.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Button
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        text
                        rounded
                        size="small"
                        @click="goBack"
                    />
                    <div>
                        <h1 class="heading-lg">{{ offer.name }}</h1>
                        <span v-if="offer.code" class="text-sm text-muted-foreground">{{ offer.code }}</span>
                    </div>
                    <Tag :value="offer.status_label" :severity="getStatusSeverity(offer.status)" />
                    <Tag :value="offer.type_label" :severity="getTypeSeverity(offer.type)" />
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="canEdit"
                        label="Edit"
                        icon="pi pi-pencil"
                        severity="secondary"
                        size="small"
                        @click="router.get(`/offers/${offer.id}/edit`)"
                    />
                    <Button
                        v-if="canActivate"
                        label="Activate"
                        icon="pi pi-check-circle"
                        severity="success"
                        size="small"
                        @click="activateDialogVisible = true"
                    />
                    <Button
                        v-if="canDisable"
                        label="Disable"
                        icon="pi pi-ban"
                        severity="warn"
                        size="small"
                        @click="disableDialogVisible = true"
                    />
                    <Button
                        v-if="canDelete"
                        label="Delete"
                        icon="pi pi-trash"
                        severity="danger"
                        size="small"
                        @click="deleteDialogVisible = true"
                    />
                </div>
            </div>

            <!-- Details Card -->
            <Card>
                <template #content>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <span class="text-sm text-muted-foreground">Discount</span>
                            <p class="font-medium">{{ formatDiscount() }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Combinable with Customer Discount</span>
                            <p class="font-medium">{{ offer.is_combinable ? 'Yes' : 'No' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Priority</span>
                            <p class="font-medium">{{ offer.priority }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Starts At</span>
                            <p>{{ formatDate(offer.starts_at) }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Ends At</span>
                            <p>{{ formatDate(offer.ends_at) }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Stores</span>
                            <p v-if="offer.apply_to_all_stores">All Stores</p>
                            <p v-else-if="offer.stores && offer.stores.length > 0">
                                {{ offer.stores.map((s) => `${s.store_name} (${s.store_code})`).join(', ') }}
                            </p>
                            <p v-else>-</p>
                        </div>
                        <div v-if="offer.description" class="sm:col-span-2 lg:col-span-3">
                            <span class="text-sm text-muted-foreground">Description</span>
                            <p>{{ offer.description }}</p>
                        </div>
                        <div v-if="offer.created_by_user">
                            <span class="text-sm text-muted-foreground">Created By</span>
                            <p>{{ offer.created_by_user.name }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Created At</span>
                            <p>{{ formatDate(offer.created_at) }}</p>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Per-Currency Amounts (for percentage offers with caps, or general display) -->
            <template v-if="offer.amounts && offer.amounts.length > 0">
                <Divider />
                <h2 class="text-lg font-semibold">
                    {{ offer.discount_type === 'fixed' ? 'Fixed Discount Amounts' : 'Maximum Discount Caps' }}
                </h2>
                <DataTable
                    :value="offer.amounts"
                    dataKey="id"
                    size="small"
                    striped-rows
                    class="overflow-hidden rounded-lg border border-border"
                >
                    <Column header="Currency">
                        <template #body="{ data }">
                            {{ data.currency?.code ?? '-' }}
                        </template>
                    </Column>
                    <Column v-if="offer.discount_type === 'fixed'" header="Discount Amount">
                        <template #body="{ data }">
                            {{ data.currency?.symbol }}{{ data.discount_amount ? Number(data.discount_amount).toFixed(2) : '-' }}
                        </template>
                    </Column>
                    <Column v-if="offer.discount_type === 'percentage'" header="Max Discount">
                        <template #body="{ data }">
                            {{ data.max_discount_amount ? `${data.currency?.symbol}${Number(data.max_discount_amount).toFixed(2)}` : 'No cap' }}
                        </template>
                    </Column>
                </DataTable>
            </template>

            <!-- Product-specific: Products -->
            <template v-if="offer.type === 'product' && offer.products && offer.products.length > 0">
                <Divider />
                <h2 class="text-lg font-semibold">Products</h2>
                <DataTable
                    :value="offer.products"
                    dataKey="id"
                    size="small"
                    striped-rows
                    class="overflow-hidden rounded-lg border border-border"
                >
                    <Column header="Product">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <div @click.stop>
                                    <Image v-if="data.product?.image_url" :src="data.product.image_url" alt="" image-class="h-8 w-8 rounded object-cover cursor-pointer" :pt="{ root: { class: 'rounded overflow-hidden flex-shrink-0' }, previewMask: { class: 'rounded' } }" preview />
                                    <Avatar v-else :label="data.product?.product_name?.charAt(0)" shape="square" class="!h-8 !w-8 flex-shrink-0 rounded bg-primary/10 text-primary" />
                                </div>
                                <div>
                                    <div class="font-medium">{{ data.product?.product_name }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ data.product?.product_number }}
                                        <span v-if="data.product?.variant_name"> - {{ data.product.variant_name }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>

            <!-- Bundle-specific: Bundle Items -->
            <template v-if="offer.type === 'bundle' && offer.bundles && offer.bundles.length > 0">
                <Divider />
                <h2 class="text-lg font-semibold">
                    Bundle Items
                    <Tag v-if="offer.bundle_mode_label" :value="offer.bundle_mode_label" severity="info" class="ml-2" />
                </h2>
                <DataTable
                    :value="offer.bundles"
                    dataKey="id"
                    size="small"
                    striped-rows
                    class="overflow-hidden rounded-lg border border-border"
                >
                    <Column header="Item">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <!-- Product entry -->
                                <template v-if="data.product_id">
                                    <div @click.stop>
                                        <Image v-if="data.product?.image_url" :src="data.product.image_url" alt="" image-class="h-8 w-8 rounded object-cover cursor-pointer" :pt="{ root: { class: 'rounded overflow-hidden flex-shrink-0' }, previewMask: { class: 'rounded' } }" preview />
                                        <Avatar v-else :label="data.product?.product_name?.charAt(0)" shape="square" class="!h-8 !w-8 flex-shrink-0 rounded bg-primary/10 text-primary" />
                                    </div>
                                    <div>
                                        <div class="font-medium">{{ data.product?.product_name }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ data.product?.product_number }}
                                            <span v-if="data.product?.variant_name"> - {{ data.product.variant_name }}</span>
                                        </div>
                                    </div>
                                </template>
                                <!-- Category entry -->
                                <template v-else-if="data.category_id">
                                    <i class="pi pi-folder text-lg text-muted-foreground"></i>
                                    <div class="font-medium">
                                        Any product from <span class="font-semibold">{{ data.category?.category_name }}</span>
                                    </div>
                                </template>
                                <!-- Subcategory entry -->
                                <template v-else-if="data.subcategory_id">
                                    <i class="pi pi-folder text-lg text-muted-foreground"></i>
                                    <div class="font-medium">
                                        Any product from <span class="font-semibold">{{ data.subcategory?.category?.category_name }}</span> &gt; <span class="font-semibold">{{ data.subcategory?.subcategory_name }}</span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </Column>
                    <Column header="Required Qty" class="w-32 text-center">
                        <template #body="{ data }">
                            {{ data.required_quantity }}
                        </template>
                    </Column>
                </DataTable>
            </template>

            <!-- Minimum Spend-specific: Thresholds -->
            <template v-if="offer.type === 'minimum_spend' && offer.minimum_spends && offer.minimum_spends.length > 0">
                <Divider />
                <h2 class="text-lg font-semibold">Minimum Spend Thresholds</h2>
                <DataTable
                    :value="offer.minimum_spends"
                    dataKey="id"
                    size="small"
                    striped-rows
                    class="overflow-hidden rounded-lg border border-border"
                >
                    <Column header="Currency">
                        <template #body="{ data }">
                            {{ data.currency?.code ?? '-' }}
                        </template>
                    </Column>
                    <Column header="Minimum Amount">
                        <template #body="{ data }">
                            {{ data.currency?.symbol }}{{ Number(data.minimum_amount).toFixed(2) }}
                        </template>
                    </Column>
                </DataTable>
            </template>

            <!-- Category-specific -->
            <template v-if="offer.type === 'category' && offer.category">
                <Divider />
                <h2 class="text-lg font-semibold">Category</h2>
                <Card>
                    <template #content>
                        <p class="font-medium">{{ offer.category.category_name }}</p>
                    </template>
                </Card>
            </template>

            <!-- Brand-specific -->
            <template v-if="offer.type === 'brand' && offer.brand">
                <Divider />
                <h2 class="text-lg font-semibold">Brand</h2>
                <Card>
                    <template #content>
                        <p class="font-medium">{{ offer.brand.brand_name }}</p>
                    </template>
                </Card>
            </template>

            <!-- Activate Dialog -->
            <Dialog
                v-model:visible="activateDialogVisible"
                header="Activate Offer"
                :modal="true"
                :style="{ width: '450px' }"
            >
                <p class="text-sm">Are you sure you want to activate this offer? It will become available immediately.</p>
                <template #footer>
                    <Button label="Cancel" severity="secondary" size="small" @click="activateDialogVisible = false" />
                    <Button label="Activate" icon="pi pi-check-circle" severity="success" size="small" @click="activateOffer" />
                </template>
            </Dialog>

            <!-- Disable Dialog -->
            <Dialog
                v-model:visible="disableDialogVisible"
                header="Disable Offer"
                :modal="true"
                :style="{ width: '450px' }"
            >
                <p class="text-sm">Are you sure you want to disable this offer? It will no longer be applied.</p>
                <template #footer>
                    <Button label="Cancel" severity="secondary" size="small" @click="disableDialogVisible = false" />
                    <Button label="Disable" icon="pi pi-ban" severity="warn" size="small" @click="disableOffer" />
                </template>
            </Dialog>

            <!-- Delete Dialog -->
            <Dialog
                v-model:visible="deleteDialogVisible"
                header="Delete Offer"
                :modal="true"
                :style="{ width: '450px' }"
            >
                <p class="text-sm">Are you sure you want to delete this offer? This action cannot be undone.</p>
                <template #footer>
                    <Button label="Cancel" severity="secondary" size="small" @click="deleteDialogVisible = false" />
                    <Button label="Delete" icon="pi pi-trash" severity="danger" size="small" @click="deleteOffer" />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
