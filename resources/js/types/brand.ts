import type { Country } from './country';

export interface Brand {
    id: number;
    brand_name: string;
    brand_code: string;
    country_id: number | null;
    country_name?: string | null;
    country?: Country;
    address_1: string | null;
    address_2: string | null;
    email: string | null;
    phone_number: string | null;
    mobile_number: string | null;
    website: string | null;
    logo_path: string | null;
    logo_filename: string | null;
    logo_mime_type: string | null;
    logo_url: string | null;
    description: string | null;
    is_active: boolean;
    is_deleted?: boolean;
    created_at: string | null;
    updated_at: string | null;
}
