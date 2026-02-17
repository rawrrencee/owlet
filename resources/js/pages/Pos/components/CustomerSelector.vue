<script setup lang="ts">
import AutoComplete from 'primevue/autocomplete';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { ref } from 'vue';

defineProps<{
    customer: any;
    disabled: boolean;
}>();

const emit = defineEmits<{
    select: [customerId: number];
    clear: [];
}>();

const searchQuery = ref('');
const suggestions = ref<any[]>([]);
const showSearch = ref(false);

async function searchCustomers(event: any) {
    const query = event.query?.trim();
    if (!query) {
        suggestions.value = [];
        return;
    }
    try {
        const response = await fetch(`/pos/search-customers?search=${encodeURIComponent(query)}`, {
            headers: {
                'Accept': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
                ),
            },
        });
        suggestions.value = await response.json();
    } catch {
        suggestions.value = [];
    }
}

function onSelect(event: any) {
    const customer = event.value;
    if (customer && typeof customer === 'object') {
        emit('select', customer.id);
        showSearch.value = false;
        searchQuery.value = '';
    }
}
</script>

<template>
    <div class="flex items-center gap-1">
        <template v-if="customer">
            <Tag
                :value="customer.full_name || `${customer.first_name} ${customer.last_name}`"
                severity="info"
                class="!text-xs"
            />
            <span v-if="customer.discount_percentage" class="text-[10px] text-green-600">
                {{ customer.discount_percentage }}% off
            </span>
            <Button icon="pi pi-times" text size="small" severity="secondary" @click="emit('clear')" />
        </template>
        <template v-else>
            <template v-if="showSearch">
                <AutoComplete
                    v-model="searchQuery"
                    :suggestions="suggestions"
                    optionLabel="full_name"
                    placeholder="Search customer..."
                    size="small"
                    class="w-40"
                    @complete="searchCustomers"
                    @item-select="onSelect"
                    @blur="showSearch = false"
                />
            </template>
            <Button
                v-else
                icon="pi pi-user-plus"
                text
                size="small"
                :disabled="disabled"
                v-tooltip.bottom="'Add Customer'"
                @click="showSearch = true"
            />
        </template>
    </div>
</template>
