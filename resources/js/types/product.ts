import type { Brand } from './brand';
import type { Currency } from './currency';

export interface ProductPrice {
    id: number;
    product_id: number;
    currency_id: number;
    currency?: Currency;
    cost_price?: string | null;
    unit_price: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface ProductStorePrice {
    id: number;
    product_store_id: number;
    currency_id: number;
    currency?: Currency;
    cost_price?: string | null;
    unit_price: string | null;
    effective_cost_price?: string | null;
    effective_unit_price?: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface ProductStore {
    id: number;
    product_id: number;
    store_id: number;
    store?: {
        id: number;
        store_name: string;
        store_code: string;
    };
    quantity: number;
    is_active: boolean;
    store_prices?: ProductStorePrice[];
    created_at: string | null;
    updated_at: string | null;
}

export interface Product {
    id: number;
    product_name: string;
    product_number: string;
    barcode: string | null;
    brand_id: number;
    brand_name?: string | null;
    brand?: {
        id: number;
        brand_name: string;
        brand_code: string;
    };
    category_id: number;
    category_name?: string | null;
    category?: {
        id: number;
        category_name: string;
        category_code: string;
    };
    subcategory_id: number;
    subcategory_name?: string | null;
    subcategory?: {
        id: number;
        subcategory_name: string;
        subcategory_code: string;
    };
    supplier_id: number;
    supplier_name?: string | null;
    supplier?: {
        id: number;
        supplier_name: string;
    };
    supplier_number: string | null;
    description: string | null;
    tags: string[];
    cost_price_remarks?: string | null;
    image_path: string | null;
    image_filename: string | null;
    image_mime_type: string | null;
    image_url: string | null;
    weight: string | null;
    weight_unit: 'kg' | 'g' | 'lb' | 'oz';
    weight_display: string | null;
    is_active: boolean;
    is_deleted?: boolean;
    prices?: ProductPrice[];
    product_stores?: ProductStore[];
    created_at: string | null;
    updated_at: string | null;
}

export interface WeightUnitOption {
    value: string;
    label: string;
}

export interface ProductSearchResult {
    id: number;
    product_name: string;
    product_number: string;
    barcode: string | null;
    brand_name: string | null;
    image_url: string | null;
}

export interface ProductAdjacentIds {
    prev_id: number | null;
    next_id: number | null;
    position: number | null;
    total: number;
}
