import type { Country } from './country';
import type { ContractLeaveEntitlement } from './leave';

export interface Company {
    id: number;
    company_name: string;
    registration_number: string | null;
    tax_registration_number: string | null;
    address_1: string | null;
    address_2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country_id: number | null;
    country_name?: string | null;
    country_code?: string | null;
    country?: Country;
    email: string | null;
    phone_number: string | null;
    mobile_number: string | null;
    website: string | null;
    logo: string | null;
    logo_url: string | null;
    active: boolean;
    is_deleted?: boolean;
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

export interface EmployeeContract {
    id: number;
    employee_id: number;
    company_id: number | null;
    start_date: string;
    end_date: string | null;
    salary_amount: string | number;
    leave_entitlements?: ContractLeaveEntitlement[];
    external_document_url: string | null;
    document_url: string | null;
    document_filename: string | null;
    document_mime_type: string | null;
    has_document: boolean;
    is_document_viewable_inline: boolean;
    comments: string | null;
    is_active: boolean;
    company?: Company;
    employee?: {
        id: number;
        first_name: string;
        last_name: string;
    };
    employee_is_deleted?: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface EmployeeInsurance {
    id: number;
    employee_id: number;
    title: string;
    insurer_name: string;
    policy_number: string;
    start_date: string;
    end_date: string | null;
    external_document_url: string | null;
    document_url: string | null;
    document_filename: string | null;
    document_mime_type: string | null;
    has_document: boolean;
    is_document_viewable_inline: boolean;
    comments: string | null;
    is_active: boolean;
    employee?: {
        id: number;
        first_name: string;
        last_name: string;
    };
    employee_is_deleted?: boolean;
    created_at: string | null;
    updated_at: string | null;
}
