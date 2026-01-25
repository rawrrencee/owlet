export interface OrgChartNodeData {
    id: number;
    name: string;
    profile_picture_url: string | null;
    designation: string | null;
    company: string | null;
    tier: number;
}

export interface OrgChartNode {
    key: string;
    type: 'employee';
    data: OrgChartNodeData;
    children: OrgChartNode[];
}

export interface SubordinateInfo {
    id: number;
    name: string;
    profile_picture_url: string | null;
    employee_number: string | null;
    email: string | null;
    phone: string | null;
    companies?: { id: number; name: string }[];
    stores?: { id: number; name: string }[];
    subordinates?: SubordinateInfo[];
}

export interface HierarchyVisibilitySettings {
    visible_sections: string[];
}

export interface AvailableSections {
    [key: string]: string;
}

export interface EmployeeHierarchyData {
    subordinates: SubordinateInfo[];
    subtree: OrgChartNode[];
    visibility_settings: HierarchyVisibilitySettings;
    available_sections: AvailableSections;
    available_subordinates: { label: string; value: number }[];
}

// Organisation Chart Edit page types
export interface ManagerInfo {
    id: number;
    name: string;
    profile_picture_url: string | null;
    employee_number: string | null;
}

export interface EmployeeWithManagers {
    id: number;
    name: string;
    profile_picture_url: string | null;
    employee_number: string | null;
    designation: string | null;
    company: string | null;
    tier: number;
    managers: ManagerInfo[];
}

export interface AvailableManager {
    label: string;
    value: number;
}

export interface EmployeeManagersData {
    employee: EmployeeWithManagers;
    available_managers: AvailableManager[];
}

export interface BulkAssignResult {
    success: number[];
    failed: Array<{ id: number; name: string; reason: string }>;
}
