<script setup lang="ts">
import AuditInfo from '@/components/AuditInfo.vue';
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ProductSearch from '@/pages/Pos/components/ProductSearch.vue';
import ProductVariantSelector from '@/pages/Pos/components/ProductVariantSelector.vue';
import RefundDialog from '@/pages/Pos/components/RefundDialog.vue';
import type { BreadcrumbItem, Transaction, TransactionItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import Divider from 'primevue/divider';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import AdjustItemDialog from './components/AdjustItemDialog.vue';
import AdjustPaymentDialog from './components/AdjustPaymentDialog.vue';
import ItemsTable from './components/ItemsTable.vue';
import PaymentsTable from './components/PaymentsTable.vue';
import TransactionSummary from './components/TransactionSummary.vue';
import VersionDiffView from './components/VersionDiffView.vue';
import VersionTimeline from './components/VersionTimeline.vue';

const props = defineProps<{
    transaction: Transaction;
    canVoid: boolean;
    paymentModes: Array<{ id: number; name: string }>;
}>();

const confirm = useConfirm();
const toast = useToast();
const activeTab = ref('0');

const currencySymbol = computed(() => props.transaction.currency?.symbol ?? '$');

const sortedVersions = computed(() => {
    if (!props.transaction.versions) return [];
    return [...props.transaction.versions].sort((a, b) => b.version_number - a.version_number);
});

const hasRefunds = computed(() => {
    return props.transaction.items?.some((item) => item.is_refunded) ?? false;
});

const isCompleted = computed(() => props.transaction.status === 'completed');
const canEdit = computed(() => isCompleted.value && props.canVoid);

// Refund dialog
const showRefundDialog = ref(false);

function handleRefund(items: Array<{ item_id: number; quantity: number; reason: string }>) {
    router.post(`/transactions/${props.transaction.id}/refund`, { items }, {
        preserveScroll: true,
        onSuccess: () => {
            showRefundDialog.value = false;
            toast.add({ severity: 'success', summary: 'Refund processed', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Failed to process refund', life: 3000 });
        },
    });
}

// Void
function confirmVoid() {
    confirm.require({
        message: `Are you sure you want to void transaction ${props.transaction.transaction_number}? This will restore inventory for all items.`,
        header: 'Void Transaction',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Void',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(`/transactions/${props.transaction.id}/void`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Transaction voided', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Failed to void transaction', life: 3000 });
                },
            });
        },
    });
}

// Adjust item dialog
const showAdjustDialog = ref(false);
const adjustItem = ref<TransactionItem | null>(null);

function handleEditItem(item: TransactionItem) {
    adjustItem.value = item;
    showAdjustDialog.value = true;
}

function handleSaveItem(data: { item_id: number; quantity: number; unit_price: number }) {
    router.put(`/transactions/${props.transaction.id}/items/${data.item_id}`, {
        quantity: data.quantity,
        unit_price: data.unit_price,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Item updated', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Failed to update item', life: 3000 });
        },
    });
}

// Remove item
function handleRemoveItem(item: TransactionItem) {
    confirm.require({
        message: `Remove "${item.product_name}" from this transaction? This will restore inventory.`,
        header: 'Remove Item',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Remove',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(`/transactions/${props.transaction.id}/items/${item.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Item removed', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Failed to remove item', life: 3000 });
                },
            });
        },
    });
}

// Add item via product search
const showVariantSelector = ref(false);
const selectedParentProduct = ref<any>(null);

function handleProductSelect(product: any) {
    if (product.variants && product.variants.length > 0) {
        selectedParentProduct.value = product;
        showVariantSelector.value = true;
    } else {
        addItemToTransaction(product.id);
    }
}

function handleVariantSelect(productId: number) {
    showVariantSelector.value = false;
    selectedParentProduct.value = null;
    addItemToTransaction(productId);
}

function addItemToTransaction(productId: number) {
    router.post(`/transactions/${props.transaction.id}/items`, {
        product_id: productId,
        quantity: 1,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Item added', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Failed to add item', life: 3000 });
        },
    });
}

