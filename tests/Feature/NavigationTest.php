<?php

use App\Models\User;
use App\Services\NavigationService;

test('admin users see users nav item', function () {
    $user = User::factory()->admin()->create();

    $navService = new NavigationService;
    $items = $navService->getMainNavItems($user);

    expect($items)->toContain(
        ['title' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'LayoutGrid']
    );
    expect($items)->toContain(
        ['title' => 'Users', 'href' => '/users', 'icon' => 'Users']
    );
});

test('staff users do not see users nav item', function () {
    $user = User::factory()->staff()->create();

    $navService = new NavigationService;
    $items = $navService->getMainNavItems($user);

    expect($items)->toContain(
        ['title' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'LayoutGrid']
    );

    $usersItem = collect($items)->firstWhere('title', 'Users');
    expect($usersItem)->toBeNull();
});

test('navigation is shared via inertia for authenticated users', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(function ($page) {
            expect($page->toArray()['props']['navigation'])->toBeArray();
            expect($page->toArray()['props']['navigation'])->toContain(
                ['title' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'LayoutGrid']
            );
            expect($page->toArray()['props']['navigation'])->toContain(
                ['title' => 'Users', 'href' => '/users', 'icon' => 'Users']
            );
        });
});

test('navigation is empty for guests', function () {
    $this->get('/')
        ->assertInertia(function ($page) {
            expect($page->toArray()['props']['navigation'])->toBeArray();
            expect($page->toArray()['props']['navigation'])->toBeEmpty();
        });
});
