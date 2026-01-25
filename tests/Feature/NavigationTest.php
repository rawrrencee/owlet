<?php

use App\Models\User;
use App\Services\NavigationService;

test('admin users see users nav item', function () {
    $user = User::factory()->admin()->create();

    $navService = new NavigationService;
    $sections = $navService->getMainNavItems($user);

    // Get all items from all sections
    $allItems = collect($sections)->pluck('items')->flatten(1)->toArray();

    expect($allItems)->toContain(
        ['title' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'LayoutGrid']
    );
    expect($allItems)->toContain(
        ['title' => 'Users', 'href' => '/users', 'icon' => 'Users']
    );
});

test('staff users do not see users nav item', function () {
    $user = User::factory()->staff()->create();

    $navService = new NavigationService;
    $sections = $navService->getMainNavItems($user);

    // Get all items from all sections
    $allItems = collect($sections)->pluck('items')->flatten(1);

    expect($allItems->toArray())->toContain(
        ['title' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'LayoutGrid']
    );

    $usersItem = $allItems->firstWhere('title', 'Users');
    expect($usersItem)->toBeNull();
});

test('navigation is shared via inertia for authenticated users', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(function ($page) {
            $navigation = $page->toArray()['props']['navigation'];
            expect($navigation)->toBeArray();

            // Get all items from all sections
            $allItems = collect($navigation)->pluck('items')->flatten(1)->toArray();

            expect($allItems)->toContain(
                ['title' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'LayoutGrid']
            );
            expect($allItems)->toContain(
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
