<script setup lang="ts">
import type { TransactionPayment } from '@/types';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import { ref } from 'vue';

const props = defineProps<{
    payments: TransactionPayment[];
    currencySymbol: string;
}>();

const expandedRows = ref({});

function fmt(amount: string | number | null | undefined): string {
    if (amount === null || amount === undefined) return `${props.currencySymbol}0.00`;
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return `${props.currencySymbol}${num.toFixed(2)}`;
}

function formatDateTime(dateStr: string | null | undefined): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <DataTable
        v-model:expandedRows="expandedRows"
        :value="payments"
        dataKey="id"
        striped-rows
        size="small"
        class="overflow-hidden rounded-lg border border-border"
    >
        <template #empty>
            <div class="p-4 text-center text-muted-foreground">No payments recorded.</div>
        </template>
        <Column expander class="w-[12%] sm:w-12 !pr-0 sm:hidden" />
        <Column field="row_number" header="#" class="w-12 hidden sm:table-cell">
            <template #body="{ data }">
                {{ data.row_number }}
            </template>
        </Column>
        <Column header="Payment Method">
            <template #body="{ data }">
                <span class="font-medium">{{ data.payment_mode_name }}</span>
            </template>
        </Column>
        <Column header="Amount">
            <template #body="{ data }">
                <span class="font-semibold">{{ fmt(data.amount) }}</span>
            </template>
        </Column>
        <Column header="Balance After" class="hidden sm:table-cell">
            <template #body="{ data }">
                {{ fmt(data.balance_after) }}
            </template>
        </Column>
        <Column header="By" class="hidden md:table-cell">
            <template #body="{ data }">
                {{ data.created_by_user?.name ?? '-' }}
            </template>
        </Column>
        <Column header="Date" class="hidden md:table-cell">
            <template #body="{ data }">
                <span class="text-sm">{{ formatDateTime(data.created_at) }}</span>
            </template>
        </Column>
        <template #expansion="{ data }">
            <div class="grid gap-3 p-3 text-sm sm:hidden">
                <div class="flex justify-between border-b border-border pb-2">
                    <span class="text-muted-foreground">#</span>
                    <span>{{ data.row_number }}</span>
                </div>
                <div class="flex justify-between border-b border-border pb-2">
                    <span class="text-muted-foreground">Balance After</span>
                    <span>{{ fmt(data.balance_after) }}</span>
                </div>
                <div class="flex justify-between border-b border-border pb-2">
                    <span class="text-muted-foreground">By</span>
                    <span>{{ data.created_by_user?.name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Date</span>
                    <span>{{ formatDateTime(data.created_at) }}</span>
                </div>
            </div>
        </template>
    </DataTable>
</template>
