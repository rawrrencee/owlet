<?php

use App\Models\Customer;
use App\Models\User;

it('can be created with factory', function () {
    $customer = Customer::factory()->create();

    expect($customer)->toBeInstanceOf(Customer::class);
    expect($customer->first_name)->not->toBeEmpty();
    expect($customer->last_name)->not->toBeEmpty();
});

it('has full name attribute', function () {
    $customer = Customer::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);

    expect($customer->full_name)->toBe('Jane Smith');
});

it('can have a user', function () {
    $customer = Customer::factory()->create();
    $user = User::factory()->forCustomer($customer)->create();

    expect($customer->user)->toBeInstanceOf(User::class);
    expect($customer->user->id)->toBe($user->id);
});

it('can exist without user account', function () {
    $customer = Customer::factory()->create();

    expect($customer->user)->toBeNull();
    expect($customer->hasUserAccount())->toBeFalse();
});

it('knows when it has user account', function () {
    $customer = Customer::factory()->create();
    User::factory()->forCustomer($customer)->create();

    expect($customer->hasUserAccount())->toBeTrue();
});

it('casts date fields correctly', function () {
    $customer = Customer::factory()->create([
        'date_of_birth' => '1985-03-20',
        'customer_since' => '2023-06-15',
    ]);

    expect($customer->date_of_birth)->toBeInstanceOf(Carbon\CarbonInterface::class);
    expect($customer->customer_since)->toBeInstanceOf(Carbon\CarbonInterface::class);
});

it('defaults discount percentage to zero', function () {
    $customer = Customer::factory()->create();

    expect($customer->discount_percentage)->toBe('0.00');
});

it('can have discount percentage', function () {
    $customer = Customer::factory()->withDiscount(15.50)->create();

    expect($customer->discount_percentage)->toBe('15.50');
});

it('defaults loyalty points to zero', function () {
    $customer = Customer::factory()->create();

    expect($customer->loyalty_points)->toBe(0);
});

it('can have loyalty points', function () {
    $customer = Customer::factory()->withLoyaltyPoints(500)->create();

    expect($customer->loyalty_points)->toBe(500);
});

it('supports soft deletes', function () {
    $customer = Customer::factory()->create();
    $customer->delete();

    expect($customer->trashed())->toBeTrue();
    expect(Customer::withTrashed()->find($customer->id))->not->toBeNull();
    expect(Customer::find($customer->id))->toBeNull();
});
