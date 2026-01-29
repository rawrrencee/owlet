// Emergency contact relationships
export const relationships = [
    'Spouse',
    'Parent',
    'Child',
    'Sibling',
    'Grandparent',
    'Grandchild',
    'Partner',
    'Friend',
    'Relative',
    'Guardian',
    'Other',
] as const;

// Helper to convert arrays to PrimeVue select options
export const relationshipOptions = relationships.map((r) => ({ label: r, value: r }));
