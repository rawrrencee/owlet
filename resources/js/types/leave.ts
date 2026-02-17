export interface LeaveType {
    id: number;
    name: string;
    code: string;
    description: string | null;
    color: string | null;
    requires_balance: boolean;
    is_active: boolean;
    sort_order: number;
    created_at: string | null;
    updated_at: string | null;
}

export interface ContractLeaveEntitlement {
    id: number;
    employee_contract_id: number;
    leave_type_id: number;
    entitled_days: number;
    taken_days: number;
    remaining_days: number;
    leave_type?: LeaveType;
    created_at: string | null;
    updated_at: string | null;
}

export type LeaveRequestStatusValue =
    | 'pending'
    | 'approved'
    | 'rejected'
    | 'cancelled';

export type HalfDayTypeValue = 'full' | 'am' | 'pm';

export interface LeaveRequest {
    id: number;
    employee_id: number;
    employee_contract_id: number;
    leave_type_id: number;
    start_date: string;
    end_date: string;
    start_half_day: HalfDayTypeValue;
    end_half_day: HalfDayTypeValue;
    total_days: number;
    reason: string | null;
    status: LeaveRequestStatusValue;
    status_label: string;
    rejection_reason: string | null;
    resolved_at: string | null;
    cancelled_at: string | null;
    employee?: {
        id: number;
        first_name: string;
        last_name: string;
        full_name: string;
        employee_number: string | null;
        profile_picture_url: string | null;
    };
    leave_type?: LeaveType;
    resolved_by?: {
        id: number;
        name: string;
    } | null;
    cancelled_by?: {
        id: number;
        name: string;
    } | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface LeaveBalance {
    leave_type: LeaveType;
    entitled_days: number;
    taken_days: number;
    remaining_days: number;
}
