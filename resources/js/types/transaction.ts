export type TransactionStatusType = 'draft' | 'suspended' | 'completed' | 'voided';

export type TransactionChangeTypeValue =
    | 'created'
    | 'item_added'
    | 'item_removed'
    | 'item_modified'
    | 'customer_changed'
    | 'payment_added'
    | 'payment_removed'
    | 'discount_applied'
    | 'offer_applied'
    | 'completed'
    | 'suspended'
    | 'resumed'
    | 'voided'
    | 'refund';

export interface TransactionItem {
    id: number;
    transaction_id: number;
    product_id: number;
    product?: {
        id: number;
        product_name: string;
        product_number: string;
        variant_name: string | null;
        barcode: string | null;
        image_path: string | null;
    } | null;
    product_name: string;
    product_number: string;
    variant_name: string | null;
    barcode: string | null;
    quantity: number;
    cost_price: string | null;
    unit_price: string;
    offer_id: number | null;
    offer_name: string | null;
    offer_discount_type: string | null;
    offer_discount_amount: string;
    offer_is_combinable: boolean | null;
    customer_discount_percentage: string | null;
    customer_discount_amount: string;
    line_subtotal: string;
    line_discount: string;
    line_total: string;
    is_refunded: boolean;
    refund_reason: string | null;
    sort_order: number;
    created_at: string | null;
    updated_at: string | null;
}

export interface TransactionPayment {
    id: number;
    transaction_id: number;
    payment_mode_id: number;
    payment_mode?: {
        id: number;
        name: string;
        code: string | null;
    } | null;
    payment_mode_name: string;
    amount: string;
    payment_data: Record<string, unknown> | null;
    row_number: number;
    balance_after: string;
    created_by: number | null;
    created_by_user?: { id: number; name: string } | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface TransactionVersion {
    id: number;
    transaction_id: number;
    version_number: number;
    change_type: TransactionChangeTypeValue;
    changed_by: number;
    changed_by_user?: { id: number; name: string } | null;
    change_summary: string;
    snapshot_items: TransactionItem[];
    snapshot_payments: TransactionPayment[];
    snapshot_totals: Record<string, string>;
    diff_data: Record<string, unknown> | null;
    created_at: string;
}

export interface Transaction {
    id: number;
    transaction_number: string;
    store_id: number;
    store?: {
        id: number;
        store_name: string;
        store_code: string;
        tax_percentage: string | null;
        include_tax: boolean;
    } | null;
    employee_id: number;
    employee?: {
        id: number;
        first_name: string;
        last_name: string;
        full_name: string;
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
    currency_id: number;
    currency?: {
        id: number;
        code: string;
        symbol: string;
        name: string;
    } | null;
    status: TransactionStatusType;
    checkout_date: string | null;
    subtotal: string;
    offer_discount: string;
    bundle_discount: string;
    minimum_spend_discount: string;
    customer_discount: string;
    manual_discount: string;
    tax_percentage: string | null;
    tax_inclusive: boolean;
    tax_amount: string;
    total: string;
    amount_paid: string;
    refund_amount: string;
    balance_due: string;
    change_amount: string;
    comments: string | null;
    bundle_offer_id: number | null;
    bundle_offer_name: string | null;
    minimum_spend_offer_id: number | null;
    minimum_spend_offer_name: string | null;
    customer_discount_percentage: string | null;
    version_count: number;
    items_count?: number;
    items?: TransactionItem[];
    payments?: TransactionPayment[];
    versions?: TransactionVersion[];
    created_by_user?: { id: number; name: string } | null;
    updated_by_user?: { id: number; name: string } | null;
    created_at: string | null;
    updated_at: string | null;
    deleted_at?: string | null;
}
