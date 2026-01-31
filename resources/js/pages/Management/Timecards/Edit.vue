<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';
import BackButton from '@/components/BackButton.vue';
import TimecardDetailsTable from '@/components/timecards/TimecardDetailsTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Timecard, TimecardDetail } from '@/types';

interface StoreOption {
    id: number;
    name: string;
    code: string;
}

interface StatusOption {
    value: string;
    label: string;
}

interface DetailTypeOption {
    value: string;
    label: string;
}

interface Props {
    timecard: Timecard;
    stores: StoreOption[];
    statuses: StatusOption[];
    detailTypes: DetailTypeOption[];
}

const props = defineProps<Props>();

const confirm = useConfirm();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Timecards', href: '/management/timecards' },
    { title: `Edit #${props.timecard.id}` },
];

const timecardForm = useForm({
    store_id: props.timecard.store_id,
    status: props.timecard.status,
});

// Detail editing
const showDetailDialog = ref(false);
const editingDetail = ref<TimecardDetail | null>(null);
const detailForm = useForm({
    type: 'WORK' as string,
    start_date: null as Date | null,
    end_date: null as Date | null,
});

const storeOptions = props.stores.map((s) => ({
    label: `${s.name} (${s.code})`,
    value: s.id,
}));

const statusOptions = props.statuses.map((s) => ({
    label: s.label,
    value: s.value,
}));

const detailTypeOptions = props.detailTypes.map((t) => ({
    label: t.label,
    value: t.value,
}));

function getStatusSeverity(): 'success' | 'info' | 'danger' {
    switch (props.timecard.status) {
        case 'COMPLETED':
            return 'success';
        case 'IN_PROGRESS':
            return 'info';
        case 'EXPIRED':
            return 'danger';
        default:
            return 'info';
    }
}

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function formatDateTime(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
}

function formatHours(hours: number): string {
    if (hours === 0) return '0h';
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
}

function handleTimecardSubmit() {
    timecardForm.put(`/management/timecards/${props.timecard.id}`, {
        preserveScroll: true,
    });
}

function openAddDetailDialog() {
    editingDetail.value = null;
    detailForm.type = 'WORK';
    detailForm.start_date = new Date();
    detailForm.end_date = null;
    showDetailDialog.value = true;
}

function openEditDetailDialog(detail: TimecardDetail) {
    editingDetail.value = detail;
    detailForm.type = detail.type;
    detailForm.start_date = new Date(detail.start_date);
    detailForm.end_date = detail.end_date ? new Date(detail.end_date) : null;
    showDetailDialog.value = true;
}

function handleDetailSubmit() {
    const data = {
        type: detailForm.type,
        start_date: detailForm.start_date?.toISOString(),
        end_date: detailForm.end_date?.toISOString(),
    };

    if (editingDetail.value) {
        router.put(
            `/management/timecards/${props.timecard.id}/details/${editingDetail.value.id}`,
            data,
            {
                preserveScroll: true,
                onSuccess: () => {
                    showDetailDialog.value = false;
                },
            }
        );
    } else {
        router.post(
            `/management/timecards/${props.timecard.id}/details`,
            data,
            {
                preserveScroll: true,
                onSuccess: () => {
                    showDetailDialog.value = false;
                },
            }
        );
    }
}

function confirmDeleteDetail(detail: TimecardDetail) {
    confirm.require({
        message: `Are you sure you want to delete this ${detail.type_label.toLowerCase()} entry?`,
        header: 'Delete Time Entry',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Delete',
        acceptProps: {
            severity: 'danger',
            size: 'small',
        },
        accept: () => {
            router.delete(
                `/management/timecards/${props.timecard.id}/details/${detail.id}`,
                { preserveScroll: true }
            );
        },
    });
}

function confirmDeleteTimecard() {
    confirm.require({
        message: 'Are you sure you want to delete this timecard? This will also delete all time entries.',
        header: 'Delete Timecard',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Delete',
        acceptProps: {
            severity: 'danger',
            size: 'small',
        },
        accept: () => {
            router.delete(`/management/timecards/${props.timecard.id}`);
        },
    });
}
</script>

