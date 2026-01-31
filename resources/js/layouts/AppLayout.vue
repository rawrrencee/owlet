<script setup lang="ts">
import Toast from 'primevue/toast';
import FloatingClockPanel from '@/components/timecards/FloatingClockPanel.vue';
import IncompleteTimecardModal from '@/components/timecards/IncompleteTimecardModal.vue';
import { usePermissionGuard } from '@/composables/usePermissionGuard';
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

// Guard routes based on permissions
usePermissionGuard();
</script>

<template>
    <Toast position="top-right" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
    <!-- Floating clock panel for time tracking -->
    <FloatingClockPanel />
    <!-- Modal for resolving incomplete timecards -->
    <IncompleteTimecardModal />
</template>
