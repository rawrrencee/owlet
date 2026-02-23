export interface WeeklyTimecardDay {
    date: string;
    day_name: string;
    hours: number;
    is_today: boolean;
}

export interface WeeklyTimecardData {
    days: WeeklyTimecardDay[];
    total_hours: number;
}

export interface TeamMember {
    id: number;
    name: string;
    profile_picture_url: string | null;
    is_timed_in: boolean;
}

export interface SalesPerformanceData {
    total_sales: number;
    transaction_count: number;
    avg_order_value: number;
    prev_total_sales: number;
    currency_symbol: string;
    period_label: string;
}

export interface UpcomingLeaveItem {
    id: number;
    type_name: string;
    type_color: string | null;
    start_date: string;
    end_date: string;
    total_days: number;
    status: string;
    status_label: string;
}

export interface RecentActivityItem {
    type: string;
    label: string;
    status: string;
    date: string;
    url: string;
}

export interface QuickLink {
    label: string;
    href: string;
    icon: string;
}

export interface QuotationPipelineStatus {
    status: string;
    label: string;
    count: number;
}

export interface QuotationPipelineData {
    total: number;
    by_status: QuotationPipelineStatus[];
}

export interface LowStockItem {
    product_id: number;
    product_name: string;
    store_name: string;
    quantity: number;
}

export interface ActiveOfferType {
    type: string;
    label: string;
    count: number;
}

export interface ActiveOffersData {
    total: number;
    by_type: ActiveOfferType[];
}

export interface PendingOrdersData {
    delivery_orders: number;
    purchase_orders: number;
}
