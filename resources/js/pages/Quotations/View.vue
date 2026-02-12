<script setup lang="ts">
import AuditInfo from '@/components/AuditInfo.vue';
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Quotation } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import { computed, ref } from 'vue';

interface Props {
    quotation: Quotation;
    logoDataUri: string | null;
    canCreate: boolean;
    canManage: boolean;
    canAdmin: boolean;
}

const props = defineProps<Props>();
const confirm = useConfirm();
const toast = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Quotations', href: '/quotations' },
    { title: props.quotation.quotation_number },
];

const isDraft = computed(() => props.quotation.status === 'draft');
const isSent = computed(() => props.quotation.status === 'sent');
const isAccepted = computed(() => props.quotation.status === 'accepted');
const canBeAccepted = computed(() =>
    ['sent', 'viewed', 'signed'].includes(props.quotation.status),
);

// Share dialog
const showShareDialog = ref(false);
const shareUrl = ref('');
const shareLoading = ref(false);
const hasPassword = ref(props.quotation.has_password ?? false);
const sharePassword = ref('');
const passwordSaving = ref(false);
const showPasswordSection = ref(false);

function getStatusSeverity(status: string): string {
    switch (status) {
        case 'draft': return 'secondary';
        case 'sent': return 'info';
        case 'viewed': return 'warn';
        case 'signed': return 'success';
        case 'accepted': return 'success';
        case 'paid': return 'contrast';
        case 'expired': return 'danger';
        default: return 'info';
    }
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatDateTime(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString();
}

function formatCurrency(value: string | null, symbol: string): string {
    if (!value) return '-';
    return `${symbol}${parseFloat(value).toFixed(2)}`;
}

function confirmSend() {
    confirm.require({
        message: 'Are you sure you want to mark this quotation as sent?',
        header: 'Mark as Sent',
        icon: 'pi pi-send',
        acceptLabel: 'Yes, Mark as Sent',
        rejectLabel: 'Cancel',
        accept: () => {
            router.post(`/quotations/${props.quotation.id}/send`);
        },
    });
}

function confirmRevertToDraft() {
    confirm.require({
        message: 'This will revert the quotation back to draft status, allowing it to be edited again.',
        header: 'Revert to Draft',
        icon: 'pi pi-undo',
        acceptLabel: 'Yes, Revert to Draft',
        rejectLabel: 'Cancel',
        accept: () => {
            router.post(`/quotations/${props.quotation.id}/revert-draft`);
        },
    });
}

function confirmAccept() {
    confirm.require({
        message: 'Are you sure you want to mark this quotation as accepted?',
        header: 'Mark as Accepted',
        icon: 'pi pi-check-circle',
        acceptLabel: 'Yes, Mark as Accepted',
        rejectLabel: 'Cancel',
        accept: () => {
            router.post(`/quotations/${props.quotation.id}/accept`);
        },
    });
}

function confirmMarkPaid() {
    confirm.require({
        message: 'Are you sure you want to mark this quotation as paid?',
        header: 'Mark as Paid',
        icon: 'pi pi-dollar',
        acceptLabel: 'Yes, Mark as Paid',
        rejectLabel: 'Cancel',
        accept: () => {
            router.post(`/quotations/${props.quotation.id}/mark-paid`);
        },
    });
}

function confirmDelete() {
    confirm.require({
        message: 'Are you sure you want to delete this quotation?',
        header: 'Delete Quotation',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(`/quotations/${props.quotation.id}`);
        },
    });
}

function duplicateQuotation() {
    router.post(`/quotations/${props.quotation.id}/duplicate`);
}

function downloadPdf() {
    window.open(`/quotations/${props.quotation.id}/pdf`, '_blank');
}

async function openShareDialog() {
    if (props.quotation.share_url) {
        shareUrl.value = props.quotation.share_url;
        showShareDialog.value = true;
        return;
    }

    shareLoading.value = true;
    try {
        const response = await fetch(`/quotations/${props.quotation.id}/share`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
        });
        if (!response.ok) throw new Error('Failed to generate share link');
        const data = await response.json();
        shareUrl.value = data.share_url;
        hasPassword.value = data.has_password;
        showShareDialog.value = true;
    } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to generate share link', life: 3000 });
    } finally {
        shareLoading.value = false;
    }
}

