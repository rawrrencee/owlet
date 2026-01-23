<?php

use App\Models\Customer;
use App\Models\Employee;
use App\Models\User;

it('can have an employee', function () {
    $employee = Employee::factory()->create();
    $user = User::factory()->forEmployee($employee)->create();

    expect($user->employee)->toBeInstanceOf(Employee::class);
    expect($user->employee->id)->toBe($employee->id);
});

it('can have a customer', function () {
    $customer = Customer::factory()->create();
    $user = User::factory()->forCustomer($customer)->create();

    expect($user->customer)->toBeInstanceOf(Customer::class);
    expect($user->customer->id)->toBe($customer->id);
});

it('can have both employee and customer', function () {
    $employee = Employee::factory()->create();
    $customer = Customer::factory()->create();
    $user = User::factory()
        ->forEmployee($employee)
        ->forCustomer($customer)
        ->create();

    expect($user->employee)->toBeInstanceOf(Employee::class);
    expect($user->customer)->toBeInstanceOf(Customer::class);
    expect($user->isEmployee())->toBeTrue();
    expect($user->isCustomer())->toBeTrue();
});

it('can exist without employee or customer', function () {
    $user = User::factory()->create();

    expect($user->employee)->toBeNull();
    expect($user->customer)->toBeNull();
    expect($user->employee_id)->toBeNull();
    expect($user->customer_id)->toBeNull();
});

it('returns true for isEmployee when has employee', function () {
    $employee = Employee::factory()->create();
    $user = User::factory()->forEmployee($employee)->create();

    expect($user->isEmployee())->toBeTrue();
});

it('returns true for isCustomer when has customer', function () {
    $customer = Customer::factory()->create();
    $user = User::factory()->forCustomer($customer)->create();

    expect($user->isCustomer())->toBeTrue();
});

it('defaults to active', function () {
    $user = User::factory()->create();

    expect($user->is_active)->toBeTrue();
});

it('can be set to inactive', function () {
    $user = User::factory()->inactive()->create();

    expect($user->is_active)->toBeFalse();
});

it('supports soft deletes', function () {
    $user = User::factory()->create();
    $user->delete();

    expect($user->trashed())->toBeTrue();
    expect(User::withTrashed()->find($user->id))->not->toBeNull();
    expect(User::find($user->id))->toBeNull();
});
