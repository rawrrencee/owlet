export interface Customer {
    id: number;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
    company_name: string | null;
    customer_since: string | null;
    loyalty_points: number;
    image_url: string | null;
}
