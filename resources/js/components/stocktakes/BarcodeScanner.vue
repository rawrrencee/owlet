<script setup lang="ts">
import { Html5Qrcode } from 'html5-qrcode';
import Button from 'primevue/button';
import { onBeforeUnmount, ref, watch } from 'vue';

const props = withDefaults(defineProps<{
    active: boolean;
    scannerId?: string;
}>(), {
    scannerId: 'barcode-scanner-region',
});

const emit = defineEmits<{
    scan: [barcode: string];
    close: [];
}>();

const scannerRef = ref<HTMLDivElement>();
const scanner = ref<Html5Qrcode | null>(null);
const facingMode = ref<'environment' | 'user'>('environment');
const error = ref<string | null>(null);

async function startScanner() {
    if (!scannerRef.value) return;

    error.value = null;

    // Check if we're on a secure context (HTTPS or localhost)
    if (!window.isSecureContext) {
        error.value =
            'Camera access requires HTTPS. Please access this site over HTTPS to use the barcode scanner.';
        return;
    }

    // Check if getUserMedia is available
    if (!navigator.mediaDevices?.getUserMedia) {
        error.value =
            'Camera access is not supported in this browser.';
        return;
    }

    try {
        scanner.value = new Html5Qrcode(props.scannerId);
        await scanner.value.start(
            { facingMode: facingMode.value },
            {
                fps: 10,
                qrbox: { width: 250, height: 150 },
            },
            (decodedText) => {
                emit('scan', decodedText);
            },
            () => {
                // Ignore scan failures (happens continuously when no barcode in view)
            },
        );
    } catch (err) {
        const message = String(err);
        if (message.includes('NotAllowedError') || message.includes('Permission')) {
            error.value = 'Camera permission denied. Please allow camera access and try again.';
        } else if (message.includes('NotFoundError') || message.includes('device')) {
            error.value = 'No camera found on this device.';
        } else {
            error.value = 'Could not access camera. Please check permissions and try again.';
        }
        console.error('Scanner error:', err);
    }
}

async function stopScanner() {
    if (scanner.value) {
        try {
            await scanner.value.stop();
        } catch {
            // Ignore stop errors
        }
        scanner.value = null;
    }
}

async function toggleCamera() {
    await stopScanner();
    facingMode.value =
        facingMode.value === 'environment' ? 'user' : 'environment';
    await startScanner();
}

function handleClose() {
    stopScanner();
    emit('close');
}

watch(
    () => props.active,
    async (isActive) => {
        if (isActive) {
            // Small delay to let the DOM render
            setTimeout(() => startScanner(), 100);
        } else {
            await stopScanner();
        }
    },
    { immediate: true },
);

function pause() {
    if (scanner.value) {
        try {
            scanner.value.pause(false);
        } catch {
            // ignore if not running
        }
    }
}

function resume() {
    if (scanner.value) {
        try {
            scanner.value.resume();
        } catch {
            // ignore if not paused
        }
    }
}

onBeforeUnmount(() => {
    stopScanner();
});

defineExpose({ pause, resume });
</script>

<template>
    <div v-if="active" class="relative rounded-lg border border-border bg-card">
        <div class="flex items-center justify-between border-b border-border p-3">
            <span class="text-sm font-medium">Barcode Scanner</span>
            <div class="flex gap-1">
                <Button
                    icon="pi pi-sync"
                    severity="secondary"
                    text
                    rounded
                    size="small"
                    @click="toggleCamera"
                    v-tooltip.top="'Switch camera'"
                />
                <Button
                    icon="pi pi-times"
                    severity="secondary"
                    text
                    rounded
                    size="small"
                    @click="handleClose"
                />
            </div>
        </div>
        <div class="p-3">
            <div
                v-if="error"
                class="rounded-md bg-red-50 p-3 text-center text-sm text-red-600 dark:bg-red-900/20 dark:text-red-400"
            >
                {{ error }}
            </div>
            <div
                :id="scannerId"
                ref="scannerRef"
                class="overflow-hidden rounded-md"
            />
        </div>
    </div>
</template>
