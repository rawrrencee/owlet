export type QuotationStatusType = 'draft' | 'sent' | 'viewed' | 'signed' | 'accepted' | 'paid' | 'expired';
export type TaxMode = 'none' | 'store' | 'manual';

export interface QuotationItem {
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
    currency_id: number;
    currency?: {
        id: number;
        code: string;
        symbol: string;
    } | null;
    quantity: number;
    unit_price: string;
    sort_order: number;
    offer_id: number | null;
    offer_name: string | null;
    offer_discount_type: string | null;
    offer_discount_amount: string | null;
    offer_is_combinable: boolean | null;
    customer_discount_percentage: string | null;
    customer_discount_amount: string | null;
    line_subtotal: string;
    line_discount: string;
    line_total: string;
}

export interface CurrencyTotal {
    currency_id: number;
    currency_code: string;
    currency_symbol: string;
    subtotal: string;
    discount: string;
    tax: string;
    total: string;
}

export interface Quotation {
    id: number;
    quotation_number: string;
    company_id: number;
    company?: {
        id: number;
        company_name: string;
        registration_number: string | null;
        tax_registration_number: string | null;
        address_1: string | null;
        address_2: string | null;
        city: string | null;
        state: string | null;
        postal_code: string | null;
        email: string | null;
        phone_number: string | null;
        logo: string | null;
        logo_url: string | null;
    } | null;
    customer_id: number | null;
    customer?: {
        id: number;
        first_name: string;
        last_name: string;
        full_name: string;
        email: string | null;
        phone: string | null;
        discount_percentage: string | null;
    } | null;
    status: QuotationStatusType;
    status_label: string;
    show_company_logo: boolean;
    show_company_address: boolean;
    show_company_uen: boolean;
    tax_mode: TaxMode;
    tax_store_id: number | null;
    tax_store?: {
        id: number;
        store_name: string;
        store_code: string;
        tax_percentage: string | null;
    } | null;
    tax_percentage: string | null;
    tax_inclusive: boolean;
    terms_and_conditions: string | null;
    internal_notes: string | null;
    external_notes: string | null;
    payment_terms: string | null;
    validity_date: string | null;
    customer_discount_percentage: string | null;
    items?: QuotationItem[];
    payment_modes?: Array<{ id: number; name: string; code: string | null }>;
    item_count?: number;
    totals?: CurrencyTotal[];
    sent_at: string | null;
    viewed_at: string | null;
    signed_at: string | null;
    expired_at: string | null;
    created_by_user?: { id: number; name: string } | null;
    updated_by_user?: { id: number; name: string } | null;
    created_at: string | null;
    updated_at: string | null;
    share_token?: string | null;
    share_url?: string | null;
    has_password?: boolean;
    deleted_at?: string | null;
}
