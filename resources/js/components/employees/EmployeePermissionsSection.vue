<script setup lang="ts">
import { type StorePermissionGroup } from '@/types';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';
import { onMounted, reactive, ref } from 'vue';

interface Props {
    employeeId: number;
}

const props = defineProps<Props>();

const loading = ref(true);
const saving = ref(false);
const hasChanges = ref(false);

const pagePermissions = ref<string[]>([]);
const availablePermissions = ref<StorePermissionGroup>({});

const form = reactive({
    page_permissions: [] as string[],
});

async function fetchPermissions() {
    loading.value = true;
    try {
        const response = await fetch(`/users/${props.employeeId}/permissions`, {
            headers: {
                Accept: 'application/json',
            },
        });
        const data = await response.json();
        pagePermissions.value = data.data.page_permissions || [];
        form.page_permissions = [...pagePermissions.value];
        availablePermissions.value = data.available_permissions || {};
    } catch (error) {
        console.error('Failed to fetch permissions:', error);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    fetchPermissions();
});

function togglePermission(permissionKey: string) {
    const index = form.page_permissions.indexOf(permissionKey);
    if (index === -1) {
        form.page_permissions.push(permissionKey);
    } else {
        form.page_permissions.splice(index, 1);
    }
    checkForChanges();
}

function toggleGroupPermissions(group: string) {
    const groupPermissions =
        availablePermissions.value[group]?.map((p) => p.key) ?? [];
    const allSelected = groupPermissions.every((p) =>
        form.page_permissions.includes(p),
    );

    if (allSelected) {
        form.page_permissions = form.page_permissions.filter(
            (p) => !groupPermissions.includes(p),
        );
    } else {
        groupPermissions.forEach((p) => {
            if (!form.page_permissions.includes(p)) {
                form.page_permissions.push(p);
            }
        });
    }
    checkForChanges();
}

function isGroupFullySelected(group: string): boolean {
    const groupPermissions =
        availablePermissions.value[group]?.map((p) => p.key) ?? [];
    return (
        groupPermissions.length > 0 &&
        groupPermissions.every((p) => form.page_permissions.includes(p))
    );
}

function isGroupPartiallySelected(group: string): boolean {
    const groupPermissions =
        availablePermissions.value[group]?.map((p) => p.key) ?? [];
    const selectedCount = groupPermissions.filter((p) =>
        form.page_permissions.includes(p),
    ).length;
    return selectedCount > 0 && selectedCount < groupPermissions.length;
}

function checkForChanges() {
    const originalSorted = [...pagePermissions.value].sort();
    const currentSorted = [...form.page_permissions].sort();
    hasChanges.value =
        JSON.stringify(originalSorted) !== JSON.stringify(currentSorted);
}

function savePermissions() {
    saving.value = true;

    router.put(
        `/users/${props.employeeId}/permissions`,
        {
            page_permissions: form.page_permissions,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                pagePermissions.value = [...form.page_permissions];
                hasChanges.value = false;
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function resetChanges() {
    form.page_permissions = [...pagePermissions.value];
    hasChanges.value = false;
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">Global Page Permissions</h3>
        </div>

        <Message severity="info" :closable="false" class="!m-0">
            These permissions control which pages the employee can access in the
            application. Store-specific permissions are configured in the
            "Stores" tab.
        </Message>

        <div v-if="loading" class="flex items-center justify-center py-8">
            <i class="pi pi-spin pi-spinner text-2xl text-muted-foreground"></i>
        </div>

        <div v-else class="flex flex-col gap-4">
            <div
                v-for="(permissions, group) in availablePermissions"
                :key="group"
                class="rounded border border-border p-4"
            >
                <div class="mb-3 flex items-center gap-2">
                    <Checkbox
                        :model-value="isGroupFullySelected(group as string)"
                        :indeterminate="
                            isGroupPartiallySelected(group as string)
                        "
                        binary
                        @change="toggleGroupPermissions(group as string)"
                    />
                    <span class="font-medium">{{ group }}</span>
                </div>
                <div class="ml-6 grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="perm in permissions"
                        :key="perm.key"
                        class="flex items-center gap-2"
                    >
                        <Checkbox
                            :model-value="
                                form.page_permissions.includes(perm.key)
                            "
                            binary
                            @change="togglePermission(perm.key)"
                        />
                        <span class="text-sm">{{ perm.label }}</span>
                    </div>
                </div>
            </div>

            <div
                v-if="hasChanges"
                class="flex justify-end gap-2 border-t border-border pt-4"
            >
                <Button
                    type="button"
                    label="Reset"
                    severity="secondary"
                    size="small"
                    @click="resetChanges"
                    :disabled="saving"
                />
                <Button
                    type="button"
                    label="Save Permissions"
                    size="small"
                    :loading="saving"
                    @click="savePermissions"
                />
            </div>
        </div>
    </div>
</template>
