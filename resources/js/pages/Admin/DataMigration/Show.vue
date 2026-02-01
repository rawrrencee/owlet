<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Dependency {
    type: string;
    display_name: string;
    legacy_count: number;
    migrated_count: number;
    is_complete: boolean;
}

interface MigrationRun {
    id: number;
    started_at: string;
    completed_at: string | null;
    status: string;
    status_label: string;
    total_records: number;
    migrated_count: number;
    failed_count: number;
    skipped_count: number;
    error_message: string | null;
    progress_percentage: number;
    initiated_by: string;
}

interface MigrationLog {
    id: number;
    legacy_id: number;
    owlet_id: number | null;
    status: string;
    error_message: string | null;
    metadata: Record<string, unknown> | null;
    updated_at: string;
}

interface Props {
    modelType: string;
    displayName: string;
    legacyCount: number;
    migratedCount: number;
    failedCount: number;
    skippedCount: number;
    dependenciesMet: boolean;
    dependencies: Dependency[];
    recentRuns: { data: MigrationRun[] };
    failedLogs: { data: MigrationLog[] };
}

const props = defineProps<Props>();

const migrating = ref(false);
const verifying = ref(false);
const retrying = ref(false);
const autoMigrating = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Data Migration', href: '/admin/data-migration' },
    { title: props.displayName },
];

const progress = computed(() => {
    if (props.legacyCount === 0) return 100;
    return Math.round((props.migratedCount / props.legacyCount) * 100);
});

const isComplete = computed(() => {
    const processed = props.migratedCount + props.skippedCount;
    return processed >= props.legacyCount;
});

const hasStarted = computed(
    () =>
        props.migratedCount > 0 ||
        props.failedCount > 0 ||
        props.skippedCount > 0,
);

const migrationButtonLabel = computed(() => {
    if (isComplete.value) return 'Complete';
    if (hasStarted.value) return 'Continue Migration';
    return 'Run Migration';
});

function runMigration() {
    migrating.value = true;
    autoMigrating.value = true;
    router.post(
        `/admin/data-migration/${props.modelType}/migrate`,
        {},
        {
            preserveScroll: true,
            onSuccess: (page) => {
                // Check if there are still pending records and auto-continue
                const data = page.props as unknown as Props;
                const processed =
                    data.migratedCount + data.failedCount + data.skippedCount;
                const stillPending = processed < data.legacyCount;

                if (stillPending && autoMigrating.value) {
                    // Continue migration after a short delay
                    setTimeout(() => {
                        if (autoMigrating.value) {
                            runMigration();
                        }
                    }, 500);
                } else {
                    migrating.value = false;
                    autoMigrating.value = false;
                }
            },
            onError: () => {
                migrating.value = false;
                autoMigrating.value = false;
            },
        },
    );
}

function stopMigration() {
    autoMigrating.value = false;
    migrating.value = false;
}

function runVerification() {
    verifying.value = true;
    router.post(
        `/admin/data-migration/${props.modelType}/verify`,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                verifying.value = false;
            },
        },
    );
}

function retryFailed() {
    retrying.value = true;
    router.post(
        `/admin/data-migration/${props.modelType}/retry-failed`,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                retrying.value = false;
            },
        },
    );
}

function goBack() {
    router.get('/admin/data-migration');
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
}

function getRunStatusSeverity(
    status: string,
): 'success' | 'info' | 'warn' | 'danger' {
    switch (status) {
        case 'completed':
            return 'success';
        case 'running':
            return 'info';
        case 'failed':
            return 'danger';
        case 'cancelled':
            return 'warn';
        default:
            return 'info';
    }
}
</script>

