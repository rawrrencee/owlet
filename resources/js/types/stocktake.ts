export interface Stocktake {
    id: number;
    employee_id: number;
    employee?: {
        id: number;
        name: string;
        employee_number: string | null;
        profile_picture_url: string | null;
    };
    store_id: number;
    store?: {
        id: number;
        store_name: string;
        store_code: string;
    };
    status: 'in_progress' | 'submitted' | 'expired';
    status_label: string;
    has_issues: boolean;
    submitted_at: string | null;
    notes: string | null;
    items?: StocktakeItem[];
    items_count?: number;
    created_at: string | null;
    updated_at?: string | null;
}

export interface StocktakeItem {
    id: number;
    product_id: number;
    product?: {
        id: number;
        product_name: string;
        product_number: string;
        variant_name: string | null;
        barcode: string | null;
        image_url: string | null;
    };
    counted_quantity: number;
    has_discrepancy: boolean;
    system_quantity?: number;
    difference?: number;
}

export interface StocktakeTemplate {
    id: number;
    employee_id: number;
    employee?: {
        id: number;
        name: string;
    };
    store_id: number;
    store?: {
        id: number;
        store_name: string;
        store_code: string;
    };
    name: string;
    description: string | null;
    products?: {
        id: number;
        product_name: string;
        product_number: string;
        variant_name: string | null;
    }[];
    products_count?: number;
    created_at?: string | null;
    updated_at?: string | null;
}

export interface StocktakeProductSearchResult {
    id: number;
    product_name: string;
    product_number: string;
    variant_name: string | null;
    barcode: string | null;
    image_url: string | null;
}

export interface StocktakeNotificationRecipient {
    id: number;
    store_id: number;
    email: string;
    name: string | null;
    is_active: boolean;
}

export interface StocktakeManagementFilters {
    store_id: string | null;
    start_date: string | null;
    end_date: string | null;
    tab: string;
    search: string | null;
}
