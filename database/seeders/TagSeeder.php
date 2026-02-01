<?php

namespace Database\Seeders;

use App\Models\Tag;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Seed product tags using Faker.
     */
    public function run(): void
    {
        $this->command->info('Seeding tags...');

        $faker = Faker::create();

        $count = config('seeders.counts.tags', 100);
        $batchSize = 50;
        $tags = [];
        $createdCount = 0;

        // Use a mix of realistic product tags and random words
        $predefinedTags = [
            'new', 'sale', 'bestseller', 'limited', 'exclusive', 'premium',
            'organic', 'eco-friendly', 'handmade', 'vintage', 'modern',
            'classic', 'trending', 'popular', 'seasonal', 'clearance',
            'gift', 'bundle', 'imported', 'local', 'featured',
            'recommended', 'top-rated', 'budget', 'luxury', 'essential',
        ];

        for ($i = 1; $i <= $count; $i++) {
            // Use predefined tags first, then generate random ones
            if ($i <= count($predefinedTags)) {
                $tagName = $predefinedTags[$i - 1];
            } else {
                $tagName = strtolower($faker->unique()->word());
            }

            $tags[] = [
                'name' => $tagName,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($tags) >= $batchSize) {
                Tag::insert($tags);
                $createdCount += count($tags);
                $tags = [];
            }
        }

        // Insert remaining
        if (! empty($tags)) {
            Tag::insert($tags);
            $createdCount += count($tags);
        }

        $this->command->info("  Created {$createdCount} tags.");
    }
}
