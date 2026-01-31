export type User = {
    id: number;
    name: string;
    display_name: string;
    email: string;
    avatar?: string;
    role: 'admin' | 'staff';
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type StorePermissions = {
    access: string[];
    operations: string[];
};

export type Permissions = {
    is_admin: boolean;
    page_permissions: string[];
    store_permissions: Record<number, StorePermissions>;
};

export type Auth = {
    user: User;
    permissions: Permissions | null;
};
