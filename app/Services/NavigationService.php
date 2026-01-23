<?php

namespace App\Services;

use App\Models\User;

class NavigationService
{
    public function getMainNavItems(User $user): array
    {
        $items = [
            ['title' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'LayoutGrid'],
        ];

        if ($user->isAdmin()) {
            $items[] = ['title' => 'Users', 'href' => '/users', 'icon' => 'Users'];
        }

        return $items;
    }
}
