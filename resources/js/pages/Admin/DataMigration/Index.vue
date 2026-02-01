<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';

interface ModelStatus {
    type: string;
    display_name: string;
    legacy_count: number;
    migrated_count: number;
    failed_count: number;
    skipped_count: number;
    dependencies: string[];
    dependencies_met: boolean;
}

interface ConnectionTest {
    success: boolean;
    message: string;
    tables?: string[];
}

interface Props {
    status: ModelStatus[];
    connectionTest: ConnectionTest;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Data Migration' },
];

function navigateToModel(modelType: string) {
    router.get(`/admin/data-migration/${modelType}`);
}

function testConnection() {
    router.post(
        '/admin/data-migration/test-connection',
        {},
        { preserveState: true },
    );
}

function getProgress(item: ModelStatus): number {
    if (item.legacy_count === 0) return 100;
    const processed = item.migrated_count + item.skipped_count;
    return Math.round((processed / item.legacy_count) * 100);
}

function isComplete(item: ModelStatus): boolean {
    const processed = item.migrated_count + item.skipped_count;
    return processed >= item.legacy_count;
}

function getStatusSeverity(
    item: ModelStatus,
): 'success' | 'info' | 'warn' | 'danger' {
    if (item.legacy_count === 0) return 'info';
    if (isComplete(item)) return 'success';
    if (item.failed_count > 0) return 'warn';
    if (item.migrated_count > 0) return 'info';
    return 'secondary' as 'info';
}

function getStatusLabel(item: ModelStatus): string {
    if (item.legacy_count === 0) return 'Empty';
    if (isComplete(item)) return 'Complete';
    if (item.failed_count > 0) return 'Has Errors';
    if (item.migrated_count > 0) return 'In Progress';
    return 'Pending';
}
</script>

<template>
    <Head title="Data Migration" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <h1 class="heading-lg">Data Migration</h1>
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        label="Test Connection"
                        icon="pi pi-database"
                        severity="secondary"
                        size="small"
                        @click="testConnection"
                    />
                </div>
            </div>

            <!-- Connection Status -->
            <div class="rounded-lg border border-border p-4">
                <div class="flex items-center gap-3">
                    <i
                        :class="[
                            'pi text-2xl',
                            connectionTest.success
                                ? 'pi-check-circle text-green-500'
                                : 'pi-times-circle text-red-500',
                        ]"
                    />
                    <div>
                        <h3 class="font-semibold">
                            Legacy Database Connection
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            {{ connectionTest.message }}
                        </p>
                    </div>
                </div>
                <div
                    v-if="connectionTest.success && connectionTest.tables"
                    class="mt-3"
                >
                    <p class="text-sm text-muted-foreground">
                        Found {{ connectionTest.tables.length }} tables in
                        legacy database.
                    </p>
                </div>
            </div>

            <!-- Migration Status Table -->
            <DataTable
                :value="status"
                dataKey="type"
                @row-click="(e) => navigateToModel(e.data.type)"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No migration models available.
                    </div>
                </template>
                <Column field="display_name" header="Model">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{
                                data.display_name
                            }}</span>
                            <i
                                v-if="
                                    !data.dependencies_met &&
                                    data.dependencies.length > 0
                                "
                                class="pi pi-lock text-orange-500"
                                v-tooltip.top="'Has unmet dependencies'"
                            />
                        </div>
                    </template>
                </Column>
                <Column header="Legacy Count" class="hidden w-28 sm:table-cell">
                    <template #body="{ data }">
                        {{ data.legacy_count.toLocaleString() }}
                    </template>
                </Column>
                <Column header="Progress" class="w-48">
                    <template #body="{ data }">
                        <div class="flex flex-col gap-1">
                            <ProgressBar
                                :value="getProgress(data)"
                                :showValue="false"
                                style="height: 8px"
                            />
                            <div
                                class="flex justify-between text-xs text-muted-foreground"
                            >
                                <span
                                    >{{ data.migrated_count.toLocaleString() }}
                                    /
                                    {{
                                        data.legacy_count.toLocaleString()
                                    }}</span
                                >
                                <span>{{ getProgress(data) }}%</span>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Failed" class="hidden w-20 md:table-cell">
                    <template #body="{ data }">
                        <span
                            :class="
                                data.failed_count > 0
                                    ? 'font-semibold text-red-500'
                                    : ''
                            "
                        >
                            {{ data.failed_count.toLocaleString() }}
                        </span>
                    </template>
                </Column>
                <Column header="Skipped" class="hidden w-20 lg:table-cell">
                    <template #body="{ data }">
                        {{ data.skipped_count.toLocaleString() }}
                    </template>
                </Column>
                <Column header="Status" class="w-28">
                    <template #body="{ data }">
                        <Tag
                            :value="getStatusLabel(data)"
                            :severity="getStatusSeverity(data)"
                        />
                    </template>
                </Column>
                <Column header="" class="w-12 !pr-4">
                    <template #body>
                        <i class="pi pi-chevron-right text-muted-foreground" />
                    </template>
                </Column>
            </DataTable>

            <!-- Help Text -->
            <div
                class="rounded-lg border border-border bg-muted/50 p-4 text-sm"
            >
                <h4 class="mb-2 font-semibold">Migration Instructions</h4>
                <ul
                    class="list-inside list-disc space-y-1 text-muted-foreground"
                >
                    <li>
                        Ensure the legacy database connection is configured
                        correctly.
                    </li>
                    <li>
                        Migrate models in dependency order (models with no
                        dependencies first).
                    </li>
                    <li>
                        Click on any row to view details and run migration for
                        that model.
                    </li>
                    <li>
                        Models with a
                        <i class="pi pi-lock text-orange-500" /> icon have unmet
                        dependencies.
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
