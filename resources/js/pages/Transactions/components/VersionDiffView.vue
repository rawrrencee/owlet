<script setup lang="ts">
import type { TransactionVersion } from '@/types';
import Select from 'primevue/select';
import { computed, ref } from 'vue';

const props = defineProps<{
    versions: TransactionVersion[];
    currencySymbol: string;
}>();

const versionOptions = computed(() =>
    props.versions.map((v) => ({
        label: `v${v.version_number} - ${getChangeLabel(v.change_type)}`,
        value: v.version_number,
    })),
);

const selectedVersion = ref<number | null>(
    props.versions.length > 0 ? props.versions[0].version_number : null,
);

const currentVersion = computed(() =>
    props.versions.find((v) => v.version_number === selectedVersion.value),
);

const previousVersion = computed(() => {
    if (!selectedVersion.value || selectedVersion.value <= 1) return null;
    return props.versions.find((v) => v.version_number === selectedVersion.value! - 1);
});

function getChangeLabel(changeType: string): string {
    switch (changeType) {
        case 'created': return 'Created';
        case 'item_added': return 'Item Added';
        case 'item_removed': return 'Item Removed';
        case 'item_modified': return 'Item Modified';
        case 'customer_changed': return 'Customer Changed';
        case 'payment_added': return 'Payment Added';
        case 'payment_removed': return 'Payment Removed';
        case 'discount_applied': return 'Discount Applied';
        case 'offer_applied': return 'Offer Applied';
        case 'completed': return 'Completed';
        case 'suspended': return 'Suspended';
        case 'resumed': return 'Resumed';
        case 'voided': return 'Voided';
        case 'refund': return 'Refund';
        default: return changeType;
    }
}

function fmt(amount: string | number | null | undefined): string {
    if (amount === null || amount === undefined) return `${props.currencySymbol}0.00`;
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return `${props.currencySymbol}${num.toFixed(2)}`;
}

// Compute item diffs
interface ItemDiff {
    product_name: string;
    product_number: string;
    type: 'added' | 'removed' | 'modified' | 'unchanged';
    before?: { quantity: number; unit_price: string; line_total: string };
    after?: { quantity: number; unit_price: string; line_total: string };
}

const itemDiffs = computed((): ItemDiff[] => {
    if (!currentVersion.value) return [];

    const currentItems = currentVersion.value.snapshot_items || [];
    const previousItems = previousVersion.value?.snapshot_items || [];

    const diffs: ItemDiff[] = [];

    // Find added and modified items
    for (const item of currentItems) {
        const prev = previousItems.find((p) => p.product_id === item.product_id && p.variant_name === item.variant_name);
        if (!prev) {
            diffs.push({
                product_name: item.product_name,
                product_number: item.product_number,
                type: 'added',
                after: { quantity: item.quantity, unit_price: item.unit_price, line_total: item.line_total },
            });
        } else if (prev.quantity !== item.quantity || prev.unit_price !== item.unit_price || prev.line_total !== item.line_total) {
            diffs.push({
                product_name: item.product_name,
                product_number: item.product_number,
                type: 'modified',
                before: { quantity: prev.quantity, unit_price: prev.unit_price, line_total: prev.line_total },
                after: { quantity: item.quantity, unit_price: item.unit_price, line_total: item.line_total },
            });
        }
    }

    // Find removed items
    for (const prev of previousItems) {
        const exists = currentItems.find((c) => c.product_id === prev.product_id && c.variant_name === prev.variant_name);
        if (!exists) {
            diffs.push({
                product_name: prev.product_name,
                product_number: prev.product_number,
                type: 'removed',
                before: { quantity: prev.quantity, unit_price: prev.unit_price, line_total: prev.line_total },
            });
        }
    }

    return diffs;
});

// Compute totals diff
interface TotalDiff {
    label: string;
    key: string;
    before: string;
    after: string;
    changed: boolean;
}

