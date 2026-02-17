<?php

use App\Models\Employee;
use App\Models\User;

test('pos page requires authentication', function () {
    $this->get('/pos')->assertRedirect('/login');
});

test('pos page requires pos.access permission', function () {
    $user = User::factory()->staff()->create();
    $this->actingAs($user)->get('/pos')->assertForbidden();
});

test('admin can access pos page', function () {
    $employee = Employee::factory()->create();
    $user = User::factory()->admin()->forEmployee($employee)->create();
    $this->actingAs($user)->get('/pos')->assertOk();
});

test('transactions page requires authentication', function () {
    $this->get('/transactions')->assertRedirect('/login');
});

test('transactions page requires transactions.view permission', function () {
    $user = User::factory()->staff()->create();
    $this->actingAs($user)->get('/transactions')->assertForbidden();
});

test('admin can access transactions page', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user)->get('/transactions')->assertOk();
});

test('transactions index renders correct inertia component', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user)->get('/transactions')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Transactions/Index'));
});
