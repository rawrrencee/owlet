export interface DeliveryOrder {
    id: number;
    order_number: string;
    store_from?: {
        id: number;
        store_name: string;
        store_code: string;
    };
    store_to?: {
        id: number;
        store_name: string;
        store_code: string;
    };
    store_id_from: number;
    store_id_to: number;
    status: 'draft' | 'submitted' | 'approved' | 'rejected';
    status_label: string;
    notes: string | null;
    items?: DeliveryOrderItem[];
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

export interface DeliveryOrderItem {
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
    quantity: number;
    received_quantity: number | null;
    correction_note: string | null;
    has_correction: boolean;
}