const totalsDiff = computed((): TotalDiff[] => {
    if (!currentVersion.value) return [];

    const current = currentVersion.value.snapshot_totals || {};
    const previous = previousVersion.value?.snapshot_totals || {};

    const keys = [
        { key: 'subtotal', label: 'Subtotal' },
        { key: 'offer_discount', label: 'Item Discounts' },
        { key: 'bundle_discount', label: 'Bundle Discount' },
        { key: 'minimum_spend_discount', label: 'Min Spend Discount' },
        { key: 'customer_discount', label: 'Customer Discount' },
        { key: 'manual_discount', label: 'Manual Discount' },
        { key: 'tax_amount', label: 'Tax' },
        { key: 'total', label: 'Total' },
        { key: 'amount_paid', label: 'Amount Paid' },
        { key: 'balance_due', label: 'Balance Due' },
    ];

    return keys
        .map(({ key, label }) => ({
            label,
            key,
            before: previous[key] ?? '0',
            after: current[key] ?? '0',
            changed: (previous[key] ?? '0') !== (current[key] ?? '0'),
        }))
        .filter((d) => d.changed || parseFloat(d.after) > 0);
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center gap-2">
            <span class="text-sm text-muted-foreground">Version:</span>
            <Select
                v-model="selectedVersion"
                :options="versionOptions"
                option-label="label"
                option-value="value"
                size="small"
                class="w-64"
            />
        </div>

        <div v-if="!currentVersion" class="text-center text-muted-foreground py-4 text-sm">
            Select a version to view changes.
        </div>

        <template v-else>
            <div class="text-sm">
                <p class="font-medium">{{ currentVersion.change_summary }}</p>
                <p class="text-xs text-muted-foreground mt-1">
                    {{ currentVersion.changed_by_user?.name ?? 'System' }}
                </p>
            </div>

            <!-- Item Changes -->
            <div v-if="itemDiffs.length > 0">
                <h4 class="text-xs text-muted-foreground font-semibold uppercase mb-2">Item Changes</h4>
                <div class="space-y-1">
                    <div
                        v-for="(diff, idx) in itemDiffs"
                        :key="idx"
                        class="rounded p-2 text-sm"
                        :class="{
                            'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300': diff.type === 'added',
                            'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300': diff.type === 'removed',
                            'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300': diff.type === 'modified',
                        }"
                    >
                        <div class="flex items-center gap-2">
                            <i
                                :class="{
                                    'pi pi-plus text-green-600': diff.type === 'added',
                                    'pi pi-minus text-red-600': diff.type === 'removed',
                                    'pi pi-pencil text-yellow-600': diff.type === 'modified',
                                }"
                                class="text-xs"
                            />
                            <span class="font-medium">{{ diff.product_name }}</span>
                            <span class="text-xs opacity-70">({{ diff.product_number }})</span>
                        </div>
                        <div class="ml-5 text-xs mt-1">
                            <template v-if="diff.type === 'added'">
                                Qty: {{ diff.after!.quantity }} @ {{ fmt(diff.after!.unit_price) }} = {{ fmt(diff.after!.line_total) }}
                            </template>
                            <template v-else-if="diff.type === 'removed'">
                                Was: Qty {{ diff.before!.quantity }} @ {{ fmt(diff.before!.unit_price) }} = {{ fmt(diff.before!.line_total) }}
                            </template>
                            <template v-else-if="diff.type === 'modified'">
                                <span>Qty: {{ diff.before!.quantity }} → {{ diff.after!.quantity }}</span>
                                <span class="ml-2">Total: {{ fmt(diff.before!.line_total) }} → {{ fmt(diff.after!.line_total) }}</span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Totals Changes -->
            <div v-if="totalsDiff.length > 0">
                <h4 class="text-xs text-muted-foreground font-semibold uppercase mb-2">Totals</h4>
                <div class="space-y-1">
                    <div
                        v-for="diff in totalsDiff"
                        :key="diff.key"
                        class="flex justify-between text-sm rounded p-1 px-2"
                        :class="diff.changed ? 'bg-yellow-50 dark:bg-yellow-900/20' : ''"
                    >
                        <span :class="diff.key === 'total' ? 'font-bold' : 'text-muted-foreground'">{{ diff.label }}</span>
                        <div v-if="diff.changed">
                            <span class="line-through text-muted-foreground mr-2">{{ fmt(diff.before) }}</span>
                            <span class="font-semibold">{{ fmt(diff.after) }}</span>
                        </div>
                        <span v-else>{{ fmt(diff.after) }}</span>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
