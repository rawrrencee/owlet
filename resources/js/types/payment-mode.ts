export interface PaymentMode {
    id: number;
    name: string;
    code: string | null;
    description: string | null;
    is_active: boolean;
    sort_order: number;
    created_by_user?: { id: number; name: string } | null;
    updated_by_user?: { id: number; name: string } | null;
    created_at: string | null;
    updated_at: string | null;
    deleted_at?: string | null;
}