function copyShareUrl() {
    navigator.clipboard.writeText(shareUrl.value).then(() => {
        toast.add({ severity: 'success', summary: 'Copied', detail: 'Link copied to clipboard', life: 2000 });
    });
}

async function saveSharePassword() {
    passwordSaving.value = true;
    try {
        const response = await fetch(`/quotations/${props.quotation.id}/share-password`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
            body: JSON.stringify({ password: sharePassword.value || null }),
        });
        if (!response.ok) throw new Error('Failed to update password');
        const data = await response.json();
        hasPassword.value = data.has_password;
        sharePassword.value = '';
        showPasswordSection.value = false;
        toast.add({
            severity: 'success',
            summary: 'Updated',
            detail: data.has_password ? 'Password set successfully' : 'Password removed',
            life: 2000,
        });
    } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update password', life: 3000 });
    } finally {
        passwordSaving.value = false;
    }
}

async function removeSharePassword() {
    passwordSaving.value = true;
    try {
        const response = await fetch(`/quotations/${props.quotation.id}/share-password`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
            body: JSON.stringify({ password: null }),
        });
        if (!response.ok) throw new Error('Failed to remove password');
        hasPassword.value = false;
        sharePassword.value = '';
        showPasswordSection.value = false;
        toast.add({ severity: 'success', summary: 'Updated', detail: 'Password removed', life: 2000 });
    } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to remove password', life: 3000 });
    } finally {
        passwordSaving.value = false;
    }
}
</script>

