export interface PagePermission {
    key: string;
    label: string;
    group: string;
}

export interface UserPagePermission {
    id: number;
    name: string;
    email: string;
    profile_picture_url: string | null;
    permissions: string[];
}

export interface PagePermissionsResponse {
    data: UserPagePermission[];
    available_permissions: PagePermission[];
}

export interface UpdatePagePermissionsPayload {
    users: Array<{
        employee_id: number;
        permissions: string[];
    }>;
}
