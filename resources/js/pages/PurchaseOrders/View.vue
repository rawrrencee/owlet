<script setup lang="ts">
import { usePermissions } from '@/composables/usePermissions';
import { useSmartBack } from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, PurchaseOrder, PurchaseOrderItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { computed, ref } from 'vue';

interface Props {
    order: PurchaseOrder;
    stores: Array<{ id: number; store_name: string; store_code: string }>;
}

const props = defineProps<Props>();

const { goBack } = useSmartBack('/purchase-orders');
const { canAccessPage } = usePermissions();
const canCreate = computed(() => canAccessPage('purchase_orders.create'));
const canAccept = computed(() => canAccessPage('purchase_orders.accept'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Purchase Orders', href: '/purchase-orders' },
    { title: props.order.order_number },
];

const storeOptions = computed(() =>
    props.stores.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: s.id,
    })),
);

// Submit dialog
const submitDialogVisible = ref(false);

// Accept dialog
const acceptDialogVisible = ref(false);
const acceptForm = useForm({
    store_id: null as number | null,
    items: [] as Array<{ id: number; received_quantity: number; correction_note: string }>,
});

function openAcceptDialog() {
    acceptForm.store_id = null;
    acceptForm.items = (props.order.items ?? []).map((item) => ({
        id: item.id,
        received_quantity: item.quantity,
        correction_note: '',
    }));
    acceptDialogVisible.value = true;
}

function submitAccept() {
    acceptForm.post(`/purchase-orders/${props.order.id}/accept`, {
        onSuccess: () => {
            acceptDialogVisible.value = false;
        },
    });
}

// Reject dialog
const rejectDialogVisible = ref(false);
const rejectForm = useForm({
    rejection_reason: '',
});

function submitReject() {
    rejectForm.post(`/purchase-orders/${props.order.id}/reject`, {
        onSuccess: () => {
            rejectDialogVisible.value = false;
        },
    });
}

function submitOrder() {
    router.post(`/purchase-orders/${props.order.id}/submit`, {}, {
        onSuccess: () => {
            submitDialogVisible.value = false;
        },
    });
}

function deleteOrder() {
    router.delete(`/purchase-orders/${props.order.id}`);
}

