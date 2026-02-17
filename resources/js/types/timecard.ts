export type TimecardStatus = 'IN_PROGRESS' | 'COMPLETED' | 'EXPIRED';
export type TimecardDetailType = 'WORK' | 'BREAK';

export interface TimecardDetail {
    id: number;
    timecard_id: number;
    type: TimecardDetailType;
    type_label: string;
    start_date: string;
    end_date: string | null;
    hours: number;
    is_work: boolean;
    is_break: boolean;
    is_open: boolean;
    created_at: string;
    updated_at: string;
}

export interface Timecard {
    id: number;
    employee_id: number;
    store_id: number;
    status: TimecardStatus;
    status_label: string;
    start_date: string;
    end_date: string | null;
    user_provided_end_date: string | null;
    hours_worked: number;
    is_in_progress: boolean;
    is_completed: boolean;
    is_expired: boolean;
    is_incomplete: boolean;
    is_inaccurate: boolean;
    is_on_break: boolean;
    employee?: {
        id: number;
        name: string;
        employee_number: string | null;
        profile_picture_url: string | null;
    };
    store?: {
        id: number;
        name: string;
        store_code: string;
    };
    details?: TimecardDetail[];
    current_detail?: TimecardDetail | null;
    created_by?: {
        id: number;
        name: string;
    } | null;
    updated_by?: {
        id: number;
        name: string;
    } | null;
    created_at: string;
    updated_at: string;
}

export interface TimecardStore {
    id: number;
    name: string;
    code: string;
}

export interface TimecardSummary {
    date: string;
    stores: {
        id: number;
        name: string;
        hours: number;
        status: TimecardStatus;
    }[];
    total_hours: number;
}

export interface CalendarLeaveData {
    id: number;
    leave_type: string;
    color: string;
    status: 'pending' | 'approved';
    is_half_day: boolean;
    half_day_type?: 'am' | 'pm';
}

export interface CalendarDayData {
    date: string;
    total_hours: number;
    status?: TimecardStatus;
    employee_count?: number;
    employees?: {
        id: number;
        name: string;
        hours: number;
    }[];
    stores?: {
        id: number;
        name: string;
        hours: number;
        status: TimecardStatus;
    }[];
    leave?: CalendarLeaveData[];
}

export interface CurrentTimecardState {
    timecard: Timecard | null;
    current_detail: TimecardDetail | null;
    is_on_break: boolean;
}

export interface TeamTimecardDayData {
    date: string;
    employees: {
        id: number;
        name: string;
        hours: number;
    }[];
    employee_count: number;
    total_hours: number;
}

export interface TimecardSubordinateInfo {
    id: number;
    name: string;
    employee_number: string | null;
    profile_picture_url: string | null;
}
