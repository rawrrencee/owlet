export interface Employee {
    id: number;
    first_name: string;
    last_name: string;
    chinese_name: string | null;
    employee_number: string | null;
    nric: string | null;
    phone: string | null;
    mobile: string | null;
    address_1: string | null;
    address_2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country: string | null;
    date_of_birth: string | null;
    gender: string | null;
    race: string | null;
    nationality: string | null;
    residency_status: string | null;
    pr_conversion_date: string | null;
    emergency_name: string | null;
    emergency_relationship: string | null;
    emergency_contact: string | null;
    emergency_address_1: string | null;
    emergency_address_2: string | null;
    bank_name: string | null;
    bank_account_number: string | null;
    hire_date: string | null;
    termination_date: string | null;
    notes: string | null;
    profile_picture_url: string | null;
    user?: {
        id: number;
        email: string;
        role: string;
        workos_id: string;
    } | null;
    companies?: {
        id: number;
        name: string;
    }[];
}

export interface WorkOSUser {
    id: string;
    email: string;
    firstName: string;
    lastName: string;
    emailVerified: boolean;
    profilePictureUrl: string | null;
    createdAt: string;
    updatedAt: string;
}
