<script setup lang="ts">
import type { PagePermission, UserPagePermission } from '@/types';
import { router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch } from 'vue';

interface Props {
    visible: boolean;
    page: string;
    pageLabel: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:visible', value: boolean): void;
}>();

const toast = useToast();
const confirm = useConfirm();

const loading = ref(false);
const saving = ref(false);
const searchQuery = ref('');
const users = ref<UserPagePermission[]>([]);
const availablePermissions = ref<PagePermission[]>([]);
const originalPermissions = ref<Map<number, string[]>>(new Map());

// Track edited permissions separately
const editedPermissions = ref<Map<number, string[]>>(new Map());

const filteredUsers = computed(() => {
    if (!searchQuery.value) return users.value;
    const query = searchQuery.value.toLowerCase();
    return users.value.filter(
        (user) =>
            user.name.toLowerCase().includes(query) ||
            user.email?.toLowerCase().includes(query),
    );
});

const hasChanges = computed(() => {
    for (const user of users.value) {
        const original = originalPermissions.value.get(user.id) ?? [];
        const current =
            editedPermissions.value.get(user.id) ?? user.permissions;
        if (
            JSON.stringify([...original].sort()) !==
            JSON.stringify([...current].sort())
        ) {
            return true;
        }
    }
    return false;
});

async function fetchUsers() {
    loading.value = true;
    try {
        const response = await fetch(`/page-permissions/${props.page}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error('Failed to fetch permissions');
        const data = await response.json();
        users.value = data.data;
        availablePermissions.value = data.available_permissions;

        // Store original permissions for change tracking
        originalPermissions.value.clear();
        editedPermissions.value.clear();
        for (const user of users.value) {
            originalPermissions.value.set(user.id, [...user.permissions]);
            editedPermissions.value.set(user.id, [...user.permissions]);
        }
    } catch {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load permissions data',
            life: 3000,
        });
    } finally {
        loading.value = false;
    }
}

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function getUserPermissions(userId: number): string[] {
    return editedPermissions.value.get(userId) ?? [];
}

function hasPermission(userId: number, permissionKey: string): boolean {
    return getUserPermissions(userId).includes(permissionKey);
}

function togglePermission(userId: number, permissionKey: string) {
    const current = getUserPermissions(userId);
    const newPerms = current.includes(permissionKey)
        ? current.filter((p) => p !== permissionKey)
        : [...current, permissionKey];
    editedPermissions.value.set(userId, newPerms);
}

function resetChanges() {
    editedPermissions.value.clear();
    for (const user of users.value) {
        editedPermissions.value.set(user.id, [
            ...(originalPermissions.value.get(user.id) ?? []),
        ]);
    }
}

async function saveChanges() {
    saving.value = true;
    try {
        // Build payload with all users that have changes
        const payload = {
            users: users.value.map((user) => ({
                employee_id: user.id,
                permissions: editedPermissions.value.get(user.id) ?? [],
            })),
        };

        const response = await fetch(`/page-permissions/${props.page}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });

        if (!response.ok) throw new Error('Failed to save permissions');

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Permissions updated successfully',
            life: 3000,
        });

        // Update original permissions to match current
        for (const user of users.value) {
            originalPermissions.value.set(user.id, [
                ...(editedPermissions.value.get(user.id) ?? []),
            ]);
        }

        // Refresh the page to update navigation
        router.reload({ only: ['navigation'] });
    } catch {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to save permissions',
            life: 3000,
        });
    } finally {
        saving.value = false;
    }
}

function handleClose() {
    if (hasChanges.value) {
        confirm.require({
            message:
                'You have unsaved changes. Are you sure you want to close?',
            header: 'Unsaved Changes',
            icon: 'pi pi-exclamation-triangle',
            rejectLabel: 'Cancel',
            rejectProps: {
                severity: 'secondary',
                size: 'small',
            },
            acceptLabel: 'Discard',
            acceptProps: {
                severity: 'danger',
                size: 'small',
            },
            accept: () => {
                emit('update:visible', false);
            },
        });
    } else {
        emit('update:visible', false);
    }
}

// Fetch users when dialog opens
watch(
    () => props.visible,
    (newVisible) => {
        if (newVisible) {
            fetchUsers();
            searchQuery.value = '';
        }
    },
);
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        :header="`Manage ${pageLabel} Permissions`"
        :style="{ width: '50rem' }"
        :breakpoints="{ '960px': '90vw', '640px': '100vw' }"
        @update:visible="handleClose"
        :closable="true"
    >
        <div class="flex flex-col gap-4">
            <!-- Search -->
            <IconField class="w-full">
                <InputIcon class="pi pi-search" />
                <InputText
                    v-model="searchQuery"
                    placeholder="Search by name or email..."
                    size="small"
                    fluid
                />
            </IconField>

            <!-- Users Table -->
            <DataTable
                :value="filteredUsers"
                :loading="loading"
                dataKey="id"
                striped-rows
                size="small"
                scrollable
                scroll-height="400px"
                class="overflow-hidden rounded-lg border border-border"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No staff users found.
                    </div>
                </template>
                <template #loading>
                    <div class="p-4 text-center text-muted-foreground">
                        Loading users...
                    </div>
                </template>
                <Column header="User" class="min-w-[200px] !pl-4">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3">
                            <Avatar
                                v-if="data.profile_picture_url"
                                :image="data.profile_picture_url"
                                shape="circle"
                                class="!h-8 !w-8"
                            />
                            <Avatar
                                v-else
                                :label="getInitials(data.name)"
                                shape="circle"
                                class="!h-8 !w-8 bg-primary/10 text-primary"
                            />
                            <div class="flex flex-col">
                                <span class="font-medium">{{ data.name }}</span>
                                <span class="text-xs text-muted-foreground">{{
                                    data.email
                                }}</span>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column
                    v-for="permission in availablePermissions"
                    :key="permission.key"
                    :header="permission.label"
                    class="w-32 text-center"
                >
                    <template #body="{ data }">
                        <div class="flex justify-center">
                            <Checkbox
                                :modelValue="
                                    hasPermission(data.id, permission.key)
                                "
                                @update:modelValue="
                                    togglePermission(data.id, permission.key)
                                "
                                :binary="true"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!-- Actions -->
            <div
                class="flex items-center justify-between border-t border-border pt-4"
            >
                <div class="text-sm text-muted-foreground">
                    <span v-if="hasChanges" class="text-amber-600">
                        <i class="pi pi-exclamation-circle mr-1"></i>
                        Unsaved changes
                    </span>
                </div>
                <div class="flex gap-2">
                    <Button
                        label="Reset"
                        severity="secondary"
                        size="small"
                        :disabled="!hasChanges || saving"
                        @click="resetChanges"
                    />
                    <Button
                        label="Save"
                        icon="pi pi-check"
                        size="small"
                        :loading="saving"
                        :disabled="!hasChanges"
                        @click="saveChanges"
                    />
                </div>
            </div>
        </div>
    </Dialog>
</template>
