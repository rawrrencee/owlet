import type { Country } from './country';

export interface Customer {
    id: number;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
    country_id: number | null;
    country_name?: string | null;
    country?: Country;
    nationality_id: number | null;
    nationality_name?: string | null;
    nationality_country?: Country;
    company_name: string | null;
    customer_since: string | null;
    loyalty_points: number;
    image_url: string | null;
}
