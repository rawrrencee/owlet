<?php

namespace App\Services;

use App\Models\User;

class NavigationService
{
    public function getMainNavItems(User $user): array
    {
        $sections = [];

        // Platform section - always visible
        $platformItems = [
            ['title' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'LayoutGrid'],
        ];

        // Add My Timecards for all authenticated users with employee record
        if ($user->employee) {
            $platformItems[] = ['title' => 'My Timecards', 'href' => '/timecards', 'icon' => 'Clock'];
        }

        $sections[] = [
            'title' => 'Platform',
            'items' => $platformItems,
        ];

        // My Tools section - for employees with subordinates
        $myToolsItems = [];
        if ($user->employee?->hasSubordinates()) {
            $myToolsItems[] = ['title' => 'My Team', 'href' => '/my-team', 'icon' => 'UsersRound'];
            $myToolsItems[] = ['title' => 'Team Timecards', 'href' => '/my-team-timecards', 'icon' => 'ClipboardList'];
        }

        if (! empty($myToolsItems)) {
            $sections[] = [
                'title' => 'My Tools',
                'items' => $myToolsItems,
            ];
        }

        // Commerce section - before Management
        $commerceItems = [];
        if ($user->isAdmin()) {
            $commerceItems[] = ['title' => 'Brands', 'href' => '/brands', 'icon' => 'Tag'];
            $commerceItems[] = ['title' => 'Categories', 'href' => '/categories', 'icon' => 'Layers'];
            $commerceItems[] = ['title' => 'Stores', 'href' => '/stores', 'icon' => 'Store'];
        }

        if (! empty($commerceItems)) {
            $sections[] = [
                'title' => 'Commerce',
                'items' => $commerceItems,
            ];
        }

        // Management section - only visible for admins
        $managementItems = [];

        if ($user->isAdmin()) {
            $managementItems[] = ['title' => 'Users', 'href' => '/users', 'icon' => 'Users'];
            $managementItems[] = ['title' => 'Documents', 'href' => '/documents', 'icon' => 'Folder'];
            $managementItems[] = ['title' => 'Timecards', 'href' => '/management/timecards', 'icon' => 'CalendarClock'];
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

        return $sections;
    }
}
