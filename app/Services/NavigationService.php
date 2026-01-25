<?php

namespace App\Services;

use App\Models\User;

class NavigationService
{
    public function getMainNavItems(User $user): array
    {
        $sections = [];

        // Platform section - always visible
        $sections[] = [
            'title' => 'Platform',
            'items' => [
                ['title' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'LayoutGrid'],
            ],
        ];

        // My Tools section - for employees with subordinates
        $myToolsItems = [];
        if ($user->employee?->hasSubordinates()) {
            $myToolsItems[] = ['title' => 'My Team', 'href' => '/my-team', 'icon' => 'UsersRound'];
        }

        if (! empty($myToolsItems)) {
            $sections[] = [
                'title' => 'My Tools',
                'items' => $myToolsItems,
            ];
        }

        // Management section - only visible for admins
        $managementItems = [];

        if ($user->isAdmin()) {
            $managementItems[] = ['title' => 'Users', 'href' => '/users', 'icon' => 'Users'];
            $managementItems[] = ['title' => 'Documents', 'href' => '/documents', 'icon' => 'Folder'];
            $managementItems[] = ['title' => 'Companies', 'href' => '/companies', 'icon' => 'Building2'];
            $managementItems[] = ['title' => 'Designations', 'href' => '/designations', 'icon' => 'BadgeCheck'];
            $managementItems[] = ['title' => 'Organisation Chart', 'href' => '/organisation-chart', 'icon' => 'Network'];
        }

        // Only add the Management section if there are items
        if (! empty($managementItems)) {
            $sections[] = [
                'title' => 'Management',
                'items' => $managementItems,
            ];
        }

        // Commerce section
        $commerceItems = [];
        if ($user->isAdmin()) {
            $commerceItems[] = ['title' => 'Stores', 'href' => '/stores', 'icon' => 'Store'];
        }

        if (! empty($commerceItems)) {
            $sections[] = [
                'title' => 'Commerce',
                'items' => $commerceItems,
            ];
        }

        return $sections;
    }
}
