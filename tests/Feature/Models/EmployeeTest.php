<?php

use App\Models\Employee;
use App\Models\User;

it('can be created with factory', function () {
    $employee = Employee::factory()->create();

    expect($employee)->toBeInstanceOf(Employee::class);
    expect($employee->first_name)->not->toBeEmpty();
    expect($employee->last_name)->not->toBeEmpty();
});

it('has full name attribute', function () {
    $employee = Employee::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    expect($employee->full_name)->toBe('John Doe');
});

it('can have a user', function () {
    $employee = Employee::factory()->create();
    $user = User::factory()->forEmployee($employee)->create();

    expect($employee->user)->toBeInstanceOf(User::class);
    expect($employee->user->id)->toBe($user->id);
});

it('casts date fields correctly', function () {
    $employee = Employee::factory()->create([
        'date_of_birth' => '1990-05-15',
        'hire_date' => '2020-01-01',
    ]);

    expect($employee->date_of_birth)->toBeInstanceOf(Carbon\CarbonInterface::class);
    expect($employee->hire_date)->toBeInstanceOf(Carbon\CarbonInterface::class);
});

it('can be terminated', function () {
    $employee = Employee::factory()->terminated()->create();

    expect($employee->termination_date)->not->toBeNull();
    expect($employee->termination_date)->toBeInstanceOf(Carbon\CarbonInterface::class);
});

it('supports soft deletes', function () {
    $employee = Employee::factory()->create();
    $employee->delete();

    expect($employee->trashed())->toBeTrue();
    expect(Employee::withTrashed()->find($employee->id))->not->toBeNull();
    expect(Employee::find($employee->id))->toBeNull();
});

it('has nullable employee number that is unique', function () {
    $employee1 = Employee::factory()->create(['employee_number' => 'EMP-001']);
    $employee2 = Employee::factory()->create(['employee_number' => null]);

    expect($employee1->employee_number)->toBe('EMP-001');
    expect($employee2->employee_number)->toBeNull();
});
