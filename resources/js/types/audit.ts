export interface AuditUser {
    id: number;
    name: string;
}

export interface HasAuditTrail {
    created_by?: AuditUser | null;
    updated_by?: AuditUser | null;
    previous_updated_by?: AuditUser | null;
    previous_updated_at?: string | null;
    created_at: string;
    updated_at: string;
}