function getStatusSeverity(status: string): string {
    switch (status) {
        case 'draft': return 'secondary';
        case 'submitted': return 'warn';
        case 'accepted': return 'success';
        case 'rejected': return 'danger';
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

function formatCost(cost: string | number): string {
    return Number(cost).toFixed(2);
}

const isDraft = computed(() => props.order.status === 'draft');
const isSubmitted = computed(() => props.order.status === 'submitted');
const isAccepted = computed(() => props.order.status === 'accepted');
const isResolved = computed(() => props.order.status === 'accepted' || props.order.status === 'rejected');

// Revert dialog
const revertDialogVisible = ref(false);

function revertOrder() {
    router.post(`/purchase-orders/${props.order.id}/revert`, {}, {
        onSuccess: () => {
            revertDialogVisible.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`PO ${order.order_number}`" />

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
                        <h1 class="heading-lg">{{ order.order_number }}</h1>
                    </div>
                    <Tag
                        :value="order.status_label"
                        :severity="getStatusSeverity(order.status)"
                    />
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="isDraft && canCreate"
                        label="Edit"
                        icon="pi pi-pencil"
                        severity="secondary"
                        size="small"
                        @click="router.get(`/purchase-orders/${order.id}/edit`)"
                    />
                    <Button
                        v-if="isDraft && canCreate"
                        label="Submit"
                        icon="pi pi-send"
                        size="small"
                        @click="submitDialogVisible = true"
                    />
                    <Button
                        v-if="isDraft && canCreate"
                        label="Delete"
                        icon="pi pi-trash"
                        severity="danger"
                        size="small"
                        @click="deleteOrder"
                    />
                    <Button
                        v-if="isSubmitted && canAccept"
                        label="Accept"
                        icon="pi pi-check"
                        severity="success"
                        size="small"
                        @click="openAcceptDialog"
                    />
                    <Button
                        v-if="isSubmitted && canAccept"
                        label="Reject"
                        icon="pi pi-times"
                        severity="danger"
                        size="small"
                        @click="rejectDialogVisible = true"
                    />
                    <Button
                        v-if="isAccepted && canAccept"
                        label="Revert"
                        icon="pi pi-undo"
                        severity="warn"
                        size="small"
                        @click="revertDialogVisible = true"
                    />
                </div>
            </div>

            <!-- Details Card -->
            <Card>
                <template #content>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <span class="text-sm text-muted-foreground">Supplier</span>
                            <p class="font-medium">{{ order.supplier?.supplier_name ?? 'Not specified' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Destination Store</span>
                            <p class="font-medium">
                                {{ order.store ? `${order.store.store_name} (${order.store.store_code})` : 'Not assigned yet' }}
                            </p>
                        </div>
                        <div v-if="order.notes">
                            <span class="text-sm text-muted-foreground">Notes</span>
                            <p>{{ order.notes }}</p>
                        </div>
                        <div v-if="order.created_by_user">
                            <span class="text-sm text-muted-foreground">Created By</span>
                            <p>{{ order.created_by_user.name }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Created At</span>
                            <p>{{ formatDate(order.created_at) }}</p>
                        </div>
                        <div v-if="order.submitted_by_user">
                            <span class="text-sm text-muted-foreground">Submitted By</span>
                            <p>{{ order.submitted_by_user.name }} on {{ formatDate(order.submitted_at) }}</p>
                        </div>
                        <div v-if="order.resolved_by_user">
                            <span class="text-sm text-muted-foreground">{{ order.status === 'accepted' ? 'Accepted' : 'Rejected' }} By</span>
                            <p>{{ order.resolved_by_user.name }} on {{ formatDate(order.resolved_at) }}</p>
                        </div>
                        <div v-if="order.rejection_reason" class="sm:col-span-2">
                            <span class="text-sm text-muted-foreground">Rejection Reason</span>
                            <p class="text-red-600">{{ order.rejection_reason }}</p>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Items Table -->
            <Divider />
            <h2 class="text-lg font-semibold">Items</h2>
            <DataTable
                :value="order.items ?? []"
                dataKey="id"
                size="small"
                striped-rows
                class="overflow-hidden rounded-lg border border-border"
            >
                <Column header="Product">
                    <template #body="{ data }: { data: PurchaseOrderItem }">
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
                <Column header="Currency" class="hidden sm:table-cell">
                    <template #body="{ data }: { data: PurchaseOrderItem }">
                        {{ data.currency?.code }}
                    </template>
                </Column>
                <Column header="Unit Cost" class="text-right">
                    <template #body="{ data }: { data: PurchaseOrderItem }">
                        {{ data.currency?.symbol }}{{ formatCost(data.unit_cost) }}
                    </template>
                </Column>
                <Column header="Qty" class="w-20 text-center">
                    <template #body="{ data }: { data: PurchaseOrderItem }">
                        {{ data.quantity }}
                    </template>
                </Column>
                <Column header="Total" class="text-right">
                    <template #body="{ data }: { data: PurchaseOrderItem }">
                        {{ data.currency?.symbol }}{{ formatCost(data.total_cost) }}
                    </template>
                </Column>
                <Column v-if="isResolved" header="Received" class="w-24 text-center">
                    <template #body="{ data }: { data: PurchaseOrderItem }">
                        <span :class="{ 'text-orange-600 font-semibold': data.has_correction }">
                            {{ data.received_quantity ?? '-' }}
                        </span>
                    </template>
                </Column>
                <Column v-if="isResolved" header="Note" class="hidden sm:table-cell">
                    <template #body="{ data }: { data: PurchaseOrderItem }">
                        {{ data.correction_note ?? '-' }}
                    </template>
                </Column>
            </DataTable>

            <!-- Submit Dialog -->
            <Dialog
                v-model:visible="submitDialogVisible"
                header="Submit Purchase Order"
                :modal="true"
                :style="{ width: '450px' }"
            >
                <p class="text-sm">Are you sure you want to submit this purchase order?</p>
                <template #footer>
                    <Button
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="submitDialogVisible = false"
                    />
                    <Button
                        label="Submit"
                        icon="pi pi-send"
                        size="small"
                        @click="submitOrder"
                    />
                </template>
            </Dialog>

            <!-- Accept Dialog -->
            <Dialog
                v-model:visible="acceptDialogVisible"
                header="Accept Purchase Order"
                :modal="true"
                :style="{ width: '750px' }"
            >
                <div class="mb-4 flex flex-col gap-2">
                    <label class="text-sm font-medium">Destination Store *</label>
                    <Select
                        v-model="acceptForm.store_id"
                        :options="storeOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Select store to receive items"
                        size="small"
                        filter
                    />
                    <small v-if="acceptForm.errors.store_id" class="text-red-500">{{ acceptForm.errors.store_id }}</small>
                </div>

                <p class="mb-4 text-sm text-muted-foreground">
                    Review received quantities and provide correction notes where needed.
                </p>

                <DataTable
                    :value="acceptForm.items"
                    dataKey="id"
                    size="small"
                    class="overflow-hidden rounded-lg border border-border"
                >
                    <Column header="Product">
                        <template #body="{ index }">
                            <div v-if="order.items?.[index]" class="flex items-center gap-2">
                                <img v-if="order.items[index].product?.image_url" :src="order.items[index].product!.image_url!" class="h-8 w-8 flex-shrink-0 rounded object-cover" alt="" />
                                <Avatar v-else :label="order.items[index].product?.product_name?.charAt(0)" shape="square" class="!h-8 !w-8 flex-shrink-0 rounded bg-primary/10 text-primary" />
                                <div>
                                    <div class="font-medium">{{ order.items[index].product?.product_name }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        Ordered: {{ order.items[index].quantity }}
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Received Qty" class="w-32">
                        <template #body="{ index }">
                            <InputNumber
                                v-model="acceptForm.items[index].received_quantity"
                                :min="0"
                                size="small"
                                fluid
                            />
                        </template>
                    </Column>
                    <Column header="Correction Note">
                        <template #body="{ index }">
                            <InputText
                                v-model="acceptForm.items[index].correction_note"
                                size="small"
                                fluid
                                @click.stop
                                :placeholder="acceptForm.items[index].received_quantity !== (order.items?.[index]?.quantity ?? 0) ? 'Required...' : 'Optional'"
                            />
                            <small v-if="(acceptForm.errors as any)[`items.${index}.correction_note`]" class="text-red-500">
                                {{ (acceptForm.errors as any)[`items.${index}.correction_note`] }}
                            </small>
                        </template>
                    </Column>
                </DataTable>
                <template #footer>
                    <Button
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="acceptDialogVisible = false"
                    />
                    <Button
                        label="Accept"
                        icon="pi pi-check"
                        severity="success"
                        size="small"
                        :loading="acceptForm.processing"
                        @click="submitAccept"
                    />
                </template>
            </Dialog>

            <!-- Reject Dialog -->
            <Dialog
                v-model:visible="rejectDialogVisible"
                header="Reject Purchase Order"
                :modal="true"
                :style="{ width: '500px' }"
            >
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Rejection Reason *</label>
                    <Textarea
                        v-model="rejectForm.rejection_reason"
                        rows="3"
                        placeholder="Provide a reason for rejection..."
                        class="w-full"
                    />
                    <small v-if="rejectForm.errors.rejection_reason" class="text-red-500">
                        {{ rejectForm.errors.rejection_reason }}
                    </small>
                </div>
                <template #footer>
                    <Button
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="rejectDialogVisible = false"
                    />
                    <Button
                        label="Reject"
                        icon="pi pi-times"
                        severity="danger"
                        size="small"
                        :loading="rejectForm.processing"
                        @click="submitReject"
                    />
                </template>
            </Dialog>

            <!-- Revert Dialog -->
            <Dialog
                v-model:visible="revertDialogVisible"
                header="Revert Purchase Order"
                :modal="true"
                :style="{ width: '450px' }"
            >
                <p class="text-sm">Are you sure you want to revert this purchase order? Inventory changes will be reversed.</p>
                <template #footer>
                    <Button
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="revertDialogVisible = false"
                    />
                    <Button
                        label="Revert"
                        icon="pi pi-undo"
                        severity="warn"
                        size="small"
                        @click="revertOrder"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
