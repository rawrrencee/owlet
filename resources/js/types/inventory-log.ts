export interface InventoryLog {
    id: number;
    product?: {
        id: number;
        product_name: string;
        product_number: string;
        variant_name: string | null;
    };
    store?: {
        id: number;
        store_name: string;
        store_code: string;
    };
    activity_code: string;
    activity_label: string;
    quantity_in: number;
    quantity_out: number;
    current_quantity: number;
    notes: string | null;
    stocktake_id: number | null;
    delivery_order_id: number | null;
    delivery_order?: {
        id: number;
        order_number: string;
    } | null;
    purchase_order_id: number | null;
    purchase_order?: {
        id: number;
        order_number: string;
    } | null;
    created_by_user?: {
        id: number;
        name: string;
    } | null;
    created_at: string | null;
}
