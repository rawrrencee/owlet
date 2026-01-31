export interface Category {
    id: number;
    category_name: string;
    category_code: string;
    description: string | null;
    is_active: boolean;
    is_deleted?: boolean;
    subcategories?: Subcategory[];
    subcategories_count?: number;
    active_subcategories_count?: number;
    created_at: string | null;
    updated_at: string | null;
}

export interface Subcategory {
    id: number;
    category_id: number;
    subcategory_name: string;
    subcategory_code: string;
    description: string | null;
    is_default: boolean;
    is_active: boolean;
    is_deleted?: boolean;
    category?: Category;
    created_at: string | null;
    updated_at: string | null;
}
