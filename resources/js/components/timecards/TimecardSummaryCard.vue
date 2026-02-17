<script setup lang="ts">
import type { Timecard } from '@/types/timecard';
import { Link } from '@inertiajs/vue3';
import { AlertTriangle, Clock, MapPin } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';

interface Props {
    timecard: Timecard;
    showEmployee?: boolean;
    showLink?: boolean;
    linkUrl?: string;
}

const props = withDefaults(defineProps<Props>(), {
    showEmployee: false,
    showLink: false,
    linkUrl: undefined,
});

const isExpanded = ref(false);

const statusSeverity = computed(() => {
    switch (props.timecard.status) {
        case 'COMPLETED':
            return 'success';
        case 'IN_PROGRESS':
            return 'info';
        case 'EXPIRED':
            return 'danger';
        default:
            return 'secondary';
    }
});

function formatTime(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatHours(hours: number): string {
    if (hours === 0) return '0h';
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
}

function formatDuration(detail: { hours: number; is_open?: boolean }): string {
    if (detail.is_open) return 'In progress';
    return formatHours(detail.hours);
}
</script>

<template>
    <Card class="timecard-summary-card">
        <template #content>
            <div class="flex flex-col gap-3">
                <!-- Header Row -->
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex flex-col gap-1">
                        <!-- Employee Name (if shown) -->
                        <div
                            v-if="showEmployee && timecard.employee"
                            class="flex items-center gap-2"
                        >
                            <img
                                v-if="timecard.employee.profile_picture_url"
                                :src="timecard.employee.profile_picture_url"
                                :alt="timecard.employee.name"
                                class="h-6 w-6 rounded-full object-cover"
                            />
                            <span class="font-medium">{{
                                timecard.employee.name
                            }}</span>
                        </div>

                        <!-- Store Name -->
                        <div
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <MapPin class="h-4 w-4 shrink-0" />
                            <span>{{
                                timecard.store?.name || 'Unknown Store'
                            }}</span>
                        </div>

                        <!-- Time Range -->
                        <div
                            class="flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <Clock class="h-4 w-4 shrink-0" />
                            <span>
                                {{ formatTime(timecard.start_date) }}
                                -
                                {{ formatTime(timecard.end_date) }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:flex-col sm:items-end">
                        <div class="flex items-center gap-2">
                            <Tag
                                v-if="timecard.is_inaccurate"
                                severity="warn"
                                v-tooltip.top="'User-provided clock-out time'"
                            >
                                <template #default>
                                    <div class="flex items-center gap-1">
                                        <AlertTriangle class="h-3 w-3" />
                                        <span>Adjusted</span>
                                    </div>
                                </template>
                            </Tag>
                            <Tag
                                :value="timecard.status_label"
                                :severity="statusSeverity"
                            />
                        </div>
                        <span
                            class="text-lg font-bold text-green-600 dark:text-green-400"
                        >
                            {{ formatHours(timecard.hours_worked) }}
                        </span>
                    </div>
                </div>

                <!-- Expandable Details -->
                <template
                    v-if="timecard.details && timecard.details.length > 0"
                >
                    <Divider />
                    <Button
                        :label="isExpanded ? 'Hide Details' : 'Show Details'"
                        :icon="
                            isExpanded
                                ? 'pi pi-chevron-up'
                                : 'pi pi-chevron-down'
                        "
                        text
                        size="small"
                        @click="isExpanded = !isExpanded"
                        class="w-full"
                    />

                    <div v-if="isExpanded" class="flex flex-col gap-2">
                        <div
                            v-for="detail in timecard.details"
                            :key="detail.id"
                            class="flex flex-col gap-1 rounded-lg bg-muted/50 px-3 py-2 text-sm sm:flex-row sm:items-center sm:justify-between sm:gap-2"
                        >
                            <div class="flex items-center gap-2">
                                <Tag
                                    :value="detail.type_label"
                                    :severity="
                                        detail.is_work ? 'success' : 'warn'
                                    "
                                    class="text-xs"
                                />
                                <span>
                                    {{ formatTime(detail.start_date) }}
                                    -
                                    {{ formatTime(detail.end_date) }}
                                </span>
                            </div>
                            <span class="font-medium">
                                {{ formatDuration(detail) }}
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Link to View -->
                <div v-if="showLink && linkUrl" class="mt-2">
                    <Link
                        :href="linkUrl"
                        class="text-sm text-primary hover:underline"
                    >
                        View full details
                    </Link>
                </div>
            </div>
        </template>
    </Card>
</template>
