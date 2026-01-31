<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WorkOS Seeder Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WorkOS user management during seeding.
    | The external_id_prefix is used to identify seeded users for cleanup.
    |
    */
    'workos' => [
        'external_id_prefix' => env('SEEDER_WORKOS_PREFIX', 'owlet-seeder-'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeder Counts
    |--------------------------------------------------------------------------
    |
    | Configure the number of records to create for each entity type.
    | These can be overridden via environment variables for employees and customers.
    |
    */
    'counts' => [
        'employees' => (int) env('SEEDER_EMPLOYEE_COUNT', 2),
        'customers' => (int) env('SEEDER_CUSTOMER_COUNT', 2),
        'companies' => 2,
        'stores_per_company' => 2,
        'brands' => 3,
        'categories' => 3,
        'subcategories_per_category' => 2,
        'designations' => 5,
        'suppliers' => 3,
        'contracts_per_employee' => 1,
        'insurances_per_employee' => 1,
        'timecards_per_employee' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Password
    |--------------------------------------------------------------------------
    |
    | The default password for all seeded user accounts.
    | This should be changed in production environments.
    |
    */
    'default_password' => env('SEEDER_DEFAULT_PASSWORD', 'SeederPassword123!'),
];
