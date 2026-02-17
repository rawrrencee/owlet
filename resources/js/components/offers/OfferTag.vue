<script setup lang="ts">
import Tag from 'primevue/tag';

defineProps<{
    name: string;
    severity?: string;
}>();
</script>

<template>
    <Tag :value="name" :severity="(severity as any) ?? 'success'" class="!text-[10px]" />
</template>
