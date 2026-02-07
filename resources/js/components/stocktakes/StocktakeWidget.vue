<script setup lang="ts">
import type { Stocktake } from '@/types';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { ref } from 'vue';

interface StoreOption {
    id: number;
    store_name: string;
    store_code: string;
}

interface Props {
    activeStocktake: Stocktake | null;
    stores: StoreOption[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    stateChanged: [];
    close: [];
}>();

const selectedStoreId = ref<number | null>(null);
const starting = ref(false);

function resumeStocktake() {
    if (props.activeStocktake) {
        emit('close');
        router.get(`/stocktakes/${props.activeStocktake.id}`);
    }
}

function startStocktake() {
    if (!selectedStoreId.value) return;
    starting.value = true;
    emit('close');
    router.post('/stocktakes', { store_id: selectedStoreId.value }, {
        onFinish: () => {
            starting.value = false;
        },
    });
}

function goToStocktakes() {
    emit('close');
    router.get('/stocktakes');
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}
</script>

<template>
    <div class="space-y-3">
        <!-- Active Stocktake -->
        <div v-if="activeStocktake" class="rounded-lg border border-primary/30 bg-primary/5 p-3">
            <div class="mb-2 flex items-center gap-2">
                <i class="pi pi-clock text-sm text-primary" />
                <span class="text-sm font-semibold">Active Stocktake</span>
            </div>
            <div class="space-y-1 text-sm">
                <div>
                    <span class="text-muted-foreground">Store: </span>
                    <span class="font-medium">
                        {{ activeStocktake.store?.store_name }}
                        ({{ activeStocktake.store?.store_code }})
                    </span>
                </div>
                <div>
                    <span class="text-muted-foreground">Items: </span>
                    <span class="font-medium">{{ activeStocktake.items_count ?? 0 }}</span>
                </div>
                <div>
                    <span class="text-muted-foreground">Started: </span>
                    <span>{{ formatDate(activeStocktake.created_at) }}</span>
                </div>
            </div>
            <Button
                label="Resume"
                icon="pi pi-play"
                size="small"
                class="mt-2 w-full"
                @click="resumeStocktake"
            />
        </div>

        <!-- Start New Stocktake -->
        <div v-else class="space-y-2">
            <Select
                v-model="selectedStoreId"
                :options="stores"
                optionLabel="store_name"
                optionValue="id"
                placeholder="Select store..."
                size="small"
                class="w-full"
                :pt="{ overlay: { style: 'width: 0; overflow: hidden' } }"
            >
                <template #value="{ value, placeholder }">
                    <span v-if="value" class="block truncate">
                        {{ stores.find((s) => s.id === value)?.store_name }}
                        ({{ stores.find((s) => s.id === value)?.store_code }})
                    </span>
                    <span v-else class="text-muted-foreground">{{ placeholder }}</span>
                </template>
                <template #option="{ option }">
                    <div class="truncate">{{ option.store_name }} ({{ option.store_code }})</div>
                </template>
            </Select>
            <Button
                label="Start Stocktake"
                icon="pi pi-play"
                size="small"
                class="w-full"
                :disabled="!selectedStoreId || starting"
                :loading="starting"
                @click="startStocktake"
            />
        </div>

        <!-- Link to full page -->
        <Button
            label="View All Stocktakes"
            icon="pi pi-external-link"
            severity="secondary"
            text
            size="small"
            class="w-full"
            @click="goToStocktakes"
        />
    </div>
</template>
