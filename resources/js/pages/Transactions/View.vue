<script setup lang="ts">
import AuditInfo from '@/components/AuditInfo.vue';
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Transaction } from '@/types';
import { Head } from '@inertiajs/vue3';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';
import ItemsTable from './components/ItemsTable.vue';
import PaymentsTable from './components/PaymentsTable.vue';
import TransactionSummary from './components/TransactionSummary.vue';
import VersionDiffView from './components/VersionDiffView.vue';
import VersionTimeline from './components/VersionTimeline.vue';

const props = defineProps<{
    transaction: Transaction;
}>();

const activeTab = ref('0');

const currencySymbol = computed(() => props.transaction.currency?.symbol ?? '$');

const sortedVersions = computed(() => {
    if (!props.transaction.versions) return [];
    return [...props.transaction.versions].sort((a, b) => b.version_number - a.version_number);
});

const hasRefunds = computed(() => {
    return props.transaction.items?.some((item) => item.is_refunded) ?? false;
});

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
                                        <ItemsTable
                                            :items="transaction.items ?? []"
                                            :currency-symbol="currencySymbol"
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
    </AppLayout>
</template>
