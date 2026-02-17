<script setup lang="ts">
import BarcodeScanner from '@/components/stocktakes/BarcodeScanner.vue';
import Dialog from 'primevue/dialog';
import { ref, watch } from 'vue';

const visible = defineModel<boolean>('visible', { default: false });

const emit = defineEmits<{
    scan: [barcode: string];
}>();

const scannerActive = ref(false);

watch(visible, (val) => {
    scannerActive.value = val;
});

function onScan(barcode: string) {
    emit('scan', barcode);
    visible.value = false;
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
        :style="{ width: '24rem' }"
        @hide="scannerActive = false"
    >
        <BarcodeScanner
            scanner-id="pos-barcode-scanner-region"
            :active="scannerActive"
            @scan="onScan"
            @close="onClose"
        />
    </Dialog>
</template>
