<script setup lang="ts">
import type { TimecardDetail } from '@/types/timecard';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';

interface Props {
    details: TimecardDetail[];
    editable?: boolean;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    editable: false,
    loading: false,
});

const emit = defineEmits<{
    (e: 'add'): void;
    (e: 'edit', detail: TimecardDetail): void;
    (e: 'delete', detail: TimecardDetail): void;
}>();

const sortedDetails = computed(() => {
    return [...props.details].sort(
        (a, b) =>
            new Date(a.start_date).getTime() - new Date(b.start_date).getTime(),
    );
});

function formatTime(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatHours(hours: number): string {
    if (hours === 0) return '-';
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
}

function getTypeSeverity(type: string): 'success' | 'warn' {
    return type === 'WORK' ? 'success' : 'warn';
}

const expandedRows = ref<Record<string, boolean>>({});
</script>

<template>
    <div class="timecard-details-table">
        <DataTable
            v-model:expandedRows="expandedRows"
            :value="sortedDetails"
            :loading="loading"
            size="small"
            striped-rows
            data-key="id"
        >
            <template #header v-if="editable">
                <div class="flex justify-end">
                    <Button
                        label="Add Entry"
                        icon="pi pi-plus"
                        size="small"
                        @click="emit('add')"
                    />
                </div>
            </template>

            <template #empty>
                <div class="py-4 text-center text-muted-foreground">
                    No time entries recorded.
                </div>
            </template>

            <!-- Expander column for mobile -->
            <Column expander class="w-[3rem] sm:hidden" />

            <Column field="type" header="Type" style="width: 100px">
                <template #body="{ data }">
                    <Tag
                        :value="data.type_label"
                        :severity="getTypeSeverity(data.type)"
                    />
                </template>
            </Column>

            <Column field="start_date" header="Start Time" class="hidden sm:table-cell">
                <template #body="{ data }">
                    {{ formatTime(data.start_date) }}
                </template>
            </Column>

            <Column field="end_date" header="End Time" class="hidden sm:table-cell">
                <template #body="{ data }">
                    <span
                        v-if="data.is_open"
                        class="text-muted-foreground italic"
                    >
                        In progress...
                    </span>
                    <span v-else>{{ formatTime(data.end_date) }}</span>
                </template>
            </Column>

            <Column field="hours" header="Duration" style="width: 100px">
                <template #body="{ data }">
                    <span v-if="data.is_open" class="text-muted-foreground"
                        >-</span
                    >
                    <span v-else>{{ formatHours(data.hours) }}</span>
                </template>
            </Column>

            <Column
                v-if="editable"
                header="Actions"
                style="width: 120px"
                class="hidden text-right md:table-cell"
            >
                <template #body="{ data }">
                    <div class="flex justify-end gap-1">
                        <Button
                            icon="pi pi-pencil"
                            text
                            size="small"
                            severity="secondary"
                            @click="emit('edit', data)"
                            v-tooltip.top="'Edit'"
                        />
                        <Button
                            icon="pi pi-trash"
                            text
                            size="small"
                            severity="danger"
                            @click="emit('delete', data)"
                            v-tooltip.top="'Delete'"
                        />
                    </div>
                </template>
            </Column>

            <!-- Expansion template for mobile -->
            <template #expansion="{ data }">
                <div class="flex flex-col gap-2 p-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Start Time</span>
                        <span>{{ formatTime(data.start_date) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">End Time</span>
                        <span v-if="data.is_open" class="text-muted-foreground italic">In progress...</span>
                        <span v-else>{{ formatTime(data.end_date) }}</span>
                    </div>
                    <div v-if="editable" class="flex justify-end gap-1 pt-2 border-t border-muted">
                        <Button
                            icon="pi pi-pencil"
                            label="Edit"
                            text
                            size="small"
                            severity="secondary"
                            @click="emit('edit', data)"
                        />
                        <Button
                            icon="pi pi-trash"
                            label="Delete"
                            text
                            size="small"
                            severity="danger"
                            @click="emit('delete', data)"
                        />
                    </div>
                </div>
            </template>
        </DataTable>
    </div>
</template>
