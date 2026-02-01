export interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
    decimal_places: number;
    active: boolean;
    exchange_rate: number | string | null;
    exchange_rate_updated_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface StoreCurrency {
    id: number;
    store_id: number;
    currency_id: number;
    is_default: boolean;
    exchange_rate: number | string | null;
    currency?: Currency;
    created_at: string | null;
    updated_at: string | null;
}
