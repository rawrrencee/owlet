<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { LeaveType } from '@/types/leave';
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';

interface Props {
    leaveTypes: LeaveType[];
    breadcrumbs: BreadcrumbItem[];
}

defineProps<Props>();

const confirm = useConfirm();

function confirmDelete(leaveType: LeaveType) {
    confirm.require({
        message: `Delete "${leaveType.name}"? This cannot be undone.`,
        header: 'Delete Leave Type',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: { severity: 'secondary', size: 'small' },
        acceptLabel: 'Delete',
        acceptProps: { severity: 'danger', size: 'small' },
        accept: () => {
            router.delete(`/management/leave-types/${leaveType.id}`, {
                preserveScroll: true,
            });
        },
    });
}
</script>

<template>
    <Head title="Leave Types" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <h1 class="heading-lg">Leave Types</h1>
                <Button
                    label="Add Leave Type"
                    icon="pi pi-plus"
                    size="small"
                    @click="router.visit('/management/leave-types/create')"
                />
            </div>

            <DataTable
                :value="leaveTypes"
                dataKey="id"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No leave types found.
                    </div>
                </template>

                <Column header="Order" :style="{ width: '4rem' }">
                    <template #body="{ data }">
                        <span class="text-muted-foreground">{{
                            data.sort_order
                        }}</span>
                    </template>
                </Column>

                <Column header="Color" :style="{ width: '3rem' }">
                    <template #body="{ data }">
                        <div
                            v-if="data.color"
                            class="h-5 w-5 rounded-full border border-border"
                            :style="{ backgroundColor: data.color }"
                        ></div>
                    </template>
                </Column>

                <Column header="Name">
                    <template #body="{ data }">
                        <div>
                            <span class="font-medium">{{ data.name }}</span>
                            <span class="ml-2 text-xs text-muted-foreground"
                                >({{ data.code }})</span
                            >
                        </div>
                        <div
                            v-if="data.description"
                            class="text-xs text-muted-foreground"
                        >
                            {{ data.description }}
                        </div>
                    </template>
                </Column>

                <Column
                    header="Balance"
                    :style="{ width: '6rem' }"
                    class="hidden sm:table-cell"
                >
                    <template #body="{ data }">
                        <Tag
                            :value="
                                data.requires_balance
                                    ? 'Required'
                                    : 'Not Required'
                            "
                            :severity="
                                data.requires_balance ? 'info' : 'secondary'
                            "
                            class="!text-xs"
                        />
                    </template>
                </Column>

                <Column header="Status" :style="{ width: '5rem' }">
                    <template #body="{ data }">
                        <Tag
                            :value="data.is_active ? 'Active' : 'Inactive'"
                            :severity="data.is_active ? 'success' : 'secondary'"
                            class="!text-xs"
                        />
                    </template>
                </Column>

                <Column header="" :style="{ width: '6rem' }">
                    <template #body="{ data }">
                        <div class="flex justify-end gap-1">
                            <Button
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click="
                                    router.visit(
                                        `/management/leave-types/${data.id}/edit`,
                                    )
                                "
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="confirmDelete(data)"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <ConfirmDialog />
        </div>
    </AppLayout>
</template>
