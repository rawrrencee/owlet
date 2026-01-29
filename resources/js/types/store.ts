import type { Company } from './company';
import type { Country } from './country';
import type { Currency, StoreCurrency } from './currency';

export interface Store {
    id: number;
    store_name: string;
    store_code: string;
    company_id: number | null;
    address_1: string | null;
    address_2: string | null;
    country_id: number | null;
    country_name?: string | null;
    country?: Country;
    email: string | null;
    phone_number: string | null;
    mobile_number: string | null;
    website: string | null;
    active: boolean;
    include_tax: boolean;
    tax_percentage: number | string;
    logo: string | null;
    logo_url: string | null;
    company?: Company;
    store_currencies?: StoreCurrency[];
    default_currency?: Currency | null;
    is_deleted?: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface StorePermission {
    key: string;
    label: string;
    group: string;
}

export interface StorePermissionGroup {
    [group: string]: StorePermission[];
}

export interface EmployeeStore {
    id: number;
    employee_id: number;
    store_id: number;
    active: boolean;
    permissions: string[];
    permissions_with_labels: StorePermission[];
    store?: Store;
    employee?: {
        id: number;
        first_name: string;
        last_name: string;
    };
    created_at: string | null;
    updated_at: string | null;
}

export interface EmployeeStoreView {
    id: number;
    employee_id: number;
    employee_name: string;
    employee_number: string | null;
    profile_picture_url: string | null;
    active: boolean;
    permissions: string[];
    permissions_with_labels: StorePermission[];
}