<template>
    <Head :title="`Edit Timecard #${timecard.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <BackButton fallback-url="/management/timecards" />
                    <h1 class="heading-lg">Edit Timecard #{{ timecard.id }}</h1>
                    <Tag :value="timecard.status_label" :severity="getStatusSeverity()" />
                </div>
                <Button
                    label="Delete Timecard"
                    icon="pi pi-trash"
                    severity="danger"
                    size="small"
                    outlined
                    @click="confirmDeleteTimecard"
                />
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Timecard Info -->
                <Card>
                    <template #title>
                        <span class="text-base font-semibold">Timecard Information</span>
                    </template>
                    <template #content>
                        <form @submit.prevent="handleTimecardSubmit" class="flex flex-col gap-4">
                            <!-- Employee (read-only) -->
                            <div class="flex items-center gap-3 rounded-lg bg-muted/50 p-3">
                                <Avatar
                                    v-if="timecard.employee?.profile_picture_url"
                                    :image="timecard.employee.profile_picture_url"
                                    shape="circle"
                                />
                                <Avatar
                                    v-else-if="timecard.employee?.name"
                                    :label="getInitials(timecard.employee.name)"
                                    shape="circle"
                                    class="bg-primary/10 text-primary"
                                />
                                <div>
                                    <p class="font-semibold">{{ timecard.employee?.name }}</p>
                                    <p
                                        v-if="timecard.employee?.employee_number"
                                        class="text-sm text-muted-foreground"
                                    >
                                        #{{ timecard.employee.employee_number }}
                                    </p>
                                </div>
                            </div>

                            <!-- Store -->
                            <div class="flex flex-col gap-2">
                                <label for="store_id" class="text-sm font-medium">Store</label>
                                <Select
                                    id="store_id"
                                    v-model="timecardForm.store_id"
                                    :options="storeOptions"
                                    option-label="label"
                                    option-value="value"
                                    size="small"
                                    :invalid="!!timecardForm.errors.store_id"
                                />
                                <small v-if="timecardForm.errors.store_id" class="text-red-500">
                                    {{ timecardForm.errors.store_id }}
                                </small>
                            </div>

                            <!-- Status -->
                            <div class="flex flex-col gap-2">
                                <label for="status" class="text-sm font-medium">Status</label>
                                <Select
                                    id="status"
                                    v-model="timecardForm.status"
                                    :options="statusOptions"
                                    option-label="label"
                                    option-value="value"
                                    size="small"
                                    :invalid="!!timecardForm.errors.status"
                                />
                                <small v-if="timecardForm.errors.status" class="text-red-500">
                                    {{ timecardForm.errors.status }}
                                </small>
                            </div>

                            <Divider />

                            <!-- Summary -->
                            <div class="grid gap-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Start Time</span>
                                    <span>{{ formatDateTime(timecard.start_date) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">End Time</span>
                                    <span>{{ formatDateTime(timecard.end_date) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Total Hours Worked</span>
                                    <span class="font-bold text-green-600">
                                        {{ formatHours(timecard.hours_worked) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <Button
                                    label="Save Changes"
                                    icon="pi pi-check"
                                    size="small"
                                    type="submit"
                                    :loading="timecardForm.processing"
                                />
                            </div>
                        </form>
                    </template>
                </Card>

                <!-- Time Entries -->
                <Card>
                    <template #title>
                        <span class="text-base font-semibold">Time Entries</span>
                    </template>
                    <template #content>
                        <TimecardDetailsTable
                            :details="timecard.details || []"
                            :editable="true"
                            @add="openAddDetailDialog"
                            @edit="openEditDetailDialog"
                            @delete="confirmDeleteDetail"
                        />
                    </template>
                </Card>
            </div>
        </div>

        <!-- Detail Edit Dialog -->
        <Dialog
            v-model:visible="showDetailDialog"
            :header="editingDetail ? 'Edit Time Entry' : 'Add Time Entry'"
            :modal="true"
            :style="{ width: '400px' }"
        >
            <form @submit.prevent="handleDetailSubmit" class="flex flex-col gap-4">
                <!-- Type -->
                <div class="flex flex-col gap-2">
                    <label for="detail_type" class="text-sm font-medium">Type</label>
                    <Select
                        id="detail_type"
                        v-model="detailForm.type"
                        :options="detailTypeOptions"
                        option-label="label"
                        option-value="value"
                        size="small"
                    />
                </div>

                <!-- Start Time -->
                <div class="flex flex-col gap-2">
                    <label for="detail_start" class="text-sm font-medium">Start Time</label>
                    <DatePicker
                        id="detail_start"
                        v-model="detailForm.start_date"
                        show-time
                        hour-format="12"
                        size="small"
                    />
                </div>

                <!-- End Time -->
                <div class="flex flex-col gap-2">
                    <label for="detail_end" class="text-sm font-medium">End Time</label>
                    <DatePicker
                        id="detail_end"
                        v-model="detailForm.end_date"
                        show-time
                        hour-format="12"
                        size="small"
                    />
                    <small class="text-muted-foreground">
                        Leave empty for entries still in progress.
                    </small>
                </div>

                <div class="flex justify-end gap-2">
                    <Button
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        type="button"
                        @click="showDetailDialog = false"
                    />
                    <Button
                        :label="editingDetail ? 'Update' : 'Add'"
                        icon="pi pi-check"
                        size="small"
                        type="submit"
                        :loading="detailForm.processing"
                    />
                </div>
            </form>
        </Dialog>

        <ConfirmDialog />
    </AppLayout>
</template>
