export interface Company {
    id: number;
    company_name: string;
    address_1: string | null;
    address_2: string | null;
    email: string | null;
    phone_number: string | null;
    mobile_number: string | null;
    website: string | null;
    logo: string | null;
    logo_url: string | null;
    active: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface Designation {
    id: number;
    designation_name: string;
    designation_code: string;
    created_at: string | null;
    updated_at: string | null;
}

export interface EmployeeCompany {
    id: number;
    employee_id: number;
    company_id: number;
    designation_id: number | null;
    levy_amount: number;
    status: 'FT' | 'PT' | 'CT' | 'CA';
    status_label: string;
    include_shg_donations: boolean;
    commencement_date: string;
    left_date: string | null;
    is_active: boolean;
    company?: Company;
    designation?: Designation;
    created_at: string | null;
    updated_at: string | null;
}

export type EmploymentStatus = 'FT' | 'PT' | 'CT' | 'CA';
