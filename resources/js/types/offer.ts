export type OfferType = 'product' | 'bundle' | 'minimum_spend' | 'category' | 'brand';
export type DiscountType = 'percentage' | 'fixed';
export type OfferStatusType = 'draft' | 'scheduled' | 'active' | 'expired' | 'disabled';
export type BundleModeType = 'whole' | 'cheapest_item';

export interface Offer {
    id: number;
    name: string;
    code: string | null;
    type: OfferType;
    type_label: string;
    discount_type: DiscountType;
    discount_type_label: string;
    discount_percentage: string | null;
    description: string | null;
    status: OfferStatusType;
    status_label: string;
    starts_at: string | null;
    ends_at: string | null;
    is_combinable: boolean;
    priority: number;
    apply_to_all_stores: boolean;
    bundle_mode: BundleModeType | null;
    bundle_mode_label: string | null;
    category?: { id: number; category_name: string } | null;
    brand?: { id: number; brand_name: string } | null;
    stores?: Array<{ id: number; store_name: string; store_code: string }>;
    amounts?: OfferAmount[];
    products?: OfferProductEntry[];
    bundles?: OfferBundleEntry[];
    minimum_spends?: OfferMinimumSpendEntry[];
    created_by_user?: { id: number; name: string } | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface OfferAmount {
    id: number;
    currency_id: number;
    currency?: { id: number; code: string; symbol: string } | null;
    discount_amount: string | null;
    max_discount_amount: string | null;
}

export interface OfferProductEntry {
    id: number;
    product_id: number;
    product?: {
        id: number;
        product_name: string;
        product_number: string;
        variant_name: string | null;
        barcode: string | null;
        image_url: string | null;
    } | null;
}

export interface OfferBundleEntry {
    id: number;
    product_id: number | null;
    category_id: number | null;
    subcategory_id: number | null;
    required_quantity: number;
    product?: {
        id: number;
        product_name: string;
        product_number: string;
        variant_name: string | null;
        barcode: string | null;
        image_url: string | null;
    } | null;
    category?: { id: number; category_name: string } | null;
    subcategory?: {
        id: number;
        subcategory_name: string;
        category?: { id: number; category_name: string } | null;
    } | null;
}

export interface OfferMinimumSpendEntry {
    id: number;
    currency_id: number;
    currency?: { id: number; code: string; symbol: string } | null;
    minimum_amount: string;
}

export interface ResolvedOffer {
    offer_id: number;
    offer_name: string;
    discount_type: DiscountType;
    discount_amount: string;
    is_combinable: boolean;
}
