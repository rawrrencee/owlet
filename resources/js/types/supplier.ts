export interface Supplier {
    id: number;
    supplier_name: string;
    country_id: number | null;
    country_name?: string | null;
    address_1: string | null;
    address_2: string | null;
    email: string | null;
    phone_number: string | null;
    mobile_number: string | null;
    website: string | null;
    logo_url: string | null;
    description: string | null;
    active: boolean;
    is_deleted?: boolean;
    created_at: string | null;
    updated_at: string | null;
}
