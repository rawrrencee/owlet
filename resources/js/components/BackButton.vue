<script setup lang="ts">
import { useSmartBack } from '@/composables/useSmartBack';
import Button from 'primevue/button';
import { ref } from 'vue';

interface Props {
    fallbackUrl: string;
}

const props = defineProps<Props>();

const { goBack } = useSmartBack(props.fallbackUrl);
const isNavigating = ref(false);

function handleClick() {
    if (isNavigating.value) return;
    isNavigating.value = true;
    goBack();
}
</script>

<template>
    <Button
        icon="pi pi-arrow-left"
        severity="secondary"
        text
        rounded
        size="small"
        :loading="isNavigating"
        :disabled="isNavigating"
        @click="handleClick"
    />
</template>
