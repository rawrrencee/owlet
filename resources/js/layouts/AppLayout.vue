<script setup lang="ts">
import Toast from 'primevue/toast';
import { useAdminGuard } from '@/composables/useAdminGuard';
import { useFlashToast } from '@/composables/useToast';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Watch for flash messages and show toasts
useFlashToast();

// Guard admin routes from staff users
useAdminGuard();
</script>

<template>
    <Toast position="top-right" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
</template>
