export interface PurchaseOrder {
    id: number;
    order_number: string;
    supplier?: {
        id: number;
        supplier_name: string;
    };
    supplier_id: number;
    store?: {
        id: number;
        store_name: string;
        store_code: string;
    } | null;
    store_id: number | null;
    status: 'draft' | 'submitted' | 'accepted' | 'rejected';
    status_label: string;
    notes: string | null;
    items?: PurchaseOrderItem[];
    items_count?: number;
    has_corrections?: boolean;
    submitted_at: string | null;
    submitted_by_user?: { id: number; name: string } | null;
    resolved_at: string | null;
    resolved_by_user?: { id: number; name: string } | null;
    rejection_reason: string | null;
    created_by_user?: { id: number; name: string } | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface PurchaseOrderItem {
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
    currency_id: number;
    currency?: {
        id: number;
        code: string;
        name: string;
        symbol: string;
    };
    unit_cost: string | number;
    quantity: number;
    total_cost: string | number;
    received_quantity: number | null;
    correction_note: string | null;
    has_correction: boolean;
}
