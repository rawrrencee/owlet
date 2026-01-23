<?php

use App\Models\Customer;
use App\Models\Employee;
use App\Models\User;

test('guests cannot access users page', function () {
    $this->get('/users')
        ->assertRedirect('/login');
});

test('staff users cannot access users page', function () {
    $user = User::factory()->staff()->create();

    $this->actingAs($user)
        ->get('/users')
        ->assertForbidden();
});

test('admin users can access users page', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/users')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Users/Index'));
});

test('users page defaults to employees type', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/users')
        ->assertInertia(function ($page) {
            expect($page->toArray()['props']['type'])->toBe('employees');
        });
});

test('users page can show customers', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/users?type=customers')
        ->assertInertia(function ($page) {
            expect($page->toArray()['props']['type'])->toBe('customers');
        });
});

test('employees are displayed when type is employees', function () {
    $user = User::factory()->admin()->create();

    Employee::factory()->count(5)->create();
    Customer::factory()->count(3)->create();

    $this->actingAs($user)
        ->get('/users?type=employees')
        ->assertInertia(function ($page) {
            expect($page->toArray()['props']['users']['data'])->toHaveCount(5);
            expect($page->toArray()['props']['type'])->toBe('employees');
        });
});

test('customers are displayed when type is customers', function () {
    $user = User::factory()->admin()->create();

    Employee::factory()->count(5)->create();
    Customer::factory()->count(3)->create();

    $this->actingAs($user)
        ->get('/users?type=customers')
        ->assertInertia(function ($page) {
            expect($page->toArray()['props']['users']['data'])->toHaveCount(3);
            expect($page->toArray()['props']['type'])->toBe('customers');
        });
});

test('employees are ordered by last name then first name', function () {
    $user = User::factory()->admin()->create();

    Employee::factory()->create(['last_name' => 'Zeta', 'first_name' => 'Alice']);
    Employee::factory()->create(['last_name' => 'Alpha', 'first_name' => 'Bob']);
    Employee::factory()->create(['last_name' => 'Alpha', 'first_name' => 'Alice']);

    $this->actingAs($user)
        ->get('/users?type=employees')
        ->assertInertia(function ($page) {
            $users = $page->toArray()['props']['users']['data'];
            expect($users[0]['last_name'])->toBe('Alpha');
            expect($users[0]['first_name'])->toBe('Alice');
            expect($users[1]['last_name'])->toBe('Alpha');
            expect($users[1]['first_name'])->toBe('Bob');
            expect($users[2]['last_name'])->toBe('Zeta');
        });
});

test('customers are ordered by last name then first name', function () {
    $user = User::factory()->admin()->create();

    Customer::factory()->create(['last_name' => 'Zeta', 'first_name' => 'Alice']);
    Customer::factory()->create(['last_name' => 'Alpha', 'first_name' => 'Bob']);
    Customer::factory()->create(['last_name' => 'Alpha', 'first_name' => 'Alice']);

    $this->actingAs($user)
        ->get('/users?type=customers')
        ->assertInertia(function ($page) {
            $users = $page->toArray()['props']['users']['data'];
            expect($users[0]['last_name'])->toBe('Alpha');
            expect($users[0]['first_name'])->toBe('Alice');
            expect($users[1]['last_name'])->toBe('Alpha');
            expect($users[1]['first_name'])->toBe('Bob');
            expect($users[2]['last_name'])->toBe('Zeta');
        });
});
