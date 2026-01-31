<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\HierarchyVisibilitySetting;
use Illuminate\Database\Seeder;

class HierarchyVisibilitySeeder extends Seeder
{
    /**
     * Seed hierarchy visibility settings for managers.
     */
    public function run(): void
    {
        $this->command->info('Seeding hierarchy visibility settings...');

        // Get all employees who have subordinates (are managers)
        $managers = Employee::whereHas('subordinates')->get();

        if ($managers->isEmpty()) {
            $this->command->warn('  No managers found. Skipping...');

            return;
        }

        $createdCount = 0;

        foreach ($managers as $manager) {
            // Skip if visibility setting already exists
            if ($manager->hierarchyVisibility) {
                continue;
            }

            // Give managers visibility to all available sections
            $allSections = array_keys(HierarchyVisibilitySetting::getAvailableSections());

            HierarchyVisibilitySetting::create([
                'manager_id' => $manager->id,
                'visible_sections' => $allSections,
            ]);

            $createdCount++;
        }

        $this->command->info("  Created {$createdCount} hierarchy visibility settings.");
    }
}
