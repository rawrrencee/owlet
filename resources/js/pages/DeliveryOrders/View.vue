<script setup lang="ts">
import { usePermissions } from '@/composables/usePermissions';
import { useSmartBack } from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, DeliveryOrder, DeliveryOrderItem } from '@/types';
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
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { computed, ref } from 'vue';

interface Props {
    order: DeliveryOrder;
}

const props = defineProps<Props>();

const { goBack } = useSmartBack('/delivery-orders');
const { canAccessPage } = usePermissions();
const canSubmitOrders = computed(() => canAccessPage('delivery_orders.submit'));
const canManage = computed(() => canAccessPage('delivery_orders.manage'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Delivery Orders', href: '/delivery-orders' },
    { title: props.order.order_number },
];

// Submit dialog
const submitDialogVisible = ref(false);

// Approve dialog
const approveDialogVisible = ref(false);
const approveForm = useForm({
    items: [] as Array<{ id: number; received_quantity: number; correction_note: string }>,
});

function openApproveDialog() {
    approveForm.items = (props.order.items ?? []).map((item) => ({
        id: item.id,
        received_quantity: item.quantity,
        correction_note: '',
    }));
    approveDialogVisible.value = true;
}

function submitApprove() {
    approveForm.post(`/delivery-orders/${props.order.id}/approve`, {
        onSuccess: () => {
            approveDialogVisible.value = false;
        },
    });
}

// Reject dialog
const rejectDialogVisible = ref(false);
const rejectForm = useForm({
    rejection_reason: '',
});

function submitReject() {
    rejectForm.post(`/delivery-orders/${props.order.id}/reject`, {
        onSuccess: () => {
            rejectDialogVisible.value = false;
        },
    });
}

function submitOrder() {
    router.post(`/delivery-orders/${props.order.id}/submit`, {}, {
        onSuccess: () => {
            submitDialogVisible.value = false;
        },
    });
}

function deleteOrder() {
    router.delete(`/delivery-orders/${props.order.id}`);
}

function getStatusSeverity(status: string): string {
    switch (status) {
        case 'draft': return 'secondary';
        case 'submitted': return 'warn';
        case 'approved': return 'success';
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

const isDraft = computed(() => props.order.status === 'draft');
const isSubmitted = computed(() => props.order.status === 'submitted');
const isApproved = computed(() => props.order.status === 'approved');
const isResolved = computed(() => props.order.status === 'approved' || props.order.status === 'rejected');

// Revert dialog
const revertDialogVisible = ref(false);

function revertOrder() {
    router.post(`/delivery-orders/${props.order.id}/revert`, {}, {
        onSuccess: () => {
            revertDialogVisible.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`DO ${order.order_number}`" />

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
                        v-if="isDraft && canSubmitOrders"
                        label="Edit"
                        icon="pi pi-pencil"
                        severity="secondary"
                        size="small"
                        @click="router.get(`/delivery-orders/${order.id}/edit`)"
                    />
                    <Button
                        v-if="isDraft && canSubmitOrders"
                        label="Submit"
                        icon="pi pi-send"
                        size="small"
                        @click="submitDialogVisible = true"
                    />
                    <Button
                        v-if="isDraft && canSubmitOrders"
                        label="Delete"
                        icon="pi pi-trash"
                        severity="danger"
                        size="small"
                        @click="deleteOrder"
                    />
                    <Button
                        v-if="isSubmitted && canManage"
                        label="Approve"
                        icon="pi pi-check"
                        severity="success"
                        size="small"
                        @click="openApproveDialog"
                    />
                    <Button
                        v-if="isSubmitted && canManage"
                        label="Reject"
                        icon="pi pi-times"
                        severity="danger"
                        size="small"
                        @click="rejectDialogVisible = true"
                    />
                    <Button
                        v-if="isApproved && canManage"
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
                            <span class="text-sm text-muted-foreground">From Store</span>
                            <p class="font-medium">{{ order.store_from?.store_name }} ({{ order.store_from?.store_code }})</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">To Store</span>
                            <p class="font-medium">{{ order.store_to?.store_name }} ({{ order.store_to?.store_code }})</p>
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
                            <span class="text-sm text-muted-foreground">{{ order.status === 'approved' ? 'Approved' : 'Rejected' }} By</span>
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
                    <template #body="{ data }: { data: DeliveryOrderItem }">
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
                <Column header="Requested Qty" class="w-32 text-center">
                    <template #body="{ data }: { data: DeliveryOrderItem }">
                        {{ data.quantity }}
                    </template>
                </Column>
                <Column v-if="isResolved" header="Received Qty" class="w-32 text-center">
                    <template #body="{ data }: { data: DeliveryOrderItem }">
                        <span :class="{ 'text-orange-600 font-semibold': data.has_correction }">
                            {{ data.received_quantity ?? '-' }}
                        </span>
                    </template>
                </Column>
                <Column v-if="isResolved" header="Correction Note" class="hidden sm:table-cell">
                    <template #body="{ data }: { data: DeliveryOrderItem }">
                        {{ data.correction_note ?? '-' }}
                    </template>
                </Column>
            </DataTable>

            <!-- Submit Dialog -->
            <Dialog
                v-model:visible="submitDialogVisible"
                header="Submit Delivery Order"
                :modal="true"
                :style="{ width: '450px' }"
            >
                <p class="text-sm">Are you sure you want to submit this delivery order for approval?</p>
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

            <!-- Approve Dialog -->
            <Dialog
                v-model:visible="approveDialogVisible"
                header="Approve Delivery Order"
                :modal="true"
                :style="{ width: '700px' }"
            >
                <p class="mb-4 text-sm text-muted-foreground">
                    Review the received quantities. Update if different from requested and provide a correction note.
                </p>
                <DataTable
                    :value="approveForm.items"
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
                                        Requested: {{ order.items[index].quantity }}
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Received Qty" class="w-32">
                        <template #body="{ index }">
                            <InputNumber
                                v-model="approveForm.items[index].received_quantity"
                                :min="0"
                                size="small"
                                fluid
                            />
                        </template>
                    </Column>
                    <Column header="Correction Note">
                        <template #body="{ index }">
                            <InputText
                                v-model="approveForm.items[index].correction_note"
                                size="small"
                                fluid
                                :placeholder="approveForm.items[index].received_quantity !== (order.items?.[index]?.quantity ?? 0) ? 'Required...' : 'Optional'"
                            />
                            <small v-if="(approveForm.errors as any)[`items.${index}.correction_note`]" class="text-red-500">
                                {{ (approveForm.errors as any)[`items.${index}.correction_note`] }}
                            </small>
                        </template>
                    </Column>
                </DataTable>
                <template #footer>
                    <Button
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="approveDialogVisible = false"
                    />
                    <Button
                        label="Approve"
                        icon="pi pi-check"
                        severity="success"
                        size="small"
                        :loading="approveForm.processing"
                        @click="submitApprove"
                    />
                </template>
            </Dialog>

            <!-- Reject Dialog -->
            <Dialog
                v-model:visible="rejectDialogVisible"
                header="Reject Delivery Order"
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
                header="Revert Delivery Order"
                :modal="true"
                :style="{ width: '450px' }"
            >
                <p class="text-sm">Are you sure you want to revert this delivery order? Inventory changes will be reversed.</p>
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
