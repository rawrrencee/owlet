<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Divider from 'primevue/divider';
import OrganizationChart from 'primevue/organizationchart';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { onMounted, reactive, ref } from 'vue';
import { type EmployeeHierarchyData, type SubordinateInfo } from '@/types';

interface Props {
    employeeId: number;
}

const props = defineProps<Props>();

const loading = ref(true);
const saving = ref(false);
const hierarchyData = ref<EmployeeHierarchyData | null>(null);
const selectedSubordinateId = ref<number | null>(null);
const expandedRows = ref({});

const visibilityForm = reactive<{ visible_sections: string[] }>({
    visible_sections: [],
});

const confirm = useConfirm();

async function fetchHierarchyData() {
    loading.value = true;
    try {
        const response = await fetch(`/users/${props.employeeId}/hierarchy`, {
            headers: {
                Accept: 'application/json',
            },
        });
        const data = await response.json();
        hierarchyData.value = data;
        visibilityForm.visible_sections = data.visibility_settings?.visible_sections ?? [];
    } catch (error) {
        console.error('Failed to fetch hierarchy data:', error);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    fetchHierarchyData();
});

function addSubordinate() {
    if (!selectedSubordinateId.value) return;

    saving.value = true;
    router.post(
        `/users/${props.employeeId}/hierarchy`,
        { subordinate_id: selectedSubordinateId.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedSubordinateId.value = null;
                fetchHierarchyData();
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function confirmRemoveSubordinate(subordinate: SubordinateInfo) {
    confirm.require({
        message: `Are you sure you want to remove "${subordinate.name}" as a subordinate?`,
        header: 'Remove Subordinate',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Remove',
        acceptProps: {
            severity: 'danger',
            size: 'small',
        },
        accept: () => {
            router.delete(`/users/${props.employeeId}/hierarchy/${subordinate.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    fetchHierarchyData();
                },
            });
        },
    });
}

function updateVisibility() {
    saving.value = true;
    router.put(
        `/users/${props.employeeId}/hierarchy/visibility`,
        { visible_sections: visibilityForm.visible_sections },
        {
            preserveScroll: true,
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function toggleSection(sectionKey: string) {
    const index = visibilityForm.visible_sections.indexOf(sectionKey);
    if (index === -1) {
        visibilityForm.visible_sections.push(sectionKey);
    } else {
        visibilityForm.visible_sections.splice(index, 1);
    }
    updateVisibility();
}

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function getTierColor(tier: number): string {
    const colors: Record<number, string> = {
        1: 'secondary',
        2: 'info',
        3: 'warn',
        4: 'success',
        5: 'danger',
    };
    return colors[tier] || 'secondary';
}
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Add Subordinate Section -->
        <div class="flex flex-col gap-4">
            <h3 class="text-lg font-medium">Subordinates</h3>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="subordinate_select" class="mb-2 block text-sm font-medium">Add Subordinate</label>
                    <Select
                        id="subordinate_select"
                        v-model="selectedSubordinateId"
                        :options="hierarchyData?.available_subordinates ?? []"
                        option-label="label"
                        option-value="value"
                        placeholder="Select an employee..."
                        filter
                        size="small"
                        fluid
                        :loading="loading"
                        :disabled="!hierarchyData?.available_subordinates?.length"
                    />
                </div>
                <Button
                    label="Add"
                    icon="pi pi-plus"
                    size="small"
                    :loading="saving"
                    :disabled="!selectedSubordinateId"
                    @click="addSubordinate"
                />
            </div>

            <!-- Subordinates Table -->
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="hierarchyData?.subordinates ?? []"
                dataKey="id"
                striped-rows
                size="small"
                :loading="loading"
                class="overflow-hidden rounded-xl border border-border dark:border-border"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No subordinates assigned. Use the dropdown above to add subordinates.
                    </div>
                </template>
                <Column expander style="width: 3rem" class="!pr-0 sm:hidden" />
                <Column header="">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3">
                            <Avatar
                                v-if="data.profile_picture_url"
                                :image="data.profile_picture_url"
                                shape="circle"
                                size="normal"
                            />
                            <Avatar
                                v-else
                                :label="getInitials(data.name)"
                                shape="circle"
                                size="normal"
                                class="bg-primary/10 text-primary"
                            />
                            <div class="flex flex-col">
                                <span class="font-medium">{{ data.name }}</span>
                                <span v-if="data.employee_number" class="text-xs text-muted-foreground">
                                    {{ data.employee_number }}
                                </span>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Email" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.email ?? '-' }}
                    </template>
                </Column>
                <Column header="" class="w-20 hidden sm:table-cell">
                    <template #body="{ data }">
                        <div class="flex justify-end">
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="confirmRemoveSubordinate(data)"
                                v-tooltip.top="'Remove'"
                            />
                        </div>
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm sm:hidden">
                        <div class="flex justify-between gap-4 border-b border-border pb-2">
                            <span class="shrink-0 text-muted-foreground">Email</span>
                            <span class="text-right">{{ data.email ?? '-' }}</span>
                        </div>
                        <div class="flex justify-end gap-1 pt-1">
                            <Button
                                icon="pi pi-trash"
                                label="Remove"
                                severity="danger"
                                text
                                size="small"
                                @click="confirmRemoveSubordinate(data)"
                            />
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>

        <Divider />

        <!-- Visibility Settings Section -->
        <div class="flex flex-col gap-4">
            <div>
                <h3 class="text-lg font-medium">Visibility Settings</h3>
                <p class="text-sm text-muted-foreground">
                    Choose what information this employee can see about their subordinates in "My Team".
                </p>
            </div>
            <div class="flex flex-col gap-3 rounded-lg border border-border p-4">
                <div
                    v-for="(label, key) in hierarchyData?.available_sections ?? {}"
                    :key="key"
                    class="flex items-center gap-3"
                >
                    <Checkbox
                        :model-value="visibilityForm.visible_sections.includes(key as string)"
                        binary
                        @change="toggleSection(key as string)"
                        :disabled="saving"
                    />
                    <span>{{ label }}</span>
                </div>
                <div v-if="!Object.keys(hierarchyData?.available_sections ?? {}).length && !loading" class="text-sm text-muted-foreground">
                    No visibility options available.
                </div>
            </div>
        </div>

        <Divider />

        <!-- Hierarchy Preview Section -->
        <div class="flex flex-col gap-4">
            <div>
                <h3 class="text-lg font-medium">Hierarchy Preview</h3>
                <p class="text-sm text-muted-foreground">
                    Visual representation of this employee's position in the hierarchy.
                </p>
            </div>
            <div v-if="hierarchyData?.subtree?.length" class="overflow-x-auto rounded-lg border border-border p-4">
                <OrganizationChart
                    v-for="node in hierarchyData.subtree"
                    :key="node.key"
                    :value="node"
                    collapsible
                >
                    <template #employee="{ node: chartNode }">
                        <div class="flex flex-col items-center gap-2 p-2">
                            <Avatar
                                v-if="chartNode.data.profile_picture_url"
                                :image="chartNode.data.profile_picture_url"
                                shape="circle"
                                size="normal"
                            />
                            <Avatar
                                v-else
                                :label="getInitials(chartNode.data.name)"
                                shape="circle"
                                size="normal"
                                class="bg-primary/10 text-primary"
                            />
                            <div class="text-center">
                                <div class="text-sm font-medium">{{ chartNode.data.name }}</div>
                                <div v-if="chartNode.data.designation" class="text-xs text-muted-foreground">
                                    {{ chartNode.data.designation }}
                                </div>
                            </div>
                            <Tag
                                :value="`Tier ${chartNode.data.tier}`"
                                :severity="getTierColor(chartNode.data.tier)"
                                class="!text-xs"
                            />
                        </div>
                    </template>
                </OrganizationChart>
            </div>
            <div v-else-if="!loading" class="rounded-lg border border-dashed border-border p-6 text-center text-muted-foreground">
                <i class="pi pi-sitemap mb-2 text-2xl"></i>
                <p>No subordinates in hierarchy.</p>
            </div>
        </div>
    </div>
</template>