// Payment adjustment dialog
const showPaymentDialog = ref(false);
const paymentSuggestedAmount = ref(0);

function openPaymentDialog(suggestedAmount: number) {
    paymentSuggestedAmount.value = suggestedAmount;
    showPaymentDialog.value = true;
}

function handleSavePayment(data: { payment_mode_id: number; amount: number }) {
    router.post(`/transactions/${props.transaction.id}/payments`, {
        payment_mode_id: data.payment_mode_id,
        amount: data.amount,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Payment recorded', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Failed to record payment', life: 3000 });
        },
    });
}

const hasRefundDue = computed(() => isCompleted.value && parseFloat(props.transaction.change_amount || '0') > 0);
const hasBalanceDue = computed(() => isCompleted.value && parseFloat(props.transaction.balance_due || '0') > 0);

function getStatusSeverity(status: string): string {
    switch (status) {
        case 'draft': return 'info';
        case 'suspended': return 'warn';
        case 'completed': return 'success';
        case 'voided': return 'danger';
        default: return 'info';
    }
}

function getStatusLabel(status: string): string {
    switch (status) {
        case 'draft': return 'Draft';
        case 'suspended': return 'Suspended';
        case 'completed': return 'Completed';
        case 'voided': return 'Voided';
        default: return status;
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transactions', href: '/transactions' },
    { title: props.transaction.transaction_number },
];
</script>

<template>
    <Head :title="`Transaction ${transaction.transaction_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <BackButton fallback-url="/transactions" />
                    <h1 class="heading-lg">{{ transaction.transaction_number }}</h1>
                    <Tag
                        :value="getStatusLabel(transaction.status)"
                        :severity="getStatusSeverity(transaction.status)"
                    />
                </div>
                <div v-if="canEdit" class="flex flex-wrap items-center gap-2">
                    <Button
                        v-if="hasRefundDue"
                        label="Record Refund"
                        icon="pi pi-replay"
                        severity="warn"
                        size="small"
                        @click="openPaymentDialog(-parseFloat(transaction.change_amount))"
                    />
                    <Button
                        v-if="hasBalanceDue"
                        label="Record Payment"
                        icon="pi pi-wallet"
                        size="small"
                        @click="openPaymentDialog(parseFloat(transaction.balance_due))"
                    />
                    <Button
                        label="Refund"
                        icon="pi pi-replay"
                        severity="warn"
                        size="small"
                        outlined
                        @click="showRefundDialog = true"
                    />
                    <Button
                        label="Void"
                        icon="pi pi-times-circle"
                        severity="danger"
                        size="small"
                        outlined
                        @click="confirmVoid"
                    />
                </div>
            </div>

            <!-- Content -->
            <div class="mx-auto w-full max-w-5xl">
                <Card>
                    <template #content>
                        <Tabs v-model:value="activeTab">
                            <TabList>
                                <Tab value="0">Details</Tab>
                                <Tab value="1">
                                    Items
                                    <span v-if="transaction.items" class="ml-1 text-xs text-muted-foreground">({{ transaction.items.length }})</span>
                                </Tab>
                                <Tab value="2">
                                    Payments
                                    <span v-if="transaction.payments" class="ml-1 text-xs text-muted-foreground">({{ transaction.payments.length }})</span>
                                </Tab>
                                <Tab value="3">
                                    History
                                    <span v-if="sortedVersions.length" class="ml-1 text-xs text-muted-foreground">({{ sortedVersions.length }})</span>
                                </Tab>
                                <Tab v-if="hasRefunds" value="4">Refunds</Tab>
                            </TabList>
                            <TabPanels>
                                <!-- Details Tab -->
                                <TabPanel value="0">
                                    <div class="py-4">
                                        <TransactionSummary :transaction="transaction" />

                                        <Divider />

                                        <AuditInfo
                                            :created-by="transaction.created_by_user"
                                            :updated-by="transaction.updated_by_user"
                                            :created-at="transaction.created_at"
                                            :updated-at="transaction.updated_at"
                                        />
                                    </div>
                                </TabPanel>

                                <!-- Items Tab -->
                                <TabPanel value="1">
                                    <div class="py-4">
                                        <div v-if="canEdit" class="mb-4">
                                            <label class="block text-sm font-medium mb-1">Add Item</label>
                                            <ProductSearch
                                                :store-id="transaction.store_id"
                                                :currency-id="transaction.currency_id"
                                                @select="handleProductSelect"
                                            />
                                        </div>
                                        <ItemsTable
                                            :items="transaction.items ?? []"
                                            :currency-symbol="currencySymbol"
                                            :editable="canEdit"
                                            @edit-item="handleEditItem"
                                            @remove-item="handleRemoveItem"
                                        />
                                    </div>
                                </TabPanel>

                                <!-- Payments Tab -->
                                <TabPanel value="2">
                                    <div class="py-4">
                                        <PaymentsTable
                                            :payments="transaction.payments ?? []"
                                            :currency-symbol="currencySymbol"
                                        />
                                    </div>
                                </TabPanel>

                                <!-- Version History Tab -->
                                <TabPanel value="3">
                                    <div class="py-4 space-y-6">
                                        <VersionTimeline :versions="sortedVersions" />

                                        <Divider v-if="sortedVersions.length > 0" />

                                        <VersionDiffView
                                            v-if="sortedVersions.length > 0"
                                            :versions="sortedVersions"
                                            :currency-symbol="currencySymbol"
                                        />
                                    </div>
                                </TabPanel>

                                <!-- Refunds Tab -->
                                <TabPanel v-if="hasRefunds" value="4">
                                    <div class="py-4">
                                        <h3 class="text-sm font-semibold mb-3">Refunded Items</h3>
                                        <div class="space-y-2">
                                            <div
                                                v-for="item in (transaction.items ?? []).filter(i => i.is_refunded)"
                                                :key="item.id"
                                                class="flex items-center justify-between rounded border p-3 text-sm bg-red-50 dark:bg-red-900/10"
                                            >
                                                <div>
                                                    <div class="font-medium">{{ item.product_name }}</div>
                                                    <div class="text-xs text-muted-foreground">
                                                        {{ item.product_number }}
                                                        <span v-if="item.variant_name"> &middot; {{ item.variant_name }}</span>
                                                    </div>
                                                    <div v-if="item.refund_reason" class="text-xs text-red-600 mt-1">
                                                        Reason: {{ item.refund_reason }}
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="font-semibold">Qty: {{ item.quantity }}</div>
                                                    <div class="text-xs text-muted-foreground">{{ currencySymbol }}{{ parseFloat(item.line_total).toFixed(2) }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="parseFloat(transaction.refund_amount || '0') > 0" class="mt-4 flex justify-between border-t pt-3 font-semibold text-red-600">
                                            <span>Total Refunded</span>
                                            <span>{{ currencySymbol }}{{ parseFloat(transaction.refund_amount).toFixed(2) }}</span>
                                        </div>
                                    </div>
                                </TabPanel>
                            </TabPanels>
                        </Tabs>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Dialogs -->
        <ConfirmDialog />

        <RefundDialog
            :visible="showRefundDialog"
            :transaction="transaction"
            :currency-symbol="currencySymbol"
            @update:visible="showRefundDialog = $event"
            @refund="handleRefund"
        />

        <AdjustItemDialog
            :visible="showAdjustDialog"
            :item="adjustItem"
            :currency-symbol="currencySymbol"
            @update:visible="showAdjustDialog = $event"
            @save="handleSaveItem"
        />

        <ProductVariantSelector
            :visible="showVariantSelector"
            :product="selectedParentProduct"
            :store-id="transaction.store_id"
            :currency-id="transaction.currency_id"
            :currency-symbol="currencySymbol"
            @update:visible="showVariantSelector = $event"
            @select="handleVariantSelect"
        />

        <AdjustPaymentDialog
            :visible="showPaymentDialog"
            :payment-modes="paymentModes"
            :currency-symbol="currencySymbol"
            :suggested-amount="paymentSuggestedAmount"
            @update:visible="showPaymentDialog = $event"
            @save="handleSavePayment"
        />
    </AppLayout>
</template>
