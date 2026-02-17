<script setup lang="ts">
import type { Transaction } from '@/types';
import Button from 'primevue/button';
import Drawer from 'primevue/drawer';
import ProgressSpinner from 'primevue/progressspinner';
import Tag from 'primevue/tag';
import { ref, watch } from 'vue';

const props = defineProps<{
    visible: boolean;
    storeId: number;
    currencySymbol: string;
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    resume: [transaction: Transaction];
}>();

const suspended = ref<any[]>([]);
const loading = ref(false);

watch(() => props.visible, async (val) => {
    if (val && props.storeId) {
        loading.value = true;
        try {
            const response = await fetch(`/pos/suspended?store_id=${props.storeId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
                    ),
                },
            });
            suspended.value = await response.json();
        } catch {
            suspended.value = [];
        } finally {
            loading.value = false;
        }
    }
});

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
}

function fmt(amount: string | number): string {
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return `${props.currencySymbol}${num.toFixed(2)}`;
}
</script>

<template>
    <Drawer
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        header="Suspended Transactions"
        position="right"
        :style="{ width: '360px' }"
    >
        <div v-if="loading" class="flex justify-center p-8">
            <ProgressSpinner style="width: 40px; height: 40px" />
        </div>

        <div v-else-if="suspended.length === 0" class="text-center text-muted-color py-8">
            <p class="text-sm">No suspended transactions.</p>
        </div>

        <div v-else class="space-y-2">
            <div
                v-for="txn in suspended"
                :key="txn.id"
                class="border rounded-lg p-3 hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors"
            >
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <div class="text-sm font-semibold">{{ txn.transaction_number }}</div>
                        <div class="text-xs text-muted-color">{{ formatDate(txn.updated_at) }}</div>
                    </div>
                    <Tag value="Suspended" severity="warn" class="!text-[10px]" />
                </div>
                <div class="text-xs text-muted-color mb-1">
                    {{ txn.employee?.first_name }} {{ txn.employee?.last_name }}
                    <span v-if="txn.customer"> &middot; {{ txn.customer.first_name }} {{ txn.customer.last_name }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-sm font-bold">{{ fmt(txn.total) }}</span>
                        <span class="text-xs text-muted-color ml-2">{{ txn.items_count }} items</span>
                    </div>
                    <Button
                        label="Resume"
                        icon="pi pi-play"
                        size="small"
                        @click="emit('resume', txn)"
                    />
                </div>
            </div>
        </div>
    </Drawer>
</template>
