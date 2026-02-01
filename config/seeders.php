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
        // People - creates ~20 pages of data at 15 per page
        'employees' => (int) env('SEEDER_EMPLOYEE_COUNT', 300),
        'employees_with_workos' => (int) env('SEEDER_EMPLOYEE_WORKOS_COUNT', 5), // First N employees get WorkOS accounts
        'customers' => (int) env('SEEDER_CUSTOMER_COUNT', 300),
        'customers_with_workos' => (int) env('SEEDER_CUSTOMER_WORKOS_COUNT', 5), // First N customers get WorkOS accounts

        // Organizations
        'companies' => 3,
        'stores_per_company' => 7, // ~20 stores total

        // Product catalog
        'brands' => 5,
        'categories' => 5,
        'subcategories_per_category' => 3,
        'suppliers' => 5,
        'products' => 300,
        'tags' => 100,

        // Employee attributes
        'designations' => 8, // Limited by predefined list
        'contracts_per_employee' => 2,
        'insurances_per_employee' => 2,
        'timecards_per_employee' => 10,
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
