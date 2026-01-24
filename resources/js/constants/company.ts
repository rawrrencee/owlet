export const employmentStatuses = ['FT', 'PT', 'CT', 'CA'] as const;

export const employmentStatusOptions = [
    { label: 'Full Time', value: 'FT' },
    { label: 'Part Time', value: 'PT' },
    { label: 'Contract', value: 'CT' },
    { label: 'Casual', value: 'CA' },
] as const;

export const employmentStatusLabels: Record<string, string> = {
    FT: 'Full Time',
    PT: 'Part Time',
    CT: 'Contract',
    CA: 'Casual',
};
