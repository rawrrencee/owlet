<script setup lang="ts">
import BarcodeScanner from '@/components/stocktakes/BarcodeScanner.vue';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Dialog from 'primevue/dialog';
import { onMounted, ref, watch } from 'vue';

const visible = defineModel<boolean>('visible', { default: false });

const emit = defineEmits<{
    scan: [barcode: string];
}>();

interface ScannedProduct {
    barcode: string;
    name: string;
    sessionQty: number;
}

const scannerRef = ref<InstanceType<typeof BarcodeScanner> | null>(null);
const scannerActive = ref(false);
const closeOnScan = ref(true);
const tapToScan = ref(false);
const isPaused = ref(false);
const scannedProducts = ref<ScannedProduct[]>([]);

// Persist preferences
onMounted(() => {
    const savedCloseOnScan = localStorage.getItem('stocktake_scanner_close_on_scan');
    if (savedCloseOnScan !== null) closeOnScan.value = savedCloseOnScan === 'true';
    const savedTapToScan = localStorage.getItem('stocktake_scanner_tap_to_scan');
    if (savedTapToScan !== null) tapToScan.value = savedTapToScan === 'true';
});

watch(closeOnScan, (val) => {
    localStorage.setItem('stocktake_scanner_close_on_scan', String(val));
});

watch(tapToScan, (val) => {
    localStorage.setItem('stocktake_scanner_tap_to_scan', String(val));
});

watch(visible, (val) => {
    if (val) {
        scannerActive.value = true;
        isPaused.value = false;
    } else {
        scannerActive.value = false;
    }
});

function onScan(barcode: string) {
    // Update scanned products list
    const existing = scannedProducts.value.find(p => p.barcode === barcode);
    if (existing) {
        existing.sessionQty++;
        // Move to top
        scannedProducts.value = [
            existing,
            ...scannedProducts.value.filter(p => p.barcode !== barcode),
        ];
    } else {
        scannedProducts.value.unshift({
            barcode,
            name: barcode, // Will be shown as barcode until matched
            sessionQty: 1,
        });
    }

    // Emit to parent to add product to stocktake
    emit('scan', barcode);

    if (tapToScan.value) {
        // Pause scanner, user taps to resume
        scannerRef.value?.pause();
        isPaused.value = true;
        if (closeOnScan.value) {
            // Close after scan when both are checked
            visible.value = false;
        }
    } else if (closeOnScan.value) {
        visible.value = false;
    }
}

function resumeScanner() {
    scannerRef.value?.resume();
    isPaused.value = false;
}

function onClose() {
    visible.value = false;
}
</script>

<template>
    <Dialog
        v-model:visible="visible"
        header="Scan Barcode"
        modal
        :style="{ width: '28rem' }"
        @hide="scannerActive = false"
    >
        <!-- Options -->
        <div class="flex flex-wrap gap-4 mb-3">
            <div class="flex items-center gap-2">
                <Checkbox v-model="closeOnScan" :binary="true" input-id="closeOnScan" />
                <label for="closeOnScan" class="text-sm">Close on scan</label>
            </div>
            <div class="flex items-center gap-2">
                <Checkbox v-model="tapToScan" :binary="true" input-id="tapToScan" />
                <label for="tapToScan" class="text-sm">Tap to scan</label>
            </div>
        </div>

        <!-- Scanner -->
        <BarcodeScanner
            ref="scannerRef"
            scanner-id="stocktake-barcode-scanner-region"
            :active="scannerActive"
            @scan="onScan"
            @close="onClose"
        />

        <!-- Resume button when paused -->
        <div v-if="isPaused" class="mt-3">
            <Button
                label="Tap to Scan Next"
                icon="pi pi-camera"
                class="w-full"
                size="small"
                @click="resumeScanner"
            />
        </div>

        <!-- Scanned products list -->
        <div v-if="scannedProducts.length > 0" class="mt-3">
            <div class="text-sm font-medium mb-2">Scanned Products</div>
            <div class="max-h-[40vh] overflow-y-auto border rounded-lg divide-y">
                <div
                    v-for="item in scannedProducts"
                    :key="item.barcode"
                    class="flex items-center justify-between px-3 py-2 text-sm"
                >
                    <div class="min-w-0 flex-1">
                        <div class="font-medium truncate">{{ item.barcode }}</div>
                    </div>
                    <span class="ml-2 text-xs text-muted-color whitespace-nowrap">
                        x{{ item.sessionQty }}
                    </span>
                </div>
            </div>
        </div>
    </Dialog>
</template>