<template>
    <Head :title="`Quotation ${quotation.quotation_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <BackButton fallback-url="/quotations" />
                    <h1 class="heading-lg">{{ quotation.quotation_number }}</h1>
                    <Tag :value="quotation.status_label" :severity="getStatusSeverity(quotation.status)" />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        v-if="isDraft && canManage"
                        label="Edit"
                        icon="pi pi-pencil"
                        size="small"
                        severity="secondary"
                        @click="router.get(`/quotations/${quotation.id}/edit`)"
                    />
                    <Button
                        v-if="isDraft && canManage"
                        label="Mark as Sent"
                        icon="pi pi-send"
                        size="small"
                        @click="confirmSend"
                    />
                    <Button
                        v-if="isSent && canManage"
                        label="Revert to Draft"
                        icon="pi pi-undo"
                        size="small"
                        severity="secondary"
                        @click="confirmRevertToDraft"
                    />
                    <Button
                        v-if="canBeAccepted && canAdmin"
                        label="Mark Accepted"
                        icon="pi pi-check-circle"
                        size="small"
                        severity="success"
                        @click="confirmAccept"
                    />
                    <Button
                        v-if="isAccepted && canAdmin"
                        label="Mark Paid"
                        icon="pi pi-dollar"
                        size="small"
                        severity="contrast"
                        @click="confirmMarkPaid"
                    />
                    <Button
                        label="PDF"
                        icon="pi pi-file-pdf"
                        size="small"
                        severity="secondary"
                        @click="downloadPdf"
                    />
                    <Button
                        v-if="!isDraft && canManage"
                        label="Share"
                        icon="pi pi-share-alt"
                        size="small"
                        severity="secondary"
                        :loading="shareLoading"
                        @click="openShareDialog"
                    />
                    <Button
                        v-if="canCreate || canManage"
                        label="Duplicate"
                        icon="pi pi-copy"
                        size="small"
                        severity="secondary"
                        @click="duplicateQuotation"
                    />
                    <Button
                        v-if="isDraft && canManage"
                        icon="pi pi-trash"
                        severity="danger"
                        text
                        size="small"
                        @click="confirmDelete"
                        v-tooltip.top="'Delete'"
                    />
                </div>
            </div>

            <div class="mx-auto w-full max-w-5xl">
                <Card>
                    <template #content>
                        <div class="flex flex-col gap-6">
                            <!-- Company Info -->
                            <div v-if="quotation.company">
                                <h3 class="mb-2 text-lg font-medium">Company</h3>
                                <img
                                    v-if="quotation.show_company_logo && logoDataUri"
                                    :src="logoDataUri"
                                    alt=""
                                    class="mb-2 h-14 object-contain"
                                />
                                <div class="text-sm">
                                    <div class="font-medium">{{ quotation.company.company_name }}</div>
                                    <div v-if="quotation.show_company_uen && quotation.company.registration_number" class="text-muted-foreground">
                                        UEN: {{ quotation.company.registration_number }}
                                    </div>
                                    <div v-if="quotation.show_company_address" class="mt-1 text-muted-foreground">
                                        <div v-if="quotation.company.address_1">{{ quotation.company.address_1 }}</div>
                                        <div v-if="quotation.company.address_2">{{ quotation.company.address_2 }}</div>
                                        <div v-if="quotation.company.city || quotation.company.postal_code">
                                            {{ quotation.company.city }}{{ quotation.company.city && quotation.company.postal_code ? ' ' : '' }}{{ quotation.company.postal_code }}
                                        </div>
                                    </div>
                                    <div v-if="quotation.company.email" class="text-muted-foreground">{{ quotation.company.email }}</div>
                                    <div v-if="quotation.company.phone_number" class="text-muted-foreground">{{ quotation.company.phone_number }}</div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Customer Info -->
                            <div>
                                <h3 class="mb-2 text-lg font-medium">Customer</h3>
                                <div v-if="quotation.customer" class="text-sm">
                                    <div class="font-medium">{{ quotation.customer.full_name }}</div>
                                    <div v-if="quotation.customer.email" class="text-muted-foreground">{{ quotation.customer.email }}</div>
                                    <div v-if="quotation.customer.phone" class="text-muted-foreground">{{ quotation.customer.phone }}</div>
                                    <div v-if="quotation.customer_discount_percentage" class="mt-1">
                                        <Tag :value="`${quotation.customer_discount_percentage}% discount`" severity="success" />
                                    </div>
                                </div>
                                <div v-else class="text-sm text-muted-foreground">No customer assigned</div>
                            </div>

                            <Divider />

                            <!-- Line Items -->
                            <div>
                                <h3 class="mb-2 text-lg font-medium">Line Items</h3>
                                <DataTable
                                    :value="quotation.items ?? []"
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
                                                    <Avatar v-else :label="data.product?.product_name?.charAt(0) ?? '?'" shape="square" class="!h-8 !w-8 flex-shrink-0 rounded bg-primary/10 text-primary" />
                                                </div>
                                                <div>
                                                    <div class="font-medium">{{ data.product?.product_name ?? 'Unknown' }}</div>
                                                    <div class="text-xs text-muted-foreground">
                                                        {{ data.product?.product_number ?? '' }}
                                                        <span v-if="data.product?.variant_name"> - {{ data.product.variant_name }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </Column>
                                    <Column header="Currency" class="hidden sm:table-cell w-20">
                                        <template #body="{ data }">
                                            {{ data.currency?.code ?? '-' }}
                                        </template>
                                    </Column>
                                    <Column header="Unit Price" class="hidden sm:table-cell w-28 text-right">
                                        <template #body="{ data }">
                                            <span class="text-right">{{ formatCurrency(data.unit_price, data.currency?.symbol ?? '') }}</span>
                                        </template>
                                    </Column>
                                    <Column header="Qty" class="w-16 text-center">
                                        <template #body="{ data }">
                                            {{ data.quantity }}
                                        </template>
                                    </Column>
                                    <Column header="Offer" class="hidden md:table-cell">
                                        <template #body="{ data }">
                                            <Tag v-if="data.offer_name" :value="data.offer_name" severity="success" />
                                            <span v-else class="text-xs text-muted-foreground">-</span>
                                        </template>
                                    </Column>
                                    <Column header="Total" class="w-28 text-right">
                                        <template #body="{ data }">
                                            <div class="text-right">
                                                <div class="font-medium">{{ formatCurrency(data.line_total, data.currency?.symbol ?? '') }}</div>
                                                <div v-if="parseFloat(data.line_discount) > 0" class="text-xs text-green-600">
                                                    -{{ formatCurrency(data.line_discount, data.currency?.symbol ?? '') }}
                                                </div>
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>

                            <!-- Totals -->
                            <div v-if="quotation.totals && quotation.totals.length > 0">
                                <h3 class="mb-2 text-lg font-medium">Totals</h3>
                                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <div
                                        v-for="total in quotation.totals"
                                        :key="total.currency_id"
                                        class="rounded-lg border border-border p-4"
                                    >
                                        <h4 class="mb-2 font-medium">{{ total.currency_code }}</h4>
                                        <div class="flex flex-col gap-1 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-muted-foreground">Subtotal</span>
                                                <span>{{ total.currency_symbol }}{{ parseFloat(total.subtotal).toFixed(2) }}</span>
                                            </div>
                                            <div v-if="parseFloat(total.discount) > 0" class="flex justify-between text-green-600">
                                                <span>Discount</span>
                                                <span>-{{ total.currency_symbol }}{{ parseFloat(total.discount).toFixed(2) }}</span>
                                            </div>
                                            <div v-if="parseFloat(total.tax) > 0" class="flex justify-between">
                                                <span class="text-muted-foreground">Tax{{ quotation.tax_inclusive ? ' (incl.)' : '' }}</span>
                                                <span>{{ total.currency_symbol }}{{ parseFloat(total.tax).toFixed(2) }}</span>
                                            </div>
                                            <Divider class="!my-1" />
                                            <div class="flex justify-between text-base font-semibold">
                                                <span>Total</span>
                                                <span>{{ total.currency_symbol }}{{ parseFloat(total.total).toFixed(2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tax Info -->
                            <div v-if="quotation.tax_mode !== 'none'">
                                <Divider />
                                <h3 class="mb-2 text-lg font-medium">Tax</h3>
                                <div class="text-sm">
                                    <div>
                                        <span class="text-muted-foreground">Mode:</span>
                                        <span class="ml-1 capitalize">{{ quotation.tax_mode }}</span>
                                    </div>
                                    <div v-if="quotation.tax_store">
                                        <span class="text-muted-foreground">Store:</span>
                                        <span class="ml-1">{{ quotation.tax_store.store_name }} ({{ quotation.tax_store.store_code }})</span>
                                    </div>
                                    <div v-if="quotation.tax_percentage">
                                        <span class="text-muted-foreground">Rate:</span>
                                        <span class="ml-1">{{ quotation.tax_percentage }}%{{ quotation.tax_inclusive ? ' (inclusive)' : '' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Optional Sections -->
                            <template v-if="quotation.terms_and_conditions">
                                <Divider />
                                <div>
                                    <h3 class="mb-2 text-lg font-medium">Terms & Conditions</h3>
                                    <div class="prose prose-sm max-w-none text-sm" v-html="quotation.terms_and_conditions" />
                                </div>
                            </template>

                            <template v-if="quotation.external_notes">
                                <Divider />
                                <div>
                                    <h3 class="mb-2 text-lg font-medium">Notes</h3>
                                    <p class="whitespace-pre-wrap text-sm">{{ quotation.external_notes }}</p>
                                </div>
                            </template>

                            <template v-if="quotation.internal_notes">
                                <Divider />
                                <div>
                                    <div class="mb-2 flex items-center gap-2">
                                        <h3 class="text-lg font-medium">Internal Notes</h3>
                                        <Tag value="Staff Only" severity="warn" />
                                    </div>
                                    <p class="whitespace-pre-wrap text-sm">{{ quotation.internal_notes }}</p>
                                </div>
                            </template>

                            <template v-if="quotation.payment_terms">
                                <Divider />
                                <div>
                                    <h3 class="mb-2 text-lg font-medium">Payment Terms</h3>
                                    <p class="whitespace-pre-wrap text-sm">{{ quotation.payment_terms }}</p>
                                </div>
                            </template>

                            <template v-if="quotation.validity_date">
                                <Divider />
                                <div>
                                    <h3 class="mb-2 text-lg font-medium">Validity</h3>
                                    <p class="text-sm">Valid until {{ formatDate(quotation.validity_date) }}</p>
                                </div>
                            </template>

                            <template v-if="quotation.payment_modes && quotation.payment_modes.length > 0">
                                <Divider />
                                <div>
                                    <h3 class="mb-2 text-lg font-medium">Accepted Payment Modes</h3>
                                    <div class="flex flex-wrap gap-2">
                                        <Tag v-for="pm in quotation.payment_modes" :key="pm.id" :value="pm.name" severity="info" />
                                    </div>
                                </div>
                            </template>

                            <!-- Lifecycle -->
                            <template v-if="quotation.sent_at || quotation.viewed_at || quotation.signed_at || quotation.expired_at">
                                <Divider />
                                <div>
                                    <h3 class="mb-2 text-lg font-medium">Lifecycle</h3>
                                    <div class="grid gap-1 text-sm sm:grid-cols-2">
                                        <div v-if="quotation.sent_at">
                                            <span class="text-muted-foreground">Sent at:</span>
                                            <span class="ml-1">{{ formatDateTime(quotation.sent_at) }}</span>
                                        </div>
                                        <div v-if="quotation.viewed_at">
                                            <span class="text-muted-foreground">Viewed at:</span>
                                            <span class="ml-1">{{ formatDateTime(quotation.viewed_at) }}</span>
                                        </div>
                                        <div v-if="quotation.signed_at">
                                            <span class="text-muted-foreground">Signed at:</span>
                                            <span class="ml-1">{{ formatDateTime(quotation.signed_at) }}</span>
                                        </div>
                                        <div v-if="quotation.expired_at">
                                            <span class="text-muted-foreground">Expired at:</span>
                                            <span class="ml-1">{{ formatDateTime(quotation.expired_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <Divider />

                            <!-- Audit Info -->
                            <AuditInfo
                                :created-by="quotation.created_by_user"
                                :updated-by="quotation.updated_by_user"
                                :created-at="quotation.created_at"
                                :updated-at="quotation.updated_at"
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Share Dialog -->
        <Dialog v-model:visible="showShareDialog" header="Share Quotation" modal :style="{ width: '450px' }">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <p class="text-sm text-muted-foreground">Share this link with the customer to view the quotation:</p>
                    <div class="flex items-center gap-2">
                        <InputText :model-value="shareUrl" readonly fluid size="small" />
                        <Button icon="pi pi-copy" severity="secondary" size="small" @click="copyShareUrl" v-tooltip.top="'Copy'" />
                    </div>
                </div>

                <Divider class="!my-0" />

                <!-- Password Protection -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-sm">
                            <i :class="hasPassword ? 'pi pi-lock text-green-600' : 'pi pi-lock-open text-muted-foreground'" />
                            <span>{{ hasPassword ? 'Password protected' : 'No password set' }}</span>
                        </div>
                        <Button
                            :label="showPasswordSection ? 'Cancel' : (hasPassword ? 'Change' : 'Set Password')"
                            severity="secondary"
                            text
                            size="small"
                            @click="showPasswordSection = !showPasswordSection; sharePassword = ''"
                        />
                    </div>

                    <div v-if="showPasswordSection" class="flex flex-col gap-2 rounded-md border border-border p-3">
                        <label class="text-sm font-medium">{{ hasPassword ? 'New Password' : 'Password' }}</label>
                        <Password
                            v-model="sharePassword"
                            size="small"
                            :feedback="false"
                            toggle-mask
                            fluid
                            placeholder="Enter password (min 4 characters)"
                        />
                        <div class="flex items-center gap-2">
                            <Button
                                :label="hasPassword ? 'Update Password' : 'Set Password'"
                                size="small"
                                :loading="passwordSaving"
                                :disabled="!sharePassword || sharePassword.length < 4"
                                @click="saveSharePassword"
                            />
                            <Button
                                v-if="hasPassword"
                                label="Remove Password"
                                severity="danger"
                                text
                                size="small"
                                :loading="passwordSaving"
                                @click="removeSharePassword"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>

        <ConfirmDialog />
        <Toast />
    </AppLayout>
</template>