<template>
    <Head :title="`${displayName} Migration`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <Button
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        text
                        rounded
                        size="small"
                        @click="goBack"
                    />
                    <h1 class="heading-lg">{{ displayName }} Migration</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        label="Verify"
                        icon="pi pi-check-circle"
                        severity="secondary"
                        size="small"
                        :loading="verifying"
                        :disabled="migratedCount === 0"
                        @click="runVerification"
                    />
                    <Button
                        v-if="failedCount > 0"
                        label="Retry Failed"
                        icon="pi pi-refresh"
                        severity="warn"
                        size="small"
                        :loading="retrying"
                        @click="retryFailed"
                    />
                    <Button
                        v-if="!migrating"
                        :label="migrationButtonLabel"
                        :icon="isComplete ? 'pi pi-check' : 'pi pi-play'"
                        size="small"
                        :disabled="!dependenciesMet || isComplete"
                        @click="runMigration"
                    />
                    <Button
                        v-else
                        label="Stop Migration"
                        icon="pi pi-stop"
                        severity="danger"
                        size="small"
                        @click="stopMigration"
                    />
                </div>
            </div>

            <!-- Progress Card -->
            <Card class="shadow-none">
                <template #content>
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-semibold"
                                >Migration Progress</span
                            >
                            <Tag
                                :value="
                                    migrating
                                        ? 'Migrating...'
                                        : isComplete
                                          ? 'Complete'
                                          : hasStarted
                                            ? 'In Progress'
                                            : 'Pending'
                                "
                                :severity="
                                    migrating
                                        ? 'warn'
                                        : isComplete
                                          ? 'success'
                                          : hasStarted
                                            ? 'info'
                                            : 'secondary'
                                "
                            />
                        </div>
                        <ProgressBar :value="progress" style="height: 16px" />
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div
                                class="rounded-lg border border-border p-3 text-center"
                            >
                                <div class="text-2xl font-bold">
                                    {{ legacyCount.toLocaleString() }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    Legacy Records
                                </div>
                            </div>
                            <div
                                class="rounded-lg border border-border p-3 text-center"
                            >
                                <div class="text-2xl font-bold text-green-600">
                                    {{ migratedCount.toLocaleString() }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    Migrated
                                </div>
                            </div>
                            <div
                                class="rounded-lg border border-border p-3 text-center"
                            >
                                <div
                                    :class="[
                                        'text-2xl font-bold',
                                        failedCount > 0 ? 'text-red-600' : '',
                                    ]"
                                >
                                    {{ failedCount.toLocaleString() }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    Failed
                                </div>
                            </div>
                            <div
                                class="rounded-lg border border-border p-3 text-center"
                            >
                                <div class="text-2xl font-bold text-orange-500">
                                    {{ skippedCount.toLocaleString() }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    Skipped
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Dependencies Section -->
            <Card v-if="dependencies.length > 0" class="shadow-none">
                <template #title>
                    <div class="flex items-center gap-2">
                        <span>Dependencies</span>
                        <Tag
                            :value="dependenciesMet ? 'All Met' : 'Unmet'"
                            :severity="dependenciesMet ? 'success' : 'warn'"
                        />
                    </div>
                </template>
                <template #content>
                    <div class="flex flex-col gap-2">
                        <div
                            v-for="dep in dependencies"
                            :key="dep.type"
                            class="flex items-center justify-between rounded-lg border border-border p-3"
                        >
                            <div class="flex items-center gap-2">
                                <i
                                    :class="[
                                        'pi',
                                        dep.is_complete
                                            ? 'pi-check-circle text-green-500'
                                            : 'pi-clock text-orange-500',
                                    ]"
                                />
                                <span class="font-medium">{{
                                    dep.display_name
                                }}</span>
                            </div>
                            <span class="text-sm text-muted-foreground">
                                {{ dep.migrated_count.toLocaleString() }} /
                                {{ dep.legacy_count.toLocaleString() }}
                            </span>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Recent Runs -->
            <Card class="shadow-none">
                <template #title>Recent Migration Runs</template>
                <template #content>
                    <DataTable
                        :value="recentRuns.data"
                        dataKey="id"
                        size="small"
                        class="overflow-hidden rounded-lg border border-border"
                    >
                        <template #empty>
                            <div class="p-4 text-center text-muted-foreground">
                                No migration runs yet.
                            </div>
                        </template>
                        <Column header="Started" class="w-40">
                            <template #body="{ data }">
                                {{ formatDate(data.started_at) }}
                            </template>
                        </Column>
                        <Column header="Status" class="w-28">
                            <template #body="{ data }">
                                <Tag
                                    :value="data.status_label"
                                    :severity="
                                        getRunStatusSeverity(data.status)
                                    "
                                />
                            </template>
                        </Column>
                        <Column
                            header="Migrated"
                            class="hidden w-24 sm:table-cell"
                        >
                            <template #body="{ data }">
                                {{ data.migrated_count.toLocaleString() }}
                            </template>
                        </Column>
                        <Column
                            header="Failed"
                            class="hidden w-24 sm:table-cell"
                        >
                            <template #body="{ data }">
                                <span
                                    :class="
                                        data.failed_count > 0
                                            ? 'text-red-500'
                                            : ''
                                    "
                                >
                                    {{ data.failed_count.toLocaleString() }}
                                </span>
                            </template>
                        </Column>
                        <Column
                            header="Initiated By"
                            class="hidden md:table-cell"
                        >
                            <template #body="{ data }">
                                {{ data.initiated_by }}
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Failed Records -->
            <Card v-if="failedLogs.data.length > 0" class="shadow-none">
                <template #title>
                    <div class="flex items-center gap-2">
                        <span>Failed Records</span>
                        <Tag
                            :value="failedCount.toString()"
                            severity="danger"
                        />
                    </div>
                </template>
                <template #content>
                    <DataTable
                        :value="failedLogs.data"
                        dataKey="id"
                        size="small"
                        class="overflow-hidden rounded-lg border border-border"
                    >
                        <Column
                            field="legacy_id"
                            header="Legacy ID"
                            class="w-28"
                        />
                        <Column field="error_message" header="Error">
                            <template #body="{ data }">
                                <div
                                    class="text-sm break-words whitespace-pre-wrap text-red-600"
                                >
                                    {{ data.error_message }}
                                </div>
                            </template>
                        </Column>
                        <Column
                            header="Last Attempt"
                            class="hidden w-40 md:table-cell"
                        >
                            <template #body="{ data }">
                                {{ formatDate(data.updated_at) }}
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
