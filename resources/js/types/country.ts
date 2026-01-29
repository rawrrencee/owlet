export interface Country {
    id: number;
    name: string;
    code: string;
    code_3: string | null;
    nationality_name: string;
    phone_code: string | null;
    active: boolean;
    sort_order: number;
}
