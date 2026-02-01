<?php

use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only migrate if the tags column exists (upgrade from older version)
        if (! Schema::hasColumn('products', 'tags')) {
            return;
        }

        // Migrate existing JSON tags to the pivot table
        $products = DB::table('products')
            ->whereNotNull('tags')
            ->get(['id', 'tags']);

        foreach ($products as $product) {
            $tags = json_decode($product->tags, true) ?? [];

            foreach ($tags as $tagName) {
                if (empty($tagName)) {
                    continue;
                }

                $tag = Tag::findOrCreateByName($tagName);

                DB::table('product_tag')->insertOrIgnore([
                    'product_id' => $product->id,
                    'tag_id' => $tag->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Remove the JSON tags column
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only run if the tags column doesn't exist
        if (Schema::hasColumn('products', 'tags')) {
            return;
        }

        // Re-add the JSON tags column
        Schema::table('products', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('description');
        });

        // Migrate tags back to JSON
        $products = DB::table('products')->get(['id']);

        foreach ($products as $product) {
            $tagNames = DB::table('product_tag')
                ->join('tags', 'tags.id', '=', 'product_tag.tag_id')
                ->where('product_tag.product_id', $product->id)
                ->pluck('tags.name')
                ->toArray();

            if (! empty($tagNames)) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['tags' => json_encode($tagNames)]);
            }
        }
    }
};
